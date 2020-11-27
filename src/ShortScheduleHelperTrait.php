<?php


namespace Spatie\ShortSchedule;


use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Console\Output\OutputInterface;

trait ShortScheduleHelperTrait
{
    protected int $count = 0;
    
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

    private function getExectution(): string
    {
        return PHP_EOL.'Execution #'.(++$this->count).' in '.now()->isoFormat('L LTS').' output:';
    }

    private function createLock(bool $onOneServer = false): bool
    {

        return Cache::add($this->lockCacheName($onOneServer), true, $this->ttl($onOneServer));
    }

    private function existsLock(bool $onOneServer = false): bool
    {
        return Cache::has($this->lockCacheName($onOneServer));
    }

    private function fogetLock(bool $onOneServer = false): bool
    {
        return Cache::forget($this->lockCacheName($onOneServer));
    }

    private function ttl(bool $onOneServer = false)
    {
        return $onOneServer ? ceil($this->frequencyInSeconds()) : 60;
    }

    private function lockCacheName(bool $onOneServer = false)
    {
        return $onOneServer
            ? $this->pendingShortScheduleCommand->cacheNameOnOneServer()
            : $this->getCacheName();
    }
}
