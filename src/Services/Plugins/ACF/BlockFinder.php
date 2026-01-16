<?php

namespace Daerisimber\Services\Plugins\ACF;

use ReflectionClass;
use Daerisimber\Config;
use Daerisimber\Services\Helper;
use Daerisimber\Utils\Traits\SingletonTrait;

class BlockFinder
{
    use SingletonTrait;

    public array $blocks;
    public array $load_paths = [];

    public array $block_categories = [
        'daeris' =>  'Daeris',
        'drs_layout' =>  'Layout',
        'drs_component' =>  'Component',
        'drs_query' =>  'Query',
    ] ;

    public string $block_folder_path = '/blocks';
    public string $module_folder_path = '/modules';

    public function init()
    {
        $root_directory = get_template_directory() . $this->block_folder_path;
        if (!is_dir($root_directory)) {
            $root_directory = get_template_directory() . '/src' . $this->block_folder_path;
        }

        $modules_directory = get_template_directory() . $this->module_folder_path;
        if (!is_dir($modules_directory)) {
            $modules_directory = get_template_directory() . '/src' . $this->module_folder_path;
        }

        $root_blocks = $this->find_all_blocks($root_directory);
        $modules_blocks = $this->find_blocks_from_active_modules($modules_directory);

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

            if (file_exists($classPath)) {
                if (!class_exists($className)) {
                    include $classPath;
                }
                //The class need to inherits BlockModel
                $refl = new ReflectionClass($className);
                $block_render = $refl->newInstanceArgs([$blockJsonPath]);
            } else {
                $block_render  = new BlockModel($blockJsonPath);
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
        if (!in_array(WP_ENV, ['development', 'local'])) {
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

    /**
     * Find blocks only from active modules
     *
     * @param string $modules_directory
     * @return array
     */
    private function find_blocks_from_active_modules(string $modules_directory): array
    {
        $temp_blocks = [];

        if (!is_dir($modules_directory)) {
            return $temp_blocks;
        }

        // Get active modules from config
        $active_modules = Config::get('app.modules') ?? [];

        // Extract module class names from the active modules list
        $active_module_names = array_map(function ($module_class) {
            // Extract module name from class name
            // e.g., Theme\Modules\Faq\FaqModule => Faq
            $parts = explode('\\', $module_class);
            return $parts[count($parts) - 2] ?? null;
        }, $active_modules);

        // Filter out null values
        $active_module_names = array_filter($active_module_names);

        // Iterate through each module directory
        $module_dirs = glob($modules_directory . '/*', GLOB_ONLYDIR);

        foreach ($module_dirs as $module_dir) {
            $module_name = basename($module_dir);

            // Only include blocks from active modules
            if (in_array($module_name, $active_module_names)) {
                $blocks_dir = $module_dir . '/blocks';

                if (is_dir($blocks_dir)) {
                    /** @var \SplFileInfo $file */
                    foreach (self::filesIn($blocks_dir) as $file) {
                        $temp_blocks[$file->getPathInfo()->getBasename()] = $file->getPathname();
                    }
                }
            }
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
