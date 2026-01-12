<?php

declare(strict_types=1);

namespace DbConfig\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

class PublishCommand extends Command
{
    private bool $overwrite = false;

    private const command = 'dbconfig publish';

    /**
     * The name of this command.
     *
     * @var string
     */
    protected string $name = self::command;

    /**
     * Get the default command name.
     *
     * @return string
     */
    public static function defaultName(): string
    {
        return self::command;
    }

    /**
     * Get the command description.
     *
     * @return string
     */
    public static function getDescription(): string
    {
        return 'Publish assets';
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/5/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->addOption('overwrite', [
            'short' => 'o',
            'help' => 'Overwrite all files.',
            'boolean' => true,
            'default' => false,
        ]);

        return parent::buildOptionParser($parser)
            ->setDescription(static::getDescription());
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $this->overwrite = $args->getOption('overwrite');

        $files = array(
            'AppSettings' . DS . 'index.php',
        );

        foreach ($files as $file) {
            $this->publish($file, $io, $args);
        }
    }

    public function publish($file, ConsoleIo $io)
    {
        $source = dirname(__DIR__, 2) . DS . 'templates' . DS . $file;
        $target = ROOT . DS . 'templates' . DS . 'plugin' . DS . 'DbConfig' . DS . $file;

        if (!file_exists($source)) {
            $io->err("Source template not found: {$source}");
            return;
        }

        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0775, true);
        }

        if (file_exists($target) && !$this->overwrite) {
            $answer = strtolower(trim($io->ask("Template already exists at: {$target}\nOverwrite? (y/n/a/yes/no/all)", 'n')));
            if (!in_array(strtolower($answer), ['y', 'yes', 'a', 'all'], true)) {
                $io->out("Publish cancelled.");
                return;
            }

            if (in_array($answer, ['a', 'all'])) {
                $this->overwrite = true;
            }
        }

        if (copy($source, $target)) {
            $io->out("Published Templates to: {$target}");
            return;
        }
    }
}
