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

		if(!session_id())
		{
			@session_start();
		}

		$client_id = check_var('client_id');
		$client_secret = check_var('client_secret');
		$callback_url = check_var('callback_url');
		$code = check_var('code');

		if($client_id != '' && $client_secret != '' && $callback_url != '')
		{
			$_SESSION['sesClientID'] = $client_id;
			$_SESSION['sesClientSecret'] = $client_secret;
			$_SESSION['sesCallbackURL'] = $callback_url;

			$url = $obj_social_feed->facebook_code_url."?client_id=".$client_id."&redirect_uri=".urlencode($obj_social_feed->facebook_redirect_url);
			mf_redirect($url);
		}
		
		else if($code != '')
		{
			$sesClientID = check_var('sesClientID');
			$sesClientSecret = check_var('sesClientSecret');
			$sesCallbackURL = check_var('sesCallbackURL');

			if($sesClientID != '' && $sesClientSecret != '' && $sesCallbackURL != '')
			{
				$url = $obj_social_feed->facebook_access_token_url
					."?client_id=".$sesClientID
					."&client_secret=".$sesClientSecret
					."&redirect_uri=".urlencode($obj_social_feed->facebook_redirect_url)
					."&code=".$code;
				//mf_redirect($url);

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
							unset($_SESSION['sesClientID']);
							unset($_SESSION['sesClientSecret']);
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
	break;
}