<?php

namespace Daerisimber;

use Timber\Timber;

class Boot
{
    public static function load()
    {
        if (!class_exists('ACF')) {
            add_action('admin_notices', [self::class, 'no_acf_error_message']);
            return;
        }
        
        self::load_timber();
        self::load_services();
        self::load_modules();
    }

    public static function no_acf_error_message()
    {
        echo '<div class="notice notice-error">
		<p>This theme require ACF Pro installed to work.</p>
	    </div>';
    }
    
    public static function load_timber()
    {
        Timber::init();
        Timber::$dirname    = ['views', 'blocks', 'modules'];
    }
    public static function load_services()
    {
        foreach (Config::get('app.services', []) as $key => $class) {
            new $class();
        }
    }

    public static function load_modules()
    {
        foreach (Config::get('app.modules', []) as $key => $class) {
            new $class();
        }
    }
}
