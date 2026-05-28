<?php

namespace Drupal\os2forms_f2\Helper;

use Drupal\advancedqueue\Entity\QueueInterface;
use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Render\ElementInfoManager;
use Drupal\os2forms_f2\Exception\RuntimeException;
use Drupal\os2forms_f2\Exception\SubmissionNotFoundException;
use Drupal\os2forms_f2\Plugin\AdvancedQueue\JobType\F2;
use Drupal\os2forms_f2\Plugin\WebformHandler\WebformHandlerF2;
use Drupal\os2forms_f2\Settings;
use Drupal\os2forms_f2\Settings\ArchiveSettings;
use Drupal\os2forms_f2\Settings\HandlerSettings;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformSubmissionStorageInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Webform helper.
 */
final class WebformHelperF2 implements LoggerInterface {
  use LoggerTrait;

  private const string PAYLOAD_KEY = 'os2forms_f2';
  private const string PAYLOAD_STATE = 'state';
  private const string STATE_UPLOAD_FILES = 'upload_files';

  private const string PAYLOAD_FILES = 'files';
  private const string STATE_CHECK_FILES = 'check_files';

  private const string PAYLOAD_FILES_DELIVERED = 'files_delivered';
  private const string STATE_SEND_DISTRIBUTION_OBJECT = 'send_distribution_object';

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
    #[Autowire(service: 'plugin.manager.element_info')]
    private readonly ElementInfoManager $elementInfoManager,
    #[Autowire(service: 'webform.token_manager')]
    private readonly WebformTokenManagerInterface $webformTokenManager,
    #[Autowire(service: 'logger.channel.os2forms_f2')]
    private readonly LoggerChannelInterface $logger,
    #[Autowire(service: 'logger.channel.os2forms_f2_submission')]
    private readonly LoggerChannelInterface $submissionLogger,
  ) {
    /** @var WebformSubmissionStorageInterface $storage */
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
   * Load submission IDs for a webform.
   */
  public function loadSubmissionIds(WebformInterface $webform): array {
    return $this->webformSubmissionStorage->getQuery()
      ->accessCheck()
      ->condition('webform_id', $webform->id())
      ->sort('created', 'DESC')
      ->sort('sid', 'DESC')
      ->execute();
  }

  /**
   * Load latest submission on a webform.
   */
  public function loadLatestSubmission(WebformInterface $webform): ?WebformSubmissionInterface {
    $submissionIds = $this->loadSubmissionIds($webform);

    $id = reset($submissionIds);

    return $id ? $this->loadSubmission($id) : NULL;
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

      throw new \RuntimeException(__METHOD__ . 'not implemented');

      return JobResult::success();
    }
    catch (\Exception $e) {
      $this->error('Error: @message', $context + [
        '@message' => $e->getMessage(),
        'exception' => $e,
      ]);

      return JobResult::failure($e->getMessage());
    }
  }

  /**
   * Replace tokens in handler settings supporting tokens.
   */
  private function replaceTokens(HandlerSettings $handlerSettings, WebformSubmissionInterface $submission): ArchiveSettings {
    // @todo Should we clone the settings before making changes?
    return $handlerSettings;
  }

}
