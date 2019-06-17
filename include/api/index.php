<?php

if(!defined('ABSPATH'))
{
	header('Content-Type: application/json');

	$folder = str_replace("/wp-content/plugins/mf_social_feed/include/api", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

if(is_plugin_active('mf_cache/index.php'))
{
	$obj_cache = new mf_cache();
	$obj_cache->fetch_request();
	$obj_cache->get_or_set_file_content('json');
}

$json_output = array();

$type = check_var('type', 'char');

switch($type)
{
	case 'posts':
		$feed_id = check_var('feed_id', 'char');
		$feeds = check_var('feeds', 'char');
		$filter = check_var('filter', 'char');
		$amount = check_var('amount', 'int');
		$load_more_posts = check_var('load_more_posts', 'char');
		$limit_source = check_var('limit_source', 'char');
		$likes = check_var('likes', 'char');

		if($feeds != '')
		{
			$feeds = explode(",", $feeds);
		}

		$obj_social_feed = new mf_social_feed();
		list($arr_post_feeds, $arr_post_posts, $has_more_posts) = $obj_social_feed->get_feeds_and_posts(array('feeds' => $feeds, 'filter' => $filter, 'amount' => $amount, 'limit_source' => $limit_source, 'likes' => $likes));

		$json_output['success'] = true;
		$json_output['feed_id'] = $feed_id;
		$json_output['response_feeds'] = $arr_post_feeds;
		$json_output['response_posts'] = $arr_post_posts;
		$json_output['has_more_posts'] = ($load_more_posts == 'yes' && $has_more_posts == true);
	break;
}

echo json_encode($json_output);