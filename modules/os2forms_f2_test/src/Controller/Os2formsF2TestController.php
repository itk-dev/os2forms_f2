<?php

declare(strict_types=1);

namespace Drupal\os2forms_f2_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Process\Process;

/**
 * Returns responses for os2forms_f2_test routes.
 */
final class Os2formsF2TestController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function __invoke(Request $request, string $command): Response {
    $command = match ($command) {
      'drush' => '../vendor/bin/drush',
      default => throw new BadRequestHttpException(sprintf('command not found: %s', $command)),
    };
    $arguments = $request->request->get('arguments') ?? $request->query->get('arguments');
    $command = trim($command . ' ' . $arguments);

    \Drupal::logger('os2forms_f2_test')->debug('Running command @command', [
      '@command' => $command,
    ]);

    $response = new StreamedResponse(headers: [
      'content-type' => 'text/plain',
    ]);
    $response->setCallback(function () use ($command): void {
      echo '> ', $command, PHP_EOL;
      $process = Process::fromShellCommandline($command);
      $process->run(function ($type, $buffer): void {
        echo $buffer;
      });
    });
    $response->send();
    $response->headers->set('X-Accel-Buffering', 'no');

    return $response;
  }

}
