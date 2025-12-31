<?php

namespace Daerisimber\Modules;

/**
 * Base class for theme modules
 *
 * Provides common functionality for modules including:
 * - ACF JSON path management (load/save)
 * - Automatic registration of module's ACF fields
 * - Custom post type registration
 */
abstract class BaseModule
{
    /**
     * The post type slugs for this module (if applicable)
     * @var array
     */
    protected array $post_types = [];

    /**
     * Register a custom post type for this module
     * Should be called in the module's constructor
     *
     * @param string $post_type The post type slug
     * @param array $labels Labels for the post type
     * @param array $args Additional arguments (merged with defaults)
     */
    protected function registerPostType(string $post_type, array $labels, array $args = []): void
    {
        $defaults = [
            'labels' => $labels,
            'public' => true,
            'has_archive' => false,
            'exclude_from_search' => true,
            'show_ui' => true,
            'query_var' => false,
            'rewrite' => false,
            'supports' => ['title'],
            'menu_icon' => $args['menu_icon'] ?? 'dashicons-admin-post',
        ];

        $post_type_args = array_merge($defaults, $args);

        add_action('init', function () use ($post_type, $post_type_args) {
            register_post_type($post_type, $post_type_args);
        });

        // Store the post_type for ACF path management
        $this->post_types[] = $post_type;

        // Automatically register ACF paths when registering a post type
        $this->registerAcfPaths();
    }

    /**
     * Get the module directory path
     *
     * @return string
     */
    protected function getModuleDirectory(): string
    {
        $reflection = new \ReflectionClass($this);
        return dirname($reflection->getFileName());
    }

    /**
     * Get the ACF JSON directory for this module
     *
     * @return string
     */
    protected function getAcfJsonDirectory(): string
    {
        return $this->getModuleDirectory() . '/acf-json';
    }

    /**
     * Register ACF JSON load and save paths for this module
     * Should be called in the module's constructor
     */
    protected function registerAcfPaths(): void
    {
        add_filter('acf/settings/load_json', [$this, 'acfLoadJson'], 11);
        add_filter('acf/json/save_paths', [$this, 'acfSavePaths'], 10, 2);
    }

    /**
     * Add module's ACF JSON directory to load paths
     *
     * @param array $paths
     * @return array
     */
    public function acfLoadJson(array $paths): array
    {
        $acf_path = $this->getAcfJsonDirectory();

        if (is_dir($acf_path)) {
            $paths[] = $acf_path;
        }

        return $paths;
    }

    /**
     * Set save path for ACF JSON files based on post type or other conditions
     * Override this method in child class if you need custom logic
     *
     * @param array $paths
     * @param array $post
     * @return array
     */
    public function acfSavePaths(array $paths, array $post): array
    {
        // Check if we should save to this module
        if ($this->shouldSaveAcfToModule($post)) {
            $acf_path = $this->getAcfJsonDirectory();

            // Create directory if it doesn't exist
            if (!is_dir($acf_path)) {
                mkdir($acf_path, 0755, true);
            }

            $paths = [$acf_path];
        }

        return $paths;
    }

    /**
     * Determine if ACF fields should be saved to this module
     * Override this method in child class to implement custom logic
     *
     * @param array $post
     * @return bool
     */
    protected function shouldSaveAcfToModule(array $post): bool
    {
        // Get the first location condition
        $main_condition = $post['location'][0][0] ?? false;

        if (!$main_condition) {
            return false;
        }

        // Check if this module has post_types defined
        if (!empty($this->post_types)) {
            // Check if the field group is for one of this module's post types
            if ($main_condition['param'] === 'post_type') {
                // Check if the condition value matches any of our post types
                return in_array($main_condition['value'], $this->post_types)
                    && $this->evaluateCondition(
                        $main_condition['value'],
                        $main_condition['operator'],
                        $main_condition['value']
                    );
            }
        }

        return false;
    }

    /**
     * Evaluate ACF location condition
     *
     * @param mixed $value
     * @param string $operator
     * @param mixed $compare
     * @return bool
     */
    protected function evaluateCondition($value, string $operator, $compare): bool
    {
        switch ($operator) {
            case '==':
                return $value == $compare;
            case '!=':
                return $value != $compare;
            default:
                return false;
        }
    }
}
