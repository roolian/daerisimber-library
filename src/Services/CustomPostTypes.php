<?php

namespace DaerisimberLibrary\Services;

class CustomPostTypes
{
    public function __construct()
    {
        add_action('init', [$this, 'create_custom_types']);
        add_filter('use_block_editor_for_post_type', [$this, 'remove_block_editor'], 10, 2);
    }

    public function create_custom_types()
    {
        foreach (config('custom_post_types.post_types', []) as $key => $value) {
            if(!empty($value['auto_labels'])) {
                $value['labels'] = $this->generateLabels($value);
            }

            register_post_type($key, $value);
        }

        foreach (config('custom_post_types.taxonomies', []) as $key => $value) {
            if(!empty($value['auto_labels'])) {
                $value['labels'] = $this->generateLabels($value);
            }
            register_taxonomy($key, [], $value);
        }

    }

    public function remove_block_editor($current_status, $post_type)
    {
        foreach (config('custom_post_types.remove_block_editor', []) as $post_type_slug) {
            if ($post_type === $post_type_slug) {
                return false;
            }
        }

        return $current_status;
    }

    public static function generateLabels(array $data)
    {
        $plural = strtolower($data['auto_labels']['plural']);
        $singular = isset($data['auto_labels']['singular']) ? strtolower($data['auto_labels']['singular']) : $plural;
        $gender = isset($data['auto_labels']['gender']) ? strtolower($data['auto_labels']['gender']) : 'm';

        $all = 'Tous';
        $one = 'un';
        $the = 'le';
        $new = 'Nouveau';
        $find = 'trouvé';
        if ($gender === "f") {
            $all = "Toutes";
            $one = "une";
            $the = "la";
            $new = "Nouvelle";
            $find = "trouvée";
        }

        return  [
            'name' => ucfirst($plural),
            'singular_name' => ucfirst($singular),
            'all_items' => $all . ' les ' . $plural,
            'add_new' => 'Ajouter',
            'add_new_item' => 'Ajouter ' . $one . ' ' . $singular,
            'edit' => 'Modifier',
            'edit_item' => 'Modifier ' . $the . ' ' . $singular,
            'new_item' => $new . ' ' . $singular,
            'view_item' => 'Voir ' . $the . ' ' . $singular,
            'search_items' => 'Rechercher ' . $one . ' ' . $singular,
            'not_found' =>  'Non ' . $find,
            'not_found_in_trash' => 'Non ' . $find . ' dans la corbeille',
            'parent_item_colon' => ''
        ];
    }

}
