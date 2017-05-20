function on_load_social_feed()
{
	jQuery('.widget.social_feed p').shorten(
	{
		'showChars': 255,
		'moreText' : script_social_feed.read_more
	});
}

jQuery.fn.shorten = function(options)
{
	var settings = jQuery.extend(
	{
		'ellipsis': "&hellip;",
		'showChars': 300,
		'moreText': 'Read More'
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

jQuery(function($)
{
	on_load_social_feed();

	if(typeof collect_on_load == 'function')
	{
		collect_on_load('on_load_social_feed');
	}

	$(document).on('click', '.widget.social_feed ul.sf_feeds a', function()
	{
		var self = $(this),
			feed_id = self.attr('id'),
			sf_posts = self.parents('.sf_feeds').siblings('.sf_posts');

		if(typeof feed_id != 'undefined')
		{
			sf_posts.children('li').addClass('hide');
			sf_posts.children('li.' + feed_id).removeClass('hide');
		}

		else
		{
			sf_posts.children('li').removeClass('hide');
		}

		self.parent('li').addClass('active').siblings('li').removeClass('active');

		return false;
	});

	$(document).on('click', '.shorten-more-link', function()
	{
		var dom_ellipsis = jQuery(this).parent('.shorten-ellipsis');

		dom_ellipsis.addClass('hide').siblings('.shorten-clipped').removeClass('hide').animate(
		{
			opacity: 1
		}, 500, function()
		{
			dom_ellipsis.parent('.shorten-shortened').removeClass('shorten-shortened');
		});

		return false;
	});
});