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
		'show_in_nav_menus' => false,
		'exclude_from_search' => true,
		'capability_type' => 'page',
		'menu_position' => 21,
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
		'show_in_nav_menus' => false,
		'exclude_from_search' => true,
		'hierarchical' => true,
		'has_archive' => false,
		//Works fine if you're a Superadmin but admins can only view posts after this change
		/*'capabilities' => array(
			'create_posts' => (is_multisite() ? 'do_not_allow' : false),
		),*/
	);

	register_post_type('mf_social_feed_post', $args);
}

function menu_social_feed()
{
	$menu_root = 'mf_social_feed/';
	$menu_start = "edit.php?post_type=mf_social_feed";
	$menu_capability = override_capability(array('page' => $menu_start, 'default' => 'edit_pages'));

	$menu_title = __("Posts", 'lang_social_feed');
	add_submenu_page($menu_root, $menu_title, $menu_title, $menu_capability, "edit.php?post_type=mf_social_feed_post");
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
					if(substr($post_meta_filtered, 0, 1) == "#")
					{
						$feed_url = "//instagram.com/explore/tags/".substr($post_meta_filtered, 1);
					}

					else
					{
						$feed_url = "//instagram.com/".$post_meta_filtered;
					}
				break;

				case 'linkedin':
					$feed_url = "//linkedin.com/company/".$post_meta_filtered;
				break;

				case 'rss':
					$feed_url = $post_meta;

					$post_meta_parts = parse_url($post_meta);
					$post_meta = isset($post_meta_parts['host']) ? $post_meta_parts['host'] : "(".__("unknown", 'lang_social_feed').")";
				break;

				case 'twitter':
					if(substr($post_meta_filtered, 0, 1) == "#")
					{
						$feed_url = "//twitter.com/search?f=tweets&src=typd&q=%23".substr($post_meta_filtered, 1);
					}

					else
					{
						$feed_url = "//twitter.com/".$post_meta_filtered;
					}
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

					if(isset($_REQUEST['btnFeedFetch']) && $intFeedID > 0 && $intFeedID == $id && wp_verify_nonce($_REQUEST['_wpnonce_feed_fetch'], 'feed_fetch_'.$id))
					{
						$obj_social_feed->set_id($id);
						$obj_social_feed->fetch_feed();
					}

					else
					{
						$fetch_link = "<a href='".wp_nonce_url(admin_url("edit.php?post_type=mf_social_feed&btnFeedFetch&intFeedID=".$id), 'feed_fetch_'.$id, '_wpnonce_feed_fetch')."'>".__("Fetch", 'lang_social_feed')."</a> | ";
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

				echo "<a href='".admin_url("edit.php?post_type=mf_social_feed_post&strFilterSocialFeed=".$id)."'>".$amount."</a>"
				."<div class='row-actions'>"
					.__("Latest", 'lang_social_feed').": ".format_date($post_latest)
				."</div>";
			}
		break;
	}
}

function column_header_social_feed_post($cols)
{
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
	$setting_social_debug = get_option('setting_social_debug');

	$obj_base = new mf_base();
	echo $obj_base->get_templates(array('lost_connection'));

	if($setting_social_debug == 'yes')
	{
		echo "<div class='social_debug'></div>";
	}

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
			<div class='meta'>
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
			</div>
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
					<div class='text'><%= content %></div>
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
		'likes' => 'no',
		'read_more' => 'yes',
	), $atts));

	$setting_social_reload = get_option('setting_social_reload');

	$out = "<div class='widget social_feed'>
		<div id='feed_".$id."' class='section'"
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