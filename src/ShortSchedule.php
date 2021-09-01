<?php

namespace Spatie\ShortSchedule;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Traits\Macroable;
use React\EventLoop\LoopInterface;
use ReflectionClass;
use Symfony\Component\Console\Output\OutputInterface;

class ShortSchedule
{
    use Macroable;

    protected LoopInterface $loop;

    protected array $pendingCommands = [];

    protected ?int $lifetime = null;

    protected ShortScheduleConsoleOutput $console;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
        $this->console = new ShortScheduleConsoleOutput(OutputInterface::VERBOSITY_NORMAL);
    }

    public function command(string $command): PendingShortScheduleCommand
    {
        $pendingCommand = (new PendingShortScheduleCommand())->command($command);

        $this->pendingCommands[] = $pendingCommand;

        return $pendingCommand;
    }

    public function exec(string $command): PendingShortScheduleCommand
    {
        $pendingCommand = (new PendingShortScheduleCommand())->exec($command);

        $this->pendingCommands[] = $pendingCommand;

        return $pendingCommand;
    }

    public function registerCommands(): self
    {
        $kernel = app(Kernel::class);

        $class = new ReflectionClass(get_class($kernel));

        $shortScheduleMethod = $class->getMethod('shortSchedule');

        $shortScheduleMethod->setAccessible(true);

        $shortScheduleMethod->invokeArgs($kernel, [$this]);

        return $this;
    }

    public function pendingCommands()
    {
        return collect($this->pendingCommands)
            ->map(function (PendingShortScheduleCommand $pendingCommand) {
                return new ShortScheduleCommand($pendingCommand);
            });
    }

    public function run(int $lifetime = null): void
    {
        if (! is_null($lifetime)) {
            $this->lifetime = $lifetime;
        }

        $this->pendingCommands()->each(function (ShortScheduleCommand $command) {
            $this->addCommandToLoop($command, $this->loop);
        });

        if (! is_null($this->lifetime)) {
            $this->addLoopTerminationTimer($this->loop);
        }

        $this->addLoopCheckRetsart($this->loop);

        $this->loop->run();
    }

    protected function addCommandToLoop(ShortScheduleCommand $command, LoopInterface $loop): void
    {
        $loop->addPeriodicTimer($command->frequencyInSeconds(),  function () use ($command) {
            if (! $command->shouldRun()) {
                return;
            }

            $command->run();
        });
    }

    protected function addLoopTerminationTimer(LoopInterface $loop): void
    {
        $loop->addPeriodicTimer($this->lifetime,  function () use ($loop) {
            $loop->stop();
        });
    }

    protected function addLoopCheckRetsart(LoopInterface $loop): void
    {
        $lastRestart = $this->getTimestampOfLastShortScheduleRestart();

        $loop->addPeriodicTimer(5,  function () use ($loop, $lastRestart) {
            if ($this->shortScheduleShouldRestart($lastRestart)) {
                $this->console->info('Short-schedule worker restarted.');
                $loop->stop();
            }
        });
    }

    /**
     * Determine if the queue worker should restart.
     *
     * @param  int|null  $lastRestart
     * @return bool
     */
    protected function shortScheduleShouldRestart($lastRestart)
    {
        return $this->getTimestampOfLastShortScheduleRestart() != $lastRestart;
    }

    /**
     * Get the last queue restart timestamp, or null.
     *
     * @return int|null
     */
    protected function getTimestampOfLastShortScheduleRestart()
    {
        return Cache::get('spatie:laravel-short-schedule:restart');
    }
}
