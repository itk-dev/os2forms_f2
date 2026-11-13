<?php

namespace Drupal\os2forms_f2\Helper;

use Drupal\advancedqueue\Entity\QueueInterface;
use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Render\ElementInfoManager;
use Drupal\os2forms_f2\Exception\InvalidAttachmentElementException;
use Drupal\os2forms_f2\Exception\RuntimeException;
use Drupal\os2forms_f2\Exception\SubmissionNotFoundException;
use Drupal\os2forms_f2\Model\Attachment;
use Drupal\os2forms_f2\Model\F2Item;
use Drupal\os2forms_f2\Model\F2Item\Type;
use Drupal\os2forms_f2\Plugin\AdvancedQueue\JobType\F2;
use Drupal\os2forms_f2\Plugin\WebformHandler\WebformHandlerF2;
use Drupal\os2forms_f2\Repository\F2ItemRepository;
use Drupal\os2forms_f2\Settings;
use Drupal\os2forms_f2\Settings\ArchiveSettings;
use Drupal\os2forms_f2\Settings\ArchiveSettings\ArchiveTarget;
use Drupal\os2forms_f2\Settings\HandlerSettings;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Drupal\webform_attachment\Element\WebformAttachmentBase;
use ItkDev\F2ApiClient\Model\Document;
use ItkDev\F2ApiClient\Model\Matter;
use ItkDev\F2ApiClient\Model\Party;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Webform helper.
 */
final readonly class WebformHelperF2 implements LoggerInterface {
  use LoggerTrait;

  /**
   * File element types.
   */
  private const array FILE_ELEMENT_TYPES = [
    'webform_image_file',
    'webform_document_file',
    'webform_video_file',
    'webform_audio_file',
    'managed_file',
  ];

  private const string PDF_MIME_TYPE = 'application/pdf';

  /**
   * Constructor.
   */
  public function __construct(
    private EntityTypeManagerInterface $entityTypeManager,
    private Settings $settings,
    private F2Helper $f2,
    private FileSystemInterface $fileSystem,
    #[Autowire(service: 'plugin.manager.element_info')]
    private ElementInfoManager $elementInfoManager,
    #[Autowire(service: 'webform.token_manager')]
    private WebformTokenManagerInterface $webformTokenManager,
    private F2ItemRepository $itemRepository,
    #[Autowire(service: 'logger.channel.os2forms_f2')]
    private LoggerChannelInterface $logger,
    #[Autowire(service: 'logger.channel.os2forms_f2_submission')]
    private LoggerChannelInterface $submissionLogger,
  ) {
  }

  /**
   * Load webform submission by id.
   */
  public function loadSubmission(int $id): ?WebformSubmissionInterface {
    /** @var ?WebformSubmissionInterface $submission */
    $submission = $this->entityTypeManager->getStorage('webform_submission')->load($id);

    return $submission;
  }

  /**
   * Load queue.
   */
  private function loadQueue(): QueueInterface {
    $id = $this->settings->getGeneralSettings()->queue ?? NULL;

    /** @var ?\Drupal\advancedqueue\Entity\QueueInterface $queue */
    $queue = $this->entityTypeManager->getStorage('advancedqueue_queue')->load($id);

    if (NULL === $queue) {
      throw new RuntimeException(sprintf('Cannot load queue %s', $id));
    }

    return $queue;
  }

  /**
   * {@inheritdoc}
   *
   * @param mixed $level
   *   The level.
   * @param string $message
   *   The message.
   * @param array<string, mixed> $context
   *   The context.
   */
  #[\Override]
  public function log($level, $message, array $context = []): void {
    $this->logger->log($level, $message, $context);
    // @see https://www.drupal.org/node/3020595
    if (isset($context['webform_submission']) && $context['webform_submission'] instanceof WebformSubmissionInterface) {
      $this->submissionLogger->log($level, $message, $context);
    }
  }

  /**
   * Create a job.
   *
   * @see self::processJob()
   */
  public function createJob(WebformSubmissionInterface $webformSubmission, WebformHandlerF2 $handler): ?Job {
    $context = [
      'handler_id' => WebformHandlerF2::ID,
      'webform_submission' => $webformSubmission,
    ];

    try {
      $handlerSettings = $this->settings->getHandlerSettings($handler, applyGlobals: TRUE);

      $job = Job::create(F2::class, [
        'formId' => $webformSubmission->getWebform()->id(),
        'submissionId' => $webformSubmission->id(),
        'handlerSettings' => $handlerSettings->toArray(),
      ]);
      $queue = $this->loadQueue();
      $queue->enqueueJob($job);
      $context['@queue'] = $queue->id();
      $this->notice('F2 job (archive on @archive_target) added to the queue @queue.', $context + [
        '@archive_target' => $handlerSettings->archive->archiveTarget->value,
      ]);

      return $job;
    }
    catch (\Exception $exception) {
      $this->error('Error creating job for F2 archive: @message', $context + [
        '@message' => $exception->getMessage(),
        'operation' => 'F2 failed',
        'exception' => $exception,
      ]);
      return NULL;
    }
  }

  /**
   * Process a job.
   *
   * @see self::createJob()
   */
  public function processJob(Job $job): JobResult {
    $payload = $job->getPayload();
    $context = [
      'handler_id' => WebformHandlerF2::ID,
      'operation' => 'F2 archive',
    ];
    try {
      $submissionId = $payload['submissionId'];
      $submission = $this->loadSubmission($submissionId);
      if (NULL === $submission) {
        $message = 'Cannot load submission @submissionId';
        $context = [
          '@submissionId' => $submissionId,
        ];
        $this->error($message, $context);

        throw new SubmissionNotFoundException(str_replace(array_keys($context), array_values($context),
          $message));
      }

      $context['webform_submission'] = $submission;
      $handlerSettings = new HandlerSettings($payload['handlerSettings']);

      $target = $handlerSettings->archive?->archiveTarget;
      return match ($target) {
        ArchiveTarget::Case => $this->archiveOnCase($submission, $handlerSettings),
        ArchiveTarget::Matter => $this->archiveOnMatter($submission, $handlerSettings),
        default => throw new RuntimeException('Invalid archive target'),
      };
    }
    catch (\Exception $exception) {
      $previous = $exception->getPrevious();
      if (NULL !== $previous) {
        $this->error('Error: @message (@previous)', $context + [
          '@message' => $exception->getMessage(),
          '@previous' => $previous->getMessage(),
          'exception' => $exception,
        ]);
      }
      else {
        $this->error('Error: @message', $context + [
          '@message' => $exception->getMessage(),
          'exception' => $exception,
        ]);
      }

      return JobResult::failure($exception->getMessage());
    }
  }

  /**
   * Replace webform submission tokens in text.
   *
   * @param string $text
   *   The text.
   * @param WebformSubmissionInterface $submission
   *   The webform submission.
   * @param array<string, string> $additionalTokens
   *   Additional tokens to replace in the text.
   */
  private function replaceTokens(string $text, WebformSubmissionInterface $submission, array $additionalTokens = []): string {
    return str_replace(
      array_keys($additionalTokens), array_values($additionalTokens),
      $this->webformTokenManager->replace($text, $submission),
    );
  }

  /**
   * Archive on case.
   */
  private function archiveOnCase(WebformSubmissionInterface $submission, HandlerSettings $handlerSettings): JobResult {
    $context = [
      'handler_id' => WebformHandlerF2::ID,
      'webform_submission' => $submission,
      'operation' => 'archive on case',
    ];
    $caseIdentifier = $handlerSettings->archive?->archiveTargetCase?->caseIdentifier;
    if (NULL === $caseIdentifier) {
      throw new RuntimeException('Cannot get case identifier');
    }
    $case = $this->f2->fetchCaseByIdentifier($caseIdentifier);

    $this->info('Archiving document for submission @submission on case @case ', $context + [
      '@submission' => $submission->label(),
      '@case' => (string) $case,
    ]);

    $itemType = Type::Matter;
    $item = $this->itemRepository->loadOneBySubmissionAndHandlerAndType($submission, $handlerSettings->handlerId, $itemType);
    if (NULL !== $item) {
      $id = $item->data['id'];
      $matter = $this->f2->client()->matterById($id);
      $this->info('Matter for submission @submission already exists: @matter', $context + [
        '@submission' => $submission->label(),
        '@matter' => (string) $matter,
      ]);
    }
    else {
      $this->info('Creating matter for submission @submission', $context + [
        '@submission' => $submission->label(),
      ]);
      $matter = new Matter();
      $title = $this->replaceTokens($handlerSettings->archive->archiveTargetCase->matterTitle, $submission);
      $matter->title = $this->truncate($title, Matter::TITLE_MAX_LENGTH);
      $matter->type = $handlerSettings->archive->archiveTargetCase->matterType;
      $matter->status = $handlerSettings->archive->archiveTargetCase->matterStatus;
      $matter->accessLevel = $handlerSettings->archive->archiveTargetCase->matterAccessLevel;
      // Archive ("journaliser") matter?
      $matter->archived = $handlerSettings->archive->archiveTargetCase->matterArchived;
      $this->setMatterResponsible($matter, $handlerSettings);
      $matter = $this->f2->client()->matterCreate(matter: $matter, case: $case);

      $this->info('Matter for submission @submission created: @matter', $context + [
        '@submission' => $submission->label(),
        '@matter' => (string) $matter,
      ]);

      $item = new F2Item(
            webformId: $submission->getWebform()->id(),
            webformHandlerId: $handlerSettings->handlerId,
            webformSubmissionId: $submission->id(),
            type: $itemType,
            identifier: NULL,
            data: $matter->toArray(),
          );
    }

    $this->itemRepository->save($item);

    return $this->archiveOnMatter($submission, $handlerSettings, $matter);
  }

  /**
   * Archive on matter.
   */
  private function archiveOnMatter(WebformSubmissionInterface $submission, HandlerSettings $handlerSettings, ?Matter $matter = NULL): JobResult {
    $context = [
      'handler_id' => WebformHandlerF2::ID,
      'webform_submission' => $submission,
      'operation' => 'archive on matter',
    ];
    if (NULL === $matter) {
      $matterIdentifier = $handlerSettings->archive?->archiveTargetMatter?->matterIdentifier;
      if (NULL === $matterIdentifier) {
        throw new RuntimeException('Cannot get matter identifier');
      }
      $matter = $this->f2->fetchMatterByIdentifier($matterIdentifier);
    }

    $this->info('Archiving document for submission @submission on matter @matter ', $context + [
      '@submission' => $submission->label(),
      '@matter' => (string) $matter,
    ]);

    $messages = [];

    $attachment = $this->getAttachment($submission, $handlerSettings);
    try {
      // Apparently the F2 API inspects the filename extension to determine
      // file type, i.e. we must keep the extension in the temporary filename.
      $fileUri = $this->fileSystem->saveData($attachment->contents, 'temporary://' . uniqid('os2forms_f2') . '-' . $attachment->filename);
      $title = $this->replaceTokens($handlerSettings->archive->documentTitle ?? $attachment->filename, $submission);
      $messages[] = $this->createDocument($matter, $submission, $handlerSettings, $fileUri, 'attachment', $title);
    }
    finally {
      if (isset($fileUri) && file_exists($fileUri)) {
        unlink($fileUri);
      }
    }

    if ($handlerSettings->archive->archiveFiles) {
      $fileElements = $this->getFileElements($submission);
      $files = $this->entityTypeManager->getStorage('file')->loadMultiple(array_keys($fileElements));
      foreach ($fileElements as $fileId => $element) {
        $file = $files[$fileId];
        $fileUri = $file->getFileUri();
        $title = $this->replaceTokens($handlerSettings->archive->fileDocumentTitle ?? $file->getFilename(), $submission, [
          '[file:name]' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
          '[file:filename]' => $file->getFilename(),
          '[file:element:title]' => $element['#title'],
        ]);
        $messages[] = $this->createDocument($matter, $submission, $handlerSettings, $fileUri, $file->getFilename(), $title);
      }
    }

    return JobResult::success(implode(PHP_EOL, $messages));
  }

  /**
   * Create document in F2.
   */
  private function createDocument(Matter $matter, WebformSubmissionInterface $submission, HandlerSettings $handlerSettings, string $fileUri, string $identifier, string $title): string {
    $context = [
      'handler_id' => WebformHandlerF2::ID,
      'webform_submission' => $submission,
      'operation' => 'create document',
    ];
    $itemType = Type::Document;
    $item = $this->itemRepository->loadOneBySubmissionAndHandlerAndType($submission, $handlerSettings->handlerId, $itemType, $identifier);
    if (NULL !== $item) {
      // Document already exists. What to do?
      $id = $item->data['id'];
      $document = $this->f2->client()->documentById($id);
      $this->info('Document @identifier on matter @matter for submission @submission already exists: @document', $context + [
        '@identifier' => $identifier,
        '@submission' => $submission->label(),
        '@matter' => (string) $matter,
        '@document' => (string) $document,
      ]);
      $message = sprintf('Document %s already exists on matter %s', $document, $matter);
    }
    else {
      $document = new Document();
      $document->title = $this->truncate($title, Document::TITLE_MAX_LENGTH);
      $document = $this->f2->client()->documentCreate($document, $fileUri, $matter);
      $this->info('Document on matter @matter for submission @submission created: @document', $context + [
        '@submission' => $submission->label(),
        '@matter' => (string) $matter,
        '@document' => (string) $document,
      ]);
      $message = sprintf('Document %s created on matter %s', $document, $matter);

      $item = new F2Item(
              webformId: $submission->getWebform()->id(),
              webformHandlerId: $handlerSettings->handlerId,
              webformSubmissionId: $submission->id(),
              type: $itemType,
              identifier: $identifier,
              data: $document->toArray(),
            );
    }

    $this->itemRepository->save($item);

    return $message;
  }

  /**
   * Get main document.
   *
   * @see WebformAttachmentController::download()
   *
   * @throws \Drupal\os2forms_f2\Exception\InvalidAttachmentElementException
   *   If no attachment can be found.
   */
  protected function getAttachment(WebformSubmissionInterface $submission, HandlerSettings $handlerSettings): Attachment {
    // Lifted from Drupal\webform_attachment\Controller\WebformAttachmentController::download.
    $elementKey = $handlerSettings->archive->attachmentElement;
    if (NULL === $elementKey) {
      throw new InvalidAttachmentElementException('Cannot get attachment element');
    }
    $element = $submission->getWebform()->getElement($elementKey) ?: [];
    if (!isset($element['#type'])) {
      throw new InvalidAttachmentElementException(sprintf('Cannot get attachment element %s', $elementKey));
    }
    [$type] = explode(':', (string) $element['#type']);
    $instance = $this->elementInfoManager->createInstance($type);

    if (!$instance instanceof WebformAttachmentBase) {
      throw new InvalidAttachmentElementException(sprintf('Attachment element must be an instance of %s. Found %s.', WebformAttachmentBase::class, $instance::class));
    }

    $fileName = $instance::getFileName($element, $submission);
    $mimeType = $instance::getFileMimeType($element, $submission);

    if (self::PDF_MIME_TYPE !== $mimeType) {
      throw new InvalidAttachmentElementException(sprintf('The attachment element must be a PDF file (%s); got %s.', self::PDF_MIME_TYPE, $mimeType));
    }

    $content = $instance::getFileContent($element, $submission);

    return new Attachment(
      $content,
      $mimeType,
      $fileName
    );
  }

  /**
   * Set responsible party on matter if requested.
   */
  private function setMatterResponsible(Matter $matter, HandlerSettings $handlerSettings): void {
    if (!ArchiveSettings::isMatterResponsiblePartyEnabled()) {
      return;
    }

    $partyIdentifier = $handlerSettings->archive->archiveTargetCase->matterResponsiblePartyIdentifier;
    if (NULL != $partyIdentifier) {
      $responsible = new Party();
      if ($partyNumber = filter_var($partyIdentifier, FILTER_VALIDATE_INT)) {
        $responsible->partyNumber = $partyNumber;
      }
      elseif ($email = filter_var($partyIdentifier, FILTER_VALIDATE_EMAIL)) {
        $responsible->email = $email;
      }
      else {
        // Unhandled value.
        return;
      }
      $matter->responsible = $responsible;
    }
  }

  /**
   * Returns list of file elements indexed by a file entity ID.
   *
   * Example:
   *
   * @code
   * [
   *  87 => [
   *   '#type' => 'webform_image_file',
   *   '#title' => 'Images',
   *   '#multiple' => 1,
   *  ],
   *  42 => [
   *   '#type' => 'webform_image_file',
   *   '#title' => 'Images',
   *   '#multiple' => 1,
   *  ],
   * ]
   * @endcode
   *
   * Notice that each element can occurs multiple times in the data if multiple
   * files are allowed in the element.
   *
   * @return array<int, array{'#title': string}>
   *   The list elements indexed by file ID.
   */
  private function getFileElements(WebformSubmissionInterface $submission): array {
    $elements = $submission->getWebform()->getElementsDecodedAndFlattened();

    $fileElements = [];

    foreach (self::FILE_ELEMENT_TYPES as $type) {
      $fileElements[] = array_filter($elements, fn(array $element) => $type === ($element['#type'] ?? NULL));
    }

    // https://dev.to/klnjmm/never-use-arraymerge-in-a-for-loop-in-php-5go1
    $fileElements = array_merge(...$fileElements);

    $result = [];
    $data = $submission->getData();
    foreach ($fileElements as $key => $element) {
      if (empty($data[$key])) {
        continue;
      }

      // Convert occurrences of singular file into array.
      foreach ((array) $data[$key] as $fileId) {
        $result[$fileId] = $element;
      }
    }

    return $result;
  }

  /**
   * Truncate string to a maximum length.
   */
  private function truncate(string $text, int $maxLength): string {
    return Unicode::truncate($text, $maxLength);
  }

}
