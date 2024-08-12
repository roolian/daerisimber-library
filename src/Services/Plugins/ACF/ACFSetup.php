<?php

namespace Daerisimber\Services\Plugins\ACF;

use Daerisimber\Services\Helper;

class ACFSetup
{
    public function __construct()
    {
        BlockFinder::load();
        OptionPage::load();

        //If we are in production, we doen't need to store fields in DB, so we deactivate sync
        if (!Helper::is_dev_env()) {
            add_filter('acf/prepare_field_group_for_import', [$this,'prepare_field_group_for_import'], 20, 1);
        }

        //When we edit a groupfield in Admin, a json file is created
        add_filter('acf/prepare_field_group_for_export', [$this,'prepare_field_group_for_export'], 20, 1);
    }

    

    //Move modified property in top of files
    public function prepare_field_group_for_export($group)
    {
        $mod = ['modified' => $group['modified']];
        return $mod + $group;
    }

    //
    public function prepare_field_group_for_import($group)
    {
        $group['private'] = true;
        return $group;
    }




}
