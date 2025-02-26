<?php

namespace Acquia\Cli\Tests\Commands\Pull;

use Acquia\Cli\Command\Pull\PullCommand;
use Acquia\Cli\Exception\AcquiaCliException;
use Symfony\Component\Console\Command\Command;

/**
 * Class PullCommandTest.
 *
 * @property \Acquia\Cli\Command\Pull\PullCommand $command
 * @package Acquia\Cli\Tests\Commands\Pull
 */
class PullCommandTest extends PullCommandTestBase {

  /**
   * @throws \JsonException
   */
  public function setUp($output = NULL): void {
    parent::setUp($output);
    $this->setupFsFixture();
  }

  /**
   * {@inheritdoc}
   */
  protected function createCommand(): Command {
    return $this->injectCommand(PullCommand::class);
  }

  /**
   * @throws \Exception
   */
  public function testMissingLocalRepo(): void {
    // Unset repo root. Mimics failing to find local git repo. Command must be re-created
    // to re-inject the parameter into the command.
    $this->acliRepoRoot = '';
    $this->removeMockGitConfig();
    $this->command = $this->createCommand();
    try {
      $inputs = [
        // Would you like to clone a project into the current directory?
        'n',
      ];
      $this->executeCommand([], $inputs);
    }
    catch (AcquiaCliException $e) {
      $this->assertEquals('Please execute this command from within a Drupal project directory or an empty directory', $e->getMessage());
    }
  }

}
