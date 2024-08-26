<?php

namespace Daerisimber\Services;

use Daerisimber\Config;

class User
{
    public function __construct()
    {
        //Here we can customize
        add_action('init', [$this,'set_roles_capabilities']);

    }
    /**
     * Customize role capabilities
     * Doc: https://wordpress.org/documentation/article/roles-and-capabilities/
     *
     * @return void
     */
    public function set_roles_capabilities(): void
    {
        global $wp_roles;
        if (! isset($wp_roles)) {
            $wp_roles = new \WP_Roles();
        }

        $data = Config::get('user.roles');
        foreach ($data as $key => $value) {
            $role = get_role($key);
            if (isset($value['capabilities']['add'])) {
                foreach ($value['capabilities']['add'] as $cap) {
                    if (!$role->has_cap($cap)) {
                        $role->add_cap($cap);
                    }
                }
            }
            if (isset($value['capabilities']['remove'])) {
                foreach ($value['capabilities']['remove'] as $cap) {
                    if ($role->has_cap($cap)) {
                        $role->remove_cap($cap);
                    }
                }
            }

            if(isset($value['name'])) {
                $wp_roles->role_names[$key] = $value['name'];
                $wp_roles->roles[$key]['name'] = $value['name'];
            }
        }

    }

}
