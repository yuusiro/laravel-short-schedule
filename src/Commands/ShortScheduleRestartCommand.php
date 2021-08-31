<?php

namespace Spatie\ShortSchedule\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\InteractsWithTime;

class ShortScheduleRestartCommand extends Command
{
    use InteractsWithTime;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'short-schedule:restart';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restart short-schedule worker daemon';

    /**
     * The cache store implementation.
     *
     * @var Cache
     */
    protected $cache;

    /**
     * Create a new queue restart command.
     *
     * @param  Cache  $cache
     * @return void
     */
    public function __construct(Cache $cache)
    {
        parent::__construct();

        $this->cache = $cache;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->cache->forever('spatie:laravel-short-schedule:restart', $this->currentTime());

        $this->info('Broadcasting short-schedule restart signal.');
    }
}
