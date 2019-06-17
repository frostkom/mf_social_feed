<?php
/*
Plugin Name: MF Social Feed
Plugin URI: https://github.com/frostkom/mf_social_feed
Description: 
Version: 5.4.8
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://frostkom.se
Text Domain: lang_social_feed
Domain Path: /lang

Depends: Meta Box, MF Base
GitHub Plugin URI: frostkom/mf_social_feed
*/

include_once("include/classes.php");

$obj_social_feed = new mf_social_feed();

add_action('cron_base', 'activate_social_feed', mt_rand(1, 10));
add_action('cron_base', array($obj_social_feed, 'cron_base'), mt_rand(1, 10));

add_action('init', array($obj_social_feed, 'init'));

if(is_admin())
{
	register_activation_hook(__FILE__, 'activate_social_feed');
	register_uninstall_hook(__FILE__, 'uninstall_social_feed');

	add_action('admin_init', array($obj_social_feed, 'settings_social_feed'));
	add_action('admin_init', array($obj_social_feed, 'admin_init'), 0);
	add_action('admin_menu', array($obj_social_feed, 'admin_menu'));

	add_action('rwmb_meta_boxes', array($obj_social_feed, 'rwmb_meta_boxes'));

	add_action('restrict_manage_posts', array($obj_social_feed, 'restrict_manage_posts'));
	add_action('pre_get_posts', array($obj_social_feed, 'pre_get_posts'));

	add_filter('count_shortcode_button', array($obj_social_feed, 'count_shortcode_button'));
	add_filter('get_shortcode_output', array($obj_social_feed, 'get_shortcode_output'));

	add_filter('manage_mf_social_feed_posts_columns', array($obj_social_feed, 'column_header'), 5);
	add_action('manage_mf_social_feed_posts_custom_column', array($obj_social_feed, 'column_cell'), 5, 2);

	add_filter('manage_mf_social_feed_post_posts_columns', array($obj_social_feed, 'column_header_post'), 5);
	add_action('manage_mf_social_feed_post_posts_custom_column', array($obj_social_feed, 'column_cell_post'), 5, 2);

	add_filter('post_row_actions', array($obj_social_feed, 'row_actions'), 10, 2);
	add_filter('page_row_actions', array($obj_social_feed, 'row_actions'), 10, 2);

	add_action('save_post', array($obj_social_feed, 'save_post'), 10, 3);
	add_action('wp_trash_post', array($obj_social_feed, 'wp_trash_post'));
	add_action('delete_post', array($obj_social_feed, 'wp_trash_post')); // Needs to be here until trash is emptied

	add_filter('wp_get_default_privacy_policy_content', array($obj_social_feed, 'add_policy'));
}

else
{
	add_action('wp_head', array($obj_social_feed, 'wp_head'), 0);
	add_action('wp_footer', array($obj_social_feed, 'wp_footer'), 0);
}

add_shortcode('mf_social_feed', array($obj_social_feed, 'shortcode_social_feed'));
add_action('widgets_init', array($obj_social_feed, 'widgets_init'));

add_action('wp_ajax_social_feed_action_hide', array($obj_social_feed, 'action_hide'));
add_action('wp_ajax_social_feed_action_ignore', array($obj_social_feed, 'action_ignore'));

load_plugin_textdomain('lang_social_feed', false, dirname(plugin_basename(__FILE__))."/lang/");

function activate_social_feed()
{
	require_plugin("meta-box/meta-box.php", "Meta Box");

	mf_uninstall_plugin(array(
		'options' => array('setting_linkedin_company_id', 'setting_linkedin_redirect_url', 'setting_linkedin_authorize', 'setting_instagram_api_token'),
	));
}

function uninstall_social_feed()
{
	mf_uninstall_plugin(array(
		'uploads' => 'mf_social_feed',
		'options' => array('setting_social_time_limit', 'setting_social_reload', 'setting_social_design', 'setting_social_full_width', 'setting_social_desktop_columns', 'setting_social_tablet_columns', 'setting_social_display_border', 'setting_facebook_api_id', 'setting_facebook_api_secret', 'setting_instagram_activate_alt_fetch', 'setting_linkedin_api_id', 'setting_linkedin_api_secret', 'setting_linkedin_redirect_url', 'setting_linkedin_authorize', 'setting_linkedin_email_when_expired', 'option_linkedin_emailed', 'option_linkedin_authkey', 'setting_twitter_api_key', 'setting_twitter_api_secret', 'setting_twitter_api_token', 'setting_twitter_api_token_secret'),
		'post_types' => array('mf_social_feed', 'mf_social_feed_post'),
	));
}