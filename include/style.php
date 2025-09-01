<?php

if(!defined('ABSPATH'))
{
	header("Content-Type: text/css; charset=utf-8");

	$folder = str_replace("/wp-content/plugins/mf_social_feed/include", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

// Same as in Navigation
##########################
$setting_breakpoint_tablet = apply_filters('get_styles_content', '', 'max_width');

if($setting_breakpoint_tablet != '')
{
	preg_match('/^([0-9]*\.?[0-9]+)([a-zA-Z%]+)$/', $setting_breakpoint_tablet, $matches);

	$setting_breakpoint_tablet = $matches[1];
	$setting_breakpoint_suffix = $matches[2];

	$setting_breakpoint_mobile = ($setting_breakpoint_tablet * .775);
}

else
{
	$setting_breakpoint_tablet = get_option_or_default('setting_navigation_breakpoint_tablet', 1200);
	$setting_breakpoint_mobile = get_option_or_default('setting_navigation_breakpoint_mobile', 930);

	$setting_breakpoint_suffix = "px";
}
##########################

$setting_social_desktop_columns = 3;
$setting_social_tablet_columns = 2;
$setting_social_mobile_columns = 1;

if(!function_exists('calc_width'))
{
	function calc_width($columns)
	{
		return (100 / $columns) - ($columns > 1 ? 1 : 0);
	}
}

$column_width_desktop = calc_width($setting_social_desktop_columns);
$column_width_tablet = calc_width($setting_social_tablet_columns);
$column_width_mobile = calc_width($setting_social_mobile_columns);

echo "@media all
{
	.widget.social_feed .sf_feeds
	{
		font-size: .8em;
		list-style: none;
		margin-bottom: 1em;
	}

		.widget.social_feed .sf_feeds li
		{
			display: inline-block;
			overflow: hidden;
		}

			.widget.social_feed .sf_feeds a
			{
				display: block;
				padding: 1.5em 1em .5em;
			}

				.widget.social_feed .sf_feeds li.active a
				{
					background: #ff993d;
					color: #fff;
				}

	.widget.social_feed .sf_posts
	{
		list-style: none;
		padding-left: 0;
	}
	
		.widget.social_feed.masonry .sf_posts
		{
			column-count: ".$setting_social_desktop_columns.";
		}
		
		.widget.social_feed.square .sf_posts
		{
			display: flex;
			flex-wrap: wrap;
		}

		.widget.social_feed .sf_posts li
		{
			margin: 0 0 .6em;
			overflow: hidden;
			position: relative;
			text-align: left;
		}
		
			.widget.social_feed.masonry .sf_posts li
			{
				page-break-inside: avoid;
				break-inside: avoid;
			}
			
			.widget.social_feed.square .sf_posts li
			{
				flex: 0 1 auto;
				width: ".$column_width_desktop."%;
			}

			.widget.social_feed .sf_posts.one_column li + li
			{
				border-top: 1px solid #ccc;
				padding-top: 1em;
			}

			.widget.social_feed .sf_posts .image
			{
				overflow: hidden;
			}

			.widget.social_feed .sf_posts.hide_text .fa, .widget.social_feed .sf_posts.hide_text .fab, .widget.social_feed .sf_posts.hide_text .name, .widget.social_feed .sf_posts.hide_text .date, .widget.social_feed .sf_posts.hide_text .content .text
			{
				display: none;
			}

				.widget.social_feed .sf_posts.hide_text .content
				{
					height: 100%;
				}

					.widget.social_feed .sf_posts.hide_text .content img
					{
						height: 100%;
						margin-top: 0;
						object-fit: cover;
					}

			.widget.social_feed .sf_posts li.no_result
			{
				padding: 1em;
			}

				.widget.social_feed .sf_posts
				{
					gap: 1%;
				}

					.widget.social_feed .sf_posts li
					{
						background: #fff;
						box-shadow: 0 .5rem .75rem rgba(0, 0, 0, .15);
					}

			.widget.social_feed img
			{
				display: block;
				object-fit: cover;
				transition: all 1s ease;
				width: 100%;
			}

				.widget.social_feed li:hover img
				{
					transform: scale(1.1);
				}

			.widget.social_feed .content
			{
				clear: both;
				display: block;
				overflow: hidden;
				padding: .5em;
			}

				.widget.social_feed .content
				{
					padding: 1em;
				}

				.widget.social_feed .meta
				{
					font-size: .7em;
					margin-bottom: .5em;
				}

					.widget.social_feed .meta > a
					{
						text-decoration: none;
					}

					.widget.social_feed .sf_posts li .meta .fa, .widget.social_feed .sf_posts li .meta .fab
					{
						margin-right: .5em;
					}

						.sf_facebook .fa, .sf_facebook .fab, .column-type .fa-facebook
						{
							color: #3b5998;
						}

						.sf_instagram .fa, .sf_instagram .fab, .column-type .fa-instagram
						{
							color: #c02f2e;
						}

						.sf_linkedin .fa, .sf_linkedin .fab, .column-type .fa-linkedin-in
						{
							color: #0077b5;
						}

						.sf_rss .fa, .sf_rss .fab, .column-type .fa-rss
						{
							color: #e9bb63;
						}

						.sf_twitter .fa, .sf_twitter .fab, .column-type .fa-twitter
						{
							color: #55acee;
						}

					.widget.social_feed .title, .widget.social_feed .name
					{
						margin-right: .5em;
					}

					.widget.social_feed .date
					{
						color: #ccc;
						font-size: .9em;
					}

					.widget.social_feed .text
					{
						font-size: .9em;
					}

						.widget.social_feed .text a
						{
							text-decoration: none;
						}

					.widget.social_feed .shorten-shortened
					{
						position: relative;
					}

						.widget.social_feed .shorten-shortened:after
						{
							bottom: 0;
							content: '';
							height: 40%;
							left: 0;
							position: absolute;
							right: 0;
						}

							.widget.social_feed .shorten-clipped
							{
								opacity: 0;
							}

							.widget.social_feed .shorten-ellipsis > div
							{
								text-align: right;
							}

								.widget.social_feed .shorten-more-link
								{
									font-size: 0.9em;
									margin: .5em 0 0 !important;
									padding: .5em 1em;
									position: relative;
									z-index: 1000;
								}

	.social_debug
	{
		background: #000;
		background: rgba(0, 0, 0, .5);
		bottom: 0;
		color: #fff;
		display: none;
		left: 0;
		padding: .5em;
		position: fixed;
	}
}";

if($setting_breakpoint_mobile > 0 && $setting_breakpoint_tablet > $setting_breakpoint_mobile)
{
	echo "@media screen and (min-width: ".$setting_breakpoint_mobile.$setting_breakpoint_suffix.") and (max-width: ".($setting_breakpoint_tablet - 1).$setting_breakpoint_suffix.")
	{
		.widget.social_feed.masonry .sf_posts
		{
			column-count: ".$setting_social_tablet_columns.";
		}

		.widget.social_feed.square .sf_posts li
		{
			width: ".$column_width_tablet."%;
		}
	}";
}

if($setting_breakpoint_mobile > 0)
{
	echo "@media screen and (max-width: ".($setting_breakpoint_mobile - 1).$setting_breakpoint_suffix.")
	{
		.widget.social_feed.masonry .sf_posts
		{
			column-count: ".$setting_social_mobile_columns.";
		}

		.widget.social_feed.square .sf_posts li
		{
			width: ".$column_width_mobile."%;
		}

			.widget.social_feed .sf_posts li + li
			{
				border-top: 1px solid #ccc;
				padding-top: 1em;
			}
	}";
}