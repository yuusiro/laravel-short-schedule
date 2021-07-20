<?php

namespace Spatie\ShortSchedule;

use Closure;
use Illuminate\Console\Application;
use Illuminate\Support\Arr;
use Spatie\ShortSchedule\RunConstraints\BetweenConstraint;
use Spatie\ShortSchedule\RunConstraints\EnvironmentConstraint;
use Spatie\ShortSchedule\RunConstraints\RunConstraint;
use Spatie\ShortSchedule\RunConstraints\WhenConstraint;
use Symfony\Component\Console\Output\OutputInterface;

class PendingShortScheduleCommand
{
    protected string $command = '';

    protected float $frequencyInSeconds = 1;

    protected bool $allowOverlaps = true;

    protected bool $onOneServer = false;

    protected bool $evenInMaintenanceMode = false;

    protected bool $runInBackground = false;

    protected array $constraints = [];

    protected int $verbosity = OutputInterface::VERBOSITY_QUIET;

    public function everySecond(float $frequencyInSeconds = 1): self
    {
        return $this->everySeconds($frequencyInSeconds);
    }

    public function everySeconds(float $frequencyInSeconds = 1): self
    {
        $this->frequencyInSeconds = $frequencyInSeconds;

        return $this;
    }

    public function command(string $artisanCommand):self
    {
        $this->command = Application::formatCommandString($artisanCommand);

        return $this;
    }

    public function exec(string $command): self
    {
        $this->command = $command;

        return $this;
    }

    public function withoutOverlapping(): self
    {
        $this->allowOverlaps = false;

        return $this;
    }

    public function runInMaintenanceMode(): self
    {
        $this->evenInMaintenanceMode = true;

        return $this;
    }

    public function shouldRun(): bool
    {
        $shouldNotRun = collect($this->constraints)
            ->contains(
                fn (RunConstraint $runConstraint) => ! $runConstraint->shouldRun()
            );

        return ! $shouldNotRun;
    }

    public function onOneServer(): self
    {
        $this->onOneServer = true;

        return $this;
    }

    public function between(string $startTime, string $endTime): self
    {
        $this->constraints[] = new BetweenConstraint($startTime, $endTime);

        return $this;
    }

    /**
     * @param string|array $environments
     *
     * @return static
     */
    public function environments($environments): self
    {
        $environments = Arr::wrap($environments);

        $this->constraints[] = new EnvironmentConstraint($environments);

        return $this;
    }

    public function when(Closure $closure): self
    {
        $this->constraints[] = new WhenConstraint($closure);

        return $this;
    }

    public function verbose(): self
    {
        $this->verbosity = OutputInterface::VERBOSITY_NORMAL;

        return $this;
    }

    public function runInBackground(): self
    {
        $this->runInBackground = true;

        return $this;
    }

    public function cacheName(): string
    {
        return 'framework'.DIRECTORY_SEPARATOR.'schedule-'.sha1($this->frequencyInSeconds.$this->command);
    }

    public function cacheNameOnOneServer(): string
    {
        return 'framework'.DIRECTORY_SEPARATOR.'schedule-'.sha1($this->frequencyInSeconds.$this->command.'onOneServer');
    }
}
