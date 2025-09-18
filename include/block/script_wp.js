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
			'social_amount':
			{
                'type': 'string',
                'default': 6
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
							TextControl,
							{
								label: script_social_feed_block_wp.social_amount_label,
								type: 'number',
								value: props.attributes.social_amount,
								onChange: function(value)
								{
									props.setAttributes({social_amount: value});
								},
								min: 0,
								step: 3,
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