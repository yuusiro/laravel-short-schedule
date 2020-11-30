<?php

namespace Spatie\ShortSchedule\Cache;

use Spatie\ShortSchedule\ShortScheduleCommand;

interface ShortScheduleCacheInterface
{
    public function createLock(ShortScheduleCommand $command): bool;

    public function existsLock(ShortScheduleCommand $command): bool;

    public function fogetLock(ShortScheduleCommand $command): bool;
}
