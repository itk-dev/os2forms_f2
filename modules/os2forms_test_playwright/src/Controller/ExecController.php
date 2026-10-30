<?php

declare(strict_types=1);

namespace Drupal\os2forms_test_playwright\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Process\Process;

/**
 * Exec controller.
 */
final class ExecController extends ControllerBase {

  public function __construct(
    #[Autowire(service: 'logger.channel.os2forms_test_playwright')]
    private readonly LoggerChannelInterface $logger,
  ) {
  }

  /**
   * Builds the response.
   */
  public function __invoke(Request $request, string $command): Response {
    try {
      $command = match ($command) {
        'drush' => '../vendor/bin/drush',
        default => throw new BadRequestHttpException(sprintf('command not found: %s', $command)),
      };
      $contentType = $request->getContentTypeFormat();
      $arguments = match ($contentType) {
        'json' => $request->toArray(),
        default => throw new BadRequestHttpException(sprintf('invalid content type: %s', $contentType)),
      };
      if (empty($arguments)) {
        throw new BadRequestHttpException('missing arguments');
      }

      $this->logger->debug('Running command @command @arguments', [
        '@command' => $command,
        '@arguments' => $arguments,
      ]);

      $response = new StreamedResponse(headers: [
        'content-type' => 'text/plain',
      ]);
      $response->setCallback(function () use ($command, $arguments): void {
        $process = new Process([$command, ...$arguments]);
        $process->mustRun(function ($type, $buffer): void {
          echo $buffer;
        });
      });
      $response->headers->set('X-Accel-Buffering', 'no');
      $response->send();

      return $response;
    }
    catch (\Throwable $throwable) {
      $this->logger->error('Error: @message', [
        '@message' => $throwable->getMessage(),
        'throwable' => $throwable,
      ]);
      throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, message: $throwable->getMessage());
    }
  }

}
