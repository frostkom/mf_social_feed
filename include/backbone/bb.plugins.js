jQuery.fn.callAPI = function(o)
{
	var op = jQuery.extend(
	{
		base_url: '',
		url: '',
		data: '',
		send_type: 'post',
		onBeforeSend: function(){},
		onSuccess: function(data){},
		onAfterSend: function(){},
		onError: function(data)
		{
			setTimeout(function()
			{
				jQuery("#overlay_lost_connection").show();
			}, 2000);
		}
	}, o);

	jQuery.ajax(
	{
		url: op.base_url + op.url,
		type: op.send_type,
		processData: false,
		data: op.data,
		dataType: 'json',
		beforeSend: function()
		{
			op.onBeforeSend();
		},
		success: function(data)
		{
			op.onSuccess(data);
			op.onAfterSend();

			if(data.mysqli_error && data.mysqli_error == true)
			{
				jQuery("#overlay_lost_connection").show();
			}

			else
			{
				jQuery("#overlay_lost_connection").hide();
			}
		},
		error: function(data)
		{
			op.onError(data);
		}
	});
};

jQuery.fn.shorten = function(options)
{
	var settings = jQuery.extend(
	{
		'ellipsis': "&hellip;",
		'showChars': 255,
		'moreText': script_social_feed_plugins.read_more
	}, options);

	return this.each(function()
	{
		var self = jQuery(this),
			text_start = self.text().slice(0, settings.showChars),
			text_end = self.text().slice(settings.showChars);

		if(text_end.length > 0)
		{
			self.addClass('shorten-shortened').html(text_start + "<span class='shorten-clipped hide'>" + text_end + "</span><span class='shorten-ellipsis form_button'>" + settings.ellipsis + "<br><a href='#' class='shorten-more-link'>" + settings.moreText + settings.ellipsis + "</a></span>");
		}
	});
};