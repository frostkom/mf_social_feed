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
	$options_area = __FUNCTION__;

	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array();

	$arr_settings['setting_social_time_limit'] = __("Interval to Fetch New", 'lang_social_feed');
	$arr_settings['setting_social_reload'] = __("Interval to Reload Site", 'lang_social_feed');
	$arr_settings['setting_social_full_width'] = __("Display Full Width on Large Screens", 'lang_social_feed');

	list($options_params, $options) = get_params();
	$website_max_width = isset($options['website_max_width']) ? $options['website_max_width'] : 0;

	$arr_settings['setting_social_desktop_columns'] = __("Columns on Desktop", 'lang_social_feed').($website_max_width > 0 ? " (> ".$website_max_width.")" : "");
	$arr_settings['setting_social_tablet_columns'] = __("Columns on Tablets", 'lang_social_feed').($website_max_width > 0 ? " (< ".$website_max_width.")" : "");

	//$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = '".$this->meta_prefix."type' WHERE post_type = 'mf_social_feed' AND post_status = 'publish' AND meta_value = '%s' LIMIT 0, 1", 'facebook'));

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
	/*if(has_feeds())
	{*/
		$out .= "<h3>".__("Social Feeds", 'lang_social_feed')."</h3>";

		$arr_data = array(
			'' => __("No", 'lang_social_feed'),
			'yes' => __("Yes", 'lang_social_feed')
		);

		$out .= show_select(array('data' => $arr_data, 'xtra' => "rel='mf_social_feed amount=0 filter=group likes=no'"));
	//}

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

			echo "<a href='".$feed_url."' rel='external'>".$post_meta."</a>";

			$fetch_link = "";

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
					}

					else
					{
						$fetch_link = "<a href='".wp_nonce_url(admin_url("edit.php?post_type=mf_social_feed&btnFeedFetch&intFeedID=".$id), "feed_fetch_".$id)."'>".__("Fetch", 'lang_social_feed')."</a> | ";
					}
				}
			}

			$post_modified = $wpdb->get_var($wpdb->prepare("SELECT post_modified FROM ".$wpdb->posts." WHERE ID = '%d' AND post_type = 'mf_social_feed'", $id));

			echo "<div class='row-actions'>"
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
	return array(
		'facebook' => __("Facebook", 'lang_social_feed'),
		'instagram' => __("Instagram", 'lang_social_feed'),
		'rss' => __("RSS", 'lang_social_feed'),
		'twitter' => __("Twitter", 'lang_social_feed'),
	);
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
	$plugin_include_url = plugin_dir_url(__FILE__);
	$plugin_version = get_plugin_version(__FILE__);

	mf_enqueue_script('underscore');
	mf_enqueue_script('backbone');
	mf_enqueue_script('script_base_plugins', plugins_url()."/mf_base/include/backbone/bb.plugins.js", $plugin_version);
	mf_enqueue_script('script_social_feed_plugins', $plugin_include_url."backbone/bb.plugins.js", array('read_more' => __("Read More", 'lang_social_feed')), $plugin_version);
	mf_enqueue_script('script_social_feed_router', $plugin_include_url."backbone/bb.router.js", $plugin_version);
	mf_enqueue_script('script_social_feed_models', $plugin_include_url."backbone/bb.models.js", array('plugin_url' => $plugin_include_url), $plugin_version);
	mf_enqueue_script('script_social_feed_views', $plugin_include_url."backbone/bb.views.js", $plugin_version);

	echo "<div id='overlay_lost_connection'><span>".__("Lost Connection", 'lang_social_feed')."</span></div>

	<script type='text/template' id='template_feed_message'>
		<li>".__("I could not find any posts at the moment. Sorry!", 'lang_social_feed')."</li>
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
			<a href='<%= link %>' class='content' rel='external'>

				<% if(image != '')
				{ %>
					<img src='<%= image %>'>
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
		'amount' => 0,
		'filter' => 'group',
		'likes' => 'no',
	), $atts));

	$setting_social_reload = get_option('setting_social_reload');

	$out = "<div class='widget social_feed'>
		<div class='section'"
			.($amount > 0 ? " data-social_amount='".$amount."'" : "")
			.($filter != '' ? " data-social_filter='".$filter."'" : "")
			.($likes != '' ? " data-social_likes='".$likes."'" : "")
			.($setting_social_reload > 0 ? " data-social_reload='".$setting_social_reload."'" : "")
		.">";

			/*if($setting_social_reload > 0)
			{*/
				$out .= "<i class='fa fa-spinner fa-spin fa-3x'></i>
				<ul class='sf_feeds hide'></ul>
				<ul class='sf_posts show_border show_read_more hide'></ul>";
			/*}

			else
			{
				$obj_social_feed = new mf_social_feed();

				$out .= $obj_social_feed->get_output(array('social_type' => 'shortcode', 'social_amount' => $amount, 'social_filter' => $filter, 'social_likes' => $likes));
			}*/

		$out .= "</div>
	</div>";
}