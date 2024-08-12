<?php

namespace Daerisimber;

use Daerisimber\Utils\Traits\SingletonTrait;
use PHLAK\Config\Config as ConfigManager;

class Config
{
    use SingletonTrait;

    private $config_data;

    public function init()
    {
        $this->config_data = ConfigManager::fromDirectory(ROOT_THEME_DIR . '/theme/config/');
        $this->config_data->load(ROOT_THEME_DIR . '/vite.json', 'vite');

        $manifest_file = ROOT_THEME_DIR . ltrim($this->config_data->get('vite.dest', '/theme/assets/dist'), '.') . '/.vite/manifest.json';
        if (file_exists($manifest_file)) {
            $this->config_data->set('vite.manifest', (new ConfigManager($manifest_file))->toArray());
        }
    }

    public static function get($key, $default = null)
    {
        return self::instance()->config_data->get($key, $default);
    }

    public static function getRelativePath($key, $default = null)
    {
        return ltrim(self::get($key, $default), './');
    }

    public static function set($key, $value)
    {
        return self::instance()->config_data->set($key, $value);
    }

}
