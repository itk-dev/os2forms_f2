<?php

namespace Drupal\os2forms_f2\Helper;

use Drupal\advancedqueue\Entity\QueueInterface;
use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Render\ElementInfoManager;
use Drupal\os2forms_f2\Exception\InvalidAttachmentElementException;
use Drupal\os2forms_f2\Exception\RuntimeException;
use Drupal\os2forms_f2\Exception\SubmissionNotFoundException;
use Drupal\os2forms_f2\Model\Attachment;
use Drupal\os2forms_f2\Plugin\AdvancedQueue\JobType\F2;
use Drupal\os2forms_f2\Plugin\WebformHandler\WebformHandlerF2;
use Drupal\os2forms_f2\Settings;
use Drupal\os2forms_f2\Settings\ArchiveSettings;
use Drupal\os2forms_f2\Settings\ArchiveSettings\ArchiveTarget;
use Drupal\os2forms_f2\Settings\HandlerSettings;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformSubmissionStorageInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Drupal\webform_attachment\Element\WebformAttachmentBase;
use ItkDev\F2ApiClient\Model\Document;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Webform helper.
 */
final class WebformHelperF2 implements LoggerInterface {
  use LoggerTrait;

  private const string PDF_MIME_TYPE = 'application/pdf';

  /**
   * The webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected WebformSubmissionStorageInterface $webformSubmissionStorage;

  /**
   * The queue storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $queueStorage;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    private readonly Settings $settings,
    private readonly F2Helper $f2,
    private readonly FileSystemInterface $fileSystem,
    #[Autowire(service: 'plugin.manager.element_info')]
    private readonly ElementInfoManager $elementInfoManager,
    #[Autowire(service: 'webform.token_manager')]
    private readonly WebformTokenManagerInterface $webformTokenManager,
    #[Autowire(service: 'logger.channel.os2forms_f2')]
    private readonly LoggerChannelInterface $logger,
    #[Autowire(service: 'logger.channel.os2forms_f2_submission')]
    private readonly LoggerChannelInterface $submissionLogger,
  ) {
    /** @var \Drupal\webform\WebformSubmissionStorageInterface $storage */
    $storage = $entityTypeManager->getStorage('webform_submission');
    $this->webformSubmissionStorage = $storage;
    $this->queueStorage = $entityTypeManager->getStorage('advancedqueue_queue');
  }

  /**
   * Load webform submission by id.
   */
  public function loadSubmission(int $id): ?WebformSubmissionInterface {
    /** @var ?WebformSubmissionInterface $submission */
    $submission = $this->webformSubmissionStorage->load($id);

    return $submission;
  }

  /**
   * Load queue.
   */
  private function loadQueue(): QueueInterface {
    $id = $this->settings->getGeneralSettings()->queue ?? NULL;

    /** @var ?\Drupal\advancedqueue\Entity\QueueInterface $queue */
    $queue = $this->queueStorage->load($id);

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
   * @param array $context
   *   The context.
   *
   * @phpstan-param array<string, mixed> $context
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
  public function createJob(WebformSubmissionInterface $webformSubmission, WebformHandlerF2|ArchiveSettings $handlerSettings, ?string $state = NULL, ?array $payload = []): ?Job {
    $context = [
      'handler_id' => WebformHandlerF2::ID,
      'webform_submission' => $webformSubmission,
    ];

    try {
      if ($handlerSettings instanceof WebformHandlerF2) {
        $handlerSettings = $this->settings->getHandlerSettings($handlerSettings);
      }

      $job = Job::create(F2::class, [
        'formId' => $webformSubmission->getWebform()->id(),
        'submissionId' => $webformSubmission->id(),
        'handlerSettings' => $handlerSettings->toArray(),
      ]);
      $queue = $this->loadQueue();
      $queue->enqueueJob($job);
      $context['@queue'] = $queue->id();
      $this->notice('F2 job added to the queue @queue.', $context);

      return $job;
    }
    catch (\Exception $exception) {
      $this->error('Error creating job for F2 archive: %message', $context + [
        '%message' => $exception->getMessage(),
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
      $this->replaceTokens($handlerSettings, $submission);

      $target = $handlerSettings->archive?->archiveTarget;
      return match ($target) {
        ArchiveTarget::MatterID => $this->archiveOnMatter($submission, $handlerSettings),
        default => throw new RuntimeException('Invalid archive target'),
      };
    }
    catch (\Exception $exception) {
      $this->error('Error: @message', $context + [
        '@message' => $exception->getMessage(),
        'exception' => $exception,
      ]);

      return JobResult::failure($exception->getMessage());
    }
  }

  /**
   * Replace tokens in handler settings supporting tokens.
   */
  private function replaceTokens(HandlerSettings $handlerSettings, WebformSubmissionInterface $submission): HandlerSettings {
    // @todo Should we clone the settings before making changes?
    $handlerSettings->archive->documentTitle = $this->webformTokenManager->replace((string) $handlerSettings->archive->documentTitle, $submission);

    return $handlerSettings;
  }

  /**
   * Archive on matter.
   */
  private function archiveOnMatter(WebformSubmissionInterface $submission, HandlerSettings $handlerSettings): JobResult {
    $matterId = $handlerSettings->archive?->archiveTargetMatter?->matterId;
    if (NULL === $matterId) {
      throw new RuntimeException('Cannot get matter ID');
    }
    $matter = $this->f2->client()->matterById($matterId);
    $attachment = $this->getAttachment($submission, $handlerSettings);
    $message = '';
    try {
      // Apparently the F2 API inspects the filename extension to determine file
      // type, i.e. we must keep the extension in the temporary filename.
      $filePath = $this->fileSystem->saveData($attachment->contents, 'temporary://' . uniqid('os2forms_f2') . '-' . $attachment->filename);
      $document = new Document();
      $document->title = $handlerSettings->archive?->documentTitle ?? $attachment->filename;
      $document = $this->f2->client()->documentCreate($document, $filePath, $matter);
      $message = sprintf('Document %s created on matter %s', $document, $matter);
    }
    finally {
      if (isset($filePath) && file_exists($filePath)) {
        unlink($filePath);
      }
    }

    return JobResult::success($message);
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

}
