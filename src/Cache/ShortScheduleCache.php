<?php


namespace Spatie\ShortSchedule\Cache;

use Illuminate\Support\Facades\Cache;
use Spatie\ShortSchedule\ShortScheduleCommand;

class ShortScheduleCache implements ShortScheduleCacheInterface
{
    public function createLock(ShortScheduleCommand $command): bool
    {
        return Cache::add($command->getCacheName(), true, 60);
    }

    public function existsLock(ShortScheduleCommand $command): bool
    {
        return Cache::has($command->getCacheName());
    }

    public function fogetLock(ShortScheduleCommand $command): bool
    {
        return Cache::forget($command->getCacheName());
    }
}
