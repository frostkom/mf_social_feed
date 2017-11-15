<?php

$is_standalone = !defined('ABSPATH');

if($is_standalone)
{
	header("Content-Type: text/css; charset=utf-8");

	$folder = str_replace("/wp-content/plugins/mf_social_feed/include", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

$setting_social_full_width = get_option('setting_social_full_width');
$setting_social_desktop_columns = get_option_or_default('setting_social_desktop_columns', 3);
$setting_social_tablet_columns = get_option_or_default('setting_social_tablet_columns', 2);

$column_width_desktop = (100 / $setting_social_desktop_columns) - 1;
$column_width_tablet = (100 / $setting_social_tablet_columns) - 1;
$column_width_mobile = 100;

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

		echo ".widget.social_feed ul.sf_feeds
		{
			font-size: .8em;
			list-style: none;
		}

			.widget.social_feed ul.sf_feeds li
			{
				display: inline-block;
				overflow: hidden;
			}

				.widget.social_feed ul.sf_feeds a
				{
					display: block;
					padding: 1.5em 1em .5em;
				}

					.widget.social_feed ul.sf_feeds li.active a
					{
						background: #ff993d;
						color: #fff;
					}

		.widget.social_feed ul.sf_posts
		{
			display: -webkit-box;
			display: -ms-flexbox;
			display: -webkit-flex;
			display: flex;
			-webkit-box-flex-wrap: wrap;
			-webkit-flex-wrap: wrap;
			-ms-flex-wrap: wrap;
			flex-wrap: wrap;
			list-style: none;
		}

			.widget.social_feed ul.sf_posts li
			{
				-webkit-box-flex: 0 1 auto;
				-webkit-flex: 0 1 auto;
				-ms-flex: 0 1 auto;
				flex: 0 1 auto;
				margin-top: 1em;
				overflow: hidden;
				padding: .5em;
				position: relative;
				text-align: left;
				width: ".$column_width_desktop."%;
			}

				.widget.social_feed ul.sf_feeds + ul.sf_posts:first-child li:first-child
				{
					margin-top: 0;
				}

				.is_mobile .widget.social_feed ul.sf_posts li + li, .widget.social_feed ul.sf_posts.one_column li + li
				{
					border-top: 1px solid #ccc;
					padding-top: 1em;
				}

				.widget.social_feed ul.sf_posts.show_border li
				{
					background: #fff;
					box-shadow: 0 .5rem .75rem rgba(0, 0, 0, .15);
					border-top: 0;
					margin-right: .5%;
					margin-left: .5%;
					padding: 1em;
				}

			.is_tablet .widget.social_feed ul.sf_posts li
			{
				width: ".$column_width_tablet."%;
			}

			.is_mobile .widget.social_feed ul.sf_posts li, .widget.social_feed ul.sf_posts.one_column li
			{
				width: ".$column_width_mobile."%;
			}

				.widget.social_feed ul.sf_posts li > .fa
				{
					float: left;
				}

					.sf_facebook .fa, .column-type .fa-facebook
					{
						color: #3b5998;
					}

					.sf_instagram .fa, .column-type .fa-instagram
					{
						color: #c02f2e;
					}

					.sf_rss .fa, .column-type .fa-rss
					{
						color: #e9bb63;
					}

					.sf_twitter .fa, .column-type .fa-twitter
					{
						color: #55acee;
					}

				.widget.social_feed .title, .widget.social_feed .name
				{
					float: left;
					font-size: .8em;
					line-height: 1.5;
					margin-left: .5em;
				}

				.widget.social_feed .date
				{
					color: #ccc;
					float: right;
					font-size: .8em;
					line-height: 1.5;
				}

				.widget.social_feed .content
				{
					clear: both;
					display: block;
					overflow: hidden;
				}

					.widget.social_feed img
					{
						display: block;
						margin-top: .5em;
					}

					.widget.social_feed p
					{
						margin-top: .5em;
					}

						.widget.social_feed p + p
						{
							font-size: .9em;
						}

						.shorten-shortened
						{
							position: relative;
						}

							.shorten-shortened:after
							{
								bottom: 0;
								content: '';
								height: 40%;
								left: 0;
								position: absolute;
								right: 0;
							}

								.shorten-clipped
								{
									opacity: 0;
								}

								.shorten-more-link
								{
									display: inline-block;
									margin: .5em 0 0 !important;
									position: relative;
									text-decoration: none;
									z-index: 1;
								}

				.widget.social_feed .likes
				{
					background: #edeeef;
					margin-top: .5em;
					padding: .5em;
				}

					.widget.social_feed .likes .fa, .widget.social_feed .likes span
					{
						color: #333;
						display: inline-block;
						margin-right: .5em;
					}

	#overlay_lost_connection
	{
		background: rgba(0, 0, 0, .5);
		bottom: 0;
		color: #ccc;
		display: none;
		font-size: 4em;
		left: 0;
		position: fixed;
		right: 0;
		text-align: center;
		top: 0;
		z-index: 1002;
	}

		#overlay_lost_connection span
		{
			display: inline-block;
			margin-top: 50vh;
			-webkit-transform: translateY(-50%);
			transform: translateY(-50%);
		}
}";