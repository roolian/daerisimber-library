<?php

namespace Daeris\DaerisimberLibrary\Services;

class User
{
    public function __construct()
    {
        //Here we can customize
        add_action('init', [$this,'set_roles_capabilities']);

        //Clients like to be Administrator
        add_action('init', [$this,'change_roles_name']);
    }
    /**
     * Customize role capabilities
     * Doc: https://wordpress.org/documentation/article/roles-and-capabilities/
     *
     * @return void
     */
    public function set_roles_capabilities(): void
    {
        $editor = get_role('editor');

    }

    public function change_roles_name()
    {
        global $wp_roles;
        if (! isset($wp_roles)) {
            $wp_roles = new \WP_Roles();
        }
        $wp_roles->roles['administrator']['name'] = 'Full admin';
        $wp_roles->role_names['administrator'] = 'Full admin';
        $wp_roles->roles['editor']['name'] = 'Administrator';
        $wp_roles->role_names['editor'] = 'Administrator';
    }
}
