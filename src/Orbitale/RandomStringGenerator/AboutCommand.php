<?php

/*
 * This file is part of the OrbitaleCmsBundle package.
 *
 * (c) Alexandre Rock Ancelet <alex@orbitale.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Orbitale\RandomStringGenerator;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This command provides information about the Orbitale random string generator.
 *
 * @author Alexandre Rock Ancelet <alex@orbitale.io>
 */
class AboutCommand extends Command
{
    private $appVersion;

    public function __construct($appVersion)
    {
        parent::__construct();

        $this->appVersion = $appVersion;
    }

    protected function configure()
    {
        $this
            ->setName('about')
            ->setDescription('Orbitale random string generator help.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Orbitale random string generator (' . $this->appVersion . ')');

        $io->text(<<<COMMAND_HELP
This application is here to help you generate random codes based on a list of characters.
It is useful for example when you want to generate tons of codes for a list of users
before sending them an email, or if you want to generate passwords that respect certain characters.
COMMAND_HELP
        );

        $io->block('To start using the generator, execute this command:');
        $io->block('$ ' . $this->getExecutedCommand() . ' generate', null, 'info');

        $io->note('Don\'t forget to execute "'.$this->getExecutedCommand().' generate --help" command to see how it works!');

        $this->getApplication();
    }

    /**
     * Returns the executed command.
     *
     * @return string
     */
    private function getExecutedCommand()
    {
        $pathDirs = explode(PATH_SEPARATOR, $_SERVER['PATH']);
        $executedCommand = $_SERVER['PHP_SELF'];
        $executedCommandDir = dirname($executedCommand);
        if (in_array($executedCommandDir, $pathDirs)) {
            $executedCommand = basename($executedCommand);
        }
        return $executedCommand;
    }
}
