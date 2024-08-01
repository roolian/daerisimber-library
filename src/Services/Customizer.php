<?php

namespace Daerisimber\Services;


use Daerisimber\Config;

class Customizer
{
    public function __construct()
    {
        add_action('customize_register', [$this, 'add_fields']);
        add_filter('timber/context', [$this, 'add_to_context']);
    }


    public function add_to_context($context)
    {
        $context['logo_url'] = wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full');

        foreach (Config::get("customizer.customize_register") as $key => $value) {
            $context[$key] = get_theme_mod($key);

        }

        return $context;
    }

    public function add_fields($wp_customize)
    {
        foreach (Config::get("customizer.customize_register") as $key => $value) {
            $wp_customize->add_setting($key, [
                'default' => $value['default'],
                'type' => $value['type'],
                'capability' => $value['capability'],
            ]);

            $wp_customize->add_control(
                new $value['control_class'](
                    $wp_customize,
                    $key,
                    array_merge($value["control_options"], [
                        'settings' => $key,
                    ])
                )
            );
        }

    }
}
