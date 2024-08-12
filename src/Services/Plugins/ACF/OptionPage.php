<?php

namespace Daerisimber\Services\Plugins\ACF;

use Daerisimber\Utils\Traits\SingletonTrait;

class OptionPage
{
    use SingletonTrait;
    public function init()
    {
        $this->register_options_page();
        add_filter('acf/json/save_file_name', [$this, 'set_json_save_filename'], 10, 3);
        add_filter('timber/context', [$this, 'add_options_to_context'], 1);

    }

    public function register_options_page()
    {
        acf_add_options_page([
            'page_title' => 'Options du site',
            'menu_title' => 'Options du site',
            'menu_slug' => 'theme-general',
            'capability' => 'edit_posts',
            'redirect' => false,
        ]);

    }

    public function add_options_to_context($context)
    {
        $options = [];

        $optionsList = get_fields('option');

        foreach ($optionsList as $key => $value) {
            $options[$key] = $value;
        }

        $context['options'] = $options;

        return $context;
    }

    /**
     * Customize filename where json groupfield are stored
     *
     */
    public function set_json_save_filename(string $filename, array $post, string $load_path): string
    {
        /*
        array:3 [▼
            "param" => "options_page"
            "operator" => "=="
            "value" => "theme-general"
        ]
        */
        $location0 = $post['location'][0][0];

        if ($location0['param'] == 'options_page') {
            $filename = 'options-' . $location0['value'] . '.json';
        }

        return $filename;
    }

}
