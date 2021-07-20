<?php

namespace Spatie\ShortSchedule;

use Illuminate\Support\Facades\App;
use Spatie\ShortSchedule\Cache\ShortScheduleCache;
use Spatie\ShortSchedule\Cache\ShortScheduleOnOneServerCache;
use Spatie\ShortSchedule\Events\ShortScheduledTaskFinished;
use Spatie\ShortSchedule\Events\ShortScheduledTaskStarted;
use Spatie\ShortSchedule\Events\ShortScheduledTaskStarting;
use Symfony\Component\Process\Process;

class ShortScheduleCommand extends PendingShortScheduleCommand
{
    protected PendingShortScheduleCommand $pendingShortScheduleCommand;

    protected ?Process $process = null;

    protected string $output = '/dev/null';

    protected ?int $exitCode;

    protected ShortScheduleCache $cache;

    protected ShortScheduleOnOneServerCache $cacheOnOneServer;

    protected ShortScheduleConsoleOutput $console;

    public function __construct(PendingShortScheduleCommand $pendingShortScheduleCommand)
    {
        $this->pendingShortScheduleCommand = $pendingShortScheduleCommand;
        $this->console = new ShortScheduleConsoleOutput($pendingShortScheduleCommand->verbosity);
        $this->cache = App::make(ShortScheduleCache::class);
        $this->cacheOnOneServer = App::make(ShortScheduleOnOneServerCache::class);

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

    public function getCacheName(): string
    {
        return $this->pendingShortScheduleCommand->cacheName();
    }

    public function getCacheNameOnOneServer(): string
    {
        return $this->pendingShortScheduleCommand->cacheNameOnOneServer();
    }

    public function getOnOneServer(): bool
    {
        return $this->pendingShortScheduleCommand->onOneServer;
    }

    public function shouldRun(): bool
    {
        $commandString = $this->buildCommand();

        if (App::isDownForMaintenance() && (! $this->pendingShortScheduleCommand->evenInMaintenanceMode)) {
            $this->console->write("Skipping command (system is down): {$commandString}", 'comment');

            return false;
        }

        if ($this->isRunning() && (! $this->pendingShortScheduleCommand->allowOverlaps)) {
            $this->console->write("Skipping command (still is running): {$commandString}", 'comment');

            return false;
        }

        if (! $this->pendingShortScheduleCommand->shouldRun()) {
            return false;
        }

        if ($this->shouldRunOnOneServer()) {
            $this->console->write("Skipping command (has already run on another server): {$commandString}", 'comment');

            return false;
        }

        return true;
    }

    public function isRunning(): bool
    {
        if ($this->cache->existsLock($this)) {
            return true;
        }

        if (isset($this->process)) {
            return $this->process->isRunning();
        }

        return false;
    }

    public function run(): void
    {
        $this->getOnOneServer() ? $this->processOnOneServer() : $this->processCommand() ;
    }

    public function callAfterEndedBackgroundCommand($exitCode)
    {
        $this->exitCode = (int) $exitCode;

        $this->cache->fogetLock($this);
    }

    protected function buildCommand()
    {
        return (new ShortScheduleCommandBuilder())->buildCommand($this);
    }

    protected function shouldRunOnOneServer(): bool
    {
        return $this->getOnOneServer()
               && $this->cacheOnOneServer->existsLock($this);
    }

    protected function processOnOneServer(): void
    {
        $this->cacheOnOneServer->createLock($this);

        $this->processCommand();
    }

    private function processCommand(): void
    {
        $commandString = $this->buildCommand();
        $this->process = Process::fromShellCommandline($commandString, base_path(), null, null, null);

        $this->callBeforeStart($commandString);

        $this->process->start();

        $this->callAfterStarting($commandString);

        $this->process->wait();

        $this->callAfterEnd($commandString);
    }

    private function callBeforeStart(string $command): void
    {
        if (! $this->pendingShortScheduleCommand->allowOverlaps) {
            $this->cache->createLock($this);
        }

        $this->console->write("Running command: {$command}");

        event(new ShortScheduledTaskStarting($command, $this->process));
    }

    private function callAfterStarting(string $command): void
    {
        event(new ShortScheduledTaskStarted($command, $this->process));
    }

    private function callAfterEnd(string $command): void
    {
        event(new ShortScheduledTaskFinished($command, $this->process));

        $this->exitCode = $this->process->getExitCode();

        if (! $this->pendingShortScheduleCommand->allowOverlaps && ! $this->pendingShortScheduleCommand->runInBackground) {
            $this->cache->fogetLock($this);
        }
    }
}
