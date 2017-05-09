<?php
/*
Plugin Name: MF Social Feed
Plugin URI: https://github.com/frostkom/mf_social_feed
Description: 
Version: 2.0.4
Author: Martin Fors
Author URI: http://frostkom.se
Text Domain: lang_social_feed
Domain Path: /lang

GitHub Plugin URI: frostkom/mf_social_feed
*/

include_once("include/classes.php");
include_once("include/functions.php");

add_action('cron_base', 'cron_social_feed', mt_rand(1, 10));

add_action('init', 'init_social_feed');
add_action('widgets_init', 'widgets_social_feed');

if(is_admin())
{
	register_activation_hook(__FILE__, 'activate_social_feed');

	add_action('admin_init', 'settings_social_feed');

	add_filter('manage_mf_social_feed_posts_columns', 'column_header_social_feed', 5);
	add_action('manage_mf_social_feed_posts_custom_column', 'column_cell_social_feed', 5, 2);
	add_action('rwmb_meta_boxes', 'meta_boxes_social_feed');
}

load_plugin_textdomain('lang_social_feed', false, dirname(plugin_basename(__FILE__))."/lang/");

function activate_social_feed()
{
	require_plugin("meta-box/meta-box.php", "Meta Box");
}