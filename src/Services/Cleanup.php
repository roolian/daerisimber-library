<?php

namespace Daeris\DaerisimberLibrary\Services;

class Cleanup
{
    public function __construct()
    {

        if (config('cleanup.disable_feeds', true)) {
            add_action('do_feed', [$this, 'disableFeed'], 1);
            add_action('do_feed_rdf', [$this, 'disableFeed'], 1);
            add_action('do_feed_rss', [$this, 'disableFeed'], 1);
            add_action('do_feed_rss2', [$this, 'disableFeed'], 1);
            add_action('do_feed_atom', [$this, 'disableFeed'], 1);
            add_action('do_feed_rss2_comments', [$this, 'disableFeed'], 1);
            add_action('do_feed_atom_comments', [$this, 'disableFeed'], 1);
            $this->removeActions();
        }

        if (config('cleanup.disable_comments', true)) {
            $this->disableComments();
        }

        
        add_action('wp_print_scripts', [$this, 'disableAutosave']);
    }

    public function removeActions()
    {
        remove_action('wp_head', 'feed_links_extra', 3); //removes comments feed.
        remove_action('wp_head', 'feed_links', 2); //removes feed links.

        remove_action('template_redirect', 'rest_output_link_header', 11, 0);
        remove_action('wp_head', 'rest_output_link_wp_head');

        remove_action('wp_head', 'rsd_link'); //removes EditURI/RSD (Really Simple Discovery) link.
        remove_action('wp_head', 'wlwmanifest_link'); //removes wlwmanifest (Windows Live Writer) link.
        remove_action('wp_head', 'wp_generator'); //removes meta name generator.
        remove_action('wp_head', 'wp_shortlink_wp_head'); //removes shortlink.

        // Désactiver les emojis
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
    }

    // Désactiver les flux non utilisés
    public function disableFeed()
    {
        wp_die(__('No feed available,please visit our <a href="' . get_bloginfo('url') . '">homepage</a>!'));
    }

    public function disableComments()
    {
        
        add_action('widgets_init', [$this, 'removeRecentCommentsStyle']);
        add_action('comments_open', [$this, 'closeComments'], 10, 2);
        add_action('admin_menu', [$this, 'removeCommentStatusMetaBox']);
        add_action('admin_menu', [$this, 'removeLinksTabMenuComments']);
    }

    // Désactiver les flux non utilisés
    public function removeRecentCommentsStyle()
    {
        global $wp_widget_factory;
        remove_action('wp_head', [$wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style']);
    }

    public function disableAutosave()
    {
        wp_deregister_script('autosave');
    }

    public function closeComments($open, $post_id)
    {
        return false;
    }

    public function removeCommentStatusMetaBox()
    {
        remove_meta_box('commentstatusdiv', 'post', 'normal');
    }

    public function removeLinksTabMenuComments()
    {
        remove_menu_page('edit-comments.php');
    }
}
