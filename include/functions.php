<?php

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

	$labels = array(
		'name' => _x(__("Posts", 'lang_social_feed'), 'post type general name'),
		'singular_name' => _x(__("Post", 'lang_social_feed'), 'post type singular name'),
		'menu_name' => __("Posts", 'lang_social_feed')
	);

	$args = array(
		'labels' => $labels,
		'public' => true,
		'show_in_menu' => false,
		'exclude_from_search' => true,
		//'supports' => array('title', 'editor', 'excerpt'),
		'hierarchical' => true,
		'has_archive' => false,
		//Works fine if you're a Superadmin but admins can only view posts after this change
		/*'capabilities' => array(
			'create_posts' => (is_multisite() ? 'do_not_allow' : false),
		),*/
	);

	register_post_type('mf_social_feed_post', $args);

	if(!is_admin())
	{
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		mf_enqueue_style('style_social_feed', $plugin_include_url."style.php", $plugin_version);
		mf_enqueue_style('style_bb', plugins_url()."/mf_base/include/backbone/style.css", $plugin_version);
	}
}

function menu_social_feed()
{
	$menu_root = 'mf_social_feed/';
	$menu_start = "edit.php?post_type=mf_social_feed";
	$menu_capability = "edit_pages";

	$menu_title = __("Posts", 'lang_social_feed');
	add_submenu_page($menu_root, $menu_title, $menu_title, $menu_capability, "edit.php?post_type=mf_social_feed_post");
}

function settings_social_feed()
{
	$options_area_orig = $options_area = __FUNCTION__;

	//Generic
	############################
	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array();
	$arr_settings['setting_social_time_limit'] = __("Interval to Fetch New", 'lang_social_feed');
	$arr_settings['setting_social_reload'] = __("Interval to Reload Site", 'lang_social_feed');
	$arr_settings['setting_social_design'] = __("Design", 'lang_social_feed');
	$arr_settings['setting_social_full_width'] = __("Display Full Width on Large Screens", 'lang_social_feed');

	if(class_exists('mf_theme_core'))
	{
		$obj_theme_core = new mf_theme_core();
		$obj_theme_core->get_params();

		$website_max_width = isset($obj_theme_core->options['website_max_width']) ? $obj_theme_core->options['website_max_width'] : 0;
	}

	else
	{
		$website_max_width = 0;
	}

	$arr_settings['setting_social_desktop_columns'] = __("Columns on Desktop", 'lang_social_feed').($website_max_width > 0 ? " (> ".$website_max_width.")" : "");

	if($website_max_width > 0)
	{
		$arr_settings['setting_social_tablet_columns'] = __("Columns on Tablets", 'lang_social_feed').($website_max_width > 0 ? " (< ".$website_max_width.")" : "");
	}

	$arr_settings['setting_social_display_border'] = __("Display Border", 'lang_social_feed');

	show_settings_fields(array('area' => $options_area, 'settings' => $arr_settings));
	############################

	//Facebook //$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = '".$this->meta_prefix."type' WHERE post_type = 'mf_social_feed' AND post_status = 'publish' AND meta_value = '%s' LIMIT 0, 1", 'facebook'));
	############################
	$options_area = $options_area_orig."_facebook";

	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array();
	$arr_settings['setting_facebook_api_id'] = __("App ID", 'lang_social_feed');
	$arr_settings['setting_facebook_api_secret'] = __("Secret", 'lang_social_feed');

	show_settings_fields(array('area' => $options_area, 'settings' => $arr_settings));
	############################

	//Instagram
	############################
	$options_area = $options_area_orig."_instagram";

	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array();
	$arr_settings['setting_instagram_api_token'] = __("Access Token", 'lang_social_feed');

	show_settings_fields(array('area' => $options_area, 'settings' => $arr_settings));
	############################

	//LinkedIn
	############################
	$options_area = $options_area_orig."_linkedin";

	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array();
	$arr_settings['setting_linkedin_api_id'] = __("Client ID", 'lang_social_feed');
	$arr_settings['setting_linkedin_api_secret'] = __("Client Secret", 'lang_social_feed');

	if(get_option('setting_linkedin_api_id') != '' && get_option('setting_linkedin_api_secret') != '')
	{
		$arr_settings['setting_linkedin_redirect_url'] = __("Redirect URL", 'lang_social_feed');
		$arr_settings['setting_linkedin_authorize'] = __("Authorize", 'lang_social_feed');
		$arr_settings['setting_linkedin_email_when_expired'] = __("Email when Expired", 'lang_social_feed');
	}

	show_settings_fields(array('area' => $options_area, 'settings' => $arr_settings));
	############################

	//Twitter
	############################
	$options_area = $options_area_orig."_twitter";

	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array();
	$arr_settings['setting_twitter_api_key'] = __("Key", 'lang_social_feed');
	$arr_settings['setting_twitter_api_secret'] = __("Secret", 'lang_social_feed');
	$arr_settings['setting_twitter_api_token'] = __("Access Token", 'lang_social_feed');
	$arr_settings['setting_twitter_api_token_secret'] = __("Access Token Secret", 'lang_social_feed');

	show_settings_fields(array('area' => $options_area, 'settings' => $arr_settings));
	############################
}

function settings_social_feed_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);

	echo settings_header($setting_key, __("Social Feeds", 'lang_social_feed'));
}

function settings_social_feed_facebook_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);

	echo settings_header($setting_key, __("Social Feeds", 'lang_social_feed')." - ".__("Facebook", 'lang_social_feed'));
}

function settings_social_feed_instagram_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);

	echo settings_header($setting_key, __("Social Feeds", 'lang_social_feed')." - ".__("Instagram", 'lang_social_feed'));
}

function settings_social_feed_linkedin_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);

	echo settings_header($setting_key, __("Social Feeds", 'lang_social_feed')." - ".__("LinkedIn", 'lang_social_feed'));
}

function settings_social_feed_twitter_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);

	echo settings_header($setting_key, __("Social Feeds", 'lang_social_feed')." - ".__("Twitter", 'lang_social_feed'));
}

function get_setting_min()
{
	$setting_base_cron = get_option('setting_base_cron');

	switch($setting_base_cron)
	{
		case 'every_two_minutes':
			return 2;
		break;

		case 'every_ten_minutes':
			return 10;
		break;

		default:
			return 60;
		break;
	}
}

function setting_social_time_limit_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 30);

	$setting_min = get_setting_min();

	$option = max($option, $setting_min);

	echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option, 'xtra' => "min='".$setting_min."' max='1440'", 'suffix' => __("min", 'lang_social_feed')." (".__("Between each API request", 'lang_social_feed').")"));
}

function setting_social_reload_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	if($option > 0)
	{
		$setting_min = get_setting_min() / 2;

		$option = max($option, $setting_min, (get_option('setting_social_time_limit') / 2));
	}

	echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option, 'xtra' => "min='0' max='60'", 'suffix' => __("min", 'lang_social_feed')." (0 = ".__("no reload", 'lang_social_feed').")"));
}

function setting_social_design_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$arr_data = array(
		'' => __("Square", 'lang_social_feed')." (".__("Default", 'lang_social_feed').")",
		'masonry' => __("Masonry", 'lang_social_feed'),
	);

	echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option));
}

function setting_social_full_width_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 'no');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
}

function setting_social_desktop_columns_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 3);

	echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option, 'xtra' => "min='1' max='6'"));
}

function setting_social_tablet_columns_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 2);

	echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option, 'xtra' => "min='1' max='3'"));
}

function setting_social_display_border_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 'yes');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
}

function setting_facebook_api_id_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$description = "<ol>
		<li>".sprintf(__("Go to %s and log in", 'lang_social_feed'), "<a href='//developers.facebook.com'>Facebook</a>")."</li>
		<li>".sprintf(__("Create a new app and copy %s and %s to paste here", 'lang_social_feed'), "App ID", "App Secret")."</li>
	</ol>";

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

	$description = "<ol>
		<li>".sprintf(__("Go to %s and click on %s", 'lang_social_feed'), "<a href='//instagram.com/developer'>Instagram</a>", "Login -> Manage Clients -> Register a new Client")."</li>
		<li>".sprintf(__("Make sure %s field is set to s%", 'lang_social_feed'), "OAuth redirect_uri", "http://localhost")."</li>
		<li>".sprintf(__("Open a new browser tab and go to %s by replacing %s with your %s", 'lang_social_feed'), "<em>https://instagram.com/oauth/authorize/?client_id=[CLIENT_ID_HERE]&redirect_uri=http://localhost&response_type=token</em>", "[CLIENT_ID_HERE]", "Client ID")."</li>
		<li>".__("Paste the returned token here", 'lang_social_feed')."</li>
	</ol>";

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'description' => $description));
}

function setting_linkedin_api_id_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option));
}

function setting_linkedin_api_secret_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	if($option == '')
	{
		$description = "<ol>
			<li>".sprintf(__("Go to %s and log in", 'lang_social_feed'), "<a href='//linkedin.com/developer/apps/'>LinkedIn</a>")."</li>
			<li>".__("Create a new app if you don't have one already and edit the app", 'lang_social_feed')."</li>
			<li>".sprintf(__("Copy %s and %s to these fields", 'lang_social_feed'), "Client ID", "Client Secret")."</li>
			<li>".sprintf(__("Make sure that %s is checked", 'lang_social_feed'), "<em>rw_company_admin</em>")."</li>
		</ol>";
	}

	else
	{
		$description = '';
	}

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'description' => $description));
}

function setting_linkedin_redirect_url_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);

	$obj_social_feed = new mf_social_feed();
	$obj_social_feed->init_linkedin_auth();
	$option = $obj_social_feed->settings_url;

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'xtra' => "readonly onclick='this.select()'", 'description' => sprintf(__("Add this URL to your App's %s", 'lang_social_feed'), "<a href='//www.linkedin.com/developer/apps/'>Authorized Redirect URLs</a>")));
}

function setting_linkedin_authorize_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);

	$obj_social_feed = new mf_social_feed();
	echo $obj_social_feed->check_access_token();
	echo $obj_social_feed->get_access_token_button();
}

function setting_linkedin_email_when_expired_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, 'yes');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
}

function setting_twitter_api_key_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$description = "<ol>
		<li>".sprintf(__("Go to %s and log in", 'lang_social_feed'), "apps.twitter.com")."</li>
		<li>".sprintf(__("Click the tab %s", 'lang_social_feed'), "Keys and Access Tokens")."</li>
		<li>".sprintf(__("Copy %s, %s, %s and %s to paste here", 'lang_social_feed'), "Consumer Key (API Key)", "Consumer Secret (API Secret)", "Access Token", "Access Token Secret")."</li>
	</ol>";

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

function count_shortcode_button_social_feed($count)
{
	if($count == 0)
	{
		/*if(has_feeds())
		{*/
			$count++;
		//}
	}

	return $count;
}

function get_shortcode_output_social_feed($out)
{
	$arr_data = array();
	get_post_children(array('add_choose_here' => true, 'post_type' => 'mf_social_feed'), $arr_data);

	if(count($arr_data) > 1)
	{
		$out .= "<h3>".__("Choose a Social Feed", 'lang_social_feed')."</h3>"
		.show_select(array('data' => $arr_data, 'xtra' => "rel='mf_social_feed amount=3 filter=group border=no text=yes likes=no'"));
	}

	return $out;
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

				case 'linkedin':
					$feed_url = "//linkedin.com/company/".$post_meta_filtered;
				break;

				case 'rss':
					$feed_url = $post_meta;

					$post_meta_parts = parse_url($post_meta);
					$post_meta = $post_meta_parts['host'];
				break;

				case 'twitter':
					$feed_url = "//twitter.com/".$post_meta_filtered;
				break;

				default:
					$feed_url = "#";
				break;
			}

			$fetch_link = "";

			if(IS_SUPER_ADMIN)
			{
				$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE ID = '%d' AND post_type = 'mf_social_feed' AND post_modified < DATE_SUB(NOW(), INTERVAL 1 MINUTE) LIMIT 0, 1", $id));

				if($wpdb->num_rows > 0)
				{
					$intFeedID = check_var('intFeedID');

					if(isset($_REQUEST['btnFeedFetch']) && $intFeedID > 0 && $intFeedID == $id && wp_verify_nonce($_REQUEST['_wpnonce'], 'feed_fetch_'.$id))
					{
						$obj_social_feed->set_id($id);
						$obj_social_feed->fetch_feed();
					}

					else
					{
						$fetch_link = "<a href='".wp_nonce_url(admin_url("edit.php?post_type=mf_social_feed&btnFeedFetch&intFeedID=".$id), "feed_fetch_".$id)."'>".__("Fetch", 'lang_social_feed')."</a> | ";
					}
				}
			}

			$post_modified = $wpdb->get_var($wpdb->prepare("SELECT post_modified FROM ".$wpdb->posts." WHERE ID = '%d' AND post_type = 'mf_social_feed'", $id));

			echo "<a href='".$feed_url."'>".$post_meta."</a>
			<div class='row-actions'>"
				.$fetch_link
				.__("Fetched", 'lang_social_feed').": ".format_date($post_modified)
			."</div>";
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
						echo "0";
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

				echo "<a href='".admin_url("edit.php?post_type=mf_social_feed_post&strFilter=".$id)."'>".$amount."</a>"
				."<div class='row-actions'>"
					.__("Latest", 'lang_social_feed').": ".format_date($post_latest)
				."</div>";
			}
		break;
	}
}

function column_header_social_feed_post($cols)
{
	$plugin_include_url = plugin_dir_url(__FILE__);
	$plugin_version = get_plugin_version(__FILE__);

	mf_enqueue_script('script_social_feed', $plugin_include_url."script_wp.js", array('ajax_url' => admin_url('admin-ajax.php')), $plugin_version);

	unset($cols['title']);
	unset($cols['date']);

	$cols['username'] = __("Username", 'lang_social_feed');
	$cols['text'] = __("Text", 'lang_social_feed');
	$cols['image'] = __("Image", 'lang_social_feed');
	//$cols['post_id'] = __("ID", 'lang_social_feed');
	$cols['date'] = __("Date", 'lang_social_feed');

	return $cols;
}

function column_cell_social_feed_post($col, $id)
{
	global $wpdb;

	$obj_social_feed = new mf_social_feed();

	switch($col)
	{
		case 'username':
			$post_meta = get_post_meta($id, $obj_social_feed->meta_prefix.'name', true);

			echo "@".$post_meta;

			$post_status = get_post_status($id);

			switch($post_status)
			{
				case 'pending':
					echo "<span class='strong nowrap'> - ".__("Ignored", 'lang_social_feed')."</span>";
				break;

				case 'draft':
					echo "<span class='strong nowrap'> - ".__("Hidden", 'lang_social_feed')."</span>";
				break;
			}
		break;

		case 'text':
			$post_content = mf_get_post_content($id);

			echo shorten_text(array('string' => $post_content, 'limit' => 50));
		break;

		case 'image':
			$post_meta = get_post_meta($id, $obj_social_feed->meta_prefix.$col, true);

			if($post_meta != '')
			{
				echo "<img src='".$post_meta."'>";
			}
		break;

		case 'post_id':
			echo get_post_title($id);
		break;
	}
}

function get_social_types_for_select()
{
	$arr_data = array();

	$arr_data['facebook'] = __("Facebook", 'lang_social_feed');
	$arr_data['instagram'] = __("Instagram", 'lang_social_feed');

	$obj_social_feed = new mf_social_feed();

	if($obj_social_feed->check_token_life())
	{
		$arr_data['linkedin'] = __("LinkedIn", 'lang_social_feed');
	}

	$arr_data['rss'] = __("RSS", 'lang_social_feed');
	$arr_data['twitter'] = __("Twitter", 'lang_social_feed');

	return $arr_data;
}

function save_social_feed($post_id, $post, $update)
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

function delete_social_feed($post_id)
{
	global $wpdb, $post_type;

	if($post_type == 'mf_social_feed')
	{
		$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'mf_social_feed_post' AND post_excerpt = '%d'", $post_id));

		foreach($result as $r)
		{
			wp_trash_post($r->ID);
		}
	}
}

function footer_social_feed()
{
	$plugin_base_include_url = plugins_url()."/mf_base/include/";
	$plugin_include_url = plugin_dir_url(__FILE__);
	$plugin_version = get_plugin_version(__FILE__);

	mf_enqueue_script('underscore');
	mf_enqueue_script('backbone');
	mf_enqueue_script('script_base_plugins', $plugin_base_include_url."backbone/bb.plugins.js", $plugin_version);
	mf_enqueue_script('script_social_feed_plugins', $plugin_include_url."backbone/bb.plugins.js", array('read_more' => __("Read More", 'lang_social_feed')), $plugin_version);
	mf_enqueue_script('script_social_feed_models', $plugin_include_url."backbone/bb.models.js", array('plugin_url' => $plugin_include_url), $plugin_version);
	mf_enqueue_script('script_social_feed_views', $plugin_include_url."backbone/bb.views.js", $plugin_version);
	mf_enqueue_script('script_base_init', $plugin_base_include_url."backbone/bb.init.js", $plugin_version);

	$obj_base = new mf_base();
	echo $obj_base->get_templates(array('lost_connection'));

	echo "<script type='text/template' id='template_feed_message'>
		<li>".__("There are no posts to display", 'lang_social_feed')."</li>
	</script>

	<script type='text/template' id='template_feed_all'>
		<li class='active'><a href='#'>".__("All", 'lang_social_feed')."</a></li>
	</script>

	<script type='text/template' id='template_feed'>
		<li><a href='#<%= id %>' id='<%= id %>'><%= name %></a></li>
	</script>

	<script type='text/template' id='template_feed_post'>
		<li class='sf_<%= service %> sf_feed_<%= feed %>'>
			<i class='fa fa-<%= service %>'></i>

			<% if(service == 'rss')
			{ %>
				<span class='name'><%= feed_title %></span>
			<% }

			else if(name != '')
			{ %>
				<span class='name'><%= name %></span>
			<% } %>

			<span class='date'><%= date %></span>
			<a href='<%= link %>' class='content'>

				<% if(image != '')
				{ %>
					<img src='<%= image %>' alt='".sprintf(__("Image for the post %s", 'lang_social_feed'), "<%= name %>")."'>
				<% }

				if(service == 'rss' && title != '')
				{ %>
					<p><%= title %></p>
				<% }

				if(content != '')
				{ %>
					<p><%= content %></p>
				<% }

				if(likes != '' || comments != '')
				{ %>
					<div class='likes'>
						<i class='fa fa-thumbs-up'></i><span><%= likes %></span>
						<i class='fa fa-comment-o'></i><span><%= comments %></span>
					</div>
				<% } %>

			</a>
		</li>
	</script>";
}

function shortcode_social_feed($atts)
{
	extract(shortcode_atts(array(
		'id' => 0,
		'filter' => 'group',
		'amount' => 3,
		'text' => 'yes',
		//'border' => 'yes',
		'likes' => 'no',
		'read_more' => 'yes',
	), $atts));

	$setting_social_reload = get_option('setting_social_reload');

	$out = "<div class='widget social_feed'>
		<div class='section'"
			.($id > 0 ? " data-social_feeds='".$id."'" : "")
			.($filter != '' ? " data-social_filter='".$filter."'" : "")
			.($amount > 0 ? " data-social_amount='".$amount."'" : "")
			.($likes != '' ? " data-social_likes='".$likes."'" : "")
			.($setting_social_reload > 0 ? " data-social_reload='".$setting_social_reload."'" : "")
		.">
			<i class='fa fa-spinner fa-spin fa-3x'></i>
			<ul class='sf_feeds hide'></ul>
			<ul class='sf_posts";

				if($text == 'yes')
				{
					//($border == 'yes' ? " show_border" : '')
					$out .= ($read_more == 'yes' ? " show_read_more" : '');
				}

				else
				{
					$out .= " hide_text";
				}

			$out .= " hide'></ul>
		</div>
	</div>";

	return $out;
}