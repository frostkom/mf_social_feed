<?php
/*
Plugin Name: MF Social Feed
Plugin URI: https://github.com/frostkom/mf_social_feed
Description:
Version: 5.12.5
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://martinfors.se
Text Domain: lang_social_feed
Domain Path: /lang

Requires Plugins: meta-box
*/

if(!function_exists('is_plugin_active') || function_exists('is_plugin_active') && is_plugin_active("mf_base/index.php"))
{
	include_once("include/classes.php");

	$obj_social_feed = new mf_social_feed();

	add_action('cron_base', array($obj_social_feed, 'cron_base'), mt_rand(1, 10));

	//add_action('cron_sync', array($obj_social_feed, 'cron_sync'));
	//add_filter('api_sync', array($obj_social_feed, 'api_sync'), 10, 2);

	add_action('enqueue_block_editor_assets', array($obj_social_feed, 'enqueue_block_editor_assets'));
	add_action('init', array($obj_social_feed, 'init'));

	if(is_admin())
	{
		register_uninstall_hook(__FILE__, 'uninstall_social_feed');

		add_action('admin_enqueue_scripts', array($obj_social_feed, 'admin_enqueue_scripts'), 11);

		add_action('admin_init', array($obj_social_feed, 'settings_social_feed'));
		add_filter('pre_update_option_setting_linkedin_api_secret', array($obj_social_feed, 'pre_update_option'), 10, 2);
		add_action('admin_init', array($obj_social_feed, 'admin_init'), 0);
		add_action('admin_menu', array($obj_social_feed, 'admin_menu'));

		add_filter('filter_sites_table_pages', array($obj_social_feed, 'filter_sites_table_pages'));

		add_action('rwmb_meta_boxes', array($obj_social_feed, 'rwmb_meta_boxes'));

		add_action('restrict_manage_posts', array($obj_social_feed, 'restrict_manage_posts'));
		add_action('pre_get_posts', array($obj_social_feed, 'pre_get_posts'));

		add_filter('manage_'.$obj_social_feed->post_type.'_posts_columns', array($obj_social_feed, 'column_header'), 5);
		add_action('manage_'.$obj_social_feed->post_type.'_posts_custom_column', array($obj_social_feed, 'column_cell'), 5, 2);

		add_filter('manage_'.$obj_social_feed->post_type_post.'_posts_columns', array($obj_social_feed, 'column_header'), 5);
		add_action('manage_'.$obj_social_feed->post_type_post.'_posts_custom_column', array($obj_social_feed, 'column_cell'), 5, 2);

		add_filter('post_row_actions', array($obj_social_feed, 'row_actions'), 10, 2);
		add_filter('page_row_actions', array($obj_social_feed, 'row_actions'), 10, 2);

		add_action('save_post', array($obj_social_feed, 'save_post'), 10, 3);
		add_action('wp_trash_post', array($obj_social_feed, 'wp_trash_post'));

		add_filter('filter_last_updated_post_types', array($obj_social_feed, 'filter_last_updated_post_types'), 10, 2);
	}

	else
	{
		add_action('wp_enqueue_scripts', array($obj_social_feed, 'wp_enqueue_scripts'), 11);

		add_action('wp_footer', array($obj_social_feed, 'wp_footer'), 0);
	}

	add_action('widgets_init', array($obj_social_feed, 'widgets_init'));

	add_action('wp_ajax_api_social_feed_action_hide', array($obj_social_feed, 'api_social_feed_action_hide'));
	add_action('wp_ajax_api_social_feed_action_ignore', array($obj_social_feed, 'api_social_feed_action_ignore'));

	add_action('wp_ajax_api_social_feed_posts', array($obj_social_feed, 'api_social_feed_posts'));
	add_action('wp_ajax_nopriv_api_social_feed_posts', array($obj_social_feed, 'api_social_feed_posts'));

	function uninstall_social_feed()
	{
		include_once("include/classes.php");

		$obj_social_feed = new mf_social_feed();

		mf_uninstall_plugin(array(
			'uploads' => $obj_social_feed->post_type,
			'options' => array('setting_social_design', 'setting_social_full_width', 'setting_social_desktop_columns', 'setting_social_tablet_columns', 'setting_social_mobile_columns', 'setting_social_display_border', 'setting_social_debug', 'setting_linkedin_api_id', 'setting_linkedin_api_secret', 'setting_linkedin_redirect_url', 'setting_linkedin_authorize', 'setting_linkedin_email_when_expired', 'option_linkedin_emailed', 'option_linkedin_authkey', 'setting_twitter_api_key', 'setting_twitter_api_secret', 'setting_twitter_api_token', 'setting_twitter_api_token_secret'),
			'meta' => array('meta_social_feed_callback_url', 'meta_social_feed_access_token'),
			'post_types' => array($obj_social_feed->post_type, $obj_social_feed->post_type_post),
		));
	}
}