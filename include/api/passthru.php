<?php

if(!defined('ABSPATH'))
{
	//header("Content-Type: application/json");

	$folder = str_replace("/wp-content/plugins/mf_social_feed/include/api", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

if(!isset($obj_social_feed))
{
	$obj_social_feed = new mf_social_feed();
}

$type = check_var('type', 'char');

switch($type)
{
	case 'fb_login':
		$obj_social_feed->get_api_credentials('facebook');

		/*if(!session_id())
		{
			@session_start();
		}*/

		//$sesCallbackURL = check_var('sesCallbackURL');
		$sesCallbackURL = get_user_meta(get_current_user_id(), 'meta_social_feed_callback_url', true);

		/*if($sesCallbackURL == '')
		{
			$sesCallbackURL = get_option('option_social_callback_url');

			if(get_option('setting_social_debug') == 'yes')
			{
				do_log("Got Callback URL from Option: ".$sesCallbackURL);
			}
		}*/

		if($sesCallbackURL != '')
		{
			$url = html_entity_decode($sesCallbackURL);

			$access_token = check_var('access_token');
			//$access_token = get_user_meta(get_current_user_id(), 'meta_social_feed_access_token', true);

			if($access_token != '')
			{
				$arr_vars = array('facebook_user_access_token' => $access_token);

				delete_user_meta(get_current_user_id(), 'meta_social_feed_callback_url');
				//delete_option('option_social_callback_url');

				if(get_option('setting_social_debug') == 'yes')
				{
					do_log("Got access token ".$access_token." for ".$sesCallbackURL);
				}
			}

			else
			{
				$arr_vars = array();

				if(get_option('setting_social_debug') == 'yes')
				{
					do_log("No access token for ".$sesCallbackURL);
				}
			}

			mf_redirect($url, $arr_vars);
		}

		else
		{
			do_log("API Error (".$type."): No session data to use (".var_export($_REQUEST, true).")");
		}

		//session_write_close();
	break;

	case 'instagram_login':
		$obj_social_feed->get_api_credentials('instagram');

		if($obj_social_feed->instagram_client_id != '')
		{
			/*if(!session_id())
			{
				@session_start();
			}*/

			//$sesCallbackURL = check_var('sesCallbackURL');
			$sesCallbackURL = get_user_meta(get_current_user_id(), 'meta_social_feed_callback_url', true);

			/*if($sesCallbackURL == '')
			{
				$sesCallbackURL = get_option('option_social_callback_url');
			}*/

			$access_token = check_var('access_token');
			//$access_token = get_user_meta(get_current_user_id(), 'meta_social_feed_access_token', true);

			if($access_token != '')
			{
				if($sesCallbackURL != '')
				{
					delete_user_meta(get_current_user_id(), 'meta_social_feed_callback_url');
					//delete_option('option_social_callback_url');

					mf_redirect(html_entity_decode($sesCallbackURL), array('instagram_access_token' => $access_token));
				}

				else
				{
					do_log("API Error (".$type."): No session data to use (".var_export($_REQUEST, true).")");
				}
			}

			else
			{
				do_log("API Error (".$type."): Malformed request (".var_export($_REQUEST, true).")");

				if($sesCallbackURL != '')
				{
					mf_redirect(html_entity_decode($sesCallbackURL));
				}
			}

			//session_write_close();
		}

		else
		{
			do_log("API Error (".$type."): Client ID must be set on this site for the API to work on all child sites");
		}
	break;
}