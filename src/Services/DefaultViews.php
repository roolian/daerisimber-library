<?php

namespace Daerisimber\Services;

class DefaultViews
{
    public function __construct()
    {
        add_filter('timber/locations', function ($paths) {
            $dir_vendor = dirname(__DIR__,2);
            $paths[] = [$dir_vendor . '/views'];

            return $paths;
        });
    }
}
