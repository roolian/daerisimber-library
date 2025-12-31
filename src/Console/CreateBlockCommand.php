<?php

namespace Daerisimber\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateBlockCommand extends Command
{
    protected static $defaultName = 'block:create';

    protected function configure()
    {
        $this
            ->setDescription('Create a new block.')
            ->setHelp('This command allows you to create a new block...');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        $question = new Question('Please enter the name of the block: ');
        $blockName = $helper->ask($input, $output, $question);

        if (empty($blockName)) {
            $io->error('Block name cannot be empty');
            return Command::FAILURE;
        }

        $categories = [
            'Layout' => 'drs_layout',
            'Component' => 'drs_component',
            'Query' => 'drs_query',
            'Module' => 'drs_module',
        ];

        $question = new ChoiceQuestion(
            'Please select the block category',
            array_keys($categories),
            0
        );
        $question->setErrorMessage('Category %s is invalid.');

        $categoryName = $helper->ask($input, $output, $question);
        $categoryKey = $categories[$categoryName];
        $categorySlug = strtolower($categoryName);

        $slug = $this->slugify($blockName);

        if ($categoryName === 'Module') {
            $modulesPath = ROOT_THEME_DIR . '/src/modules';
            $modules = array_filter(glob($modulesPath . '/*'), 'is_dir');
            $modules = array_map('basename', $modules);

            if (empty($modules)) {
                $io->error('No modules found in src/modules');
                return Command::FAILURE;
            }

            $moduleQuestion = new ChoiceQuestion(
                'Please select the module',
                $modules,
                0
            );
            $moduleQuestion->setErrorMessage('Module %s is invalid.');
            $moduleName = $helper->ask($input, $output, $moduleQuestion);

            $blockPath = ROOT_THEME_DIR . '/src/modules/' . $moduleName . '/blocks/' . $slug;
            // For modules, we might want to keep the category generic or specific to the module
            // For now, let's keep it as drs_module or maybe drs_query if it's dynamic
            // The user request didn't specify a category key for modules, but 'drs_query' seems common for dynamic blocks
             $categoryKey = 'drs_query'; 
        } else {
            $blockPath = ROOT_THEME_DIR . '/src/blocks/' . $categorySlug . '/' . $slug;
        }

        if (is_dir($blockPath)) {
            $io->error('Block already exists');
            return Command::FAILURE;
        }

        mkdir($blockPath, 0755, true);

        $this->createBlockJson($blockPath, $blockName, $slug, $categoryKey);
        $this->createBlockModel($blockPath, $blockName, $slug);
        $this->createTwigTemplate($blockPath, $slug);
        $this->createAcfJson($blockPath, $blockName, $slug);

        $io->success('Block created successfully at ' . $blockPath);

        return Command::SUCCESS;
    }

    private function slugify($text)
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    private function createBlockJson($path, $name, $slug, $category)
    {
        $content = json_encode([
            'name' => 'daerisimber/' . $slug,
            'title' => $name,
            'description' => 'A custom block.',
            'category' => $category,
            'icon' => 'admin-comments',
            'keywords' => [$name, $slug],
            'acf' => [
                'mode' => 'preview',
                'renderTemplate' => $slug . '.twig',
            ],
            'supports' => [
                'anchor' => true,
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        file_put_contents($path . '/block.json', $content);
    }

    private function createTwigTemplate($path, $slug)
    {
        $content = <<<EOT
{#
  Title: Block $slug
  Description: A custom block
  Category: formatting
  Icon: admin-comments
  Keywords: $slug
  Mode: preview
  Align: center
  PostTypes: page post
  SupportsAlign: left right wide full
#}

<div class="{{ slug }}-block">
    <h2>Block: {{ title }}</h2>
    <p>Edit this file in {{ directory }}/{{ slug }}.twig</p>
    <p>Sample variable from model: {{ sample_variable }}</p>
</div>
EOT;
        file_put_contents($path . '/' . $slug . '.twig', $content);
    }

    private function createBlockModel($path, $name, $slug)
    {
        $className = str_replace('-', '', ucwords($slug, '-')) . 'BlockModel';
        
        $content = <<<EOT
<?php

use Daerisimber\Services\Plugins\ACF\BlockModel;

class $className extends BlockModel
{
    public function before_render(): void
    {
        \$this->timber_context['sample_variable'] = 'Hello from $className';
        \$this->timber_context['title'] = \$this->fields['title'] ?? '$name';
        \$this->timber_context['slug'] = '$slug';
        \$this->timber_context['directory'] = __DIR__;
    }
}
EOT;
        file_put_contents($path . '/' . $className . '.php', $content);
    }

    private function createAcfJson($path, $name, $slug)
    {
        $groupKey = 'group_' . uniqid();
        $fieldKey = 'field_' . uniqid();

        $content = json_encode([
            'key' => $groupKey,
            'title' => $name,
            'fields' => [
                [
                    'key' => $fieldKey,
                    'label' => 'Title',
                    'name' => 'title',
                    'type' => 'text',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'default_value' => '',
                    'placeholder' => '',
                    'prepend' => '',
                    'append' => '',
                    'maxlength' => '',
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'block',
                        'operator' => '==',
                        'value' => 'daerisimber/' . $slug,
                    ],
                ],
            ],
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => true,
            'description' => '',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        file_put_contents($path . '/acf.json', $content);
    }
}
