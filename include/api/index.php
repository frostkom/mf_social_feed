<?php

if(!defined('ABSPATH'))
{
	header("Content-Type: application/json");

	$folder = str_replace("/wp-content/plugins/mf_social_feed/include/api", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

do_action('run_cache', array('suffix' => 'json'));

if(!isset($obj_social_feed))
{
	$obj_social_feed = new mf_social_feed();
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

		list($arr_post_feeds, $arr_post_posts, $has_more_posts) = $obj_social_feed->get_feeds_and_posts(array('feeds' => $feeds, 'filter' => $filter, 'amount' => $amount, 'limit_source' => $limit_source, 'likes' => $likes));

		$json_output['success'] = true;
		$json_output['feed_id'] = $feed_id;
		$json_output['response_feeds'] = $arr_post_feeds;
		$json_output['response_posts'] = $arr_post_posts;
		$json_output['has_more_posts'] = ($load_more_posts == 'yes' && $has_more_posts == true);
	break;
}

echo json_encode($json_output);