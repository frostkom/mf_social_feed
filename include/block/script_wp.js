(function()
{
	var __ = wp.i18n.__,
		el = wp.element.createElement,
		registerBlockType = wp.blocks.registerBlockType,
		SelectControl = wp.components.SelectControl,
		TextControl = wp.components.TextControl,
		MediaUpload = wp.blockEditor.MediaUpload,
	    Button = wp.components.Button,
		MediaUploadCheck = wp.blockEditor.MediaUploadCheck;

	registerBlockType('mf/socialfeed',
	{
		title: __("Social Feed", 'lang_social_feed'),
		description: __("Display a Social Feed", 'lang_social_feed'),
		icon: 'megaphone',
		category: 'widgets',
		'attributes':
		{
			'align':
			{
				'type': 'string',
				'default': ''
			},
			'social_heading':
			{
                'type': 'string',
                'default': ''
            },
			'social_feeds':
			{
                'type': 'array',
                'default': ''
            },
			'social_filter':
			{
                'type': 'string',
                'default': 'no'
            },
			'social_amount':
			{
                'type': 'string',
                'default': 18
            },
			'social_load_more_posts':
			{
                'type': 'string',
                'default': 'no'
            },
			'social_limit_source':
			{
                'type': 'string',
                'default': 'no'
            },
			'social_text':
			{
                'type': 'string',
                'default': 'yes'
            },
			'social_likes':
			{
                'type': 'string',
                'default': 'no'
            },
			'social_read_more':
			{
                'type': 'string',
                'default': 'yes'
            }
		},
		'supports':
		{
			'html': false,
			'multiple': false,
			'align': true,
			'spacing':
			{
				'margin': true,
				'padding': true
			},
			'color':
			{
				'background': true,
				'gradients': false,
				'text': true
			},
			'defaultStylePicker': true,
			'typography':
			{
				'fontSize': true,
				'lineHeight': true
			},
			"__experimentalBorder":
			{
				"radius": true
			}
		},
		edit: function(props)
		{
			var arr_out = [];

			/* Text */
			/* ################### */
			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					TextControl,
					{
						label: __("Heading", 'lang_social_feed'),
						type: 'text',
						value: props.attributes.social_heading,
						onChange: function(value)
						{
							props.setAttributes({social_heading: value});
						}
					}
				)
			));
			/* ################### */

			/* Select */
			/* ################### */
			var arr_options = [];

			jQuery.each(script_social_feed_block_wp.social_feeds, function(index, value)
			{
				if(index == "")
				{
					index = 0;
				}

				arr_options.push({label: value, value: index});
			});

			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					SelectControl,
					{
						label: __("Feeds", 'lang_social_feed'),
						value: props.attributes.social_feeds,
						options: arr_options,
						multiple: true,
						onChange: function(value)
						{
							props.setAttributes({social_feeds: value});
						}
					}
				)
			));
			/* ################### */

			/* Select */
			/* ################### */
			var arr_options = [];

			jQuery.each(script_social_feed_block_wp.social_filter, function(index, value)
			{
				if(index == "")
				{
					index = 0;
				}

				arr_options.push({label: value, value: index});
			});

			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					SelectControl,
					{
						label: __("Display Filter", 'lang_social_feed'),
						value: props.attributes.social_filter,
						options: arr_options,
						onChange: function(value)
						{
							props.setAttributes({social_filter: value});
						}
					}
				)
			));
			/* ################### */

			/* Text */
			/* ################### */
			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					TextControl,
					{
						label: __("Amount", 'lang_social_feed'),
						type: 'number',
						value: props.attributes.social_amount,
						onChange: function(value)
						{
							props.setAttributes({social_amount: value});
						}
					}
				)
			));
			/* ################### */

			/* Select */
			/* ################### */
			var arr_options = [];

			jQuery.each(script_social_feed_block_wp.yes_no_for_select, function(index, value)
			{
				if(index == "")
				{
					index = 0;
				}

				arr_options.push({label: value, value: index});
			});

			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					SelectControl,
					{
						label: __("Load More Posts", 'lang_social_feed'),
						value: props.attributes.social_load_more_posts,
						options: arr_options,
						onChange: function(value)
						{
							props.setAttributes({social_load_more_posts: value});
						}
					}
				)
			));
			/* ################### */

			/* Select */
			/* ################### */
			var arr_options = [];

			jQuery.each(script_social_feed_block_wp.yes_no_for_select, function(index, value)
			{
				if(index == "")
				{
					index = 0;
				}

				arr_options.push({label: value, value: index});
			});

			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					SelectControl,
					{
						label: __("Limit Source", 'lang_social_feed'), /* + " <i class='fa fa-info-circle blue' title='" + __("This will prevent one source from taking over the whole feed if it is posted to much more often than the other sources", 'lang_social_feed') + "'></i>"*/
						value: props.attributes.social_limit_source,
						options: arr_options,
						onChange: function(value)
						{
							props.setAttributes({social_limit_source: value});
						}
					}
				)
			));
			/* ################### */

			/* Select */
			/* ################### */
			var arr_options = [];

			jQuery.each(script_social_feed_block_wp.yes_no_for_select, function(index, value)
			{
				if(index == "")
				{
					index = 0;
				}

				arr_options.push({label: value, value: index});
			});

			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					SelectControl,
					{
						label: __("Display Text", 'lang_social_feed'),
						value: props.attributes.social_text,
						options: arr_options,
						onChange: function(value)
						{
							props.setAttributes({social_text: value});
						}
					}
				)
			));
			/* ################### */

			/* Select */
			/* ################### */
			var arr_options = [];

			jQuery.each(script_social_feed_block_wp.yes_no_for_select, function(index, value)
			{
				if(index == "")
				{
					index = 0;
				}

				arr_options.push({label: value, value: index});
			});

			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					SelectControl,
					{
						label: __("Display Read More", 'lang_social_feed'),
						value: props.attributes.social_read_more,
						options: arr_options,
						onChange: function(value)
						{
							props.setAttributes({social_read_more: value});
						}
					}
				)
			));
			/* ################### */

			/* Select */
			/* ################### */
			var arr_options = [];

			jQuery.each(script_social_feed_block_wp.yes_no_for_select, function(index, value)
			{
				if(index == "")
				{
					index = 0;
				}

				arr_options.push({label: value, value: index});
			});

			arr_out.push(el(
				'div',
				{className: "wp_mf_block " + props.className},
				el(
					SelectControl,
					{
						label: __("Display Likes", 'lang_social_feed'),
						value: props.attributes.social_likes,
						options: arr_options,
						onChange: function(value)
						{
							props.setAttributes({social_likes: value});
						}
					}
				)
			));
			/* ################### */

			return arr_out;
		},
		save: function()
		{
			return null;
		}
	});
})();