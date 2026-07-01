<?php

namespace Daerisimber\Services\Plugins\ACF;

use Timber\Timber;
use DirectoryIterator;
use Daerisimber\Services\Helper;

class BlockModel
{
    public string $block_path;
    public object $json_data;
    public array $block;
    public string $content;
    public bool $is_preview;
    public bool $is_thumbnail = false;
    public int $post_id;
    public array $context;
    public array $timber_context = [];
    public array|false $fields;
    public string $name;
    public array $class;

    public function __construct(string $blockJsonPath)
    {
        $this->block_path = dirname($blockJsonPath);
        $json = file_get_contents($blockJsonPath);

        if ($json === false) {
            throw new \RuntimeException("Could not read block.json file: {$blockJsonPath}");
        }

        $json_data = json_decode($json);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(
                "Invalid JSON format in {$blockJsonPath}: " . json_last_error_msg()
            );
        }

        $this->json_data = $json_data;

        // add_action('acf/include_fields', [$this, 'add_variant_field']);
        $this->add_variant_field();
        $this->custom_construct();
    }
    /**
     * Custom construct method to be overridden in child classes.
     */
    public function custom_construct()
    {
        // To be overridden in child classes
    }

    /**
     * Render
     *
     * @param    array    $attributes The block attributes.
     * @param    string   $content The block content.
     * @param    bool     $is_preview Whether or not the block is being rendered for editing preview.
     * @param    int      $post_id The current post being edited or viewed.
     * @param    \WP_Block $wp_block The block instance (since WP 5.5).
     */
    public function render(array $block, string $content, bool $is_preview, int $post_id, \WP_Block $wp_block = null, array $context)
    {
        $this->block = $block;
        $this->content = $content;
        $this->is_preview = $is_preview;
        $this->post_id = $post_id;
        $this->context = $context;
        $this->fields = get_fields();
        $this->name = explode('/', $block['name'])[1];

        if (!$this->fields) {
            $this->fields = $block['data'];
            $this->is_thumbnail = true;
        }

        $this->generate_common_classes();

        $this->timber_context['post']   = Timber::get_post();
        $this->timber_context['block']  = $block;

        $this->timber_context['id'] = $this->get_container_id();
        $this->timber_context['fields'] = $this->fields;
        $this->timber_context['context'] = $context;
        $this->timber_context['post_id'] = $post_id;
        $this->timber_context['is_preview'] = $is_preview;
        $this->timber_context['is_thumbnail'] = $this->is_thumbnail;
        $this->timber_context['class'] = $this->get_class();

        $context = Timber::context();

        $this->before_render();

        $template  = [$this->block['path'] . '/' . $this->name . '.twig', $this->block['path'] . '/index.twig'];
        Timber::render($template, array_merge($context, $this->timber_context));
    }

    public function generate_common_classes()
    {
        $this->class = [];
        $this->add_class($this->name);

        $this->add_class('wp-block-acf');

        if (isset($this->block['className'])) {
            $this->add_class($this->block['className']);
        }

        if (!empty($this->block['align'])) {
            $this->add_class('align' . $this->block['align']);
        }
    }

    public function before_render()
    {
        //To be overrided
    }

    public function add_class($class)
    {
        $this->class[] = $class;
    }

    public function get_class()
    {
        return implode(' ', array_unique($this->class));
    }

    private function get_container_id()
    {
        $id = $this->name . '-' . $this->block['id'];

        return isset($this->block['anchor']) ? $this->block['anchor'] : $id;
    }

    public function get_variant_list()
    {
        $variant_list_choice = [];
        $path = $this->block_path . '/views/variant/';

        //Check without views folder
        if (!is_dir($path)) {
            $path = $this->block_path . '/variant/';
        }

        if (is_dir($path)) {
            $this->timber_context['variant_path'] = $path;

            // directory to scan
            $directory = new DirectoryIterator($path);

            foreach ($directory as $fileinfo) {
                // must be a file
                if ($fileinfo->isFile()) {
                    // file extension
                    $extension = strtolower(pathinfo($fileinfo->getFilename(), PATHINFO_EXTENSION));
                    // check if extension match
                    if ($extension == 'twig') {
                        // add to result
                        $name = str_replace('.twig', '', $fileinfo->getFilename());
                        $variant_list_choice[$fileinfo->getFilename()] = Helper::str_to_title($name);
                    }
                }
            }
        }

        return $variant_list_choice;
    }

    public function add_variant_field()
    {
        $list = $this->get_variant_list();
        if (empty($list)) {
            return;
        }
        $key = substr($this->block_path, -10);
        acf_add_local_field_group([
            'modified' => null,
            'key' => "group_var_{$key}",
            'title' => 'Variante',
            'fields' => [
                [
                    'key' => "field_var_{$key}",
                    'label' => 'Variante',
                    'name' => 'variant',
                    'aria-label' => '',
                    'type' => 'select',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'choices' => $this->get_variant_list(),
                    'default_value' => false,
                    'return_format' => 'value',
                    'multiple' => 0,
                    'allow_null' => 0,
                    'ui' => 0,
                    'ajax' => 0,
                ],
            ],
            'active' => true,
            'menu_order' => -1,
            'location' => [
                [
                    [
                        'param' => 'block',
                        'operator' => '==',
                        'value' =>  $this->json_data->name,
                    ],
                ],
            ],
        ]);
    }

    public static function get_fields_from_page(string $blockId, int $postId, $fields = false)
    {
        $post = get_post($postId);

        if (! $post) {
            return false;
        }

        $blocks = parse_blocks($post->post_content);

        if ($blocks) {
            $iterator = new \RecursiveArrayIterator($blocks);
            $recursive = new \RecursiveIteratorIterator(
                $iterator,
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($recursive as $key => $value) {
                if (isset($value['attrs'], $value['attrs']['id'], $value['attrs']['data'])) {
                    if ($value['attrs']['id'] === $blockId) {
                        acf_setup_meta($value['attrs']['data'], $value['attrs']['id'], true);
                        if (! $fields) {
                            $returnedFields = get_fields();
                        }

                        if (is_array($fields)) {
                            $returnedFields = [];
                            foreach ($fields as $key) {
                                $returnedFields[$key] = get_field($key);
                            }
                        } else {
                            $returnedFields = get_field($fields);
                        }

                        acf_reset_meta($value['attrs']['id']);

                        //return $value['attrs']['data'];
                        return $returnedFields;
                    }
                }
            }
        }

        return false;
    }
}
