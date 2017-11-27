<?php

class mf_social_feed
{
	function __construct($id = 0)
	{
		$this->id = $id > 0 ? $id : 0;
		$this->type = $this->search = "";

		$this->meta_prefix = "mf_social_feed_";
	}

	// Admin
	#########################
	function meta_feed_info()
	{
		$out = "<ul id='".$this->meta_prefix."info_facebook'>
			<li><strong>".__("Facebook", 'lang_social_feed')."</strong>: ".__("Posts can only be fetched from Facebook Pages, not personal Profiles", 'lang_social_feed')."</li>
			<li><strong>".__("Instagram", 'lang_social_feed')."</strong>: ".__("Posts can either be fetched from @users or #hashtags", 'lang_social_feed')."</li>
			<li><strong>".__("RSS", 'lang_social_feed')."</strong>: ".__("Posts can only be fetched by entering the full URL to the feed", 'lang_social_feed')."</li>
			<li><strong>".__("Twitter", 'lang_social_feed')."</strong>: ".__("Posts can either be fetched from @users or #hashtags", 'lang_social_feed')."</li>
		</ul>";

		return $out;
	}

	function meta_post_info()
	{
		global $post;

		$post_id = $post->ID;
		$post_date = $post->post_date;

		$post_service = get_post_meta($post_id, $this->meta_prefix.'service', true);
		$post_feed = get_post_meta($post_id, $this->meta_prefix.'feed_id', true);
		$post_username = get_post_meta($post_id, $this->meta_prefix.'name', true);
		$post_image = get_post_meta($post_id, $this->meta_prefix.'image', true);
		$post_link = get_post_meta($post_id, $this->meta_prefix.'link', true);

		$out = "<ul id='".$this->meta_prefix."info'>"
			."<li><i class='fa fa-".$post_service."'></i> ".get_post_title($post_feed)."</li>"
			."<li><a href='".$post_link."' rel='external'>@".$post_username."</a></li>"
			.($post_image != '' ? "<li><img src='".$post_image."'></li>" : "")
			."<li>".format_date($post_date)."</li>"
		."</ul>";

		return $out;
	}

	function meta_boxes($meta_boxes)
	{
		global $wpdb;

		#####################
		$arr_data_social_types = get_social_types_for_select();

		$default_type = "";

		$post_id = $wpdb->get_var("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'mf_social_feed' ORDER BY post_modified DESC LIMIT 0, 1");

		if($post_id > 0)
		{
			$default_type = get_post_meta($post_id, $this->meta_prefix.'type', true);
		}
		#####################

		$meta_boxes[] = array(
			'id' => $this->meta_prefix.'settings',
			'title' => __("Settings", 'lang_social_feed'),
			'post_types' => array('mf_social_feed'),
			//'context' => 'side',
			'priority' => 'low',
			'fields' => array(
				array(
					'name' => __("Service", 'lang_social_feed'),
					'id' => $this->meta_prefix.'type',
					'type' => 'select',
					'options' => $arr_data_social_types,
					'std' => $default_type,
					/*'attributes' => array(
						'condition_type' => 'show_if',
						'condition_value' => 'facebook',
						'condition_field' => $this->meta_prefix.'info_facebook',
					),*/
				),
				array(
					'name' => __("Search for", 'lang_social_feed'),
					'id' => $this->meta_prefix.'search_for',
					'type' => 'text',
				),
				array(
					'id' => $this->meta_prefix.'info',
					'type' => 'custom_html',
					'callback' => array($this, 'meta_feed_info'),
				),
			)
		);

		$meta_boxes[] = array(
			'id' => $this->meta_prefix.'info',
			'title' => __("Information", 'lang_social_feed'),
			'post_types' => array('mf_social_feed_post'),
			'context' => 'side',
			'priority' => 'high',
			'fields' => array(
				array(
					'id' => $this->meta_prefix.'info',
					'type' => 'custom_html',
					'callback' => array($this, 'meta_post_info'),
				),
			)
		);

		return $meta_boxes;
	}

	function post_filter_select()
	{
		global $post_type, $wpdb;

		if($post_type == 'mf_social_feed_post')
		{
			$strFilter = check_var('strFilter');

			$arr_data = array();
			get_post_children(array('post_type' => 'mf_social_feed', 'post_status' => '', 'add_choose_here' => true), $arr_data);

			if(count($arr_data) > 1)
			{
				echo show_select(array('data' => $arr_data, 'name' => "strFilter", 'value' => $strFilter));
			}
		}
	}

	function post_filter_query($wp_query)
	{
		global $post_type, $pagenow;

		if($pagenow == 'edit.php' && $post_type == 'mf_social_feed_post')
		{
			$strFilter = check_var('strFilter');

			if($strFilter != '')
			{
				$wp_query->query_vars['meta_query'] = array(
					array(
						'key' => $this->meta_prefix.'feed_id',
						'value' => $strFilter,
						'compare' => '=',
					),
				);
			}
		}
	}

	function row_actions($actions, $post)
	{
		if($post->post_type == 'mf_social_feed_post')
		{
			unset($actions['inline hide-if-no-js']);
			unset($actions['view']);

			$post_id = $post->ID;

			$feed_id = get_post_meta($post_id, $this->meta_prefix.'feed_id', true);
			$post_username = get_post_meta($post_id, $this->meta_prefix.'name', true);

			$post_username = "@".$post_username;
			$feed_name = get_post_meta($feed_id, $this->meta_prefix.'search_for', true);

			if($post->post_status == 'publish')
			{
				unset($actions['trash']);

				$actions['action_hide'] = "<a href='#id_".$post_id."' class='social_feed_post_action action_hide' confirm_text='".__("Are you sure?", 'lang_social_feed')."'>".__("Hide", 'lang_social_feed')."</a>"; //draft

				if($post_username != $feed_name)
				{
					$actions['action_ignore'] = "<a href='#id_".$post_id."' class='social_feed_post_action action_ignore' confirm_text='".sprintf(__("Are you sure? This will make all future posts by %s to be ignored aswell!"), $post_username)."'>".__("Ignore Future Posts", 'lang_social_feed')."</a>"; //pending
				}
			}
		}

		return $actions;
	}

	function action_hide()
	{
		global $wpdb, $done_text, $error_text;

		$action_id = check_var('action_id', 'int');

		$result = array();

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_status = 'draft' WHERE ID = '%d' AND post_type = 'mf_social_feed_post'", $action_id));
		
		if($wpdb->rows_affected > 0)
		{
			$done_text = __("I have hidden the post for you now", 'lang_social_feed');
		}

		else
		{
			$error_text = __("I could not hide the post for you. An admin has been notified about this issue", 'lang_social_feed');

			do_log($error_text." (".$wpdb->last_query.")");
		}

		$out = get_notification();

		if($done_text != '')
		{
			$result['success'] = true;
			$result['message'] = $out;
		}

		else
		{
			$result['error'] = $out;
		}

		echo json_encode($result);
		die();
	}

	function action_ignore()
	{
		global $wpdb, $done_text, $error_text;

		$action_id = check_var('action_id', 'int');

		$result = array();

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_status = 'pending' WHERE ID = '%d' AND post_type = 'mf_social_feed_post'", $action_id));
		
		if($wpdb->rows_affected > 0)
		{
			$done_text = __("I have ignored the post for you now. This means that all future posts by this user will be ignored aswell", 'lang_social_feed');
		}

		else
		{
			$error_text = __("I could not ignore the post for you. An admin has been notified about this issue", 'lang_social_feed');

			do_log($error_text." (".$wpdb->last_query.")");
		}

		$out = get_notification();

		if($done_text != '')
		{
			$result['success'] = true;
			$result['message'] = $out;
		}

		else
		{
			$result['error'] = $out;
		}

		echo json_encode($result);
		die();
	}
	#########################

	// Fetch
	#########################
	function set_id($id)
	{
		$this->id = $id;
		$this->type = $this->search = "";
	}

	function get_type()
	{
		if($this->type == '')
		{
			$this->type = get_post_meta($this->id, $this->meta_prefix.'type', true);

			$this->get_api_credentials();
		}

		$this->get_search();

		return $this->type;
	}

	function get_search()
	{
		if($this->search == '')
		{
			$this->search = get_post_meta($this->id, $this->meta_prefix.'search_for', true);
		}

		return $this->search;
	}

	function get_api_credentials()
	{
		switch($this->type) //$this->get_type()
		{
			case 'facebook':
				$this->facebook_api_id = get_option_or_default('setting_facebook_api_id', '218056055327780');
				$this->facebook_api_secret = get_option_or_default('setting_facebook_api_secret', 'b00ccbc6513724fafca0ff41685d735b');
			break;

			case 'instagram':
				$this->instagram_api_token = get_option_or_default('setting_instagram_api_token', '1080170513.3a81a9f.43201f5429d443b4ae063cd77dbea968');
			break;

			case 'twitter':
				$this->twitter_api_key = get_option_or_default('setting_twitter_api_key', 'Vj7sGbggGlC7gWApxOA33Q');
				$this->twitter_api_secret = get_option_or_default('setting_twitter_api_secret', 'CfqozaoWeZSaZiBtVIaSbdoXAO5Mjqo1P5dzevFee9o');
				$this->twitter_api_token = get_option_or_default('setting_twitter_api_token', '102995511-L5WzrPl7UsWZ0W4UVFz5lLprENSk62aTYtvdyWhI');
				$this->twitter_api_token_secret = get_option_or_default('setting_twitter_api_token_secret', 'PsdHdppzVztxFd5GpNgfRSqkyw2e7Cbb6HhjqirCew');
			break;
		}
	}

	function get_amount($id = 0)
	{
		global $wpdb;

		if($id > 0)
		{
			$this->id = $id;
		}

		return $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".$wpdb->posts." WHERE post_type = 'mf_social_feed_post' AND post_status = 'publish' AND post_excerpt = '%d'", $this->id));
	}

	function fetch_feed()
	{
		$type = $this->get_type();

		if($this->search != '')
		{
			$this->arr_posts = array();

			switch($type)
			{
				case 'facebook':
					$this->fetch_facebook();
				break;

				case 'instagram':
					$this->fetch_instagram();
				break;

				case 'rss':
					$this->fetch_rss();
				break;

				case 'twitter':
					$this->fetch_twitter();
				break;
			}

			$this->set_date_modified();

			$this->insert_posts();
		}
	}

	function filter_search_for($value)
	{
		if(strpos($value, "/"))
		{
			$arr_search = explode("/", $value);

			$value = $arr_search[count($arr_search) - 1];
		}

		if(substr($value, 0, 1) == "@")
		{
			$value = substr($value, 1);
		}

		return $value;
	}

	function fetch_facebook()
	{
		$this->search = $this->filter_search_for($this->search);

		$fb_access_token = $this->facebook_api_id."|".$this->facebook_api_secret;
		$fb_feed_url = "https://graph.facebook.com/".$this->search."/feed?fields=id,from,message,story,full_picture,created_time&access_token=".$fb_access_token; //&limit=10

		$content = get_url_content($fb_feed_url);
		$json = json_decode($content, true);

		if(isset($json['data']))
		{
			foreach($json['data'] as $post)
			{
				/*array (
					'id' => '[id]_[id]',
					'from' => array (
						'name' => '[name]',
						'id' => '[id]'
					),
					'message' => '[text]',
					'full_picture' => '[url]',
					'created_time' => '2017-06-06T18:58:29+0000'
				)*/

				$post_id = $post['id'];
				$arr_post_id = explode("_", $post_id);

				$post_author = $post['from']['id'];

				$post_content = "";

				if(isset($post['message']))
				{
					$post_content = $post['message'];
				}

				else if(isset($post['story']))
				{
					$post_content = $post['story'];
				}

				$this->arr_posts[] = array(
					'type' => $this->type,
					'id' => $post_id,
					'name' => $this->search,
					'text' => $post_content,
					'link' => "//facebook.com/".$arr_post_id[0]."/posts/".$arr_post_id[1],
					'image' => isset($post['full_picture']) && $post['full_picture'] != '' ? $post['full_picture'] : "",
					'created' => date("Y-m-d H:i:s", strtotime($post['created_time'])),
					'is_owner' => ($arr_post_id[0] == $post_author),
				);
			}

			delete_post_meta($this->id, $this->meta_prefix.'error');
		}

		else
		{
			update_post_meta($this->id, $this->meta_prefix.'error', $json['error']['message']);
		}
	}

	function fetch_instagram()
	{
		$filter = "";

		if(substr($this->search, 0, 1) == "#")
		{
			$filter = "tags/".substr($this->search, 1)."/media/recent";
		}

		else if(substr($this->search, 0, 1) == "@")
		{
			$url = "https://api.instagram.com/v1/users/search?q=".substr($this->search, 1)."&access_token=".$this->instagram_api_token;

			$result = wp_remote_retrieve_body(wp_remote_get($url));
			$result = json_decode($result);

			if(isset($result->data))
			{
				foreach($result->data as $user)
				{
					$filter = "users/".$user->id."/media/recent";

					break;
				}

				delete_post_meta($this->id, $this->meta_prefix.'error');
			}

			else
			{
				update_post_meta($this->id, $this->meta_prefix.'error', sprintf(__("The JSON I got back was not correct. Have a look at %s", 'lang_social_feed'), $url));
			}
		}

		else
		{
			$filter = "users/self/feed";
		}

		if($filter != '')
		{
			$url = "https://api.instagram.com/v1/".$filter."?access_token=".$this->instagram_api_token; //."&count=".$instagram_amount

			$result = wp_remote_retrieve_body(wp_remote_get($url));
			$result = json_decode($result);

			if(isset($result->data))
			{
				foreach($result->data as $post)
				{
					/*array('location' => NULL,
						'caption' => stdClass::__set_state(array('text' => 'Text #hashtag', 'created_time' => '[id]',
							'from' => stdClass::__set_state(array('profile_picture' => 'https://instagram.com/x.jpg', 'username' => 'username', 'id' => '[id]', 'full_name' => 'Name'))
							, 'id' => '[id]'
						)),
						'id' => '[id]_[id]', 'tags' => array(0 => 'hashtags'), 'user_has_liked' => false,
						'users_in_photo' => array(0 => stdClass::__set_state(array(
							'user' => stdClass::__set_state(array(
								'profile_picture' => 'https://instagram.com/x.jpg', 'username' => 'username', 'id' => '[id]', 'full_name' => 'Name'
							)),
							'position' => stdClass::__set_state(array('y' => 0.435, 'x' => 0.237))
						))),
						'created_time' => '1487162314', 'filter' => 'Valencia',
						'user' => stdClass::__set_state(array('profile_picture' => 'https://instagram.com/x.jpg', 'username' => 'username', 'id' => '[id]', 'full_name' => 'Name')),
						'type' => 'image', 'link' => 'https://www.instagram.com/p/[id]/', 'attribution' => NULL,
						'images' => stdClass::__set_state(array(
							'standard_resolution' => stdClass::__set_state(array('height' => 640, 'width' => 640, 'url' => 'https://instagram.com/x.jpg')),
							'thumbnail' => stdClass::__set_state(array('height' => 150, 'width' => 150, 'url' => 'https://instagram.com/x.jpg')),
							'low_resolution' => stdClass::__set_state(array('height' => 320, 'width' => 320, 'url' => 'https://instagram.com/x.jpg'))
						)),
						'likes' => stdClass::__set_state(array('count' => 51)),
						'comments' => stdClass::__set_state(array('count' => 3))
					)*/

					$this->arr_posts[] = array(
						'type' => $this->type,
						'id' => $post->id,
						'name' => isset($post->caption->from->username) ? $post->caption->from->username : $this->search,
						'text' => isset($post->caption->text) ? $post->caption->text : "",
						'link' => $post->link,
						'image' => $post->images->standard_resolution->url,
						'created' => date("Y-m-d H:i:s", $post->created_time),
						'likes' => $post->likes->count,
						'comments' => $post->comments->count,
					);
				}

				delete_post_meta($this->id, $this->meta_prefix.'error');
			}

			else
			{
				update_post_meta($this->id, $this->meta_prefix.'error', sprintf(__("The JSON I got back was not correct. Have a look at %s", 'lang_social_feed'), $url));
			}
		}
	}

	function fetch_twitter()
	{
		include_once("twitter/twitter.class.php");

		$twitter = new Twitter($this->twitter_api_key, $this->twitter_api_secret, $this->twitter_api_token, $this->twitter_api_token_secret);

		if(substr($this->search, 0, 1) == "#")
		{
			try
			{
				$results = $twitter->search($this->search);
			}
			
			catch(TwitterException $e)
			{
				update_post_meta($this->id, $this->meta_prefix.'error', __("Twitter Error", 'lang_social_feed').": ".var_export($e->getMessage(), true));
			}
		}

		else if(substr($this->search, 0, 1) == "@")
		{
			$this->search = substr($this->search, 1);

			try
			{
				$results = $twitter->search('from:'.$this->search);
			}
			
			catch(TwitterException $e)
			{
				update_post_meta($this->id, $this->meta_prefix.'error', __("Twitter Error", 'lang_social_feed').": ".var_export($e->getMessage(), true));
			}
		}

		else
		{
			try
			{
				$results = $twitter->load(Twitter::ME);
			}
			
			catch(TwitterException $e)
			{
				update_post_meta($this->id, $this->meta_prefix.'error', __("Twitter Error", 'lang_social_feed').": ".var_export($e->getMessage(), true));
			}
		}

		if(is_array($results))
		{
			foreach($results as $key => $post)
			{
				/*array('created_at' => 'Fri Feb 17 08:09:54 +0000 2017', 'id' => '[id]', 'id_str' => '[id]', 'text' => 'Text #hashtag', 'truncated' => false,
				'entities' => stdClass::__set_state(array(
					'hashtags' => array(0 => stdClass::__set_state(array('text' => 'svpol', 'indices' => array(0 => 43)))),
					'symbols' => array(), 'user_mentions' => array(), 'urls' => array(),
					'media' => array(0 => stdClass::__set_state(array(
						'id' => '[id]', 'id_str' => '[id]', 'indices' => array(0 => 50), 'media_url' => 'http://twimg.com/media/x.jpg', 'media_url_https' => 'http://twimg.com/media/x.jpg', 'url' => 'https://t.co/x', 'display_url' => 'pic.twitter.com/x', 'expanded_url' => 'https://twitter.com/username/status/[id]/photo/1', 'type' => 'photo',
						'sizes' => stdClass::__set_state(array(
							'large' => stdClass::__set_state(array('w' => 1600, 'h' => 1200, 'resize' => 'fit')),
							'thumb' => stdClass::__set_state(array('w' => 150, 'h' => 150, 'resize' => 'crop')),
							'medium' => stdClass::__set_state(array('w' => 1200, 'h' => 900, 'resize' => 'fit')),
							'small' => stdClass::__set_state(array('w' => 680, 'h' => 510, 'resize' => 'fit'))
						))
					)))
				)),
				'extended_entities' => stdClass::__set_state(array(
					'media' => array(0 => stdClass::__set_state(array(
						'id' => '[id]', 'id_str' => '[id]', 'indices' => array(0 => 50), 'media_url' => 'http://twimg.com/media/x.jpg', 'media_url_https' => 'http://twimg.com/media/x.jpg', 'url' => 'https://t.co/x', 'display_url' => 'pic.twitter.com/x', 'expanded_url' => 'https://twitter.com/username/status/[id]/photo/1', 'type' => 'photo',
						'sizes' => stdClass::__set_state(array(
							'large' => stdClass::__set_state(array('w' => 1600, 'h' => 1200, 'resize' => 'fit')),
							'thumb' => stdClass::__set_state(array('w' => 150, 'h' => 150, 'resize' => 'crop')),
							'medium' => stdClass::__set_state(array('w' => 1200, 'h' => 900, 'resize' => 'fit')),
							'small' => stdClass::__set_state(array('w' => 680, 'h' => 510, 'resize' => 'fit'))
						))
					)))
				)),
				'metadata' => stdClass::__set_state(array('iso_language_code' => 'sv', 'result_type' => 'recent')),
				'source' => 'Twitter for Dumbphone', 'in_reply_to_status_id' => NULL, 'in_reply_to_status_id_str' => NULL, 'in_reply_to_user_id' => NULL, 'in_reply_to_user_id_str' => NULL, 'in_reply_to_screen_name' => NULL,
				'user' => stdClass::__set_state(array(
					'id' => [id], 'id_str' => '[id]', 'name' => 'Name', 'screen_name' => 'username', 'location' => 'Sverige', 'description' => 'Description', 'url' => 'https://t.co/x',
					'entities' => stdClass::__set_state(array(
						'url' => stdClass::__set_state(array(
							'urls' => array(0 => stdClass::__set_state(array(
								'url' => 'https://t.co/x', 'expanded_url' => 'http://domain.com', 'display_url' => 'domain.com', 'indices' => array(0 => 2)
							)))
						)),
						'description' => stdClass::__set_state(array('urls' => array()))
					)),
					'protected' => false, 'followers_count' => 78040, 'friends_count' => 4127, 'listed_count' => 594, 'created_at' => 'Tue Jan 20 10:19:51 +0000 2009', 'favourites_count' => 420, 'utc_offset' => 3600, 'time_zone' => 'Brantevik', 'geo_enabled' => true, 'verified' => true, 'statuses_count' => 12821, 'lang' => 'sv', 'contributors_enabled' => false, 'is_translator' => false, 'is_translation_enabled' => false, 'profile_background_color' => 'ffffff', 'profile_background_image_url' => 'http://pbs.twimg.com/profile_background_images/[id]/x.jpg', 'profile_background_image_url_https' => 'https://pbs.twimg.com/profile_background_images/[id]/x.jpeg', 'profile_background_tile' => false, 'profile_image_url' => 'http://pbs.twimg.com/profile_images/[id]/x.png', 'profile_image_url_https' => 'https://pbs.twimg.com/profile_images/[id]/x.png', 'profile_banner_url' => 'https://pbs.twimg.com/profile_banners/[id]/[id]', 'profile_link_color' => 'ffffff', 'profile_sidebar_border_color' => 'ffffff', 'profile_sidebar_fill_color' => 'ffffff', 'profile_text_color' => '333333', 'profile_use_background_image' => true, 'has_extended_profile' => false, 'default_profile' => false, 'default_profile_image' => false, 'following' => false, 'follow_request_sent' => false, 'notifications' => false, 'translator_type' => 'none'
				)),
				'geo' => NULL, 'coordinates' => NULL, 'place' => NULL, 'contributors' => NULL, 'is_quote_status' => false, 'retweet_count' => 9, 'favorite_count' => 27, 'favorited' => false, 'retweeted' => false, 'possibly_sensitive' => false, 'lang' => 'sv')*/

				$this->arr_posts[] = array(
					'type' => $this->type,
					'id' => $post->id,
					'name' => isset($post->user->screen_name) ? $post->user->screen_name : $this->search,
					'text' => $post->text,
					'link' => "//twitter.com/".$this->search."/status/".$post->id,
					'image' => (isset($post->entities->media[0]->media_url) ? $post->entities->media[0]->media_url : ""),
					'created' => date("Y-m-d H:i:s", strtotime($post->created_at)),
				);
			}

			delete_post_meta($this->id, $this->meta_prefix.'error');
		}
	}

	function fetch_rss()
	{
		include_once("simplepie_1.3.1.compiled.php");

		$feed = new SimplePie();
		$feed->set_feed_url(validate_url($this->search, false));
		//$feed->set_cache_location($globals['server_temp_cache']);
		//$feed->enable_cache(false);
		//$feed->handle_content_type(); // text/html utf-8 character encoding
		//$feed->set_output_encoding('ISO-8859-1');
		//$feed->enable_order_by_date(false);
		$feed->strip_htmltags(array('a', 'b', 'div', 'p', 'span'));
		$feed->strip_attributes(array('class', 'target', 'style', 'align'));
		$check = $feed->init();

		if($check)
		{
			foreach($feed->get_items() as $item)
			{
				$post_link = $item->get_permalink();
				$post_title = $item->get_title();
				$post_content = $item->get_description();

				$post_image = "";

				/*if($enclosure = $item->get_enclosure())
				{
					$post_image = $enclosure->get_link();
				}*/

				$post_date = $item->get_date('Y-m-d H:i:s');

				$this->arr_posts[] = array(
					'type' => $this->type,
					//'id' => md5($post_title.$post_link),
					'name' => $this->search,
					'title' => $post_title,
					'text' => $post_content,
					'link' => $post_link,
					'image' => $post_image,
					'created' => $post_date,
				);
			}

			delete_post_meta($this->id, $this->meta_prefix.'error');
		}

		else
		{
			update_post_meta($this->id, $this->meta_prefix.'error', $json['error']['message']);
		}
	}

	function set_date_modified()
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_modified = NOW() WHERE ID = '%d' AND post_type = 'mf_social_feed'", $this->id));
	}

	function insert_posts()
	{
		global $wpdb;

		foreach($this->arr_posts as $post)
		{
			$post_title = (isset($post['title']) && $post['title'] != '' ? $post['title'] : $post['type']." ".$post['id']);
			$post_name = sanitize_title_with_dashes(sanitize_title($post_title));

			$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'mf_social_feed_post' AND (post_title = %s OR post_name = %s) AND post_excerpt = '%d' LIMIT 0, 1", $post_title, $post_name, $this->id));

			if($wpdb->num_rows == 0)
			{
				$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = 'mf_social_feed_post' AND post_excerpt = '%d' AND post_status = 'pending' AND meta_key = '".$this->meta_prefix."name' AND meta_value = %s LIMIT 0, 1", $this->id, $post['name']));
				$post_status = $wpdb->num_rows > 0 ? 'draft' : 'publish';

				if($post_status == 'draft')
				{
					do_log("A post was set to ".$post_status." because ".$post['name']." previously has been set to be ignored (".$wpdb->last_query.")");
				}

				$post_data = array(
					'post_type' => 'mf_social_feed_post',
					'post_status' => $post_status,
					'post_name' => $post_name, //Can this be removed?
					'post_title' => $post_title,
					'post_content' => $post['text'],
					'post_date' => $post['created'],
					'post_excerpt' => $this->id, //Can this be removed?
					'meta_input' => array(
						$this->meta_prefix.'service' => $post['type'],
						$this->meta_prefix.'feed_id' => $this->id,
						$this->meta_prefix.'name' => $post['name'],
						$this->meta_prefix.'image' => $post['image'],
						$this->meta_prefix.'link' => $post['link'],
					),
				);

				if(!isset($post['is_owner']) || $post['is_owner'] == true)
				{
					$post_id = wp_insert_post($post_data);

					if(isset($post['likes']))
					{
						update_post_meta($post_id, $this->meta_prefix.'likes', $post['likes']);
					}

					if(isset($post['comments']))
					{
						update_post_meta($post_id, $this->meta_prefix.'comments', $post['comments']);
					}
				}
			}

			else
			{
				foreach($result as $r)
				{
					$post_data = array(
						'ID' => $r->ID,
						'post_name' => $post_name, //Can this be removed?
						'post_title' => $post_title,
						'post_content' => $post['text'],
						'meta_input' => array(
							$this->meta_prefix.'service' => $post['type'],
							$this->meta_prefix.'feed_id' => $this->id,
							$this->meta_prefix.'name' => $post['name'],
							$this->meta_prefix.'image' => $post['image'],
							$this->meta_prefix.'link' => $post['link'],
						),
					);

					if(!isset($post['is_owner']) || $post['is_owner'] == true)
					{
						wp_update_post($post_data);

						if(isset($post['likes']))
						{
							update_post_meta($r->ID, $this->meta_prefix.'likes', $post['likes']);
						}

						if(isset($post['comments']))
						{
							update_post_meta($r->ID, $this->meta_prefix.'comments', $post['comments']);
						}
					}

					else
					{
						wp_trash_post($r->ID);
					}
				}
			}
		}
	}
	#########################

	// Public
	#########################
	function get_feeds_and_posts($data)
	{
		global $wpdb;

		$arr_public_feeds = $arr_post_feeds = $arr_post_posts = array();
		$query_where = "";

		if(is_array($data['feeds']) && count($data['feeds']) > 0)
		{
			$query_where .= " AND ID IN('".implode("','", $data['feeds'])."')";
		}

		$result = $wpdb->get_results("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'mf_social_feed' AND post_status = 'publish'".$query_where);

		foreach($result as $r)
		{
			$arr_public_feeds[] = $r->ID;
		}

		if(count($arr_public_feeds) > 0)
		{
			$obj_social_feed = new mf_social_feed();

			$result = $wpdb->get_results("SELECT ID, post_title, post_content, post_date, guid FROM ".$wpdb->posts." WHERE post_type = 'mf_social_feed_post' AND post_status = 'publish' AND post_excerpt IN('".implode("','", $arr_public_feeds)."') ORDER BY post_date DESC LIMIT 0, ".$data['amount']); //, post_excerpt

			if($wpdb->num_rows > 0)
			{
				$arr_services = get_social_types_for_select();

				$arr_post_feeds = $arr_post_posts = array();

				foreach($result as $r)
				{
					$post_id = $r->ID;
					$post_title = $r->post_title;
					$post_content = $r->post_content;
					$post_date = $r->post_date;
					//$post_feed = $r->post_excerpt;

					$post_service = get_post_meta($post_id, $obj_social_feed->meta_prefix.'service', true);
					$post_feed = get_post_meta($post_id, $obj_social_feed->meta_prefix.'feed_id', true);
					$post_username = get_post_meta($post_id, $obj_social_feed->meta_prefix.'name', true);
					$post_image = get_post_meta($post_id, $obj_social_feed->meta_prefix.'image', true);
					$post_link = get_post_meta($post_id, $obj_social_feed->meta_prefix.'link', true);

					if($data['likes'] == 'yes')
					{
						$post_likes = get_post_meta($post_id, $obj_social_feed->meta_prefix.'likes', true);
						$post_comments = get_post_meta($post_id, $obj_social_feed->meta_prefix.'comments', true);
					}

					else
					{
						$post_likes = $post_comments = '';
					}

					if($post_service == '')
					{
						list($post_service, $service_id) = explode(" ", $r->post_title);
					}

					if($post_link == '')
					{
						$post_link = $r->guid;
					}

					if($post_content != '' || $post_image != '')
					{
						switch($data['filter'])
						{
							case 'yes':
								$arr_post_feeds[$post_feed] = array(
									'id' => "sf_feed_".$post_feed,
									'name' => get_post_title($post_feed),
								);
							break;

							case 'group':
								$arr_post_feeds[$post_service] = array(
									'id' => "sf_".$post_service,
									'name' => $arr_services[$post_service],
								);
							break;
						}

						$arr_post_posts[] = array(
							'service' => $post_service,
							'feed' => $post_feed,
							'feed_title' => get_post_title($post_feed),
							'link' => $post_link,
							'name' => $post_username,
							'title' => $post_title,
							'content' => $post_content,
							'image' => $post_image,
							'date' => format_date($post_date),
							'likes' => $post_likes,
							'comments' => $post_comments,
						);
					}
				}

				if($data['filter'] != 'no' && count($arr_post_feeds) > 1)
				{
					$arr_post_feeds = array_sort(array('array' => $arr_post_feeds, 'on' => 'name'));
				}

				else
				{
					$arr_post_feeds = array();
				}
			}
		}

		return array($arr_post_feeds, $arr_post_posts);
	}

	/*function get_output($data)
	{
		global $wpdb;

		if(!isset($data['social_type'])){										$data['social_type'] = 'widget';}
		if(!isset($data['social_amount']) || $data['social_amount'] < 1){		$data['social_amount'] = 18;}
		if(!isset($data['social_filter'])){										$data['social_filter'] = 'no';}
		if(!isset($data['social_border'])){										$data['social_border'] = 'yes';}
		if(!isset($data['social_likes'])){										$data['social_likes'] = 'no';}
		if(!isset($data['social_read_more'])){									$data['social_read_more'] = 'yes';}
		
		$out = "";

		list($arr_post_feeds, $arr_post_posts) = $this->get_feeds_and_posts(array('feeds' => $data['social_feeds'], 'amount' => $data['social_amount'], 'filter' => $data['social_filter'], 'likes' => $data['social_likes']));

		if(count($arr_post_posts) > 0)
		{
			if($data['social_filter'] != 'no' && count($arr_post_feeds) > 1)
			{
				$out .= "<ul class='sf_feeds'>
					<li class='active'><a href='#'>".__("All", 'lang_social_feed')."</a></li>";

					foreach($arr_post_feeds as $value)
					{
						$out .= "<li><a href='#' id='".$value['id']."'>".$value['name']."</a></li>";
					}

				$out .= "</ul>";
			}

			$class_xtra = "";

			if(count($arr_post_posts) < 3){				$class_xtra .= " one_column";}
			if($data['social_border'] == 'yes'){		$class_xtra .= " show_border";}
			if($data['social_read_more'] == 'yes'){		$class_xtra .= " show_read_more";}

			$out .= "<ul class='sf_posts".$class_xtra."'>";

				foreach($arr_post_posts as $post)
				{
					$out .= "<li class='sf_".$post['service']." sf_feed_".$post['feed']."'>
						<i class='fa fa-".$post['service']."'></i>";

						if($post['service'] == 'rss')
						{
							$out .= "<span class='name'>".$post['feed_title']."</span>";
						}

						else if($post['name'] != '')
						{
							$out .= "<span class='name'>".$post['name']."</span>";
						}

						$out .= "<span class='date'>".$post['date']."</span>
						<a href='".$post['link']."' class='content' rel='external'>";

							if($post['image'] != '')
							{
								$out .= "<img src='".$post['image']."'>";
							}

							if($post['service'] == 'rss' && $post['title'] != '')
							{
								$out .= "<p>".$post['title']."</p>";
							}

							if($post['content'] != '')
							{
								$out .= "<p>".$post['content']."</p>";
							}

							if($post['likes'] != '' || $post['comments'] != '')
							{
								$out .= "<div class='likes'>
									<i class='fa fa-thumbs-up'></i><span>".$post['likes']."</span>
									<i class='fa fa-comment-o'></i><span>".$post['comments']."</span>
								</div>";
							}

						$out .= "</a>
					</li>";
				}

			$out .= "</ul>";
		}

		else
		{
			$out .= "<p>".__("I could not find any posts at the moment. Sorry!", 'lang_social_feed')."</p>";
		}

		return $out;
	}*/
	#########################
}

class widget_social_feed extends WP_Widget
{
	function __construct()
	{
		$widget_ops = array(
			'classname' => 'social_feed',
			'description' => __("Display Social Feeds", 'lang_social_feed')
		);

		$this->arr_default = array(
			'social_heading' => "",
			'social_feeds' => array(),
			'social_filter' => 'no',
			'social_amount' => 18,
			'social_text' => 'yes',
			'social_border' => 'yes',
			'social_likes' => 'no',
			'social_read_more' => 'yes',
		);

		parent::__construct('social-feed-widget', __("Social Feed", 'lang_social_feed'), $widget_ops);

		$this->meta_prefix = "mf_social_feed_";
	}

	function widget($args, $instance)
	{
		extract($args);

		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$setting_social_reload = get_option('setting_social_reload');

		echo $before_widget;

			if($instance['social_heading'] != '')
			{
				echo $before_title
					.$instance['social_heading']
				.$after_title;
			}

			echo "<div class='section'"
				.(is_array($instance['social_feeds']) && count($instance['social_feeds']) > 0 ? " data-social_feeds='".implode(",", $instance['social_feeds'])."'" : "")
				.($instance['social_filter'] != '' ? " data-social_filter='".$instance['social_filter']."'" : "")
				.($instance['social_amount'] > 0 ? " data-social_amount='".$instance['social_amount']."'" : "")
				.($instance['social_likes'] != '' ? " data-social_likes='".$instance['social_likes']."'" : "")
				.($setting_social_reload > 0 ? " data-social_reload='".$setting_social_reload."'" : "")
			.">
				<i class='fa fa-spinner fa-spin fa-3x'></i>
				<ul class='sf_feeds hide'></ul>
				<ul class='sf_posts";

					if($instance['social_text'] == 'yes')
					{
						echo ($instance['social_border'] == 'yes' ? " show_border" : '')
						.($instance['social_read_more'] == 'yes' ? " show_read_more" : '');
					}

					else
					{
						echo " hide_text";
					}

				echo " hide'></ul>
			</div>"
		.$after_widget;
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;

		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['social_heading'] = sanitize_text_field($new_instance['social_heading']);
		$instance['social_feeds'] = is_array($new_instance['social_feeds']) ? $new_instance['social_feeds'] : array();
		$instance['social_filter'] = sanitize_text_field($new_instance['social_filter']);
		$instance['social_amount'] = sanitize_text_field($new_instance['social_amount']);
		$instance['social_text'] = sanitize_text_field($new_instance['social_text']);
		$instance['social_border'] = sanitize_text_field($new_instance['social_border']);
		$instance['social_likes'] = sanitize_text_field($new_instance['social_likes']);
		$instance['social_read_more'] = sanitize_text_field($new_instance['social_read_more']);

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$arr_data_feeds = array();
		get_post_children(array('post_type' => 'mf_social_feed'), $arr_data_feeds);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('social_heading'), 'text' => __("Heading", 'lang_social_feed'), 'value' => $instance['social_heading']));

			if(count($arr_data_feeds) > 1)
			{
				echo "<div class='flex_flow'>"
					.show_select(array('data' => $arr_data_feeds, 'name' => $this->get_field_name('social_feeds')."[]", 'text' => __("Feeds", 'lang_social_feed'), 'value' => $instance['social_feeds']));

					if(count($instance['social_feeds']) != 1)
					{
						$arr_data_filter = array(
							'no' => __("No", 'lang_social_feed'),
							'yes' => __("Yes", 'lang_social_feed')." (".__("Individually", 'lang_social_feed').")",
							'group' => __("Yes", 'lang_social_feed')." (".__("Grouped", 'lang_social_feed').")",
						);

						echo show_select(array('data' => $arr_data_filter, 'name' => $this->get_field_name('social_filter'), 'text' => __("Display Filter", 'lang_social_feed'), 'value' => $instance['social_filter']));
					}

				echo "</div>";
			}

			echo show_textfield(array('type' => 'number', 'name' => $this->get_field_name('social_amount'), 'text' => __("Amount", 'lang_social_feed'), 'value' => $instance['social_amount']))
			.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('social_text'), 'text' => __("Display Text", 'lang_social_feed'), 'value' => $instance['social_text']));

			if('yes' == $instance['social_text'])
			{
				echo show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('social_border'), 'text' => __("Display Border", 'lang_social_feed'), 'value' => $instance['social_border']))
				."<div class='flex_flow'>"
					.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('social_read_more'), 'text' => __("Display Read More", 'lang_social_feed'), 'value' => $instance['social_read_more']))
					.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('social_likes'), 'text' => __("Display Likes", 'lang_social_feed'), 'value' => $instance['social_likes']))
				."</div>";
			}

		echo "</div>";
	}
}