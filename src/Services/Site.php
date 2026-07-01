<?php

namespace Daerisimber\Services;

use Timber\Timber;
use Timber\Site as TimberSite;
use Daerisimber\Config;
use WP_Block_Type_Registry;

class Site extends TimberSite
{
    public array $menus = [];
    public function __construct()
    {
        $this->menus = Config::get('site.menus', []);

        add_action('init', [$this, 'register_menus']);
        add_filter('allowed_block_types_all', [$this, 'blacklist_block_types'], 10, 2);
        add_filter('should_load_remote_block_patterns', [$this, 'should_load_remote_block_patterns']);
        add_action('after_setup_theme', [$this, 'theme_supports']);
        add_filter('upload_mimes', [$this, 'allow_file_type_upload']);
        add_filter('timber/context', [$this, 'add_to_context']);

        parent::__construct();
    }

    public function should_load_remote_block_patterns()
    {
        return Config::get('site.should_load_remote_block_patterns', true);
    }

    public function register_menus()
    {
        register_nav_menus($this->menus);
    }

    public function add_to_context(array $context)
    {
        $context['site'] = $this;

        foreach ($this->menus as $key => $value) {
            $context[$key] = Timber::get_menu($key);
        }

        // Require block functions files
        foreach (glob(get_template_directory() . '/blocks/*/functions.php') as $file) {
            require_once $file;
        }

        return $context;
    }

    public function theme_supports()
    {
        foreach (Config::get('site.themes_remove_supports', []) as $key) {
            remove_theme_support($key);
        }

        foreach (Config::get('site.themes_supports', []) as $key => $value) {
            if (!$value) {
                continue;
            }
            if (is_bool($value)) {
                add_theme_support($key);
            } else {
                add_theme_support($key, $value);
            }
        }
    }

    public function allow_file_type_upload(array $mime_types)
    {
        foreach (Config::get('site.upload_mimes', []) as $key => $mime_type) {
            $mime_types[$key] = $mime_type;
        }

        return $mime_types;
    }

    public function blacklist_block_types($allowed_blocks, $editor_context)
    {
        // get all the registered blocks
        $blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();
        $blackList = Config::get('site.blocks_blacklist', []);

        foreach ($blackList as $block) {
            if (isset($blocks[$block])) {
                unset($blocks[$block]);
            }
        }

        return array_keys($blocks);
    }
}
