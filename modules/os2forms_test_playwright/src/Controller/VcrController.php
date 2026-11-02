<?php

declare(strict_types=1);

namespace Drupal\os2forms_test_playwright\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\os2forms_test_playwright\Exception\CassetteException;
use Drupal\os2forms_test_playwright\VcrOperator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * VCR controller.
 */
final class VcrController extends ControllerBase {

  public function __construct(
    private readonly VcrOperator $operator,
    #[Autowire(service: 'logger.channel.os2forms_test_playwright')]
    private readonly LoggerChannelInterface $logger,
  ) {
  }

  /**
   * Builds the response.
   */
  public function __invoke(Request $request, string $command): Response {
    switch ($command) {
      case 'configure':
        try {
          $arguments = $request->toArray();
        }
        catch (\Throwable $throwable) {
          return $this->sendBadRequestHttpException($throwable->getMessage());
        }

        try {
          $configuration = $this->operator->configure($arguments);
        }
        catch (CassetteException $exception) {
          return $this->sendBadRequestHttpException($exception->getMessage());
        }

        $this->logger->debug('VCR configuration: @configuration', [
          '@configuration' => json_encode($configuration),
        ]);
        return new Response(sprintf('VCR configured: %s', json_encode($configuration)), headers: [
          'content-type' => 'text/plain',
        ]);

      case 'record':
        try {
          $arguments = $request->toArray();
        }
        catch (\Throwable $throwable) {
          return $this->sendBadRequestHttpException($throwable->getMessage());
        }

        try {
          $cassette = $arguments['cassette'] ?? NULL;
          if (!is_string($cassette)) {
            throw new CassetteException('Missing cassette');
          }
          if (!preg_match('/^[a-z][a-z0-9_-]+$/', $cassette)) {
            throw new CassetteException('Invalid cassette (must match /^[a-z][a-z0-9_-]+$/)');
          }

          $cassette = $this->operator->insertCassette($cassette);
        }
        catch (CassetteException $exception) {
          return $this->sendBadRequestHttpException($exception->getMessage());
        }
        $this->logger->debug('VCR: Cassette "@cassette" inserted.', ['@cassette' => $cassette]);

        return new Response(sprintf('Cassette "%s" inserted.', $cassette), headers: [
          'content-type' => 'text/plain',
        ]);

      case 'eject':
        $cassette = $this->operator->ejectCassette();

        $this->logger->debug('VCR: Cassette "@cassette" ejected.', ['@cassette' => $cassette]);

        return new Response(
          NULL !== $cassette
            ? sprintf('Cassette "%s" ejected.', $cassette)
            : 'No cassetted inserted.',
          headers: [
            'content-type' => 'text/plain',
          ]
        );

      default:
        return $this->sendBadRequestHttpException(sprintf('command not found: %s', $command));
    }
  }

  /**
   * Create bad request exception.
   */
  private function sendBadRequestHttpException(string $message): Response {
    $exception = new BadRequestHttpException($message);
    $this->logger->error('VCR error: @message', [
      '@message' => $exception->getMessage(),
      'exception' => $exception,
    ]);

    return new Response(
      $exception->getMessage(),
      status: $exception->getStatusCode(),
      headers: [
        'content-type' => 'text/plain',
      ]
    );
  }

}
