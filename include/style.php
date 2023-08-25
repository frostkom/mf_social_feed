<?php

if(!defined('ABSPATH'))
{
	header("Content-Type: text/css; charset=utf-8");

	$folder = str_replace("/wp-content/plugins/mf_social_feed/include", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

$setting_social_design = get_option('setting_social_design');
$setting_social_full_width = get_option('setting_social_full_width');
$setting_social_desktop_columns = get_option_or_default('setting_social_desktop_columns', 3);
$setting_social_tablet_columns = get_option_or_default('setting_social_tablet_columns', 2);
$setting_social_mobile_columns = 1;

$setting_social_display_border = get_option('setting_social_display_border', 'yes');

$post_container_desktop = $post_container_tablet = $post_container_mobile = $post_item_desktop = $post_item_tablet = $post_item_mobile = "";

if(!function_exists('calc_width'))
{
	function calc_width($columns)
	{
		return (100 / $columns) - ($columns > 1 ? 1 : 0);
	}
}

switch($setting_social_design)
{
	case 'masonry':
		$post_container_desktop = "-webkit-column-count: ".$setting_social_desktop_columns.";
		column-count: ".$setting_social_desktop_columns.";";

		$post_container_tablet = "-webkit-column-count: ".$setting_social_tablet_columns.";
		column-count: ".$setting_social_tablet_columns.";";

		$post_container_mobile = "-webkit-column-count: ".$setting_social_mobile_columns.";
		column-count: ".$setting_social_mobile_columns.";";

		$post_item_desktop = "-webkit-column-break-inside: avoid;
		page-break-inside: avoid;
		break-inside: avoid;";
	break;

	default:
		$column_width_desktop = calc_width($setting_social_desktop_columns);
		$column_width_tablet = calc_width($setting_social_tablet_columns);
		$column_width_mobile = calc_width($setting_social_mobile_columns);

		$post_container_desktop = "display: -webkit-box;
		display: -ms-flexbox;
		display: -webkit-flex;
		display: flex;
		-webkit-box-flex-wrap: wrap;
		-webkit-flex-wrap: wrap;
		-ms-flex-wrap: wrap;
		flex-wrap: wrap;";

		$post_item_desktop = "-webkit-box-flex: 0 1 auto;
		-webkit-flex: 0 1 auto;
		-ms-flex: 0 1 auto;
		flex: 0 1 auto;
		width: ".$column_width_desktop."%;";

		$post_item_tablet = "width: ".$column_width_tablet."%;";

		$post_item_mobile = "width: ".$column_width_mobile."%;";
	break;
}

echo "@media all
{
	.widget.social_feed
	{
		overflow: hidden;
	}";

		if($setting_social_full_width == 'yes')
		{
			echo ".widget.social_feed .section
			{
				max-width: 100% !important;
			}";
		}

		echo ".widget.social_feed .sf_feeds
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
		{"
			.$post_container_desktop
			."list-style: none;
		}";

			if($post_container_tablet != '')
			{
				echo ".is_tablet .widget.social_feed .sf_posts
				{"
					.$post_container_tablet
				."}";
			}

			if($post_container_mobile != '')
			{
				echo ".is_mobile .widget.social_feed .sf_posts
				{"
					.$post_container_mobile
				."}";
			}

			echo ".widget.social_feed .sf_posts li
			{"
				.$post_item_desktop
				."margin: 0 0 .6em;
				overflow: hidden;
				position: relative;
				text-align: left;
			}

				.is_mobile .widget.social_feed .sf_posts li + li, .widget.social_feed .sf_posts.one_column li + li
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
				}";

				if($setting_social_display_border == 'yes')
				{
					echo ".widget.social_feed .sf_posts
					{
						margin-right: -.5%;
						margin-left: -.5%;
					}

						.widget.social_feed .sf_posts li
						{
							background: #fff;
							box-shadow: 0 .5rem .75rem rgba(0, 0, 0, .15);
							border-top: 0;
							margin-right: .5%;
							margin-left: .5%;
						}";
				}

			if($post_item_tablet != '')
			{
				echo ".is_tablet .widget.social_feed .sf_posts li
				{"
					.$post_item_tablet
				."}";
			}

			if($post_item_mobile != '')
			{
				echo ".is_mobile .widget.social_feed .sf_posts li, .widget.social_feed .sf_posts.one_column li
				{"
					.$post_item_mobile
				."}";
			}

				echo ".widget.social_feed img
				{
					display: block;
					transition: all 1s ease;
				}

					.widget.social_feed li:hover img
					{
						-webkit-transform: scale(1.1);
						transform: scale(1.1);
					}

				.widget.social_feed .content
				{
					clear: both;
					display: block;
					overflow: hidden;
					padding: .5em;
				}";

					if($setting_social_display_border == 'yes')
					{
						echo ".widget.social_feed .content
						{
							padding: 1em;
						}";
					}

					echo ".widget.social_feed .meta
					{
						display: -webkit-box;
						display: -ms-flexbox;
						display: -webkit-flex;
						display: flex;
					}

						.widget.social_feed .sf_posts li .meta .fa, .widget.social_feed .sf_posts li .meta .fab
						{
							-webkit-box-flex: 0 0 auto;
							-webkit-flex: 0 0 auto;
							-ms-flex: 0 0 auto;
							flex: 0 0 auto;
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
							-webkit-box-flex: 0 auto auto;
							-webkit-flex: 0 auto auto;
							-ms-flex: 0 auto auto;
							flex: 0 auto auto;
							font-size: .7em;
							line-height: 1.5;
							margin-right: .5em;
							overflow: hidden;
							text-overflow: ellipsis;
							white-space: nowrap;
						}

						.widget.social_feed .date
						{
							color: #ccc;
							-webkit-box-flex: 1 0 0;
							-webkit-flex: 1 0 0;
							-ms-flex: 1 0 0;
							flex: 1 0 0;
							font-size: .6em;
							line-height: 1.8;
							text-align: right;
						}

					.widget.social_feed p, .widget.social_feed .text
					{
						margin-top: .5em;
					}

						.widget.social_feed .text
						{
							font-size: .9em;
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

								.widget.social_feed .shorten-more-link
								{
									display: inline-block;
									margin: .5em 0 0 !important;
									position: relative;
									text-decoration: underline;
									z-index: 1;
								}

				.widget.social_feed .likes
				{
					background: #edeeef;
					margin-top: .5em;
					padding: .5em;
				}

					.widget.social_feed .likes .fa, .widget.social_feed .likes .fab, .widget.social_feed .likes span
					{
						color: #333;
						display: inline-block;
						margin-right: .5em;
					}

		.widget.social_feed .load_more_posts
		{
			margin-top: 1em;
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