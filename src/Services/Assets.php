<?php

namespace Daerisimber\Services;

use Daerisimber\Config;

class Assets
{
    private string $env;
    public string $hmr_host ;
    public string $dist_uri;
    public string $dist_path;
    public string $file_main_js;
    public string $file_editor_css;
    public ?array $manifest  = null;

    public array $config;

    public function __construct()
    {
        $this->env =                Config::get('vite.environment', 'production');
        $this->hmr_host =           'http://' . Config::get('vite.server.hmr.host') . ':' . Config::get('vite.server.port');
        $this->file_main_js =       Config::getRelativePath('vite.entries.main');
        $this->file_editor_css =    Config::getRelativePath('vite.entries.editor');

        $this->manifest =           Config::get('vite.manifest', null);

        $this->dist_uri = get_template_directory_uri() . '/assets/dist';
        $this->dist_path = get_template_directory() . '/assets/dist';

        add_action('enqueue_block_editor_assets', [$this, 'dequeue_default_assets']);
        add_action('wp_enqueue_scripts', [$this, 'dequeue_default_assets']);

        if (is_admin()) {
            add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
            add_action('enqueue_block_editor_assets', [$this, 'enqueue_block_editor_assets']);
        } else {
            if ($this->env === 'development') {
                add_action('wp_head', [$this,'enqueue_dev_assets']);
            } else {
                add_action('wp_enqueue_scripts', [$this, 'enqueue_prod_assets']);
            }
        }
    }

    public function dequeue_default_assets()
    {
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('wp-block-style');
        wp_dequeue_script('jquery');
    }

    public function enqueue_dev_assets()
    {
        printf('<script type="module" crossorigin src="%s/@vite/client"></script>', $this->hmr_host);
        printf('<script type="module" crossorigin src="%s/%s"></script>', $this->hmr_host, $this->file_main_js);
    }

    public function enqueue_prod_assets()
    {
        wp_enqueue_style('main', $this->get_main_style());
        wp_enqueue_script('main', $this->get_main_script(), [], '', ['strategy'  => 'defer', 'in_footer' => true, ]);
        // wp_enqueue_style('prefix-editor-font', '//fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap');
    }

    public function admin_enqueue_scripts()
    {
        
    }

    public function enqueue_block_editor_assets()
    {
        //wp_deregister_style('wp-reset-editor-styles');
        wp_enqueue_style('editor', $this->get_editor_style());
        wp_enqueue_script('main', $this->get_main_script(), [], '', ['strategy'  => 'defer', 'in_footer' => true, ]);
    }

    public function get_main_script(): string|false
    {
        return $this->get_url_from_manifest($this->file_main_js, 'file');
    }
    public function get_main_style(): string|false
    {
        return $this->get_url_from_manifest($this->file_main_js, 'css');
    }
    public function get_editor_style(): string|false
    {
        return $this->get_url_from_manifest($this->file_editor_css, 'file');

    }

    private function get_url_from_manifest(string $resource, string $data)
    {
        $path = Config::get('vite.manifest', [$resource => [$data => false]])[$resource][$data];

        //If data is css array from manifest
        if(is_array($path)) {
            $path = $path[0];
        }

        return $path ? "$this->dist_uri/$path" : false;
    }

}
