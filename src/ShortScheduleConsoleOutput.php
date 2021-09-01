<?php


namespace Spatie\ShortSchedule;

use Illuminate\Support\Facades\App;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ShortScheduleConsoleOutput
{
    private ConsoleOutput $console;

    private int $verbosity;

    private int $count = 0;

    public function __construct(int $verbosity)
    {
        $this->verbosity = $verbosity;
        $this->console = new ConsoleOutput($this->verbosity);
    }

    public function write($string, $style = null): void
    {
        if (App::environment('testing') && $this->verbosity === OutputInterface::VERBOSITY_NORMAL) {
            echo $this->getExectution().$string;

            return;
        }

        $this->console->writeln('<info>'.$this->getExectution().'</info>');

        $styled = $style ? "<$style>$string</$style>" : $string;

        $this->console->writeln($styled);
    }

    public function info($string): void
    {
        $this->console->writeln('<info>'.$string.'</info>');
    }

    private function getExectution(): string
    {
        return PHP_EOL.'Execution #'.(++$this->count).' in '.now()->isoFormat('L LTS.SSS').' output:';
    }
}
