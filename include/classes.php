<?php

class mf_social_feed
{
	function __construct($id = 0)
	{
		$this->id = $id > 0 ? $id : 0;
		$this->type = $this->search = "";

		$this->meta_prefix = "mf_social_feed_";
	}

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
		switch($this->get_type())
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

	function fetch_feed()
	{
		$this->arr_posts = array();

		switch($this->get_type())
		{
			case 'facebook':
				$this->fetch_facebook();
			break;

			case 'instagram':
				$this->fetch_instagram();
			break;

			case 'twitter':
				$this->fetch_twitter();
			break;
		}

		$this->set_date_modified();

		$this->insert_posts();
	}

	function fetch_facebook()
	{
		include_once("Facebook/autoload.php");

		if(substr($this->search, 0, 1) == "@")
		{
			$this->search = substr($this->search, 1);
		}

		$fb = new Facebook\Facebook([
		  'app_id' => $this->facebook_api_id,
		  'app_secret' => $this->facebook_api_secret,
		  'default_graph_version' => 'v2.8',
		]);

		$response = $fb->get("/".$this->search."/feed?access_token=".$this->facebook_api_id."|".$this->facebook_api_secret);
		$graphObject = $response->getGraphEdge();

		foreach($graphObject as $key => $post)
		{
			/*array('items' => array('message' => 'Text #hashtag', 'created_time' => DateTime::__set_state(array('date' => '2017-02-08 11:59:40.000000', 'timezone_type' => 1, 'timezone' => '+00:00')), 'id' => '[id]_[id]')*/

			$post_id = $post['id'];
			$arr_post_id = explode("_", $post_id);
			$post_link = "//facebook.com/".$arr_post_id[0]."/posts/".$arr_post_id[1];

			$post_content = "";

			if(isset($post['message']))
			{
				$post_content = $post['message'];
			}

			else if(isset($post['story']))
			{
				$post_content = $post['story'];
			}

			$post_image = "https://graph.facebook.com/".$arr_post_id[1]."/picture";
			$post_image_size = @getimagesize($post_image);
			$post_has_image = is_array($post_image_size) && $post_image_size[0] > 0;

			$post_date = "";

			foreach($post['created_time'] as $key => $time)
			{
				if($key == 'date')
				{
					$post_date = date("Y-m-d H:i:s", strtotime($time));
				}
			}

			$this->arr_posts[] = array(
				'type' => $this->type,
				'id' => $post_id,
				'name' => $this->search,
				'text' => $post_content,
				'link' => $post_link,
				'image' => ($post_has_image ? $post_image : ""),
				'created' => $post_date,
			);
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

				do_log(__("The JSON I got back was not correct", 'lang_social_feed'), 'trash');
			}

			else
			{
				update_post_meta($this->id, $this->meta_prefix.'error', 'instagram');

				do_log(sprintf(__("The JSON I got back was not correct. Have a look at %s", 'lang_social_feed'), $url));
			}
		}

		else
		{
			$filter = "users/self/feed";
		}

		if($filter != '')
		{
			$result = wp_remote_retrieve_body(wp_remote_get("https://api.instagram.com/v1/".$filter."?access_token=".$this->instagram_api_token)); //."&count=".$instagram_amount
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
			}
		}
	}

	function fetch_twitter()
	{
		include_once("twitter/twitter.class.php");

		$twitter = new Twitter($this->twitter_api_key, $this->twitter_api_secret, $this->twitter_api_token, $this->twitter_api_token_secret);

		if(substr($this->search, 0, 1) == "#")
		{
			$results = $twitter->search($this->search);
		}

		else if(substr($this->search, 0, 1) == "@")
		{
			if(substr($this->search, 0, 1) == "@")
			{
				$this->search = substr($this->search, 1);
			}

			$results = $twitter->search('from:'.$this->search);
		}

		else
		{
			$results = $twitter->load(Twitter::ME);
		}

		//$results = $twitter->load(Twitter::ME_AND_FRIENDS);
		//$results = $twitter->request('statuses/retweets_of_me', 'GET', ['count' => 20]);

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
			$post_title = $post['type']." ".$post['id'];

			$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'mf_social_feed_post' AND post_title = %s AND post_excerpt = '%d' LIMIT 0, 1", $post_title, $this->id));

			if($wpdb->num_rows == 0)
			{
				$post_data = array(
					'post_type' => 'mf_social_feed_post',
					'post_status' => 'publish',
					'post_title' => $post_title,
					'post_content' => $post['text'],
					'post_date' => $post['created'],
					'guid' => $post['link'],
					'post_excerpt' => $this->id,
					'meta_input' => array(
						$this->meta_prefix.'name' => $post['name'],
						$this->meta_prefix.'image' => $post['image'],
						//$this->meta_prefix.'likes' => $post['likes'],
						//$this->meta_prefix.'comments' => $post['comments'],
					),
				);

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

			else
			{
				foreach($result as $r)
				{
					$post_data = array(
						'ID' => $r->ID,
						'post_content' => $post['text'],
						'guid' => $post['link'],
						'meta_input' => array(
							$this->meta_prefix.'name' => $post['name'],
							$this->meta_prefix.'image' => $post['image'],
							//$this->meta_prefix.'likes' => $post['likes'],
							//$this->meta_prefix.'comments' => $post['comments'],
						),
					);

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
			}
		}
	}
}

class widget_social_feed extends WP_Widget
{
	function __construct()
	{
		$widget_ops = array(
			'classname' => 'social_feed',
			'description' => __("Display Social Feeds", 'lang_social_feed')
		);

		$control_ops = array('id_base' => 'social-feed-widget');

		parent::__construct('social-feed-widget', __("Social Feed", 'lang_social_feed'), $widget_ops, $control_ops);

		$this->meta_prefix = "mf_social_feed_";
	}

	function widget($args, $instance)
	{
		global $wpdb;

		extract($args);

		$instance['social_border'] = isset($instance['social_border']) ? $instance['social_border'] : 'yes';

		echo $before_widget;

			if($instance['social_heading'] != '')
			{
				echo $before_title
					.$instance['social_heading']
				.$after_title;
			}

			echo "<div class='section'>";

				$query_where = "";

				if(count($instance['social_feeds']) > 0)
				{
					$query_where .= " AND post_excerpt IN('".implode("','", $instance['social_feeds'])."')";
				}

				$result = $wpdb->get_results("SELECT ID, post_title, post_content, post_date, guid, post_excerpt FROM ".$wpdb->posts." WHERE post_type = 'mf_social_feed_post' AND post_status = 'publish'".$query_where." ORDER BY post_date DESC LIMIT 0, ".($instance['social_amount'] >= 1 ? $instance['social_amount'] : 18));

				if($wpdb->num_rows > 0)
				{
					$arr_services = get_social_types_for_select();

					$arr_post_feeds = $arr_post_posts = array();

					foreach($result as $r)
					{
						$post_id = $r->ID;
						$post_content = $r->post_content;
						$post_date = $r->post_date;
						$post_link = $r->guid;
						$post_feed = $r->post_excerpt;

						list($service, $service_id) = explode(" ", $r->post_title);

						$post_name = get_post_meta($post_id, $this->meta_prefix.'name', true);
						$post_image = get_post_meta($post_id, $this->meta_prefix.'image', true);
						$post_likes = get_post_meta($post_id, $this->meta_prefix.'likes', true);
						$post_comments = get_post_meta($post_id, $this->meta_prefix.'comments', true);

						if($post_content != '' || $post_image != '')
						{
							if(isset($instance['social_filter']) && $instance['social_filter'] != 'no')
							{
								if($instance['social_filter'] == 'yes')
								{
									$arr_post_feeds[$post_feed] = get_post_title($post_feed);
								}

								else if($instance['social_filter'] == 'group')
								{
									$arr_post_feeds[$service] = $arr_services[$service];
								}
							}

							$arr_post_posts[] = array(
								'service' => $service,
								'feed' => $post_feed,
								'link' => $post_link,
								'name' => $post_name,
								'content' => $post_content,
								'image' => $post_image,
								'date' => $post_date,
								'likes' => $post_likes,
								'comments' => $post_comments,
							);
						}
					}

					if(isset($instance['social_filter']) && $instance['social_filter'] != 'no')
					{
						mf_enqueue_script('script_social_feed', plugin_dir_url(__FILE__)."script.js", array('read_more' => __("Read More", 'lang_social_feed')), get_plugin_version(__FILE__));

						echo "<ul class='sf_feeds'>
							<li class='active'><a href='#'>".__("All", 'lang_social_feed')."</a></li>";

							foreach($arr_post_feeds as $key => $value)
							{
								echo "<li><a href='#' id='".($instance['social_filter'] == 'yes' ? "sf_feed_".$key : "sf_".$key)."'>".$value."</a></li>";
							}

						echo "</ul>";
					}

					$class_xtra = "";

					if(count($arr_post_posts) < 3)
					{
						$class_xtra .= " one_column";
					}

					if($instance['social_border'] == 'yes')
					{
						$class_xtra .= " show_border";
					}

					echo "<ul class='sf_posts".$class_xtra."'>";

						foreach($arr_post_posts as $post)
						{
							echo "<li class='sf_".$post['service']." sf_feed_".$post['feed']."'>
								<i class='fa fa-".$post['service']."'></i>";

								if($post['name'])
								{
									echo "<span class='name'>".$post['name']."</span>";
								}

								echo "<span class='date'>".format_date($post['date'])."</span>
								<a href='".$post['link']."' class='content' rel='external'>";

									if($post['image'] != '')
									{
										echo "<img src='".$post['image']."'>";
									}

									if($post['content'] != '')
									{
										echo "<p>".$post['content']."</p>";
									}

									if(isset($instance['social_likes']) && $instance['social_likes'] == 'yes' && ($post['likes'] != '' || $post['comments'] != ''))
									{
										echo "<div class='likes'>
											<i class='fa fa-thumbs-up'></i><span>".$post['likes']."</span>
											<i class='fa fa-comment-o'></i><span>".$post['comments']."</span>
										</div>";
									}

								echo "</a>
							</li>";
						}

					echo "</ul>";
				}

				else
				{
					echo "<p>".__("I could not find any posts at the moment. Sorry!", 'lang_social_feed')."</p>";
				}

			echo "</div>"
		.$after_widget;
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;

		$instance['social_heading'] = strip_tags($new_instance['social_heading']);
		$instance['social_feeds'] = isset($new_instance['social_feeds']) ? $new_instance['social_feeds'] : array();
		$instance['social_amount'] = strip_tags($new_instance['social_amount']);
		$instance['social_filter'] = strip_tags($new_instance['social_filter']);
		$instance['social_border'] = isset($new_instance['social_border']) ? strip_tags($new_instance['social_border']) : 'yes';
		$instance['social_likes'] = isset($new_instance['social_likes']) ? strip_tags($new_instance['social_likes']) : 'no';

		return $instance;
	}

	function form($instance)
	{
		global $wpdb;

		$defaults = array(
			'social_heading' => "",
			'social_feeds' => array(),
			'social_amount' => 18,
			'social_filter' => 'no',
			'social_border' => 'yes',
			'social_likes' => 'no',
		);
		$instance = wp_parse_args((array)$instance, $defaults);

		$arr_data_feeds = array();
		get_post_children(array('post_type' => 'mf_social_feed'), $arr_data_feeds);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('social_heading'), 'text' => __("Heading", 'lang_social_feed'), 'value' => $instance['social_heading']))
			.show_select(array('data' => $arr_data_feeds, 'name' => $this->get_field_name('social_feeds')."[]", 'text' => __("Feeds", 'lang_social_feed'), 'value' => $instance['social_feeds']))
			."<div class='flex_flow'>"
				.show_textfield(array('type' => 'number', 'name' => $this->get_field_name('social_amount'), 'text' => __("Amount", 'lang_social_feed'), 'value' => $instance['social_amount']))
				.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('social_border'), 'text' => __("Show Border", 'lang_social_feed'), 'value' => $instance['social_border']))
				.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('social_likes'), 'text' => __("Show Likes", 'lang_social_feed'), 'value' => $instance['social_likes']))
			."</div>";

			if(count($arr_data_feeds) > 1)
			{
				$arr_data_filter = array(
					'no' => __("No", 'lang_social_feed'),
					'yes' => __("Yes", 'lang_social_feed')." (".__("Individually", 'lang_social_feed').")",
					'group' => __("Yes", 'lang_social_feed')." (".__("Grouped", 'lang_social_feed').")",
				);

				echo show_select(array('data' => $arr_data_filter, 'name' => $this->get_field_name('social_filter'), 'text' => __("Show Filter", 'lang_social_feed'), 'value' => $instance['social_filter']));
			}

		echo "</div>";
	}
}