<?php

namespace Spatie\ShortSchedule;

use Illuminate\Console\Application;
use Illuminate\Support\ProcessUtils;

class ShortScheduleCommandBuilder
{
    /**
     * Build the command.
     *
     * @param ShortScheduleCommand $command
     * @return string
     */
    public function buildCommand(ShortScheduleCommand $command)
    {
        if ($command->getRunInBackround()) {
            return $this->buildBackgroundCommand($command);
        }

        return $this->buildForegroundCommand($command);
    }

    /**
     * Build the command in the foreground.
     *
     * @param ShortScheduleCommand $command
     * @return string
     */
    protected function buildForegroundCommand(ShortScheduleCommand $command)
    {
        $output = ProcessUtils::escapeArgument($command->getOutput());

        return $command->getCommand().' > '.$output.' 2>&1';
    }

    /**
     * Build the command in the background.
     *
     * @param ShortScheduleCommand $command
     * @return string
     */
    protected function buildBackgroundCommand(ShortScheduleCommand $command)
    {
        $output = ProcessUtils::escapeArgument($command->getOutput());

        $redirect = ' > ';

        $finished = Application::formatCommandString('short-schedule:finish').' "'.$command->getCacheName().'"';

        return '('.$command->getCommand().$redirect.$output.' 2>&1 ; '.$finished.' "$?") > '
            .$output.' 2>&1 &';
    }
}
