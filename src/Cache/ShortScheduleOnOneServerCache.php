<?php


namespace Spatie\ShortSchedule\Cache;

use Illuminate\Support\Facades\Cache;
use Spatie\ShortSchedule\ShortScheduleCommand;

class ShortScheduleOnOneServerCache implements ShortScheduleCacheInterface
{
    public function createLock(ShortScheduleCommand $command): bool
    {
        return Cache::add($command->getCacheNameOnOneServer(), true, ceil($this->command->frequencyInSeconds()));
    }

    public function existsLock(ShortScheduleCommand $command): bool
    {
        return Cache::has($command->getCacheNameOnOneServer());
    }

    public function fogetLock(ShortScheduleCommand $command): bool
    {
        return Cache::forget($command->getCacheNameOnOneServer());
    }
}
