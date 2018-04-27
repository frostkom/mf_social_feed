<?php

class mf_social_feed
{
	function __construct($id = 0)
	{
		$this->id = $id > 0 ? $id : 0;
		$this->type = $this->search = "";

		$this->meta_prefix = "mf_social_feed_";
	}

	function admin_init()
	{
		global $pagenow;

		if($pagenow == 'edit.php' && check_var('post_type') == 'mf_social_feed_post')
		{
			$plugin_include_url = plugin_dir_url(__FILE__);
			$plugin_version = get_plugin_version(__FILE__);

			mf_enqueue_script('script_social_feed', $plugin_include_url."script_wp.js", array('ajax_url' => admin_url('admin-ajax.php')), $plugin_version);
		}
	}

	function wp_head()
	{
		$plugin_base_include_url = plugins_url()."/mf_base/include/";
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		$setting_social_debug = get_option('setting_social_debug');

		mf_enqueue_style('style_social_feed', $plugin_include_url."style.php", $plugin_version);
		mf_enqueue_style('style_bb', $plugin_base_include_url."backbone/style.css", $plugin_version);

		mf_enqueue_script('underscore');
		mf_enqueue_script('backbone');
		mf_enqueue_script('script_base_plugins', $plugin_base_include_url."backbone/bb.plugins.js", $plugin_version);
		mf_enqueue_script('script_social_feed_plugins', $plugin_include_url."backbone/bb.plugins.js", array('read_more' => __("Read More", 'lang_social_feed')), $plugin_version);
		mf_enqueue_script('script_social_feed_models', $plugin_include_url."backbone/bb.models.js", array('plugin_url' => $plugin_include_url), $plugin_version);
		mf_enqueue_script('script_social_feed_views', $plugin_include_url."backbone/bb.views.js", array('debug' => $setting_social_debug), $plugin_version);
		mf_enqueue_script('script_base_init', $plugin_base_include_url."backbone/bb.init.js", $plugin_version);
	}

	// Admin
	#########################
	function meta_feed_facebook_info()
	{
		return "<p condition_type='show_this_if' condition_selector='".$this->meta_prefix."type' condition_value='facebook'>".__("Posts can only be fetched from Facebook Pages, not personal Profiles", 'lang_social_feed')."</p>";
	}

	function meta_feed_instagram_info()
	{
		return "<p condition_type='show_this_if' condition_selector='".$this->meta_prefix."type' condition_value='instagram'>".__("Posts can either be fetched from @users or #hashtags", 'lang_social_feed')."</p>";
	}

	function meta_feed_instagram_access_token_info()
	{
		global $post;

		$post_id = $post->ID;
		
		$edit_url = admin_url("post.php?post=".$post_id."&action=edit");
		
		$instagram_access_token = get_post_meta($post_id, $this->meta_prefix.'instagram_access_token', true);

		if($instagram_access_token != '')
		{
			$out = "<strong><i class='fa fa-check green'></i> ".__("All Done!", 'lang_social_feed')."</strong>";
		}

		else
		{
			$instagram_client_id = get_post_meta($post_id, $this->meta_prefix.'instagram_client_id', true);

			if($instagram_client_id != '')
			{
				$out = "<ol condition_type='show_this_if' condition_selector='".$this->meta_prefix."type' condition_value='instagram'>"
					."<li><a href='https://www.instagram.com/oauth/authorize/?client_id=".$instagram_client_id."&redirect_uri=".$edit_url."&response_type=token&scope=public_content'>".__("Authorize Here", 'lang_social_feed')."</a></li>"
					."<li>".sprintf(__("When you arrive back here after authorization, just copy the access token from the address bar and paste it in the %s field above", 'lang_social_feed'), __("Access Token", 'lang_social_feed'))."</li>"
				."</ol>";
			}

			else
			{
				$out = "<ol condition_type='show_this_if' condition_selector='".$this->meta_prefix."type' condition_value='instagram'>"
					."<li>".sprintf(__("Go to %sInstagram for Developers%s and Log in. It is important that you log in with the account that you want to fetch posts from", 'lang_social_feed'), "<a href='//instagram.com/developer/'>", "</a>")."</li>"
					."<li>".sprintf(__("Enter the fields in the form %s and press %s", 'lang_social_feed'), "Developer Signup", "Sign up")."</li>" //Your website, Phone number, What do you want to build with API
					."<li>".sprintf(__("Click on %s or %s and then %s", 'lang_social_feed'), "Register Your Application", "Manage Clients", "Register a New Client")."</li>"
					."<li>".sprintf(__("Enter the fields in the form %s and press %s. It is important that you enter all the fields and that %s is set to %s. You should also uncheck %s", 'lang_social_feed'), "Register new Client ID", "Register", "Valid redirect URIs", $edit_url, "Disable implicit OAuth")."</li>"
					."<li>".sprintf(__("Copy %s and enter into the %s field above", 'lang_social_feed'), "CLIENT ID", __("Client ID", 'lang_social_feed'))."</li>"
				."</ol>";
			}
		}

		return $out;
	}

	function meta_feed_linkedin_info()
	{
		return "<p condition_type='show_this_if' condition_selector='".$this->meta_prefix."type' condition_value='linkedin'>".__("Posts can be fetched with company ID as seen in the URL when visiting the page", 'lang_social_feed')."</p>";
	}

	function meta_feed_rss_info()
	{
		return "<p condition_type='show_this_if' condition_selector='".$this->meta_prefix."type' condition_value='rss'>".__("Posts can only be fetched by entering the full URL to the feed", 'lang_social_feed')."</p>";
	}

	function meta_feed_twitter_info()
	{
		return "<p condition_type='show_this_if' condition_selector='".$this->meta_prefix."type' condition_value='twitter'>".__("Posts can either be fetched from @users or #hashtags", 'lang_social_feed')."</p>";
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
			."<li><a href='".$post_link."'>@".$post_username."</a></li>"
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
				),
				array(
					'name' => __("Search for", 'lang_social_feed'),
					'id' => $this->meta_prefix.'search_for',
					'type' => 'text',
				),
				array(
					'id' => $this->meta_prefix.'facebook_info',
					'type' => 'custom_html',
					'callback' => array($this, 'meta_feed_facebook_info'),
				),
				array(
					'id' => $this->meta_prefix.'instagram_info',
					'type' => 'custom_html',
					'callback' => array($this, 'meta_feed_instagram_info'),
				),
				array(
					'id' => $this->meta_prefix.'linkedin_info',
					'type' => 'custom_html',
					'callback' => array($this, 'meta_feed_linkedin_info'),
				),
				array(
					'id' => $this->meta_prefix.'rss_info',
					'type' => 'custom_html',
					'callback' => array($this, 'meta_feed_rss_info'),
				),
				array(
					'id' => $this->meta_prefix.'twitter_info',
					'type' => 'custom_html',
					'callback' => array($this, 'meta_feed_twitter_info'),
				),
				array(
					'name' => __("Client ID", 'lang_social_feed'),
					'id' => $this->meta_prefix.'instagram_client_id',
					'type' => 'text',
					'attributes' => array(
						'condition_type' => 'show_this_if',
						'condition_selector' => $this->meta_prefix.'type',
						'condition_value' => 'instagram',
					),
				),
				array(
					'name' => __("Access Token", 'lang_social_feed'),
					'id' => $this->meta_prefix.'instagram_access_token',
					'type' => 'text',
					'attributes' => array(
						'condition_type' => 'show_this_if',
						'condition_selector' => $this->meta_prefix.'type',
						'condition_value' => 'instagram',
					),
				),
				array(
					'id' => $this->meta_prefix.'instagram_access_token_info',
					'type' => 'custom_html',
					'callback' => array($this, 'meta_feed_instagram_access_token_info'),
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

				$actions['social_feed_action_hide'] = "<a href='#id_".$post_id."' class='social_feed_post_action social_feed_action_hide' confirm_text='".__("Are you sure?", 'lang_social_feed')."'>".__("Hide", 'lang_social_feed')."</a>"; //draft

				if($post_username != $feed_name)
				{
					$actions['social_feed_action_ignore'] = "<a href='#id_".$post_id."' class='social_feed_post_action social_feed_action_ignore' confirm_text='".sprintf(__("Are you sure? This will make all future posts by %s to be ignored aswell!"), $post_username)."'>".__("Ignore Future Posts", 'lang_social_feed')."</a>"; //pending
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
			$error_text = __("I could not hide the post for you. If the problem persists, please contact an admin", 'lang_social_feed');
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

		header('Content-Type: application/json');
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
			$error_text = __("I could not ignore the post for you. If the problem persists, please contact an admin", 'lang_social_feed');
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

		header('Content-Type: application/json');
		echo json_encode($result);
		die();
	}
	#########################

	//LinkedIn Auth
	#########################
	function init_linkedin_auth()
	{
		$this->client_id = get_option('setting_linkedin_api_id');
		$this->auth_options = get_option('option_linkedin_authkey');
		$this->settings_url = admin_url('options-general.php?page=settings_mf_base');
	}

	function get_access_token_button()
	{
		$this->check_token_life();

		$_SESSION['state'] = $state = substr(md5(rand()), 0, 7);

		$params = array(
			'response_type' => 'code',
			'client_id'     => $this->client_id,
			'state'         => $state,
			'redirect_uri'  => $this->settings_url,
		);

		if($this->auth_options)
		{
			$authorize_string = __("Generate new Access Token", 'lang_social_feed');
			$authorization_message = "<p>".$this->get_auth_expiration_string($this->auth_options['expires_in'])."</p>";
		}

		else
		{
			$authorize_string = __("Generate Access Token", 'lang_social_feed');
			$authorization_message = "<p>".__("You must authorize in order to use the API", 'lang_social_feed')."</p>";
		}

		$out = "<a href='https://www.linkedin.com/uas/oauth2/authorization?".http_build_query($params)."' class='button-secondary'>"
			.$authorize_string
		."</a>"
		.$authorization_message;

		return $out;
	}

	function check_token_life()
	{
		$this->init_linkedin_auth();

		$this->token_life = intval($this->auth_options['expires_in']) - strtotime(date('Y-m-d H:m:s'));

		if($this->token_life < 0)
		{
			$this->token_life = false;

			if(get_option('setting_linkedin_email_when_expired') == 'yes' && !get_option('option_linkedin_emailed'))
			{
				$this->email_when_expired();

				update_option('option_linkedin_emailed', 1);
			}

			else
			{
				//add_action('admin_notices', '');
			}
		}

		return $this->token_life;
	}

	function email_when_expired()
	{
		$mail_to = get_bloginfo('admin_email');
		$mail_subject = "[".get_bloginfo('name')."] ".__("LinkedIn Access Token has Expired", 'lang_social_feed');
		$mail_content = sprintf(__("Please generate a new Access Token for LinkedIn %sHere%s", 'lang_social_feed'), "<a href='".$this->settings_url."#settings_social_feed_linkedin'>", "</a>");

		$sent = send_email(array('to' => $mail_to, 'subject' => $mail_subject, 'content' => $mail_content));
	}

	function get_auth_expiration_string($time)
	{
		$out = "";

		if($this->token_life)
		{
			$datetime = new DateTime('@'.$this->token_life, new DateTimeZone('UTC'));
			$date = new DateTime();
			$times = array(
				'days' => $datetime->format('z'),
				'hours' => $datetime->format('G'),
			);
			$date->modify('+'.$times['days'].' days');

			if($times['days'] < 10)
			{
				$out .= "<i class='fa fa-warning yellow display_warning'></i> ";
			}

			$out .= sprintf(
				__("Expires in %s days, %s hours", 'lang_social_feed'),
				$times['days'],
				$times['hours']
			);
		}

		else
		{
			$out .= __("The Access Token has expired. Please generate a new", 'lang_social_feed');
		}

		return $out;
	}

	function get_access_token($code)
	{
		$this->init_linkedin_auth();

		$params = array(
			'grant_type'    => 'authorization_code',
			'client_id'     => $this->client_id,
			'client_secret' => get_option('setting_linkedin_api_secret'),
			'code'          => $code,
			'redirect_uri'  => $this->settings_url,
		);

		$url = "https://www.linkedin.com/uas/oauth2/accessToken?".http_build_query($params);
		$result = wp_remote_retrieve_body(wp_remote_get($url));
		$json = json_decode($result);

		if(!isset($json->access_token) || 5 >= strlen($json->access_token))
		{
			do_log(__("I did not recieve an access token", 'lang_social_feed')." (".var_export($json, true).")");

			return false;
		}

		else
		{
			return $json;
		}
	}

	function check_access_token()
	{
		global $done_text, $error_text;

		if(isset($_GET['code']))
		{
			$this->init_linkedin_auth();

			$token = $this->get_access_token($_GET['code']);

			if(false === $token)
			{
				$error_text = __("I could not update the Access Token for you", 'lang_social_feed');
			}

			else
			{
				$end_date = time() + $token->expires_in;

				$_SESSION['access_token'] = $token->access_token;
				$_SESSION['expires_in'] = $token->expires_in;
				$_SESSION['expires_at'] = $end_date;

				$this->auth_options = $auth_options = array(
					'access_token' => $token->access_token,
					'expires_in' => $end_date
				);

				update_option('option_linkedin_authkey', $auth_options);
				delete_option('option_linkedin_emailed');

				$done_text = __("I updated the Access Token for you", 'lang_social_feed');
			}

			return get_notification()
			."<script>location.hash = 'settings_social_feed_linkedin';</script>";

		}

		/*else if(isset($_GET['new_token'])){}*/
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
		switch($this->type)
		{
			case 'facebook':
				$this->facebook_api_id = get_option_or_default('setting_facebook_api_id', '218056055327780');
				$this->facebook_api_secret = get_option_or_default('setting_facebook_api_secret', 'b00ccbc6513724fafca0ff41685d735b');
			break;

			/*case 'instagram':
				$this->instagram_api_token = get_option('setting_instagram_api_token');
			break;*/

			case 'linkedin':
				$this->linkedin_api_id = get_option('setting_linkedin_api_id');
				$this->linkedin_api_secret = get_option('setting_linkedin_api_secret');
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
					$this->instagram_api_token = get_post_meta($this->id, $this->meta_prefix.'instagram_access_token', true);

					$this->fetch_instagram();
				break;

				case 'linkedin':
					$this->fetch_linkedin();
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
			//do_log("FB: ".htmlspecialchars(var_export($json['data'], true)));

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

				/*array(
					'id' => '[id]_[id]',
					'message' => '[text]',
					'full_picture' => '[url]',
					'created_time' => '2018-04-27T12:42:07+0000'
				)*/

				$post_id = $post['id'];
				$arr_post_id = explode("_", $post_id);

				//$post_author = isset($post['from']) ? $post['from']['id'] : 0;

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
					//'is_owner' => ( == $post_author),
					'is_owner' => (isset($post['from']) ? $post['from']['id'] == $arr_post_id[0] : true),
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

		else
		{
			$url = "https://api.instagram.com/v1/users/self/?access_token=".$this->instagram_api_token;

			$result = wp_remote_retrieve_body(wp_remote_get($url));
			$json = json_decode($result);

			if(isset($json->data->id))
			{
				$filter = "users/".$json->data->id."/media/recent";

				delete_post_meta($this->id, $this->meta_prefix.'error');
			}

			else
			{
				update_post_meta($this->id, $this->meta_prefix.'error', "<a href='".$url."'>".__("The JSON I got back was not correct", 'lang_social_feed')."</a>");
			}
		}

		/*else if(substr($this->search, 0, 1) == "@")
		{
			$url = "https://api.instagram.com/v1/users/search?q=".substr($this->search, 1)."&access_token=".$this->instagram_api_token;

			$result = wp_remote_retrieve_body(wp_remote_get($url));
			$json = json_decode($result);

			if(isset($json->data))
			{
				foreach($json->data as $user)
				{
					$filter = "users/".$user->id."/media/recent";

					break;
				}

				delete_post_meta($this->id, $this->meta_prefix.'error');
			}

			else
			{
				update_post_meta($this->id, $this->meta_prefix.'error', "<a href='".$url."'>".__("The JSON I got back was not correct", 'lang_social_feed')."</a>");
			}
		}

		else
		{
			$filter = "users/self/feed";
		}*/

		if($filter != '')
		{
			$url = "https://api.instagram.com/v1/".$filter."?access_token=".$this->instagram_api_token; //."&count=".$instagram_amount

			$result = wp_remote_retrieve_body(wp_remote_get($url));
			$json = json_decode($result);

			if(isset($json->data))
			{
				foreach($json->data as $post)
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

	function linkedin_api_call($path, $key, $params = array())
	{
		$default_params = array(
			'format' => 'json',
			'oauth2_access_token' => $this->auth_options['access_token']
		);

		$url = "https://api.linkedin.com/v1/companies/".$path."?".http_build_query(array_merge($default_params, $params));
		$result = wp_remote_retrieve_body(wp_remote_get($url));
		$json = json_decode($result, true);

		if(false === $json || !isset($json[$key]) || empty($json[$key]))
		{
			return false;
		}

		return $json[$key];
	}

	function fetch_linkedin()
	{
		$feed_limit = 20;

		if($this->check_token_life())
		{
			$results = $this->linkedin_api_call($this->search."/updates", 'values', array('count' => $feed_limit, 'event-type' => 'status-update'));

			if(isset($results) && is_array($results))
			{
				foreach($results as $post)
				{
					/*array ( 'isCommentable' => true, 'isLikable' => true, 'isLiked' => false, 'numLikes' => 0, 'timestamp' => [timestamp], 'updateComments' => array ( '_total' => 0, ), 'updateContent' => array ( 'company' => array ( 'id' => [id], 'name' => '[text]', ), 'companyStatusUpdate' => array ( 'share' => array ( 'comment' => '[text]', 'id' => 's[id]', 'source' => array ( 'serviceProvider' => array ( 'name' => 'LINKEDIN', ), 'serviceProviderShareId' => 's[id]', ), 'timestamp' => [timestamp], 'visibility' => array ( 'code' => 'anyone', ), ), ), ), 'updateKey' => 'UPDATE-c18432292-[id]', 'updateType' => 'CMPY', )*/

					//do_log("LinkedIn: ".var_export($post, true));

					$post_share = $post['updateContent']['companyStatusUpdate']['share'];

					//do_log("LinkedIn Timestamp: ".$post_share['timestamp']." -> ".date("Y-m-d H:i:s", ($post_share['timestamp'] / 1000)));

					$post_image_url = $post_image_link = '';

					if(array_key_exists('content', $post_share))
					{
						$shared_content = $post_share['content'];

						if(array_key_exists('submittedImageUrl', $shared_content) && 'https://static.licdn.com/scds/common/u/img/spacer.gif' !== $shared_content['submittedImageUrl'])
						{
							$post_image_url = $shared_content['submittedImageUrl'];
							$post_image_link = $shared_content['submittedUrl'];
						}
					}

					// Filter the content for links
					$post_content = preg_replace('!(((f|ht)tp(s)?://)[-a-zA-Z?-??-?()0-9@:%_+.~#?&;//=]+)!i', "<a href='$1'>$1</a>", $post_share['comment']);

					$post_pieces = explode('-', $post['updateKey']);
					$post_id = end($post_pieces);

					$this->arr_posts[] = array(
						'type' => $this->type,
						'id' => $post_id,
						'name' => $post['updateContent']['company']['name'],
						'text' => $post_content,
						//'link' => "//www.linkedin.com/company/".$this->search,
						'link' => "//linkedin.com/nhome/updates?topic=".$post_id,
						'image' => $post_image_url,
						'created' => date("Y-m-d H:i:s", ($post_share['timestamp'] / 1000)),
					);
				}

				delete_post_meta($this->id, $this->meta_prefix.'error');
			}

			else
			{
				update_post_meta($this->id, $this->meta_prefix.'error', __("LinkedIn", 'lang_social_feed').": ".var_export($results, true));
			}
		}

		else
		{
			update_post_meta($this->id, $this->meta_prefix.'error', __("LinkedIn", 'lang_social_feed').": ".__("Token has expired", 'lang_social_feed'));
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
				update_post_meta($this->id, $this->meta_prefix.'error', __("Twitter", 'lang_social_feed').": ".var_export($e->getMessage(), true));
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
				update_post_meta($this->id, $this->meta_prefix.'error', __("Twitter", 'lang_social_feed').": ".var_export($e->getMessage(), true));
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
				update_post_meta($this->id, $this->meta_prefix.'error', __("Twitter", 'lang_social_feed').": ".var_export($e->getMessage(), true));
			}
		}

		if(isset($results) && is_array($results))
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

	function set_date_modified()
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_modified = NOW() WHERE ID = '%d' AND post_type = 'mf_social_feed'", $this->id));
	}

	function insert_posts()
	{
		global $wpdb;

		//do_log("Social Feed - Posts: ".htmlspecialchars(var_export($this->arr_posts, true)));

		foreach($this->arr_posts as $post)
		{
			$post_title = (isset($post['title']) && $post['title'] != '' ? $post['title'] : $post['type']." ".$post['id']);
			$post_name = sanitize_title_with_dashes(sanitize_title($post_title));

			$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'mf_social_feed_post' AND (post_title = %s OR post_name = %s) AND post_excerpt = '%d' LIMIT 0, 1", $post_title, $post_name, $this->id));

			if($wpdb->num_rows == 0)
			{
				if(!isset($post['is_owner']) || $post['is_owner'] == true)
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

					$post_id = wp_insert_post($post_data);

					if(isset($post['likes']) && $post['likes'] > 0)
					{
						update_post_meta($post_id, $this->meta_prefix.'likes', $post['likes']);
					}

					if(isset($post['comments']) && $post['comments'] > 0)
					{
						update_post_meta($post_id, $this->meta_prefix.'comments', $post['comments']);
					}
				}
			}

			else
			{
				foreach($result as $r)
				{
					if(!isset($post['is_owner']) || $post['is_owner'] == true)
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

						wp_update_post($post_data);

						if(isset($post['likes']) && $post['likes'] > 0)
						{
							update_post_meta($r->ID, $this->meta_prefix.'likes', $post['likes']);
						}

						if(isset($post['comments']) && $post['comments'] > 0)
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
			//$obj_social_feed = new mf_social_feed();

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

					$post_service = get_post_meta($post_id, $this->meta_prefix.'service', true);
					$post_feed = get_post_meta($post_id, $this->meta_prefix.'feed_id', true);
					$post_username = get_post_meta($post_id, $this->meta_prefix.'name', true);
					$post_image = get_post_meta($post_id, $this->meta_prefix.'image', true);
					$post_link = get_post_meta($post_id, $this->meta_prefix.'link', true);

					if($data['likes'] == 'yes')
					{
						$post_likes = get_post_meta($post_id, $this->meta_prefix.'likes', true);
						$post_comments = get_post_meta($post_id, $this->meta_prefix.'comments', true);
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
							'content' => apply_filters('the_content', $post_content),
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
			//'social_border' => 'yes',
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
						//($instance['social_border'] == 'yes' ? " show_border" : '')
						echo ($instance['social_read_more'] == 'yes' ? " show_read_more" : '');
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
		//$instance['social_border'] = sanitize_text_field($new_instance['social_border']);
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

			if($instance['social_text'] == 'yes')
			{
				//show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('social_border'), 'text' => __("Display Border", 'lang_social_feed'), 'value' => $instance['social_border']))
				echo "<div class='flex_flow'>"
					.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('social_read_more'), 'text' => __("Display Read More", 'lang_social_feed'), 'value' => $instance['social_read_more']))
					.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('social_likes'), 'text' => __("Display Likes", 'lang_social_feed'), 'value' => $instance['social_likes']))
				."</div>";
			}

		echo "</div>";
	}
}