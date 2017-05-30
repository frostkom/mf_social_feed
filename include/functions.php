<?php

function init_social_feed()
{
	$labels = array(
		'name' => _x(__("Social Feeds", 'lang_social_feed'), 'post type general name'),
		'singular_name' => _x(__("Social Feed", 'lang_social_feed'), 'post type singular name'),
		'menu_name' => __("Social Feeds", 'lang_social_feed')
	);

	$args = array(
		'labels' => $labels,
		'public' => true,
		'exclude_from_search' => true,
		'menu_position' => 99,
		'menu_icon' => 'dashicons-megaphone',
		'supports' => array('title'),
		'hierarchical' => true,
		'has_archive' => false,
	);

	register_post_type('mf_social_feed', $args);

	mf_enqueue_style('style_social_feed', plugin_dir_url(__FILE__)."style.css", get_plugin_version(__FILE__));
}

function cron_social_feed()
{
	global $wpdb;

	$obj_cron = new mf_cron();
	$obj_social_feed = new mf_social_feed();

	$setting_social_time_limit = get_option_or_default('setting_social_time_limit', 30);

	$result = $wpdb->get_results("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'mf_social_feed' AND post_status = 'publish' AND post_modified < DATE_SUB(NOW(), INTERVAL ".$setting_social_time_limit." MINUTE) ORDER BY RAND()");

	foreach($result as $r)
	{
		if($obj_cron->has_expired(array('margin' => .9)))
		{
			break;
		}

		$obj_social_feed->set_id($r->ID);
		$obj_social_feed->fetch_feed();
	}
}

function settings_social_feed()
{
	$options_area = __FUNCTION__;

	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array();

	$arr_settings['setting_social_time_limit'] = __("Time Limit", 'lang_social_feed');
	$arr_settings['setting_facebook_api_id'] = __("Facebook APP ID", 'lang_social_feed');
	$arr_settings['setting_facebook_api_secret'] = __("Facebook Secret", 'lang_social_feed');
	$arr_settings['setting_instagram_api_token'] = __("Instagram Access Token", 'lang_social_feed');
	$arr_settings['setting_twitter_api_key'] = __("Twitter Key", 'lang_social_feed');
	$arr_settings['setting_twitter_api_secret'] = __("Twitter Secret", 'lang_social_feed');
	$arr_settings['setting_twitter_api_token'] = __("Twitter Access Token", 'lang_social_feed');
	$arr_settings['setting_twitter_api_token_secret'] = __("Twitter Access Token Secret", 'lang_social_feed');

	show_settings_fields(array('area' => $options_area, 'settings' => $arr_settings));
}

function settings_social_feed_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);

	echo settings_header($setting_key, __("Social Feeds", 'lang_social_feed'));
}

function setting_social_time_limit_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 30);

	$description = __("Minutes between each API request", 'lang_social_feed');

	echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option, 'xtra' => "min='10' max='1440'", 'suffix' => $description));
}

function setting_facebook_api_id_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$description = "1. ".sprintf(__("Go to %s and log in", 'lang_social_feed'), "developers.facebook.com")."<br>"
	."2. ".sprintf(__("Create a new app and copy %s and %s to paste here", 'lang_social_feed'), "App ID", "App Secret");

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'description' => $description));
}

function setting_facebook_api_secret_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option));
}

function setting_instagram_api_token_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$description = "1. ".sprintf(__("Go to %s and click on %s", 'lang_social_feed'), "instagram.com/developer", "Login -> Manage Clients -> Register a new Client")."<br>"
	."2. ".sprintf(__("Make sure %s field is set to s%", 'lang_social_feed'), "OAuth redirect_uri", "http://localhost")."<br>"
	."3. ".sprintf(__("Open a new browser tab and go to %s by replacing %s with your %s", 'lang_social_feed'), "https://instagram.com/oauth/authorize/?client_id=[CLIENT_ID_HERE]&redirect_uri=http://localhost&response_type=token", "[CLIENT_ID_HERE]", "Client ID")."<br>" //&scope=public_content
	."4. ".__("Paste the returned token here", 'lang_social_feed');

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'description' => $description));
}

function setting_twitter_api_key_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$description = "1. ".sprintf(__("Go to %s and log in", 'lang_social_feed'), "apps.twitter.com")."<br>"
	."2. ".sprintf(__("Click the tab %s", 'lang_social_feed'), "Keys and Access Tokens")."<br>"
	."3. ".sprintf(__("Copy %s, %s, %s and %s to paste here", 'lang_social_feed'), "Consumer Key (API Key)", "Consumer Secret (API Secret)", "Access Token", "Access Token Secret");

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'description' => $description));
}

function setting_twitter_api_secret_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option));
}

function setting_twitter_api_token_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option));
}

function setting_twitter_api_token_secret_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option));
}

function widgets_social_feed()
{
	register_widget('widget_social_feed');
}

function column_header_social_feed($cols)
{
	unset($cols['date']);

	$cols['type'] = __("Service", 'lang_social_feed');
	$cols['search_for'] = __("Search for", 'lang_social_feed');
	$cols['amount_of_posts'] = __("Amount", 'lang_social_feed');

	return $cols;
}

function column_cell_social_feed($col, $id)
{
	global $wpdb;

	$obj_social_feed = new mf_social_feed();

	$post_meta = get_post_meta($id, $obj_social_feed->meta_prefix.$col, true);

	switch($col)
	{
		case 'type':
			echo "<i class='fa fa-".$post_meta." fa-2x'></i>";
		break;

		case 'search_for':
			$service = get_post_meta($id, $obj_social_feed->meta_prefix.'type', true);

			$post_meta_filtered = $obj_social_feed->filter_search_for($post_meta);

			switch($service)
			{
				case 'facebook':
					$feed_url = "//facebook.com/".$post_meta_filtered;
				break;

				case 'instagram':
					$feed_url = "//instagram.com/".$post_meta_filtered;
				break;

				case 'twitter':
					$feed_url = "//twitter.com/".$post_meta_filtered;
				break;

				default:
					$feed_url = "#";
				break;
			}

			echo "<a href='".$feed_url."' rel='external'>".$post_meta."</a>";

			if(IS_SUPER_ADMIN)
			{
				$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE ID = '%d' AND post_type = 'mf_social_feed' AND post_modified < DATE_SUB(NOW(), INTERVAL 1 MINUTE)", $id));

				if($wpdb->num_rows > 0)
				{
					$intFeedID = check_var('intFeedID');

					if(isset($_REQUEST['btnFeedFetch']) && $intFeedID > 0 && $intFeedID == $id && wp_verify_nonce($_REQUEST['_wpnonce'], 'feed_fetch_'.$id))
					{
						$obj_social_feed->set_id($id);
						$obj_social_feed->fetch_feed();

						echo "<div class='row-actions'>
							".__("Fetched", 'lang_social_feed')."
						</div>";
					}

					else
					{
						$post_modified = $wpdb->get_var($wpdb->prepare("SELECT post_modified FROM ".$wpdb->posts." WHERE ID = '%d' AND post_type = 'mf_social_feed'", $id));

						echo "<div class='row-actions'>
							<a href='".wp_nonce_url(admin_url("edit.php?post_type=mf_social_feed&btnFeedFetch&intFeedID=".$id), "feed_fetch_".$id)."'>".__("Fetch", 'lang_social_feed')."</a> | ".__("Fetched", 'lang_social_feed').": ".format_date($post_modified)."
						</div>";
					}
				}
			}
		break;

		case 'amount_of_posts':
			$amount = $obj_social_feed->get_amount($id);

			$post_error = get_post_meta($id, $obj_social_feed->meta_prefix.'error', true);

			if($post_error != '')
			{
				echo "<i class='fa fa-close red fa-2x'></i>
				<div class='row-actions'>".($post_error != '' ? $post_error : __("I got an error when accessing the feed", 'lang_social_feed'))."</div>";
			}

			else if($amount == 0)
			{
				$setting_social_time_limit = get_option_or_default('setting_social_time_limit', 30);

				$result = $wpdb->get_results($wpdb->prepare("SELECT post_date, post_modified FROM ".$wpdb->posts." WHERE post_type = 'mf_social_feed' AND ID = '%d' LIMIT 0, 1", $id));

				foreach($result as $r)
				{
					$post_date = $r->post_date;
					$post_modified = $r->post_modified;

					if($post_modified > $post_date || $post_modified < date("Y-m-d H:i:s", strtotime("-".$setting_social_time_limit." minute")))
					{
						echo "<i class='fa fa-close red fa-2x'></i>
						<div class='row-actions'>".__("The feed does not seam to work. This might be due to that the feed you are trying to access is not public.", 'lang_social_feed')."</div>";
					}

					else
					{
						echo "<i class='fa fa-spinner fa-spin fa-2x'></i>
						<div class='row-actions'>".__("I am waiting to get access to the feed", 'lang_social_feed')."</div>";
					}
				}
			}

			else if($amount > 0)
			{
				$post_latest = $wpdb->get_var($wpdb->prepare("SELECT post_date FROM ".$wpdb->posts." WHERE post_type = 'mf_social_feed_post' AND post_excerpt = '%d' ORDER BY post_date DESC LIMIT 0, 1", $id));

				echo $amount
				."<div class='row-actions'>"
					.__("Latest", 'lang_social_feed').": ".format_date($post_latest)
				."</div>";
			}
		break;
	}
}

function get_social_types_for_select()
{
	return array(
		'facebook' => "Facebook",
		'instagram' => "Instagram",
		'twitter' => "Twitter",
	);
}

function get_feed_info()
{
	$meta_prefix = "mf_social_feed_";

	$out = "<ul id='".$meta_prefix."info_facebook'>
		<li><strong>".__("Facebook", 'lang_social_feed')."</strong>: ".__("Posts can only be fetched from Facebook Pages, not personal Profiles", 'lang_social_feed')."</li>
		<li><strong>".__("Instagram", 'lang_social_feed')."</strong>: ".__("Posts can either be fetched from @users or #hashtags", 'lang_social_feed')."</li>
		<li><strong>".__("Twitter", 'lang_social_feed')."</strong>: ".__("Posts can either be fetched from @users or #hashtags", 'lang_social_feed')."</li>
	</ul>";

	return $out;
}


function meta_boxes_social_feed($meta_boxes)
{
	global $wpdb;

	$meta_prefix = "mf_social_feed_";

	#####################
	$default_type = "";

	$post_id = $wpdb->get_var("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'mf_social_feed' ORDER BY post_modified DESC LIMIT 0, 1");

	if($post_id > 0)
	{
		$default_type = get_post_meta($post_id, $meta_prefix.'type', true);
	}
	#####################

	$meta_boxes[] = array(
		'id' => $meta_prefix.'settings',
		'title' => __("Settings", 'lang_social_feed'),
		'post_types' => array('mf_social_feed'),
		//'context' => 'side',
		'priority' => 'low',
		'fields' => array(
			array(
				'name' => __("Service", 'lang_social_feed'),
				'id' => $meta_prefix.'type',
				'type' => 'select',
				'options' => get_social_types_for_select(),
				'std' => $default_type,
				/*'attributes' => array(
					'condition_type' => 'show_if',
					'condition_value' => 'facebook',
					'condition_field' => $meta_prefix.'info_facebook',
				),*/
			),
			array(
				'name' => __("Search for", 'lang_social_feed'),
				'id' => $meta_prefix.'search_for',
				'type' => 'text',
			),
			array(
				'id' => $meta_prefix.'info',
				'type' => 'custom_html',
				'callback' => 'get_feed_info',
			),
		)
	);

	return $meta_boxes;
}

function save_post_social_feed($post_id, $post, $update)
{
	global $wpdb;

	if($post->post_type == 'mf_social_feed' && $post->post_status == 'publish') // && $update == false
	{
		$obj_social_feed = new mf_social_feed($post_id);

		if(!($obj_social_feed->get_amount() > 0))
		{
			$obj_social_feed->fetch_feed();
		}
	}
}

function shortcode_social_feed($atts)
{
	extract(shortcode_atts(array(
		'amount' => 0,
		'filter' => 'group',
		'likes' => 'no',
	), $atts));

	$obj_social_feed = new mf_social_feed();

	return $obj_social_feed->get_output(array('social_type' => 'shortcode', 'social_amount' => $amount, 'social_filter' => $filter, 'social_likes' => $likes));
}