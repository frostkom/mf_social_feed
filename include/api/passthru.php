<?php

if(!defined('ABSPATH'))
{
	//header('Content-Type: application/json');

	$folder = str_replace("/wp-content/plugins/mf_social_feed/include/api", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

$obj_social_feed = new mf_social_feed();

$type = check_var('type', 'char');

switch($type)
{
	case 'fb_login':
		$obj_social_feed->get_api_credentials('facebook');

		if($obj_social_feed->facebook_api_id != '' && $obj_social_feed->facebook_api_secret != '')
		{
			if(!session_id())
			{
				@session_start();
			}

			$callback_url = check_var('callback_url');
			$code = check_var('code');

			if($callback_url != '')
			{
				$_SESSION['sesCallbackURL'] = $callback_url;

				$url = $obj_social_feed->facebook_code_url."?client_id=".$obj_social_feed->facebook_api_id."&redirect_uri=".urlencode($obj_social_feed->facebook_redirect_url);
				mf_redirect($url);
			}
			
			else if($code != '')
			{
				$sesCallbackURL = check_var('sesCallbackURL');

				if($sesCallbackURL != '')
				{
					$url = $obj_social_feed->facebook_access_token_url
						."?client_id=".$obj_social_feed->facebook_api_id
						."&client_secret=".$obj_social_feed->facebook_api_secret
						."&redirect_uri=".urlencode($obj_social_feed->facebook_redirect_url)
						."&code=".$code;

					list($content, $headers) = get_url_content(array(
						'url' => $url,
						'catch_head' => true,
					));

					switch($headers['http_code'])
					{
						case 200:
							$json = json_decode($content);

							if($json->access_token != '')
							{
								unset($_SESSION['sesCallbackURL']);

								$url = html_entity_decode($sesCallbackURL);
								mf_redirect($url, array('access_token' => $json->access_token));
							}

							else
							{
								do_log("API Error (".$type."): Malformed response (".$content.")");
							}
						break;

						default:
							do_log("I could not connect to FB: ".$headers['http_code']." (".var_export($headers, true).", ".$content.")");
						break;
					}
				}
				
				else
				{
					do_log("API Error (".$type."): No session data to use (".var_export($_REQUEST, true).", ".var_export($_SESSION, true).")");
				}
			}

			else
			{
				do_log("API Error (".$type."): Malformed request (".var_export($_REQUEST, true).")");
			}
		}

		else
		{
			do_log("API Error (".$type."): App ID and Secret must be set on this site for the API to work on all child sites");
		}
	break;
}