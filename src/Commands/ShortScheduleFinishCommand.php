<?php

namespace Spatie\ShortSchedule\Commands;

use Illuminate\Console\Command;
use React\EventLoop\Factory;
use Spatie\ShortSchedule\ShortSchedule;

class ShortScheduleFinishCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'short-schedule:finish {id} {code=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handle the completion of a scheduled command';

    /**
     * Indicates whether the command should be shown in the Artisan command list.
     *
     * @var bool
     */
    protected $hidden = true;

    /**
     * Execute the console command.
     *
     * @param  Spatie\ShortSchedule\ShortSchedule  $shortSchedule
     * @return void
     */
    public function handle()
    {
        $loop = Factory::create();

        $shortSchedule = (new ShortSchedule($loop))->registerCommands();

        $shortSchedule->pendingCommands()->filter(function ($value) {
            return $value->getCacheName() == $this->argument('id');
        })->each->callAfterEndedBackgroundCommand($this->argument('code'));
    }
}
