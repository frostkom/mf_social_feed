(function()
{
	var el = wp.element.createElement,
		registerBlockType = wp.blocks.registerBlockType,
		SelectControl = wp.components.SelectControl,
		TextControl = wp.components.TextControl,
		InspectorControls = wp.blockEditor.InspectorControls;

	registerBlockType('mf/socialfeed',
	{
		title: script_social_feed_block_wp.block_title,
		description: script_social_feed_block_wp.block_description,
		icon: 'megaphone',
		category: 'widgets',
		'attributes':
		{
			'align':
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
			return el(
				'div',
				{className: 'wp_mf_block_container'},
				[
					el(
						InspectorControls,
						'div',
						el(
							SelectControl,
							{
								label: script_social_feed_block_wp.social_feeds_label,
								value: props.attributes.social_feeds,
								options: convert_php_array_to_block_js(script_social_feed_block_wp.social_feeds),
								multiple: true,
								onChange: function(value)
								{
									props.setAttributes({social_feeds: value});
								}
							}
						),
						el(
							SelectControl,
							{
								label: script_social_feed_block_wp.social_filter_label,
								value: props.attributes.social_filter,
								options: convert_php_array_to_block_js(script_social_feed_block_wp.social_filter),
								onChange: function(value)
								{
									props.setAttributes({social_filter: value});
								}
							}
						),
						el(
							TextControl,
							{
								label: script_social_feed_block_wp.social_amount_label,
								type: 'number',
								value: props.attributes.social_amount,
								onChange: function(value)
								{
									props.setAttributes({social_amount: value});
								}
							}
						),
						el(
							SelectControl,
							{
								label: script_social_feed_block_wp.social_load_more_posts_label,
								value: props.attributes.social_load_more_posts,
								options: convert_php_array_to_block_js(script_social_feed_block_wp.yes_no_for_select),
								onChange: function(value)
								{
									props.setAttributes({social_load_more_posts: value});
								}
							}
						),
						el(
							SelectControl,
							{
								label: script_social_feed_block_wp.social_limit_source_label, /* + " <i class='fa fa-info-circle blue' title='" + __("This will prevent one source from taking over the whole feed if it is posted to much more often than the other sources", 'lang_social_feed') + "'></i>"*/
								value: props.attributes.social_limit_source,
								options: convert_php_array_to_block_js(script_social_feed_block_wp.yes_no_for_select),
								onChange: function(value)
								{
									props.setAttributes({social_limit_source: value});
								}
							}
						),
						el(
							SelectControl,
							{
								label: script_social_feed_block_wp.social_text_label,
								value: props.attributes.social_text,
								options: convert_php_array_to_block_js(script_social_feed_block_wp.yes_no_for_select),
								onChange: function(value)
								{
									props.setAttributes({social_text: value});
								}
							}
						),
						el(
							SelectControl,
							{
								label: script_social_feed_block_wp.social_read_more_label,
								value: props.attributes.social_read_more,
								options: convert_php_array_to_block_js(script_social_feed_block_wp.yes_no_for_select),
								onChange: function(value)
								{
									props.setAttributes({social_read_more: value});
								}
							}
						),
						el(
							SelectControl,
							{
								label: script_social_feed_block_wp.social_likes_label,
								value: props.attributes.social_likes,
								options: convert_php_array_to_block_js(script_social_feed_block_wp.yes_no_for_select),
								onChange: function(value)
								{
									props.setAttributes({social_likes: value});
								}
							}
						)
					),
					el(
						'strong',
						{className: props.className},
						script_social_feed_block_wp.block_title
					)
				]
			);
		},
		save: function()
		{
			return null;
		}
	});
})();