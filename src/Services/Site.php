<?php

namespace Daeris\DaerisimberLibrary\Services;

use Timber\Timber;
use Timber\Site as TimberSite;

class Site extends TimberSite
{
    public Array $menus = [];
    public function __construct()
    {
        $this->menus = config('site.menus', []);

        add_action('init', [$this, 'register_menus']);
        add_action('after_setup_theme', [$this, 'theme_supports']);
        add_filter('upload_mimes', [$this, 'allow_file_type_upload']);
        add_filter('timber/context', [$this, 'add_to_context']);

        parent::__construct();
    }

    public function register_menus()
    {
        register_nav_menus($this->menus);
    }

    public function add_to_context($context)
    {
        $context['site'] = $this;

        foreach ($this->menus as $key => $value) {
            $context[$key] = Timber::get_menu($key);
        }

        // Require block functions files
        foreach (glob(__DIR__ . '/blocks/*/functions.php') as $file) {
            require_once $file;
        }

        return $context;
    }

    public function theme_supports()
    {
        foreach (config('site.themes_supports', []) as $key => $value) {
            if(!$value) {
                continue;
            }
            if (is_bool($value)) {
                add_theme_support($key);
            } else {
                add_theme_support($key, $value);
            }
        }

    }

    public function allow_file_type_upload($mimes)
    {
        foreach (config('site.upload_mimes', []) as $key => $value) {
            return $mimes;
        }

    }
}
