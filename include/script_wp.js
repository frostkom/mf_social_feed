jQuery(function($)
{
	$(document).on('click', ".social_feed_post_action", function(e)
	{
		var dom_obj = $(e.currentTarget);
			action_type = '',
			action_id = dom_obj.attr('href').replace('#id_', '');

		if(dom_obj.hasClass('api_social_feed_action_hide'))
		{
			action_type = 'api_social_feed_action_hide';
		}

		else if(dom_obj.hasClass('api_social_feed_action_ignore'))
		{
			action_type = 'api_social_feed_action_ignore';
		}

		if(action_type != '')
		{
			var confirm_text = dom_obj.attr('confirm_text');

			if(typeof confirm_text != 'undefined' && !confirm(confirm_text))
			{
				return false;
			}

			dom_obj.html(script_social_feed_wp.loading_animation);

			$.ajax(
			{
				url: script_social_feed_wp.ajax_url,
				type: 'post',
				dataType: 'json',
				data: {
					action: action_type,
					action_id: action_id
				},
				success: function(data)
				{
					dom_obj.html(data.html);
				}
			});
		}

		return false;
	});
});