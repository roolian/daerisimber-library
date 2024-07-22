<?php

namespace DaerisimberLibrary\Services\Plugins\ACF;

use ReflectionClass;
use DaerisimberLibrary\Services\Helper;
use DaerisimberLibrary\Utils\Traits\SingletonTrait;

class BlockFinder
{
    use SingletonTrait;

    public array $blocks;
    public array $load_paths = [];

    public array $block_categories = [
        'daeris' =>  'Daeris',
        'drs_layout' =>  'Layout',
        'drs_interaction' =>  'Interaction',
    ] ;

    public string $block_folder_path = '/blocks';

    public function init()
    {
        //Find blocks in block folder
        $root_blocks = $this->find_all_blocks(get_template_directory() . $this->block_folder_path);
        //Find blocks in modules folder
        $modules_blocks = $this->find_all_blocks(get_template_directory() . '/modules');

        $this->blocks = array_merge($root_blocks, $modules_blocks);

        add_action('acf/init', [$this, 'register_blocks'], 5);
        add_action('block_categories_all', [$this, 'register_block_categories']);
        add_filter('acf/settings/load_json', [$this, 'set_json_load_point'], 11);
        add_filter('acf/json/save_paths', [$this, 'set_json_save_paths'], 10, 2);
        add_filter('acf/json/save_file_name', [$this, 'set_json_save_filename'], 10, 3);
    }

    public function register_blocks()
    {
        foreach ($this->blocks as $slug => $blockJsonPath) {
            
            $className = Helper::str_to_camel($slug) . 'BlockModel';
            $classPath = dirname($blockJsonPath) . '/' . $className . '.php';

            if(file_exists($classPath)) {
                if(!class_exists($className)) {
                    include $classPath;
                }
                //The class need to inherits BlockModel
                $refl = new ReflectionClass($className);
                $block_render = $refl->newInstanceArgs();
            } else {
                $block_render  = new BlockModel();
            }

            register_block_type($blockJsonPath, [
                'render_callback' => [$block_render, 'render']
            ]);
        }
    }

    public function register_block_categories($categories)
    {
        return array_merge(
            array_map(fn ($key, $value) => ['slug' => $key, 'title' => $value], array_keys($this->block_categories), array_values($this->block_categories)),
            $categories
        );
    }

    /**
     * Customize filename where json groupfield are stored
     *
     */
    public function set_json_save_filename(string $filename, array $post, string $load_path): string
    {
        if ($this->get_block_name_from_fieldgroup_location($post['location'])) {
            $filename = 'acf.json';
        }

        return $filename;
    }

    /**
     * Customize folder where json groupfield are stored
     *
     */
    public function set_json_save_paths(array $paths, array $post): array
    {
        //If not in dev, we don't save json file
        if(!in_array(WP_ENV, ['development', 'local'])) {
            return [];
        }

        if ($name = $this->get_block_name_from_fieldgroup_location($post['location'])) {
            $paths = [dirname($this->blocks[$name])];
        }

        return $paths;
    }

    /**
     * Customize folder where json groupfield are loaded
     * We want search in each block folder
     *
     */
    public function set_json_load_point($paths)
    {
        // Remove the original path (optional).
        //unset($paths[0]);

        foreach ($this->blocks as $key => $value) {
            // Append the new path and return it.
            $paths[] = dirname($value);
        }

        return $paths;
    }

    public function find_all_blocks($blocks_directory): array
    {
        $temp_blocks = [];

        /** @var \SplFileInfo $file   */
        foreach (self::filesIn($blocks_directory) as $file) {
            $temp_blocks[$file->getPathInfo()->getBasename()] = $file->getPathname();
            //$this->load_paths[] = $file->getPathInfo()->getRealPath();
        }

        asort($temp_blocks);

        return $temp_blocks;
    }

    private static function filesIn(string $path): \Generator
    {
        if (! is_dir($path)) {
            throw new \RuntimeException("{$path} is not a directory ");
        }

        $it = new \RecursiveDirectoryIterator($path);
        $it = new \RecursiveIteratorIterator($it);
        $it = new \RegexIterator($it, '/block.json$/', \RegexIterator::MATCH);

        yield from $it;
    }

    /**
     * Find block name from the field group definition
     *
     * @param array $location
     * A multidimensionnal array.
     * [
     *      [
     *          [
     *              "param" => "block",
     *              "operator" => "==",
     *              "value" => "daeris/hero"
     *          ],
     *          [
     *              ...
     *              can have additionnal AND conditions
     *          ]
     *      ],
     *      [
     *          ...
     *          can have additionnal OR conditions
     *      ]
     * ]
     * @return string|boolean
     */
    private function get_block_name_from_fieldgroup_location(array $location): string|false
    {
        $main_condition = $location[0][0];
        if ($main_condition['param'] === 'block') {
            //Remove first part to get name
            return explode('/', $main_condition['value'])[1];
        }

        return false;
    }
}
