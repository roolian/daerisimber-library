<?php

namespace Daerisimber\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ViewPublishCommand extends Command
{
    public $base_path = ROOT_THEME_DIR . '/vendor/daerisimber/library/views';

    public function rsearch($folder, $regPattern)
    {
        $dir = new \RecursiveDirectoryIterator($folder);
        $ite = new \RecursiveIteratorIterator($dir);

        return new \RegexIterator($ite, $regPattern, \RegexIterator::GET_MATCH);
    }

    public function get_templates()
    {

        $files = $this->rsearch($this->base_path, '/.*\.twig/');
        $fileList = [];
        foreach ($files as $file) {
            $path = $file[0];
            $name = str_replace($this->base_path, '', $path);
            // $fileList[$file->getPathInfo()->getBasename()] = $file->getPathname();
            $fileList[] = $name;
            // $fileList = array_merge($fileList, $file);
        }

        return $fileList;
    }

    /**
     * Configure.
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('view:publish');
        $this->setDescription('Publish a default view in your theme to customize.');
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
    }

    /**
     * Execute command.
     *
     * @return int integer 0 on success, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$output instanceof ConsoleOutputInterface) {
            throw new \LogicException('This command accepts only an instance of "ConsoleOutputInterface".');
        }

        $section0 = $output->section();
        $section1 = $output->section();
        $section133 = $output->section();
        
        $section0->writeln('Copy a default twig view in your theme.');
        $section0->writeln('');
        $section133->writeln('');


        // $files = glob(COMMAND_DIR.'/vendor/daeris/daerisimber-library/*/*.twig');
        $files = $this->get_templates();


        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            '<question>Please select a template</question>',
            // choices can also be PHP objects that implement __toString() method
            $files,
            0
        );
        $question->setErrorMessage('<error>Template %s is invalid.</error>');

        $file = $helper->ask($input, $section1, $question);

        if (file_exists(ROOT_THEME_DIR . '/theme/views' . $file)) {
            $confirm = new ConfirmationQuestion('<question>File already exist. Delete existing file?</question> yes or no(default) : ', false);

            if (!$helper->ask($input, $section1, $confirm)) {
                $section1->overwrite('<comment>Operation cancelled</comment>');
                return Command::SUCCESS;
            }
        }

        $section1->overwrite('');

        if(copy($this->base_path . '/' . $file, ROOT_THEME_DIR . '/theme/views' . $file)) {
            $section1->writeln('<info>View successfully created in</info> : ' . ROOT_THEME_DIR . '/theme/views' . $file);
        } else {
            $section1->writeln('<error>Error occured</error>');
        }

        return Command::SUCCESS;
    }
}
