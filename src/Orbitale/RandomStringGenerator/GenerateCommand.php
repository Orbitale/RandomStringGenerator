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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command that will generate a random string based on many arguments.
 *
 * @author Alexandre Rock Ancelet <alex@orbitale.io>
 */
class GenerateCommand extends Command
{

    /**
     * @var integer
     */
    private $sleepTime;

    /**
     * @var integer
     */
    private $codeLength;

    /**
     * @var integer
     */
    private $numberOfCodesToGenerate;

    /**
     * @var array
     */
    private $possibleChars;

    /**
     * @var SymfonyStyle
     */
    private $io;

    protected function configure()
    {
        $this
            ->setName('generate')
            ->setDescription('Generates random strings based on the different arguments and options.')
            ->addOption('length', 'l', InputOption::VALUE_OPTIONAL,
                'The length you want for each code.',
                1
            )
            ->addOption('amount', 'a', InputOption::VALUE_OPTIONAL,
                'The number of codes you want to generate.',
                1
            )
            ->addOption('sleepTime', 's', InputOption::VALUE_OPTIONAL,
                'Time to wait between each code generation, in '.
                'milliseconds (mostly for visual comfort)',
                0
            )
            ->addOption('characters', 'c', InputOption::VALUE_OPTIONAL,
                "The characters you want to use, as a normal string. ",
                implode('', array_map('strval', array_merge(range('a', 'z'), range('0', '9'))))
            )
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL,
                'The file to which you want to output the codes, instead of directly in the console.'
            )
            ->addOption('sort', null, InputOption::VALUE_NONE,
                'If specified, sorts the final results for it to be more readable.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->sort = $input->getOption('sort');

        $this->sleepTime = $input->getOption('sleepTime');

        if (!$input->hasOption('sleepTime') && $output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
            $this->sleepTime = 25;
        }

        // Transform milliseconds in microseconds for usleep()
        $this->sleepTime = $this->sleepTime * 1000;

        $this->numberOfCodesToGenerate = $input->getOption('amount');

        // The length of each outputted code
        $this->codeLength = $input->getOption('length');

        // All possible chars. By default, 'A' to 'Z' and '0' to '9'
        $this->possibleChars = str_split($input->getOption('characters'));
        $baseNumberOfChars = count($this->possibleChars);
        $this->possibleChars = array_unique($this->possibleChars);
        // If there's an error here, we'll say it later

        $maxPossibleNumberOfCombinations = pow(count($this->possibleChars), $this->codeLength);

        if ($maxPossibleNumberOfCombinations < $this->numberOfCodesToGenerate) {
            $this->io->error(sprintf('Cannot generate %s combinations because there are only %s combinations possible',
                number_format($this->numberOfCodesToGenerate, 0, '', ' '),
                number_format($maxPossibleNumberOfCombinations, 0, '', ' ')
            ));

            return 1;
        } else {
            $this->io->block(sprintf('Generating %s combinations.', number_format($this->numberOfCodesToGenerate, 0, '', ' ')), null, 'info');
            if ($maxPossibleNumberOfCombinations > $this->numberOfCodesToGenerate) {
                $this->io->block(sprintf('Note: If you need you can generate %s more combinations (with a maximum of %s).',
                    number_format($maxPossibleNumberOfCombinations - $this->numberOfCodesToGenerate, 0, '', ' '),
                    number_format($maxPossibleNumberOfCombinations, 0, '', ' ')
                ), null, 'comment');
            }
        }

        $this->io->block('Available characters:');
        $this->io->block(implode('', $this->possibleChars), null, 'info');

        $codesList = $this->doGenerate();

        $outputFile = $input->getOption('output');

        if ($outputFile) {

            $save = true;
            if (file_exists($outputFile)) {
                $save = $this->io->confirm(sprintf('File %s exists. Erase it?', $outputFile), false);
            }
            if ($save) {
                $this->io->block(sprintf('Output results to %s', $outputFile), null, 'info');
                if (!file_put_contents($outputFile, implode("\n", $codesList))) {
                    throw new \Exception(sprintf('Could not write to %s...', $outputFile));
                };
            }
        } else {
            $this->io->text($codesList);
        }

        if ($baseNumberOfChars !== count($this->possibleChars)) {
            $this->io->warning(sprintf(
                'We detected that there were duplicate characters in "%s", so we removed them.',
                $input->getOption('characters')
            ));
        }

        return 0;
    }

    /**
     * @return array
     */
    private function doGenerate()
    {
        $numberOfChars = count($this->possibleChars) - 1;

        $codesList = [];

        $this->io->progressStart($this->numberOfCodesToGenerate);

        for ($i = 0; $i < $this->numberOfCodesToGenerate; $i++) {

            do {
                $code = '';
                for ($j = 0; $j < $this->codeLength; $j++) {
                    $code .= $this->possibleChars[rand(0, $numberOfChars)];
                }
            } while (isset($codesList[$code]));

            $codesList[$code] = $code;

            $this->io->progressAdvance();

            if ($this->sleepTime) {
                usleep($this->sleepTime);
            }
        }

        $this->io->progressFinish();

        if ($this->sort) {
            sort($codesList);
        }

        return array_unique($codesList);
    }
}
