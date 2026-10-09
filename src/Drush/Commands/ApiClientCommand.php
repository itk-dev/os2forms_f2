<?php

declare(strict_types=1);

namespace Drupal\os2forms_f2\Drush\Commands;

use Drupal\os2forms_f2\Settings;
use Drush\Commands\AutowireTrait;
use ItkDev\F2ApiClient\Command\F2ApiClientCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// phpcs:disable Drupal.Commenting.ClassComment.Missing
#[AsCommand(
  name: self::NAME,
  description: 'Wrapper for calling the F2ApiClientCommand from https://github.com/itk-dev/f2-api-client',
)]
final class ApiClientCommand extends Command {
  use AutowireTrait;

  public const string NAME = 'os2forms-f2:api-client';

  public function __construct(
    private readonly Settings $settings,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->addArgument('action', InputArgument::REQUIRED, 'The action')
      ->addArgument('arg', InputArgument::OPTIONAL, 'The action argument')
      ->addOption('show-links', NULL, InputOption::VALUE_NONE, 'Show links.')
      ->addOption('cache-directory', NULL, InputOption::VALUE_REQUIRED, 'Path to to cache directory. If not specified, the default file system cache directory will be used.');
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getHelp(): string {
    return (new F2ApiClientCommand())->getHelp();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $command = new F2ApiClientCommand();

    $apiSettings = $this->settings->getF2ApiSettings();
    putenv("F2_API_URI={$apiSettings->uri}");
    putenv("F2_API_USERNAME={$apiSettings->username}");
    putenv("F2_API_SECRET={$apiSettings->secret}");
    putenv("F2_F2_USERNAME={$apiSettings->username}");

    return $command->__invoke(
      new SymfonyStyle($input, $output),
      $input->getArgument('action'),
      $input->getArgument('arg'),
      $input->getOption('show-links'),
      $input->getOption('cache-directory'),
    );
  }

}
