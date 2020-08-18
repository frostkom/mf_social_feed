<?php

class mf_social_feed
{
	function __construct($id = 0)
	{
		$this->id = $id > 0 ? $id : 0;
		$this->type = $this->search = "";

		$this->post_type = 'mf_social_feed';
		$this->post_type_post = 'mf_social_feed_post';
		$this->meta_prefix = $this->post_type.'_';

		$this->sync_settings = array(
			'setting_social_api_url',
			'setting_instagram_client_id',
		);
	}

	function cron_base()
	{
		global $wpdb;

		$obj_cron = new mf_cron();
		$obj_cron->start(__CLASS__);

		if($obj_cron->is_running == false)
		{
			$setting_social_time_limit = get_option_or_default('setting_social_time_limit', 30);

			$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = %s AND post_modified < DATE_SUB(NOW(), INTERVAL ".$setting_social_time_limit." MINUTE) ORDER BY RAND()", $this->post_type, 'publish'));

			foreach($result as $r)
			{
				$this->set_id($r->ID);
				$this->fetch_feed();
			}
		}

		$obj_cron->end();
	}

	function cron_sync($json)
	{
		global $wpdb;

		if(isset($json['settings']) && count($json['settings']) > 0)
		{
			foreach($this->sync_settings as $setting_key)
			{
				if(isset($json['settings'][$setting_key]) && $json['settings'][$setting_key] != '')
				{
					if(get_option($setting_key) == '')
					{
						update_option($setting_key, $json['settings'][$setting_key]);
					}
				}
			}
		}
	}

	function api_sync($json_output, $data = array())
	{
		if(!isset($json_output['settings']))
		{
			$json_output['settings'] = array();
		}

		foreach($this->sync_settings as $setting_key)
		{
			$setting_value = get_option($setting_key);

			if($setting_value != '')
			{
				$json_output['settings'][$setting_key] = $setting_value;
			}
		}

		return $json_output;
	}

	function get_message_error_amount($data = array())
	{
		global $wpdb;

		$out = "";

		$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND meta_key = %s AND meta_value != ''", $this->post_type, $this->meta_prefix.'error'));
		$rows = $wpdb->num_rows;

		if($rows > 0)
		{
			$out = "&nbsp;<span class='update-plugins' title='".__("Errors", 'lang_social_feed')."'>
				<span>".$rows."</span>
			</span>";
		}

		return $out;
	}

	function init()
	{
		//$count_message = $this->get_message_error_amount();

		$labels = array(
			'name' => _x(__("Social Feeds", 'lang_social_feed'), 'post type general name'),
			'singular_name' => _x(__("Social Feed", 'lang_social_feed'), 'post type singular name'),
			'menu_name' => __("Social Feeds", 'lang_social_feed'), //.$count_message // This will display HTML code in the menu. It has to be added with JS instead
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

		register_post_type($this->post_type, $args);

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

		register_post_type($this->post_type_post, $args);
	}

	function settings_social_feed()
	{
		$options_area_orig = $options_area = __FUNCTION__;

		$data_temp = array(
			'post_type' => $this->post_type,
			'meta' => array(
				$this->meta_prefix.'type' => 'facebook',
			),
		);

		$has_facebook = does_post_exists($data_temp);

		$data_temp = array(
			'post_type' => $this->post_type,
			'meta' => array(
				$this->meta_prefix.'type' => 'instagram',
			),
		);

		$has_instagram = does_post_exists($data_temp);

		//Generic
		############################
		add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = array();

		if($has_facebook || $has_instagram)
		{
			$arr_settings['setting_social_api_url'] = __("API URL", 'lang_social_feed');
		}

		$arr_settings['setting_social_keep_posts'] = __("Keep Posts", 'lang_social_feed');

		$arr_settings['setting_social_time_limit'] = __("Interval to Fetch New", 'lang_social_feed');

		if(!is_plugin_active("mf_widget_logic_select/index.php") || apply_filters('get_widget_search', 'social-feed-widget') > 0)
		{
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
		}

		$arr_settings['setting_social_debug'] = __("Debug", 'lang_social_feed');

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
		############################

		//Facebook
		############################
		/*if($has_facebook)
		{
			$options_area = $options_area_orig."_facebook";

			add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

			$arr_settings = array();

			show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
		}*/
		############################

		//Instagram
		############################
		if($has_instagram)
		{
			$options_area = $options_area_orig."_instagram";

			add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

			$arr_settings = array(
				'setting_instagram_client_id' => __("Client ID", 'lang_social_feed'),
			);

			show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
		}
		############################

		//LinkedIn
		############################
		$data_temp = array(
			'post_type' => $this->post_type,
			'meta' => array(
				$this->meta_prefix.'type' => 'linkedin',
			),
		);

		if(does_post_exists($data_temp))
		{
			$options_area = $options_area_orig."_linkedin";

			add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

			$arr_settings = array();
			$arr_settings['setting_linkedin_api_id'] = __("Client ID", 'lang_social_feed');
			$arr_settings['setting_linkedin_api_secret'] = __("Client Secret", 'lang_social_feed');

			if(get_option('setting_linkedin_api_id') != '' && get_option('setting_linkedin_api_secret') != '')
			{
				$arr_settings['setting_linkedin_redirect_url'] = __("Redirect URL", 'lang_social_feed');
				$arr_settings['setting_linkedin_authorize'] = __("Authorize", 'lang_social_feed');
				$arr_settings['setting_linkedin_email_when_expired'] = __("Email when Expired", 'lang_social_feed');
			}

			show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
		}
		############################

		//Twitter
		############################
		$data_temp = array(
			'post_type' => $this->post_type,
			'meta' => array(
				$this->meta_prefix.'type' => 'twitter',
			),
		);

		if(does_post_exists($data_temp))
		{
			$options_area = $options_area_orig."_twitter";

			add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

			$arr_settings = array(
				'setting_twitter_api_key' => __("Key", 'lang_social_feed'),
				'setting_twitter_api_secret' => __("Secret", 'lang_social_feed'),
				'setting_twitter_api_token' => __("Access Token", 'lang_social_feed'),
				'setting_twitter_api_token_secret' => __("Access Token Secret", 'lang_social_feed'),
			);

			show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
		}
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

		echo settings_header($setting_key, __("Social Feeds", 'lang_social_feed')." - Facebook");
	}

	function settings_social_feed_instagram_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Social Feeds", 'lang_social_feed')." - Instagram");
	}

	function settings_social_feed_linkedin_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Social Feeds", 'lang_social_feed')." - LinkedIn");
	}

	function settings_social_feed_twitter_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Social Feeds", 'lang_social_feed')." - Twitter");
	}

	function setting_social_api_url_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		settings_save_site_wide($setting_key);
		$option = get_site_option($setting_key, get_option($setting_key));

		echo show_textfield(array('type' => 'url', 'name' => $setting_key, 'value' => $option, 'placeholder' => get_site_url()));
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
				return 15;
			break;
		}
	}

	function setting_social_keep_posts_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option_or_default($setting_key, 12);

		echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option, 'xtra' => "min='1' max='120'", 'suffix' => __("months", 'lang_social_feed')));
	}

	function setting_social_time_limit_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option_or_default($setting_key, 30);

		$setting_min = $this->get_setting_min();

		$option = max($option, $setting_min);

		echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option, 'xtra' => "min='".$setting_min."' max='1440'", 'suffix' => __("min", 'lang_social_feed')." (".__("Between each API request", 'lang_social_feed').")"));
	}

	function setting_social_reload_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		if($option > 0)
		{
			$setting_min = $this->get_setting_min() / 2;

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

	function setting_social_debug_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option_or_default($setting_key, 'no');

		echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
	}

	function setting_instagram_client_id_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		settings_save_site_wide($setting_key);
		$option = get_site_option($setting_key, get_option($setting_key));

		echo show_textfield(array('name' => $setting_key, 'value' => $option));
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
				<li>".__("Create a new app if you do not have one already and edit the app", 'lang_social_feed')."</li>
				<li>".sprintf(__("Copy %s and %s to these fields", 'lang_social_feed'), "Client ID", "Client Secret")."</li>
				<li>".sprintf(__("Make sure that %s is checked", 'lang_social_feed'), "<em>rw_company_admin</em>")."</li>
			</ol>";
		}

		else
		{
			$description = '';
		}

		echo show_password_field(array('name' => $setting_key, 'value' => $option, 'description' => $description));
	}

	function setting_linkedin_redirect_url_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		$this->init_linkedin_auth();
		$option = $this->settings_url;

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'xtra' => "readonly onclick='this.select()'", 'description' => sprintf(__("Add this URL to your Apps %s", 'lang_social_feed'), "<a href='//linkedin.com/developer/apps/'>Authorized Redirect URLs</a>")));
	}

	function setting_linkedin_authorize_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo $this->check_access_token()
		.$this->get_access_token_button();
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

	function admin_init()
	{
		global $pagenow;

		switch($pagenow)
		{
			case 'admin.php':
				if(isset($_GET['page']) && isset($_GET['access_token']))
				{
					switch($_GET['page'])
					{
						case 'cff-top':
							$this->get_api_credentials('facebook');

							mf_redirect($this->facebook_redirect_url."&access_token=".check_var('access_token'));
						break;

						case 'sb-instagram-feed':
							$this->get_api_credentials('instagram');

							mf_redirect($this->instagram_redirect_url."&access_token=".check_var('access_token'));
						break;
					}
				}
			break;

			case 'edit.php':
				$plugin_include_url = plugin_dir_url(__FILE__);
				$plugin_version = get_plugin_version(__FILE__);

				switch(check_var('post_type'))
				{
					case $this->post_type:
						mf_enqueue_style('style_social_feed', $plugin_include_url."style.php", $plugin_version); // Just for icon colors
					break;

					case $this->post_type_post:
						mf_enqueue_style('style_social_feed', $plugin_include_url."style.php", $plugin_version); // Just for icon colors
						mf_enqueue_script('script_social_feed', $plugin_include_url."script_wp.js", array('ajax_url' => admin_url('admin-ajax.php')), $plugin_version);
					break;
				}
			break;
		}
	}

	function admin_menu()
	{
		/*$menu_root = 'mf_social_feed/';
		$menu_start = "edit.php?post_type=".$this->post_type;
		$menu_capability = override_capability(array('page' => $menu_start, 'default' => 'edit_pages'));

		$menu_title = __("Posts", 'lang_social_feed');
		add_submenu_page($menu_root, $menu_title, $menu_title, $menu_capability, "edit.php?post_type=".$this->post_type_post);*/

		$menu_capability = 'edit_posts';
		$menu_title = __("Posts", 'lang_social_feed');
		add_submenu_page('cff-top', $menu_title, $menu_title, $menu_capability, 'cff-top', 'cff_settings_page');
		add_submenu_page('sb-instagram-feed', $menu_title, $menu_title, $menu_capability, 'sb-instagram-feed', 'sb_instagram_settings_page');
	}

	function meta_feed_facebook_info()
	{
		return "<p condition_type='show_this_if' condition_selector='".$this->meta_prefix."type' condition_value='facebook'>".__("Posts can only be fetched from Facebook Pages, not personal Profiles", 'lang_social_feed')."</p>";
	}

	function meta_feed_facebook_access_token_info()
	{
		global $post;

		$post_id = $post->ID;

		$edit_url = admin_url("post.php?post=".$post_id."&action=edit");

		$facebook_access_token = get_post_meta($post_id, $this->meta_prefix.'facebook_access_token', true);

		$out = "<div condition_type='show_this_if' condition_selector='".$this->meta_prefix."type' condition_value='facebook'>";

			if($facebook_access_token != '')
			{
				$out .= "<strong><i class='fa fa-check green'></i> ".__("All Done!", 'lang_social_feed')."</strong>";
			}

			else
			{
				$this->get_api_credentials('facebook');

				if($this->setting_social_api_url != '')
				{
					if(!session_id())
					{
						@session_start();
					}

					$_SESSION['sesCallbackURL'] = $edit_url;
					update_option('option_social_callback_url', $edit_url, 'no');

					$out .= "<strong>".sprintf(__("Go to %s and log in", 'lang_social_feed'), "<a href='".$this->facebook_authorize_url."'>Facebook</a>")."</strong>";
				}

				else
				{
					$out .= "<strong>".sprintf(__("Go to %sSettings%s and add an API URL", 'lang_social_feed'), "<a href='".admin_url("options-general.php?page=settings_mf_base#settings_social_feed")."'>", "</a>")."</strong>";
				}
			}

		$out .= "</div>";

		return $out;
	}

	function meta_feed_instagram_info()
	{
		return "<p condition_type='show_this_if' condition_selector='".$this->meta_prefix."type' condition_value='instagram'>".__("Posts can be fetched from @users", 'lang_social_feed')."</p>";
	}

	function meta_feed_instagram_access_token_info()
	{
		global $post;

		$post_id = $post->ID;

		$instagram_action = check_var('instagram_action');

		switch($instagram_action)
		{
			case 'set_business_name':
				update_post_meta($post_id, $this->meta_prefix.'instagram_id', check_var('id'));
				update_post_meta($post_id, $this->meta_prefix.'instagram_name', check_var('name'));
				update_post_meta($post_id, $this->meta_prefix.'instagram_username', check_var('username'));
				update_post_meta($post_id, $this->meta_prefix.'instagram_profile_picture', check_var('profile_picture'));
			break;
		}

		$edit_url = admin_url("post.php?post=".$post_id."&action=edit");

		$instagram_access_token = get_post_meta($post_id, $this->meta_prefix.'instagram_access_token', true);

		$out = "<div condition_type='show_this_if' condition_selector='".$this->meta_prefix."type' condition_value='instagram'>";

			if($instagram_access_token != '')
			{
				$instagram_id = get_post_meta($post_id, $this->meta_prefix.'instagram_id', true);

				if(!($instagram_id > 0))
				{
					$this->instagram_access_token = get_post_meta($post_id, $this->meta_prefix.'instagram_access_token', true);

					$result_id = $this->get_instagram_business_id();

					if(is_array($result_id) && count($result_id) > 0)
					{
						$out .= "<h3>".__("Choose an Account", 'lang_social_feed')."</h3>
						<ul>";

							foreach($result_id as $instagram_id)
							{
								$result_name = $this->get_instagram_business_name($instagram_id);

								if(is_array($result_name) && isset($result_name['name']))
								{
									$out .= "<li>
										<a href='".$edit_url."&instagram_action=set_business_name&id=".$instagram_id."&name=".$result_name['name']."&username=".$result_name['username']."&profile_picture=".$result_name['profile_picture']."'>"
											//."<img src='".$result_name['profile_picture']."'> "
											.$result_name['name']." (".$result_name['username'].")"
										."</a>
									</li>";
								}

								else
								{
									$out .= "<li>
										<a href='".$result_name."'>"
											.__("I could not get the name for the business account", 'lang_social_feed')
										."</a>
									</li>";
								}
							}

						$out .= "</ul>";
					}

					else
					{
						$out .= "<strong><i class='fa fa-times red'></i> <a href='".$result_id."'>".__("There are no business accounts connected to the login that you used", 'lang_social_feed')."</a></strong>";
					}
				}

				else
				{
					$instagram_name = get_post_meta($post_id, $this->meta_prefix.'instagram_name', true);

					if($instagram_name == '')
					{
						$instagram_username = get_post_meta($post_id, $this->meta_prefix.'instagram_username', true);
						$instagram_profile_picture = get_post_meta($post_id, $this->meta_prefix.'instagram_profile_picture', true);
					}

					else
					{
						$out .= "<strong><i class='fa fa-check green'></i> ".__("All Done!", 'lang_social_feed')."</strong>";
					}
				}
			}

			else
			{
				$this->get_api_credentials('instagram');

				if($this->setting_social_api_url == '')
				{
					$out .= "<strong>".sprintf(__("Go to %sSettings%s and add an API URL", 'lang_social_feed'), "<a href='".admin_url("options-general.php?page=settings_mf_base#settings_social_feed")."'>", "</a>")."</strong>";
				}

				else if($this->instagram_client_id == '')
				{
					$out .= "<strong>".sprintf(__("Go to %sSettings%s and add a %s", 'lang_social_feed'), "<a href='".admin_url("options-general.php?page=settings_mf_base#settings_social_feed_instagram")."'>", "</a>", __("Client ID", 'lang_social_feed'))."</strong>";
				}

				else
				{
					if(!session_id())
					{
						@session_start();
					}

					$_SESSION['sesCallbackURL'] = $edit_url;
					update_option('option_social_callback_url', $edit_url, 'no');

					$out .= "<strong><a href='".$this->instagram_authorize_url."'>".__("Authorize Here", 'lang_social_feed')."</a></strong>";
				}
			}

		$out .= "</div>";

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

	function get_post_icon($post_service)
	{
		switch($post_service)
		{
			case 'rss':
				$post_icon = "fa fa-rss";
			break;

			default:
				$post_icon = "fab fa-".$post_service;
			break;
		}

		return $post_icon;
	}

	function meta_post_info()
	{
		global $post;

		$post_id = $post->ID;
		$post_date = $post->post_date;

		$post_feed = get_post_meta($post_id, $this->meta_prefix.'feed_id', true);
		//$post_feed = $post->post_parent;

		$post_service = get_post_meta($post_id, $this->meta_prefix.'service', true);
		$post_username = get_post_meta($post_id, $this->meta_prefix.'name', true);
		$post_image = get_post_meta($post_id, $this->meta_prefix.'image', true);
		$post_link = get_post_meta($post_id, $this->meta_prefix.'link', true);

		$out = "<ul id='".$this->meta_prefix."info'>"
			."<li><i class='".$this->get_post_icon($post_service)."'></i> ".get_post_title($post_feed)."</li>"
			."<li><a href='".$post_link."'>@".$post_username."</a></li>"
			.($post_image != '' ? "<li><img src='".$post_image."'></li>" : "")
			."<li>".format_date($post_date)."</li>"
		."</ul>";

		return $out;
	}

	function get_social_types_for_select()
	{
		$arr_data = array();

		$arr_data['facebook'] = "Facebook";
		$arr_data['instagram'] = "Instagram";

		if($this->check_token_life())
		{
			$arr_data['linkedin'] = "LinkedIn";
		}

		$arr_data['rss'] = "RSS";
		$arr_data['twitter'] = "Twitter";

		return $arr_data;
	}

	function rwmb_meta_boxes($meta_boxes)
	{
		global $wpdb;

		$arr_data_social_types = $this->get_social_types_for_select();

		$default_type = "";

		$post_id = check_var('post');

		if($post_id > 0)
		{
			$facebook_access_token = check_var('facebook_access_token');

			if($facebook_access_token != '')
			{
				update_post_meta($post_id, $this->meta_prefix.'facebook_access_token', $facebook_access_token);
			}

			$instagram_access_token = check_var('instagram_access_token');

			if($instagram_access_token != '')
			{
				update_post_meta($post_id, $this->meta_prefix.'instagram_access_token', $instagram_access_token);
			}
		}

		else
		{
			$last_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s ORDER BY post_modified DESC LIMIT 0, 1", $this->post_type));

			if($last_id > 0)
			{
				$default_type = get_post_meta($last_id, $this->meta_prefix.'type', true);
			}
		}

		$meta_boxes[] = array(
			'id' => $this->meta_prefix.'settings',
			'title' => __("Settings", 'lang_social_feed'),
			'post_types' => array($this->post_type),
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
				/*array(
					'name' => __("Page ID", 'lang_social_feed'),
					'id' => $this->meta_prefix.'facebook_page_id',
					'type' => 'text',
					'attributes' => array(
						'condition_type' => 'show_this_if',
						'condition_selector' => $this->meta_prefix.'type',
						'condition_value' => 'facebook',
					),
				),*/
				array(
					'name' => __("Access Token", 'lang_social_feed'),
					'id' => $this->meta_prefix.'facebook_access_token',
					'type' => 'text',
					'attributes' => array(
						'condition_type' => 'show_this_if',
						'condition_selector' => $this->meta_prefix.'type',
						'condition_value' => 'facebook',
					),
				),
				array(
					'id' => $this->meta_prefix.'facebook_access_token_info',
					'type' => 'custom_html',
					'callback' => array($this, 'meta_feed_facebook_access_token_info'),
				),
				array(
					'name' => __("Include", 'lang_social_feed'),
					'id' => $this->meta_prefix.'facebook_include',
					'type' => 'select',
					'options' => array(
						'other' => __("Others", 'lang_social_feed'),
					),
					'multiple' => true,
					'attributes' => array(
						'condition_type' => 'show_this_if',
						'condition_selector' => $this->meta_prefix.'type',
						'condition_value' => 'facebook',
					),
				),
				array(
					'id' => $this->meta_prefix.'instagram_info',
					'type' => 'custom_html',
					'callback' => array($this, 'meta_feed_instagram_info'),
				),
				/*array(
					'name' => __("Client ID", 'lang_social_feed'),
					'id' => $this->meta_prefix.'instagram_client_id',
					'type' => 'text',
					'attributes' => array(
						'condition_type' => 'show_this_if',
						'condition_selector' => $this->meta_prefix.'type',
						'condition_value' => 'instagram',
					),
				),*/
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
					'name' => __("ID", 'lang_social_feed'),
					'id' => $this->meta_prefix.'instagram_id',
					'type' => 'text',
					'attributes' => array(
						'condition_type' => 'show_this_if',
						'condition_selector' => $this->meta_prefix.'type',
						'condition_value' => 'instagram',
					),
				),
				array(
					'name' => __("Name", 'lang_social_feed'),
					'id' => $this->meta_prefix.'instagram_name',
					'type' => 'text',
					'attributes' => array(
						'condition_type' => 'show_this_if',
						'condition_selector' => $this->meta_prefix.'type',
						'condition_value' => 'instagram',
					),
				),
				array(
					'name' => __("Username", 'lang_social_feed'),
					'id' => $this->meta_prefix.'instagram_username',
					'type' => 'text',
					'attributes' => array(
						'condition_type' => 'show_this_if',
						'condition_selector' => $this->meta_prefix.'type',
						'condition_value' => 'instagram',
					),
				),
				array(
					'name' => __("Profile Picture", 'lang_social_feed'),
					'id' => $this->meta_prefix.'instagram_profile_picture',
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
					'name' => __("Include", 'lang_social_feed'),
					'id' => $this->meta_prefix.'twitter_include',
					'type' => 'select',
					'options' => array(
						'other' => __("Others", 'lang_social_feed'),
						'reply' => __("Replies", 'lang_social_feed'),
						'retweet' => "Retweets",
					),
					'multiple' => true,
					'attributes' => array(
						'condition_type' => 'show_this_if',
						'condition_selector' => $this->meta_prefix.'type',
						'condition_value' => 'twitter',
					),
				),
			)
		);

		$meta_boxes[] = array(
			'id' => $this->meta_prefix.'info',
			'title' => __("Information", 'lang_social_feed'),
			'post_types' => array($this->post_type_post),
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

	function restrict_manage_posts()
	{
		global $post_type;

		if($post_type == $this->post_type_post)
		{
			//$strFilterSocialFeed = get_or_set_table_filter(array('key' => 'strFilterSocialFeed', 'save' => true));
			$strFilterSocialFeed = check_var('strFilterSocialFeed');

			$arr_data = array();
			get_post_children(array('post_type' => $this->post_type, 'post_status' => '', 'add_choose_here' => true), $arr_data);

			if(count($arr_data) > 2)
			{
				echo show_select(array('data' => $arr_data, 'name' => 'strFilterSocialFeed', 'value' => $strFilterSocialFeed));
			}
		}
	}

	function pre_get_posts($wp_query)
	{
		global $post_type, $pagenow;

		if($pagenow == 'edit.php' && $post_type == $this->post_type_post)
		{
			//$strFilterSocialFeed = get_or_set_table_filter(array('key' => 'strFilterSocialFeed'));
			$strFilterSocialFeed = check_var('strFilterSocialFeed');

			if($strFilterSocialFeed != '')
			{
				$wp_query->query_vars['meta_query'] = array(
					array(
						'key' => $this->meta_prefix.'feed_id',
						'value' => $strFilterSocialFeed,
						'compare' => '=',
					),
				);
			}
		}
	}

	function count_shortcode_button($count)
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

	function get_shortcode_output($out)
	{
		$arr_data = array();
		get_post_children(array('add_choose_here' => true, 'post_type' => $this->post_type), $arr_data);

		if(count($arr_data) > 1)
		{
			$out .= "<h3>".__("Choose a Social Feed", 'lang_social_feed')."</h3>"
			.show_select(array('data' => $arr_data, 'xtra' => "rel='".$this->post_type." amount=3 filter=group border=no text=yes likes=no'"));
		}

		return $out;
	}

	function column_header($cols)
	{
		global $post_type;

		switch($post_type)
		{
			case $this->post_type:
				unset($cols['date']);

				$cols['type'] = __("Service", 'lang_social_feed');
				$cols['search_for'] = __("Search for", 'lang_social_feed');
				$cols['in_use'] = __("In Use", 'lang_social_feed');
				$cols['amount_of_posts'] = __("Amount", 'lang_social_feed');
			break;

			case $this->post_type_post:
				unset($cols['title']);
				unset($cols['date']);

				$cols['type'] = __("Type", 'lang_social_feed');
				$cols['name'] = __("Username", 'lang_social_feed');
				$cols['text'] = __("Text", 'lang_social_feed');
				$cols['image'] = __("Image", 'lang_social_feed');
				//$cols['post_id'] = __("ID", 'lang_social_feed');
				$cols['info'] = __("Information", 'lang_social_feed');
				$cols['date'] = __("Date", 'lang_social_feed');
			break;
		}

		return $cols;
	}

	function column_cell($col, $id)
	{
		global $wpdb, $post;

		switch($post->post_type)
		{
			case $this->post_type:
				switch($col)
				{
					case 'type':
						$post_meta = get_post_meta($id, $this->meta_prefix.$col, true);

						echo "<i class='".$this->get_post_icon($post_meta)." fa-2x'></i>";
					break;

					case 'search_for':
						$post_meta = get_post_meta($id, $this->meta_prefix.$col, true);
						$service = get_post_meta($id, $this->meta_prefix.'type', true);

						$post_meta_filtered = $this->filter_search_for($post_meta);

						if($post_meta_filtered != '')
						{
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

							$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE ID = '%d' AND post_type = %s AND post_modified < DATE_SUB(NOW(), INTERVAL 1 MINUTE) LIMIT 0, 1", $id, $this->post_type));

							if($wpdb->num_rows > 0)
							{
								$intFeedID = check_var('intFeedID');

								if(isset($_REQUEST['btnFeedFetch']) && $intFeedID > 0 && $intFeedID == $id && wp_verify_nonce($_REQUEST['_wpnonce_feed_fetch'], 'feed_fetch_'.$id))
								{
									$this->set_id($id);
									$this->fetch_feed();
								}

								else
								{
									$fetch_link = "<a href='".wp_nonce_url(admin_url("edit.php?post_type=".$this->post_type."&btnFeedFetch&intFeedID=".$id), 'feed_fetch_'.$id, '_wpnonce_feed_fetch')."'>".__("Fetch", 'lang_social_feed')."</a> | ";
								}
							}

							$post_modified = $wpdb->get_var($wpdb->prepare("SELECT post_modified FROM ".$wpdb->posts." WHERE ID = '%d' AND post_type = %s", $id, $this->post_type));

							echo "<a href='".$feed_url."'>".$post_meta."</a>
							<div class='row-actions'>"
								.$fetch_link
								.__("Fetched", 'lang_social_feed').": ".format_date($post_modified)
							."</div>";
						}
					break;

					case 'in_use':
						$option_widgets = get_option('widget_social-feed-widget');

						if(is_array($option_widgets))
						{
							//echo var_export($option_widgets, true);

							$is_in_use = false;

							foreach($option_widgets as $arr_widget)
							{
								if(isset($arr_widget['social_feeds']) && (count($arr_widget['social_feeds']) == 0 || in_array($id, $arr_widget['social_feeds'])))
								{
									$is_in_use = true;

									break;
								}
							}

							if($is_in_use)
							{
								echo "<i class='fa fa-check green'></i>";
							}

							else
							{
								echo "<i class='fa fa-times red'></i>";
							}
						}
					break;

					case 'amount_of_posts':
						$amount = $this->get_amount($id);

						$post_error = get_post_meta($id, $this->meta_prefix.'error', true);

						if($post_error != '')
						{
							echo "<i class='fa fa-times red fa-2x'></i>
							<div class='row-actions'>".($post_error != '' ? $post_error : __("I got an error when accessing the feed", 'lang_social_feed'))."</div>";
						}

						else if($amount == 0)
						{
							$setting_social_time_limit = get_option_or_default('setting_social_time_limit', 30);

							$result = $wpdb->get_results($wpdb->prepare("SELECT post_date, post_modified FROM ".$wpdb->posts." WHERE post_type = %s AND ID = '%d' LIMIT 0, 1", $this->post_type, $id));

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
							echo "<a href='".admin_url("edit.php?post_type=".$this->post_type_post."&strFilterSocialFeed=".$id)."'>".$amount."</a>";

							$post_latest = $wpdb->get_var($wpdb->prepare("SELECT post_date FROM ".$wpdb->posts." WHERE post_type = %s AND post_parent = '%d' ORDER BY post_date DESC LIMIT 0, 1", $this->post_type_post, $id));

							if($post_latest > DEFAULT_DATE)
							{
								echo "<div class='row-actions'>"
									.__("Latest", 'lang_social_feed').": ".format_date($post_latest)
								."</div>";
							}
						}
					break;
				}
			break;

			case $this->post_type_post:
				switch($col)
				{
					case 'type':
						$post_feed = get_post_meta($id, $this->meta_prefix.'feed_id', true);
						//$post_feed = $post->post_parent;

						$post_meta = get_post_meta($post_feed, $this->meta_prefix.$col, true);

						if($post_meta != '')
						{
							echo "<i class='".$this->get_post_icon($post_meta)." fa-2x'></i>";
						}

						else
						{
							//do_log("The parent ".$post_feed." does not exist anymore");

							wp_trash_post($id);
						}
					break;

					case 'name':
						$post_meta = get_post_meta($id, $this->meta_prefix.$col, true);

						if(substr($post_meta, 0, 1) != "@")
						{
							$post_meta = "@".$post_meta;
						}

						echo $post_meta;

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
						$post_meta = get_post_meta($id, $this->meta_prefix.$col, true);

						if($post_meta != '')
						{
							echo "<img src='".$post_meta."'>";
						}
					break;

					case 'post_id':
						echo get_post_title($id);
					break;

					case 'info':
						$post_feed = get_post_meta($id, $this->meta_prefix.'feed_id', true);
						//$post_feed = $post->post_parent;

						$post_meta = get_post_meta($post_feed, $this->meta_prefix.'type', true);

						switch($post_meta)
						{
							case 'facebook':
								$post_owner = get_post_meta($id, $this->meta_prefix.'is_owner', true);

								if($post_owner == 1)
								{
									echo "<i class='fa fa-user fa-2x' title='".__("Owner", 'lang_social_feed')."'></i>";
								}

								else if($post_owner === 0)
								{
									echo "<span class='fa-stack fa-lg' title='".__("Not Owner", 'lang_social_feed')."'>
										<i class='fa fa-user fa-stack-1x'></i>
										<i class='fa fa-ban fa-stack-2x red'></i>
									</span>";
								}
							break;

							case 'twitter':
								$post_owner = get_post_meta($id, $this->meta_prefix.'is_owner', true);

								if($post_owner == 1)
								{
									echo "<i class='fa fa-user fa-2x' title='".__("Owner", 'lang_social_feed')."'></i>";
								}

								else if($post_owner === 0)
								{
									echo "<span class='fa-stack fa-lg' title='".__("Not Owner", 'lang_social_feed')."'>
										<i class='fa fa-user fa-stack-1x'></i>
										<i class='fa fa-ban fa-stack-2x red'></i>
									</span>";
								}

								if(get_post_meta($id, $this->meta_prefix.'is_reply', true) == 1)
								{
									echo "<i class='fa fa-reply fa-2x' title='".__("Answer", 'lang_social_feed')."'></i>";
								}

								if(get_post_meta($id, $this->meta_prefix.'is_retweet', true) == 1)
								{
									echo "<i class='fa fa-share fa-2x' title='"."Retweet"."'></i>";
								}
							break;
						}
					break;
				}
			break;
		}
	}

	function row_actions($actions, $post)
	{
		if($post->post_type == $this->post_type_post)
		{
			unset($actions['inline hide-if-no-js']);
			unset($actions['view']);

			$post_id = $post->ID;

			$post_feed = get_post_meta($post_id, $this->meta_prefix.'feed_id', true);
			//$post_feed = $post->post_parent;

			$post_username = get_post_meta($post_id, $this->meta_prefix.'name', true);

			$post_username = "@".$post_username;
			$feed_name = get_post_meta($post_feed, $this->meta_prefix.'search_for', true);

			if($post->post_status == 'publish')
			{
				unset($actions['trash']);

				$actions['social_feed_action_hide'] = "<a href='#id_".$post_id."' class='social_feed_post_action social_feed_action_hide' confirm_text='".__("Are you sure?", 'lang_social_feed')."'>".__("Hide", 'lang_social_feed')."</a>"; //draft

				if($post_username != $feed_name)
				{
					$actions['social_feed_action_ignore'] = "<a href='#id_".$post_id."' class='social_feed_post_action social_feed_action_ignore' confirm_text='".sprintf(__("Are you sure? This will make all future posts by %s to be ignored aswell!", 'lang_social_feed'), $post_username)."'>".__("Ignore Future Posts", 'lang_social_feed')."</a>"; //pending
				}
			}
		}

		return $actions;
	}

	function add_policy($content)
	{
		$arr_data = array();
		get_post_children(array('add_choose_here' => false, 'post_type' => $this->post_type), $arr_data);

		if(count($arr_data) > 0)
		{
			$content .= "<h3>".__("Social Feed", 'lang_social_feed')."</h3>
			<p>"
				.__("Posts from social feeds are stored in the database to make it possible to present them in the fastest way possible to you as a visitor.", 'lang_social_feed')
			."</p>";
		}

		return $content;
	}

	function save_post($post_id, $post, $update)
	{
		if($post->post_type == $this->post_type && $post->post_status == 'publish') // && $update == false
		{
			$this->set_id($post_id);

			if(!($this->get_amount() > 0))
			{
				$this->fetch_feed();
			}

			delete_post_meta($this->id, $this->meta_prefix.'error');
		}
	}

	function wp_trash_post($post_id)
	{
		global $wpdb;

		if(get_post_type($post_id) == $this->post_type)
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_parent = '%d'", $this->post_type_post, $post_id));

			foreach($result as $r)
			{
				wp_trash_post($r->ID);
			}
		}
	}

	function wp_head()
	{
		if(!is_plugin_active("mf_widget_logic_select/index.php") || apply_filters('get_widget_search', 'social-feed-widget') > 0)
		{
			$plugin_base_include_url = plugins_url()."/mf_base/include/";
			$plugin_include_url = plugin_dir_url(__FILE__);
			$plugin_version = get_plugin_version(__FILE__);

			$setting_social_debug = get_option('setting_social_debug');

			mf_enqueue_style('style_social_feed', $plugin_include_url."style.php", $plugin_version);
			mf_enqueue_style('style_base_bb', $plugin_base_include_url."backbone/style.css", $plugin_version);

			mf_enqueue_script('underscore');
			mf_enqueue_script('backbone');
			mf_enqueue_script('script_base_plugins', $plugin_base_include_url."backbone/bb.plugins.js", $plugin_version);

			mf_enqueue_script('script_social_feed_models', $plugin_include_url."backbone/bb.models.js", array('plugin_url' => $plugin_include_url), $plugin_version);
			mf_enqueue_script('script_social_feed_views', $plugin_include_url."backbone/bb.views.js", array('debug' => $setting_social_debug), $plugin_version);

			mf_enqueue_script('script_base_init', $plugin_base_include_url."backbone/bb.init.js", $plugin_version);
		}
	}

	function wp_footer()
	{
		global $obj_base;

		if(!isset($obj_base))
		{
			$obj_base = new mf_base();
		}

		echo $obj_base->get_templates(array('lost_connection'));

		if(get_option('setting_social_debug') == 'yes')
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
				<% if(image != '')
				{ %>
					<img src='<%= image %>' alt='".sprintf(__("Image for the post %s", 'lang_social_feed'), "<%= name %>")."'>
				<% } %>
				<a href='<%= link %>' class='content'>
					<div class='meta'>
						<i class='<%= icon %>'></i>

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

					<% if(service == 'rss' && title != '')
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
							<i class='fa fa-comment'></i><span><%= comments %></span>
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
			'load_more_posts' => 'no',
			'text' => 'yes',
			'likes' => 'no',
			'read_more' => 'yes',
		), $atts));

		$setting_social_reload = get_option('setting_social_reload');

		$out = "<div class='widget social_feed'>
			<div id='feed_".$id."' class='section'"
				.($id > 0 ? " data-social_feeds='".$id."'" : "")
				.($filter == 'yes' ? " data-social_filter='".$filter."'" : "")
				.($amount > 0 ? " data-social_amount='".$amount."'" : "")
				.($load_more_posts == 'yes' ? " data-social_load_more_posts='".$load_more_posts."'" : "")
				.($likes == 'yes' ? " data-social_likes='".$likes."'" : "")
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

				$out .= " hide'></ul>";

				if($load_more_posts == 'yes')
				{
					$out .= "<div class='form_button'><a href='#' class='load_more_posts button hide'>".__("View More", 'lang_social_feed')."</a></div>";
				}

			$out .= "</div>
		</div>";

		return $out;
	}

	function widgets_init()
	{
		register_widget('widget_social_feed');
	}

	function action_hide()
	{
		global $wpdb, $done_text, $error_text;

		$action_id = check_var('action_id', 'int');

		$result = array();

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_status = 'draft' WHERE ID = '%d' AND post_type = %s", $action_id, $this->post_type_post));

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

		header("Content-Type: application/json");
		echo json_encode($result);
		die();
	}

	function action_ignore()
	{
		global $wpdb, $done_text, $error_text;

		$action_id = check_var('action_id', 'int');

		$result = array();

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_status = 'pending' WHERE ID = '%d' AND post_type = %s", $action_id, $this->post_type_post));

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

		header("Content-Type: application/json");
		echo json_encode($result);
		die();
	}

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
			'client_id' => $this->client_id,
			'state' => $state,
			'redirect_uri' => $this->settings_url,
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

		$out = "<a href='https://linkedin.com/uas/oauth2/authorization?".http_build_query($params)."' class='button-secondary'>"
			.$authorize_string
		."</a>"
		.$authorization_message;

		return $out;
	}

	function check_token_life()
	{
		$this->init_linkedin_auth();

		$this->token_life = intval($this->auth_options['expires_in']) - strtotime(date("Y-m-d H:m:s"));

		if($this->token_life < 0)
		{
			$this->token_life = false;

			if(get_option('setting_linkedin_email_when_expired') == 'yes' && !get_option('option_linkedin_emailed'))
			{
				$this->email_when_expired();

				update_option('option_linkedin_emailed', 1, 'no');
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
		$mail_subject = "[".get_bloginfo('name')."] ".sprintf(__("%s Access Token has Expired", 'lang_social_feed'), "LinkedIn");
		$mail_content = sprintf(__("Please generate a new Access Token for %s %sHere%s", 'lang_social_feed'), "LinkedIn", "<a href='".$this->settings_url."#settings_social_feed_linkedin'>", "</a>");

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
				$out .= "<i class='fa fa-exclamation-triangle yellow display_warning'></i> ";
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

		$arr_post_data = array(
			'grant_type' => 'authorization_code',
			'client_id' => $this->client_id,
			'client_secret' => get_option('setting_linkedin_api_secret'),
			'code' => $code,
			'redirect_uri' => $this->settings_url,
		);

		$url = "https://linkedin.com/uas/oauth2/accessToken?".http_build_query($arr_post_data);
		$result = wp_remote_retrieve_body(wp_remote_get($url));
		$json = json_decode($result);

		if(!isset($json->access_token) || 5 >= strlen($json->access_token))
		{
			do_log("I did not recieve an access token (".var_export($json, true).")");

			return false;
		}

		else
		{
			return $json;
		}

		/* Does not work yet */
		//$url = "https://linkedin.com/oauth/v2/accessToken";

		/*$result = wp_remote_post($url, array(
			'body' => $arr_post_data,
		));

		switch($result['response']['code'])
		{
			case 200:
			case 201:
				$json = json_decode($result['body'], true);

				if(!isset($json->access_token) || 5 >= strlen($json->access_token))
				{
					do_log("I did not recieve an access token (".var_export($json, true).")");

					return false;
				}

				else
				{
					return $json;
				}
			break;

			default:
				do_log("I could not connect to LinkedIn: ".$result['response']['code']." (".json_encode($arr_post_data).", ".$result['body'].")");
			break;
		}*/

		/*list($content, $headers) = get_url_content(array(
			'url' => $url,
			'catch_head' => true,
			'headers' => array(
				'Cache-Control: no-cache',
				'Content-Type: x-www-form-urlencoded',
			),
			'post_data' => json_encode($arr_post_data),
		));

		switch($headers['http_code'])
		{
			case 200:
			case 201:
				$json = json_decode($content, true);

				if(!isset($json->access_token) || 5 >= strlen($json->access_token))
				{
					do_log("I did not recieve an access token (".var_export($json, true).")");

					return false;
				}

				else
				{
					return $json;
				}
			break;

			default:
				do_log("I could not connect to LinkedIn: ".$headers['http_code']." (".var_export($headers, true).", ".json_encode($arr_post_data).", ".$content.")");
			break;
		}*/
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

				update_option('option_linkedin_authkey', $auth_options, 'no');
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

	function get_api_credentials($type = '')
	{
		if($type != '')
		{
			$this->type = $type;
		}

		switch($this->type)
		{
			case 'facebook':
				$this->setting_social_api_url = get_site_option('setting_social_api_url', get_option('setting_social_api_url'));

				$this->facebook_authorize_url = $this->setting_social_api_url."/facebook-login.php?state=".admin_url("admin.php?page=cff-top");
				$this->facebook_redirect_url = get_site_url()."/wp-content/plugins/mf_social_feed/include/api/passthru.php?type=fb_login";
			break;

			case 'instagram':
				$this->setting_social_api_url = get_site_option('setting_social_api_url', get_option('setting_social_api_url'));
				$this->instagram_client_id = get_site_option('setting_instagram_client_id', get_option('setting_instagram_client_id'));

				$this->instagram_redirect_url = get_site_url()."/wp-content/plugins/mf_social_feed/include/api/passthru.php?type=instagram_login";
				$this->instagram_authorize_url = "//facebook.com/dialog/oauth?client_id=".$this->instagram_client_id."&redirect_uri=".$this->setting_social_api_url."/instagram-graph-api-redirect.php&scope=manage_pages,instagram_basic,instagram_manage_insights,instagram_manage_comments&state=".admin_url("admin.php?page=sb-instagram-feed");
			break;

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

		//do_log("get_amount: ".$wpdb->prepare("SELECT COUNT(ID) FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND post_status = %s AND meta_key = '".$this->meta_prefix."feed_id' AND meta_value = '%d'", $this->post_type_post, 'publish', $this->id));

		return $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND post_status = %s AND meta_key = '".$this->meta_prefix."feed_id' AND meta_value = '%d'", $this->post_type_post, 'publish', $this->id));
	}

	function fetch_feed()
	{
		$this->get_type();

		if($this->search != '')
		{
			$this->arr_posts = array();

			switch($this->type)
			{
				case 'facebook':
					$this->facebook_access_token = get_post_meta($this->id, $this->meta_prefix.'facebook_access_token', true);

					if($this->facebook_access_token == '')
					{
						update_post_meta($this->id, $this->meta_prefix.'error', __("Edit and add an access token", 'lang_social_feed'));
					}

					else
					{
						$this->fetch_facebook();
					}
				break;

				case 'instagram':
					$this->instagram_access_token = get_post_meta($this->id, $this->meta_prefix.'instagram_access_token', true);

					if($this->instagram_access_token == '')
					{
						update_post_meta($this->id, $this->meta_prefix.'error', __("Edit and add an access token", 'lang_social_feed'));
					}

					else
					{
						$this->fetch_instagram();
					}
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

		$facebook_access_token = $this->facebook_access_token;
		$fb_feed_url = "https://graph.facebook.com/".$this->search."/feed?fields=id,from,message,story,full_picture,created_time&access_token=".$facebook_access_token; //&limit=10

		list($content, $headers) = get_url_content(array('url' => $fb_feed_url, 'catch_head' => true));
		$json = json_decode($content, true);

		if(isset($json['data']))
		{
			if(get_option('setting_social_debug') == 'yes')
			{
				do_log("Facebook: ".$fb_feed_url." -> ".htmlspecialchars(var_export($json['data'], true)));
			}

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
					'created_time' => '[datetime]'
				)*/

				/*array(
					'id' => '[id]_[id]',
					'message' => '[text]',
					'full_picture' => '[url]',
					'created_time' => '[datetime]'
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

				if(isset($post['from']))
				{
					$is_owner = ($post['from']['id'] == $arr_post_id[0]);
				}

				else
				{
					$is_owner = false;
				}

				$this->arr_posts[] = array(
					'type' => $this->type,
					'id' => $post_id,
					'name' => $this->search,
					'text' => $post_content,
					'link' => "//facebook.com/".$arr_post_id[0]."/posts/".$arr_post_id[1],
					'image' => isset($post['full_picture']) && $post['full_picture'] != '' ? $post['full_picture'] : "",
					'created' => date("Y-m-d H:i:s", strtotime($post['created_time'])),
					'is_owner' => $is_owner, 
				);
			}

			delete_post_meta($this->id, $this->meta_prefix.'error');
		}

		else
		{
			update_post_meta($this->id, $this->meta_prefix.'error', $json['error']['message']);
		}
	}

	function get_instagram_business_id()
	{
		$url = "https://graph.facebook.com/me/accounts?fields=instagram_business_account,access_token&limit=5&access_token=".$this->instagram_access_token;
		/*{
		   "data": [
			  {
				 "instagram_business_account": {
					"id": "[number]"
				 },
				 "access_token": "[token]",
				 "id": "[number]"
			  }
		   ]
		}*/

		$result = wp_remote_retrieve_body(wp_remote_get($url));
		$json = json_decode($result);

		if(isset($json->data))
		{
			$arr_ids = array();

			if(get_option('setting_social_debug') == 'yes')
			{
				do_log("Instagram: ".$url." -> ".htmlspecialchars(var_export($json->data, true)));
			}

			foreach($json->data as $data_account)
			{
				if(isset($data_account->instagram_business_account->id))
				{
					$arr_ids[] = $data_account->instagram_business_account->id;
				}
			}

			if(count($arr_ids) > 0)
			{
				return $arr_ids;
			}
		}

		else
		{
			update_post_meta($this->id, $this->meta_prefix.'error', "<a href='".$url."'>".__("The JSON I got back was not correct", 'lang_social_feed')."</a>");
		}

		return $url;
	}

	function get_instagram_business_name($id)
	{
		$url = "https://graph.facebook.com/".$id."?fields=name,username,profile_picture_url&access_token=".$this->instagram_access_token;
		/*{
		   "name": "[text]",
		   "username": "[text]",
		   "profile_picture_url": "[url]",
		   "id": "[number]"
		}*/

		$result = wp_remote_retrieve_body(wp_remote_get($url));
		$json = json_decode($result);

		if(isset($json->name))
		{
			if(get_option('setting_social_debug') == 'yes')
			{
				do_log("Instagram: ".$url." -> ".htmlspecialchars(var_export($json->data, true)));
			}

			return array(
				'name' => $json->name,
				'username' => $json->username,
				'profile_picture' => $json->profile_picture_url,
			);
		}

		else
		{
			update_post_meta($this->id, $this->meta_prefix.'error', "<a href='".$url."'>".__("The JSON I got back was not correct", 'lang_social_feed')."</a>");
		}

		return $url;
	}

	function fetch_instagram()
	{
		$instagram_id = get_post_meta($this->id, $this->meta_prefix.'instagram_id', true);

		if($instagram_id != '')
		{
			$url = "https://graph.facebook.com/".$instagram_id."/media?fields=media_url,thumbnail_url,caption,id,media_type,timestamp,username,comments_count,like_count,permalink,children{media_url,id,media_type,timestamp,permalink,thumbnail_url}&limit=20&access_token=".$this->instagram_access_token;

			$result = wp_remote_retrieve_body(wp_remote_get($url));
			$json = json_decode($result);

			if(isset($json->data))
			{
				if(get_option('setting_social_debug') == 'yes')
				{
					do_log("Instagram: ".$url." -> ".htmlspecialchars(var_export($json->data, true)));
				}

				foreach($json->data as $post)
				{
					/*{
						"data": [
							{
								"media_url": "[url]",
								'thumbnail_url' => '[url]',
								"caption": "[text]",
								"id": "[number]",
								"media_type": "[IMAGE/VIDEO]",
								"timestamp": "YYYY-MM-DDTHH:MM:SS+0000",
								"username": "[username]",
								"comments_count": [number],
								"like_count": [number],
								"permalink": "[url]"
							},
						],
						"paging": {
							"cursors": {
								"before": "QVF...",
								"after": "QVF..."
							},
							"next": "[url]"
						}
					}*/

					switch($post->media_type)
					{
						case 'IMAGE':
						case 'CAROUSEL_ALBUM':
							$post_image = $post->media_url;
						break;

						case 'VIDEO':
							$post_image = $post->thumbnail_url;
						break;

						default:
							do_log("Unknown media type: ".$post->media_type." (".var_export($post, true).")");

							$post_image = "";
						break;
					}

					$this->arr_posts[] = array(
						'type' => $this->type,
						'id' => $post->id,
						'user_id' => $post->id,
						'name' => $post->username,
						'text' => isset($post->caption) ? $post->caption : "",
						'link' => $post->permalink,
						'image' => $post_image,
						'created' => date("Y-m-d H:i:s", strtotime($post->timestamp)),
						'likes' => $post->like_count,
						'comments' => $post->comments_count,
					);
				}

				delete_post_meta($this->id, $this->meta_prefix.'error');
			}

			else
			{
				update_post_meta($this->id, $this->meta_prefix.'error', "<a href='".$url."'>".__("The JSON I got back was not correct", 'lang_social_feed')."</a>");
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
			if(isset($json['message']))
			{
				update_post_meta($this->id, $this->meta_prefix.'error', "LinkedIn: ".$json['message']);
			}

			else
			{
				update_post_meta($this->id, $this->meta_prefix.'error', "LinkedIn: No key found (".var_export($json, true).")");
			}

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
				if(get_option('setting_social_debug') == 'yes')
				{
					do_log("LinkedIn: ".htmlspecialchars(var_export($results, true)));
				}

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
						//'link' => "//linkedin.com/company/".$this->search,
						'link' => "//linkedin.com/nhome/updates?topic=".$post_id,
						'image' => $post_image_url,
						'created' => date("Y-m-d H:i:s", ($post_share['timestamp'] / 1000)),
					);
				}

				delete_post_meta($this->id, $this->meta_prefix.'error');
			}
		}

		else
		{
			update_post_meta($this->id, $this->meta_prefix.'error', "LinkedIn: <a href='".admin_url("options-general.php?page=settings_mf_base#settings_social_feed_linkedin")."'>".__("Token has expired", 'lang_social_feed')."</a>");
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
			if(get_option('setting_social_debug') == 'yes')
			{
				do_log("RSS: ".$this->search." -> ".htmlspecialchars(var_export($feed->get_items(), true)));
			}

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

				$post_date = $item->get_date("Y-m-d H:i:s");

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
			update_post_meta($this->id, $this->meta_prefix.'error', __("I could not find a feed", 'lang_social_feed'));
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
				update_post_meta($this->id, $this->meta_prefix.'error', "Twitter: ".var_export($e->getMessage(), true));
			}
		}

		else if($this->search != '')
		{
			if(substr($this->search, 0, 1) == "@")
			{
				$this->search = substr($this->search, 1);
			}

			try
			{
				$results = $twitter->search('from:'.$this->search);
			}

			catch(TwitterException $e)
			{
				update_post_meta($this->id, $this->meta_prefix.'error', "Twitter: ".var_export($e->getMessage(), true));
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
				update_post_meta($this->id, $this->meta_prefix.'error', "Twitter: ".var_export($e->getMessage(), true));
			}
		}

		if(isset($results) && is_array($results))
		{
			if(get_option('setting_social_debug') == 'yes')
			{
				$results_temp = (array)$results;

				foreach($results_temp as $key => $arr_value)
				{
					unset($results_temp[$key]->id_str);
					unset($results_temp[$key]->truncated);
					unset($results_temp[$key]->entities->hashtags);
					unset($results_temp[$key]->entities->symbols);
					unset($results_temp[$key]->entities->user_mentions);
					unset($results_temp[$key]->entities->urls);
					unset($results_temp[$key]->entities->media->indices);
					unset($results_temp[$key]->entities->media->media_url_https);
					unset($results_temp[$key]->extended_entities);
					unset($results_temp[$key]->metadata);
					unset($results_temp[$key]->source);
					unset($results_temp[$key]->in_reply_to_status_id_str);
					unset($results_temp[$key]->in_reply_to_user_id_str);
					unset($results_temp[$key]->user->id_str);
					unset($results_temp[$key]->user->entities);
					unset($results_temp[$key]->user->protected);
					unset($results_temp[$key]->user->followers_count);
					unset($results_temp[$key]->user->friends_count);
					unset($results_temp[$key]->user->listed_count);
					unset($results_temp[$key]->user->created_at);
					unset($results_temp[$key]->user->favourites_count);
					unset($results_temp[$key]->user->utc_offset);
					unset($results_temp[$key]->user->time_zone);
					unset($results_temp[$key]->user->geo_enabled);
					unset($results_temp[$key]->user->statuses_count);
					unset($results_temp[$key]->user->lang);
					unset($results_temp[$key]->user->contributors_enabled);
					unset($results_temp[$key]->user->is_translator);
					unset($results_temp[$key]->user->is_translation_enabled);
					unset($results_temp[$key]->user->profile_background_color);
					unset($results_temp[$key]->user->profile_background_image_url);
					unset($results_temp[$key]->user->profile_background_image_url_https);
					unset($results_temp[$key]->user->profile_background_tile);
					unset($results_temp[$key]->user->profile_image_url);
					unset($results_temp[$key]->user->profile_image_url_https);
					unset($results_temp[$key]->user->profile_banner_url);
					unset($results_temp[$key]->user->profile_link_color);
					unset($results_temp[$key]->user->profile_sidebar_border_color);
					unset($results_temp[$key]->user->profile_sidebar_fill_color);
					unset($results_temp[$key]->user->profile_text_color);
					unset($results_temp[$key]->user->profile_use_background_image);
					unset($results_temp[$key]->user->has_extended_profile);
					unset($results_temp[$key]->user->default_profile);
					unset($results_temp[$key]->user->default_profile_image);
					unset($results_temp[$key]->user->following);
					unset($results_temp[$key]->user->follow_request_sent);
					unset($results_temp[$key]->user->notifications);
					unset($results_temp[$key]->user->translator_type);
					unset($results_temp[$key]->geo);
					unset($results_temp[$key]->coordinates);
					unset($results_temp[$key]->place);
					unset($results_temp[$key]->contributors);
					unset($results_temp[$key]->possibly_sensitive);
					unset($results_temp[$key]->lang);
				}

				do_log("Twitter: ".htmlspecialchars(var_export($results_temp, true)));
			}

			foreach($results as $key => $post)
			{
				/*array(
					'created_at' => 'Fri Feb 17 08:09:54 +0000 2017',
					'id' => '[id]', 'id_str' => '[id]',
					'text' => 'Text #hashtag',
					'truncated' => false,
					'entities' => stdClass::__set_state(array(
						'hashtags' => array(0 => stdClass::__set_state(array('text' => 'svpol', 'indices' => array(0 => 43)))),
						'symbols' => array(),
						'user_mentions' => array(),
						'urls' => array(),
						'media' => array(0 => stdClass::__set_state(array(
							'id' => '[id]', 'id_str' => '[id]',
							'indices' => array(0 => 50),
							'media_url' => '[url]', 'media_url_https' => '[url]',
							'url' => '[url]',
							'display_url' => 'pic.twitter.com/x',
							'expanded_url' => 'https://twitter.com/username/status/[id]/photo/1',
							'type' => 'photo',
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
							'id' => '[id]', 'id_str' => '[id]',
							'indices' => array(0 => 50),
							'media_url' => '[url]', 'media_url_https' => '[url]',
							'url' => '[url]',
							'display_url' => 'pic.twitter.com/x',
							'expanded_url' => 'https://twitter.com/username/status/[id]/photo/1',
							'type' => 'photo',
							'sizes' => stdClass::__set_state(array(
								'large' => stdClass::__set_state(array('w' => 1600, 'h' => 1200, 'resize' => 'fit')),
								'thumb' => stdClass::__set_state(array('w' => 150, 'h' => 150, 'resize' => 'crop')),
								'medium' => stdClass::__set_state(array('w' => 1200, 'h' => 900, 'resize' => 'fit')),
								'small' => stdClass::__set_state(array('w' => 680, 'h' => 510, 'resize' => 'fit'))
							))
						)))
					)),
					'metadata' => stdClass::__set_state(array('iso_language_code' => 'sv', 'result_type' => 'recent')),
					'source' => 'Twitter for Dumbphone',
					'in_reply_to_status_id' => NULL, 'in_reply_to_status_id_str' => NULL,
					'in_reply_to_user_id' => NULL, 'in_reply_to_user_id_str' => NULL,
					'in_reply_to_screen_name' => NULL,
					'user' => stdClass::__set_state(array(
						'id' => [id], 'id_str' => '[id]',
						'name' => 'Name',
						'screen_name' => 'username',
						'location' => 'Sverige',
						'description' => 'Description',
						'url' => '[url]',
						'entities' => stdClass::__set_state(array(
							'url' => stdClass::__set_state(array(
								'urls' => array(0 => stdClass::__set_state(array(
									'url' => '[url]', 'expanded_url' => '[url]', 'display_url' => 'domain.com', 'indices' => array(0 => 2)
								)))
							)),
							'description' => stdClass::__set_state(array('urls' => array()))
						)),
						'protected' => false, 'followers_count' => 78040, 'friends_count' => 4127, 'listed_count' => 594, 'created_at' => 'Tue Jan 20 10:19:51 +0000 2009', 'favourites_count' => 420, 'utc_offset' => 3600, 'time_zone' => '[TZ]', 'geo_enabled' => true, 'verified' => true, 'statuses_count' => 12821, 'lang' => 'sv', 'contributors_enabled' => false, 'is_translator' => false, 'is_translation_enabled' => false, 'profile_background_color' => 'ffffff', 'profile_background_image_url' => 'http://pbs.twimg.com/profile_background_images/[id]/x.jpg', 'profile_background_image_url_https' => 'https://pbs.twimg.com/profile_background_images/[id]/x.jpeg', 'profile_background_tile' => false, 'profile_image_url' => 'http://pbs.twimg.com/profile_images/[id]/x.png', 'profile_image_url_https' => 'https://pbs.twimg.com/profile_images/[id]/x.png', 'profile_banner_url' => 'https://pbs.twimg.com/profile_banners/[id]/[id]', 'profile_link_color' => 'ffffff', 'profile_sidebar_border_color' => 'ffffff', 'profile_sidebar_fill_color' => 'ffffff', 'profile_text_color' => '333333', 'profile_use_background_image' => true, 'has_extended_profile' => false, 'default_profile' => false, 'default_profile_image' => false, 'following' => false, 'follow_request_sent' => false, 'notifications' => false, 'translator_type' => 'none'
					)),
					'geo' => NULL, 'coordinates' => NULL, 'place' => NULL, 'contributors' => NULL, 'is_quote_status' => false, 'retweet_count' => 9, 'favorite_count' => 27, 'favorited' => false, 'retweeted' => false, 'possibly_sensitive' => false, 'lang' => 'sv'
				)*/

				$username = (isset($post->user->screen_name) ? $post->user->screen_name : $this->search);

				if(substr($this->search, 0, 1) == "#")
				{
					$is_owner = true;
				}

				else
				{
					$is_owner = (strtolower($username) == strtolower($this->search));
				}

				$this->arr_posts[] = array(
					'type' => $this->type,
					'id' => $post->id,
					'name' => $username,
					'text' => $post->text,
					'link' => "//twitter.com/".$this->search."/status/".$post->id,
					'image' => (isset($post->entities->media[0]->media_url) ? $post->entities->media[0]->media_url : ""),
					'created' => date("Y-m-d H:i:s", strtotime($post->created_at)),
					'is_owner' => $is_owner,
					'is_reply' => ($post->in_reply_to_status_id != ''),
					'is_retweet' => (substr($post->text, 0, 3) == "RT "),
				);
			}

			delete_post_meta($this->id, $this->meta_prefix.'error');
		}
	}

	function set_date_modified()
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_modified = NOW() WHERE ID = '%d' AND post_type = %s", $this->id, $this->post_type));
	}

	function check_is_settings($post)
	{
		$out = true;

		$setting_social_keep_posts = get_option_or_default('setting_social_keep_posts', 12);

		if($post['created'] < date("Y-m-d", strtotime("-".$setting_social_keep_posts." month")))
		{
			//do_log($post['created']." is older than ".date("Y-m-d", strtotime("-".$setting_social_keep_posts." month"))." (".var_export($post, true).")");

			$out = false;
		}

		if($out == true)
		{
			switch($post['type'])
			{
				case 'facebook':
					if($post['is_owner'] == true)
					{
						// Do nothing
					}

					else
					{
						$post_include = get_post_meta($this->id, $this->meta_prefix.'facebook_include', false);

						if(is_array($post_include) && count($post_include) > 0)
						{
							if(!in_array('other', $post_include))
							{
								$out = false;

								//do_log("Hide because other (".var_export($post, true).")");
							}
						}

						else
						{
							$out = false;

							//do_log("Hide because no FB include (".var_export($post, true).")");
						}
					}
				break;

				case 'twitter':
					if($post['is_owner'] == true && $post['is_reply'] == false && $post['is_retweet'] == false)
					{
						// Do nothing

						//do_log("Do nothing. It's from the owner and neither a reply or a retweet (".var_export($post, true).")");
					}

					else
					{
						$post_include = get_post_meta($this->id, $this->meta_prefix.'twitter_include', false);

						if(is_array($post_include) && count($post_include) > 0)
						{
							if($post['is_owner'] == false && !in_array('other', $post_include))
							{
								$out = false;

								//do_log("Hide because not owner (".var_export($post, true).")");
							}

							else if($post['is_reply'] == true && !in_array('reply', $post_include))
							{
								$out = false;

								//do_log("Hide because reply (".var_export($post, true).")");
							}

							else if($post['is_retweet'] == true && !in_array('retweet', $post_include))
							{
								$out = false;

								//do_log("Hide because retweet (".var_export($post, true).")");
							}
						}

						else
						{
							$out = false;

							//do_log("Hide because no Twitter include (".var_export($post, true).")");
						}
					}
				break;
			}
		}

		return $out;
	}

	function insert_posts()
	{
		global $wpdb;

		if(get_option('setting_social_debug') == 'yes')
		{
			do_log("Social Posts: ".htmlspecialchars(var_export($this->arr_posts, true)));
		}

		if(count($this->arr_posts) > 0)
		{
			foreach($this->arr_posts as $post)
			{
				$post_title = (isset($post['title']) && $post['title'] != '' ? $post['title'] : $post['type']." ".$post['id']);
				$post_name = sanitize_title_with_dashes(sanitize_title($post_title));

				$post_data = array(
					'post_name' => $post_name,
					'post_title' => $post_title,
					'post_content' => $post['text'],
					//'post_excerpt' => $this->id, //This can be removed when post_parent is used everywhere
					'post_parent' => $this->id,
					'meta_input' => array(
						$this->meta_prefix.'service' => $post['type'],
						$this->meta_prefix.'feed_id' => $this->id, //This can be removed when post_parent is used everywhere
						$this->meta_prefix.'name' => $post['name'],
						$this->meta_prefix.'image' => $post['image'],
						$this->meta_prefix.'link' => $post['link'],
					),
				);

				$arr_meta_input_types = array('is_owner', 'is_reply', 'is_retweet', 'user_id', 'likes', 'comments');

				foreach($arr_meta_input_types as $meta_input_type)
				{
					if(isset($post[$meta_input_type]) && $post[$meta_input_type] > 0)
					{
						$post_data['meta_input'][$this->meta_prefix.$meta_input_type] = $post[$meta_input_type];
					}
				}

				$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = %s AND (post_title = %s OR post_name = %s) AND post_parent = '%d'", $this->post_type_post, 'publish', $post_title, $post_name, $this->id));

				if($wpdb->num_rows == 0)
				{
					if($this->check_is_settings($post))
					{
						$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND post_parent = '%d' AND post_status = %s AND meta_key = '".$this->meta_prefix."name' AND meta_value = %s LIMIT 0, 1", $this->post_type_post, $this->id, 'pending', $post['name']));
						$post_status = ($wpdb->num_rows > 0 ? 'draft' : 'publish');

						/*if($post_status == 'draft')
						{
							do_log("A post was set to ".$post_status." because ".$post['name']." previously has been set to be ignored (".$wpdb->last_query.")");
						}*/

						$post_data['post_type'] = $this->post_type_post;
						$post_data['post_status'] = $post_status;
						$post_data['post_date'] = $post['created'];

						$post_id = wp_insert_post($post_data);
					}
				}

				else
				{
					$i = 0;

					foreach($result as $r)
					{
						$post_id = $r->ID;

						if($this->check_is_settings($post) && $i == 0)
						{
							$post_data['ID'] = $post_id;

							wp_update_post($post_data);

							$i++;
						}

						else
						{
							wp_trash_post($post_id);
						}
					}
				}
			}
		}

		// Remove old posts
		###########
		$setting_social_keep_posts = get_option_or_default('setting_social_keep_posts', 12);

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND (post_excerpt = '%d' OR post_parent = '%d') AND post_status = %s AND post_date < %s", $this->post_type_post, $this->id, $this->id, 'publish', date("Y-m-d", strtotime("-".$setting_social_keep_posts." month"))));

		if($wpdb->num_rows > 0)
		{
			foreach($result as $r)
			{
				$post_id = $r->ID;

				//do_log("Remove ".get_post_title($post_id)." because it is older than ".date("Y-m-d", strtotime("-".$setting_social_keep_posts." month")));

				wp_trash_post($post_id);
			}
		}

		/*else
		{
			do_log("There were no old posts to remove (".$wpdb->last_query.")");
		}*/
		###########
	}
	#########################

	// Public
	#########################
	function get_feeds_and_posts($data)
	{
		global $wpdb;

		if(!isset($data['filter']) || $data['filter'] == ''){				$data['filter'] = 'no';}
		if(!isset($data['limit_source']) || $data['limit_source'] == ''){	$data['limit_source'] = 'no';}
		if(!isset($data['likes']) || $data['likes'] == ''){					$data['likes'] = 'no';}

		$arr_public_feeds = $arr_post_feeds_count = $arr_post_feeds = $arr_post_posts = array();
		$has_more_posts = false;
		$query_where = "";

		if(is_array($data['feeds']) && count($data['feeds']) > 0)
		{
			$query_where = " AND ID IN('".implode("','", $data['feeds'])."')";
		}

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = %s".$query_where, $this->post_type, 'publish'));

		foreach($result as $r)
		{
			$arr_public_feeds[] = $r->ID;
		}

		$count_public_feeds = count($arr_public_feeds);

		if($count_public_feeds > 0)
		{
			$limit_start = 0;

			if($data['filter'] == 'group')
			{
				$arr_services = $this->get_social_types_for_select();
			}

			$limit_source_amount = ($data['limit_source'] == 'yes' && $count_public_feeds != 1 ? ceil(($data['amount'] / $count_public_feeds) * 1.2) : $data['amount']);

			/* Group Posts */
			$query_select = $query_group = "";

			if($count_public_feeds > 1)
			{
				$query_select = ", CONCAT(SUBSTRING(post_date, 1, 10), SUBSTRING(post_content, 1, 40)) AS post_group";
				$query_group = " GROUP BY post_group";
			}

			/*while(count($arr_post_posts) < $data['amount'])
			{*/
				$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_content, post_parent, post_date, guid".$query_select." FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = %s AND post_parent IN('".implode("','", $arr_public_feeds)."')".$query_group." ORDER BY post_date DESC LIMIT ".$limit_start.", ".($data['amount'] + 1), $this->post_type_post, 'publish')); //
				$rows = $wpdb->num_rows;

				if($rows > 0)
				{
					foreach($result as $r)
					{
						if(count($arr_post_posts) < $data['amount'])
						{
							$post_id = $r->ID;
							$post_title = $r->post_title;
							$post_content = $r->post_content;
							$post_date = $r->post_date;

							$post_feed = get_post_meta($post_id, $this->meta_prefix.'feed_id', true);
							//$post_feed = $r->post_parent;

							$post_service = get_post_meta($post_id, $this->meta_prefix.'service', true);
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

								if(!isset($arr_post_feeds_count[$post_feed]) || $arr_post_feeds_count[$post_feed] < $limit_source_amount)
								{
									$arr_post_posts[] = array(
										'service' => $post_service,
										'icon' => $this->get_post_icon($post_service),
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

									if(isset($arr_post_feeds_count[$post_feed])){	$arr_post_feeds_count[$post_feed]++;}
									else{											$arr_post_feeds_count[$post_feed] = 0;}
								}
							}
						}

						else
						{
							$has_more_posts = true;

							break;
						}
					}

					//$limit_start += $data['amount'];
				}

				//do_log("get_feeds_and_posts: ".$limit_start.", ".$data['amount'].", ".$rows.", ".count($arr_post_posts));

				/*else
				{
					break;
				}*/
			//}

			if($data['filter'] != 'no' && count($arr_post_feeds) > 1)
			{
				$arr_post_feeds = array_sort(array('array' => $arr_post_feeds, 'on' => 'name'));
			}
		}

		return array($arr_post_feeds, $arr_post_posts, $has_more_posts);
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
			'social_load_more_posts' => 'no',
			'social_limit_source' => 'no',
			'social_text' => 'yes',
			'social_likes' => 'no',
			'social_read_more' => 'yes',
		);

		parent::__construct('social-feed-widget', __("Social Feed", 'lang_social_feed'), $widget_ops);

		$this->obj_social_feed = new mf_social_feed();
	}

	function widget($args, $instance)
	{
		extract($args);
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$setting_social_reload = get_option('setting_social_reload');

		echo $before_widget;

			if($instance['social_heading'] != '')
			{
				$instance['social_heading'] = apply_filters('widget_title', $instance['social_heading'], $instance, $this->id_base);

				echo $before_title
					.$instance['social_heading']
				.$after_title;
			}

			$feed_id = is_array($instance['social_feeds']) && count($instance['social_feeds']) > 0 ? implode("_", $instance['social_feeds']) : 0;

			echo "<div id='feed_".$feed_id."' class='section'"
				.(is_array($instance['social_feeds']) && count($instance['social_feeds']) > 0 ? " data-social_feeds='".implode(",", $instance['social_feeds'])."'" : "")
				.($instance['social_filter'] == 'yes' ? " data-social_filter='".$instance['social_filter']."'" : "")
				.($instance['social_amount'] > 0 ? " data-social_amount='".$instance['social_amount']."'" : "")
				.($instance['social_load_more_posts'] == 'yes' ? " data-social_load_more_posts='".$instance['social_load_more_posts']."'" : "")
				.($instance['social_limit_source'] == 'yes' ? " data-social_limit_source='".$instance['social_limit_source']."'" : "")
				.($instance['social_likes'] == 'yes' ? " data-social_likes='".$instance['social_likes']."'" : "")
				.($setting_social_reload > 0 ? " data-social_reload='".$setting_social_reload."'" : "")
			.">
				<i class='fa fa-spinner fa-spin fa-3x'></i>
				<ul class='sf_feeds hide'></ul>
				<ul class='sf_posts";

					if($instance['social_text'] == 'yes')
					{
						echo ($instance['social_read_more'] == 'yes' ? " show_read_more" : '');
					}

					else
					{
						echo " hide_text";
					}

				echo " hide'></ul>";

				if($instance['social_load_more_posts'] == 'yes')
				{
					echo "<div class='form_button'><a href='#' class='load_more_posts button hide'>".__("View More", 'lang_social_feed')."</a></div>";
				}

			echo "</div>"
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
		$instance['social_load_more_posts'] = sanitize_text_field($new_instance['social_load_more_posts']);
		$instance['social_limit_source'] = sanitize_text_field($new_instance['social_limit_source']);
		$instance['social_text'] = sanitize_text_field($new_instance['social_text']);
		$instance['social_likes'] = sanitize_text_field($new_instance['social_likes']);
		$instance['social_read_more'] = sanitize_text_field($new_instance['social_read_more']);

		return $instance;
	}

	function get_display_filter_for_select()
	{
		return array(
			'no' => __("No", 'lang_social_feed'),
			'yes' => __("Yes", 'lang_social_feed')." (".__("Individually", 'lang_social_feed').")",
			'group' => __("Yes", 'lang_social_feed')." (".__("Grouped", 'lang_social_feed').")",
		);
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$arr_data_feeds = array();
		get_post_children(array('post_type' => $this->obj_social_feed->post_type, 'order_by' => 'post_title'), $arr_data_feeds);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('social_heading'), 'text' => __("Heading", 'lang_social_feed'), 'value' => $instance['social_heading'], 'xtra' => " id='social-title'"));

			if(count($arr_data_feeds) > 1)
			{
				echo "<div class='flex_flow'>"
					.show_select(array('data' => $arr_data_feeds, 'name' => $this->get_field_name('social_feeds')."[]", 'text' => __("Feeds", 'lang_social_feed'), 'value' => $instance['social_feeds']));

					if(count($instance['social_feeds']) != 1)
					{
						echo show_select(array('data' => $this->get_display_filter_for_select(), 'name' => $this->get_field_name('social_filter'), 'text' => __("Display Filter", 'lang_social_feed'), 'value' => $instance['social_filter']));
					}

				echo "</div>";
			}

			echo "<div class='flex_flow'>"
				.show_textfield(array('type' => 'number', 'name' => $this->get_field_name('social_amount'), 'text' => __("Amount", 'lang_social_feed'), 'value' => $instance['social_amount']));

				if($instance['social_limit_source'] != 'yes')
				{
					echo show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('social_load_more_posts'), 'text' => __("Load More Posts", 'lang_social_feed'), 'value' => $instance['social_load_more_posts']));
				}

				if(count($instance['social_feeds']) != 1)
				{
					echo show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('social_limit_source'), 'text' => __("Limit Source", 'lang_social_feed')." <i class='fa fa-info-circle blue' title='".__("This will prevent one source from taking over the whole feed if it is posted to much more often than the other sources", 'lang_social_feed')."'></i>", 'value' => $instance['social_limit_source']));
				}

			echo "</div>
			<div class='flex_flow'>"
				.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('social_text'), 'text' => __("Display Text", 'lang_social_feed'), 'value' => $instance['social_text']));

				if($instance['social_text'] == 'yes')
				{
					echo show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('social_read_more'), 'text' => __("Display Read More", 'lang_social_feed'), 'value' => $instance['social_read_more']))
					.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('social_likes'), 'text' => __("Display Likes", 'lang_social_feed'), 'value' => $instance['social_likes']));
				}

			echo "</div>
		</div>";
	}
}