<?php

namespace Daerisimber;

use Timber\Timber;
use Daerisimber\Config;

class Boot
{

    public static function load()
    {
        self::load_timber();
        self::load_services();
        self::load_modules();
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
