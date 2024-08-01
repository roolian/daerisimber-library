<?php

namespace Daerisimber\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
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
        // $files = glob(COMMAND_DIR.'/vendor/daeris/daerisimber-library/*/*.twig');
        $files = $this->get_templates();

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select a template',
            // choices can also be PHP objects that implement __toString() method
            $files,
            0
        );
        $question->setErrorMessage('Template %s is invalid.');

        $file = $helper->ask($input, $output, $question);

        if (file_exists(COMMAND_DIR . '/theme/views' . $file)) {
            $confirm = new ConfirmationQuestion('File already exist. Continue with this action? yes or no :', false);

            if (!$helper->ask($input, $output, $confirm)) {
                return Command::SUCCESS;
            }
        }

        if(copy($this->base_path . '/' . $file, COMMAND_DIR . '/theme/views' . $file)) {
            $output->writeln('View successfully created in : ' . COMMAND_DIR . '/theme/views' . $file);
        } else {
            $output->writeln('Error occured');
        }

        return Command::SUCCESS;
    }
}
