<?php

namespace Acquia\Cli\Command\Push;

use Acquia\Cli\Command\Pull\PullCommandBase;
use Acquia\Cli\Exception\AcquiaCliException;
use Acquia\Cli\Output\Checklist;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PushFilesCommand.
 */
class PushFilesCommand extends PullCommandBase {

  protected static $defaultName = 'push:files';

  /**
   * @var string
   */
  protected $dir;

  /**
   * {inheritdoc}.
   */
  protected function configure() {
    $this->setDescription('Push Drupal files from your IDE to a Cloud Platform environment')
      ->addOption('cloud-env-uuid', 'from', InputOption::VALUE_REQUIRED,
        'The UUID of the associated Cloud Platform source environment')
      ->addArgument('dir', InputArgument::OPTIONAL, 'The directory containing the Drupal project with files to be pushed');
  }

  /**
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *
   * @return int 0 if everything went fine, or an exit code
   * @throws \Exception
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->setDirAndRequireProjectCwd($input);
    $destination_environment = $this->determineEnvironment($input, $output);
    if ($this->isAcsfEnv($destination_environment)) {
      $chosen_site = $this->promptChooseFiles($destination_environment);
    }
    else {
      $chosen_site = NULL;
    }
    $answer = $this->io->confirm("Overwrite the public files directory on <bg=cyan;options=bold>{$destination_environment->name}</> with a copy of the files from the current machine?");
    if (!$answer) {
      return 0;
    }

    $this->checklist = new Checklist($output);
    $this->checklist->addItem('Pushing public files directory to remote machine');
    $this->rsyncFilesToCloud($destination_environment, $this->getOutputCallback($output, $this->checklist), $chosen_site);
    $this->checklist->completePreviousItem();

    return 0;
  }

  /**
   * @param $chosen_environment
   * @param callable $output_callback
   * @param string|null $acsf_site
   *
   * @throws \Acquia\Cli\Exception\AcquiaCliException
   */
  protected function rsyncFilesToCloud($chosen_environment, $output_callback = NULL, $acsf_site = NULL): void {
    $source = $this->dir . '/docroot/sites/default/';
    $sitegroup = self::getSiteGroupFromSshUrl($chosen_environment);

    if ($acsf_site) {
      $dest_dir = '/mnt/files/' . $sitegroup . '.' . $chosen_environment->name . '/sites/g/files/' . $acsf_site . '/files';
    }
    else {
      $dest_dir = '/home/' . $sitegroup . '/' . $chosen_environment->name . '/sites/default/files';
    }
    $command = [
      'rsync',
      '-rltDvPhe',
      'ssh -o StrictHostKeyChecking=no',
      $source,
      $chosen_environment->sshUrl . ':' . $dest_dir,
    ];
    $process = $this->localMachineHelper->execute($command, $output_callback, NULL, $this->output->isVerbose(), NULL);
    if (!$process->isSuccessful()) {
      throw new AcquiaCliException('Unable to sync files to Cloud. {message}', ['message' => $process->getErrorOutput()]);
    }
  }

}
