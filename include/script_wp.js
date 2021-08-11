jQuery(function($)
{
	$(document).on('click', ".social_feed_post_action", function(e)
	{
		var dom_obj = $(e.currentTarget);
			action_type = '',
			action_id = dom_obj.attr('href').replace('#id_', '');

		if(dom_obj.hasClass('social_feed_action_hide'))
		{
			action_type = 'social_feed_action_hide';
		}

		else if(dom_obj.hasClass('social_feed_action_ignore'))
		{
			action_type = 'social_feed_action_ignore';
		}

		if(action_type != '')
		{
			var confirm_text = dom_obj.attr('confirm_text');

			if(typeof confirm_text != 'undefined' && !confirm(confirm_text))
			{
				return false;
			}

			dom_obj.html("<i class='fa fa-spinner fa-spin fa-2x'></i>");

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
					if(data.success)
					{
						dom_obj.html(data.message);
					}

					else
					{
						dom_obj.html(data.error);
					}
				}
			});
		}

		/*else
		{
			console.log("No action_type: " , dom_obj);
		}*/

		return false;
	});
});