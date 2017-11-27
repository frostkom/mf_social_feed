<?php

$wp_root = '../../../..';

if(file_exists($wp_root.'/wp-load.php'))
{
	require_once($wp_root.'/wp-load.php');
}

else
{
	require_once($wp_root.'/wp-config.php');
}

$json_output = "";

$type = check_var('type', 'char');
$feeds = check_var('feeds', 'char');
$amount = check_var('amount', 'int');
$filter = check_var('filter', 'char');
$likes = check_var('likes', 'char');

if($type == 'posts')
{
	if($feeds != '')
	{
		$feeds = explode(",", $feeds);
	}

	$obj_social_feed = new mf_social_feed();
	list($arr_post_feeds, $arr_post_posts) = $obj_social_feed->get_feeds_and_posts(array('feeds' => $feeds, 'amount' => $amount, 'filter' => $filter, 'likes' => $likes));

	$json_output['response_feeds'] = $arr_post_feeds;
	$json_output['response_posts'] = $arr_post_posts;
	$json_output['success'] = true;
}

echo json_encode($json_output);