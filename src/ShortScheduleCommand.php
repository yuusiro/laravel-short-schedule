<?php

namespace Spatie\ShortSchedule;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Spatie\ShortSchedule\Events\ShortScheduledTaskStarted;
use Spatie\ShortSchedule\Events\ShortScheduledTaskStarting;
use Spatie\ShortSchedule\Events\ShortScheduledTaskFinished;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ShortScheduleCommand extends PendingShortScheduleCommand
{
    protected PendingShortScheduleCommand $pendingShortScheduleCommand;

    protected ?Process $process = null;

    protected int $count = 0;

    protected string $output = '/dev/null';

    protected ?int $exitCode;

    public function __construct(PendingShortScheduleCommand $pendingShortScheduleCommand)
    {
        $this->pendingShortScheduleCommand = $pendingShortScheduleCommand;
        $this->console = new ConsoleOutput($pendingShortScheduleCommand->verbosity);

        $this->output = $this->getDefaultOutput();
    }

    public function getDefaultOutput()
    {
        return (DIRECTORY_SEPARATOR === '\\') ? 'NUL' : '/dev/null';
    }

    public function frequencyInSeconds(): float
    {
        return $this->pendingShortScheduleCommand->frequencyInSeconds;
    }

    public function getCommand(): string
    {
        return $this->pendingShortScheduleCommand->command;
    }

    public function getRunInBackround(): bool
    {
        return $this->pendingShortScheduleCommand->runInBackground;
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function getCacheName()
    {
        return $this->pendingShortScheduleCommand->cacheName();
    }

    public function shouldRun(): bool
    {
        $commandString = $this->buildCommand();

        if (App::isDownForMaintenance() && (! $this->pendingShortScheduleCommand->evenInMaintenanceMode)) {
            $this->write("Skipping command (system is down): {$commandString}", 'comment');

            return false;
        }

        if ($this->shouldRunOnOneServer()) {
            $this->write("Skipping command (has already run on another server): {$commandString}", 'comment');

            return false;
        }

        if ($this->isRunning() && (! $this->pendingShortScheduleCommand->allowOverlaps)) {
            $this->write("Skipping command (still is running): {$commandString}", 'comment');

            return false;
        }

        if (! $this->pendingShortScheduleCommand->shouldRun()) {
            return false;
        }

        return true;
    }

    public function isRunning(): bool
    {
        if ($this->existsLock()) {
            return true;
        }

        if (isset($this->process)) {
            return $this->process->isRunning();
        }

        return false;
    }

    public function run(): void
    {
        $this->pendingShortScheduleCommand->getOnOneServer() ? $this->processOnOneServer() : $this->processCommand() ;
    }

    public function callAfterEndedBackgroundCommand($exitCode)
    {
        $this->exitCode = (int) $exitCode;

        $this->fogetLock();
    }

    protected function buildCommand()
    {
        return (new CommandBuilder())->buildCommand($this);
    }

    protected function shouldRunOnOneServer(): bool
    {
        return $this->pendingShortScheduleCommand->getOnOneServer()
               && $this->existsLock(true);
    }

    protected function processOnOneServer(): void
    {
        $this->createLock(true);

        $this->processCommand();
    }

    private function processCommand(): void
    {
        if (! $this->pendingShortScheduleCommand->allowOverlaps) {
            $this->createLock();
        }

        $commandString = $this->buildCommand();
        $this->process = Process::fromShellCommandline($commandString, base_path(), null, null, null);

        $this->write("Running command: {$commandString}");

        event(new ShortScheduledTaskStarting($commandString, $this->process));
        $this->process->start();
        event(new ShortScheduledTaskStarted($commandString, $this->process));
        $this->process->wait();
        event(new ShortScheduledTaskFinished($commandString, $this->process));

        $this->exitCode = $this->process->getExitCode();

        if (! $this->pendingShortScheduleCommand->runInBackground) {
            $this->fogetLock();
        }
    }

    private function getExectution(): string
    {
        return PHP_EOL.'Execution #'.(++$this->count).' in '.now()->isoFormat('L LTS').' output:';
    }

    private function write($string, $style = null): void
    {
        if (App::environment('testing') && $this->pendingShortScheduleCommand->verbosity === OutputInterface::VERBOSITY_NORMAL) {
            echo $this->getExectution().$string;

            return;
        }

        $this->console->writeln('<info>'.$this->getExectution().'</info>');

        $styled = $style ? "<$style>$string</$style>" : $string;

        $this->console->writeln($styled);
    }

    private function createLock($onOneServer = false): bool
    {
        $cacheName = $onOneServer ? $this->pendingShortScheduleCommand->cacheNameOnOneServer() : $this->getCacheName();

        return Cache::add($cacheName, true, $onOneServer ? $this->frequencyInSeconds() : 60);
    }

    private function existsLock($onOneServer = false): bool
    {
        $cacheName = $onOneServer ? $this->pendingShortScheduleCommand->cacheNameOnOneServer() : $this->getCacheName();

        return Cache::has($cacheName);
    }

    private function fogetLock($onOneServer = false): bool
    {
        $cacheName = $onOneServer ? $this->pendingShortScheduleCommand->cacheNameOnOneServer() : $this->getCacheName();

        return Cache::forget($cacheName);
    }
}
