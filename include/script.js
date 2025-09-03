jQuery.fn.shorten = function(options)
{
	var settings = jQuery.extend(
	{
		'ellipsis': "&hellip;",
		'showChars': 255,
		'moreText': script_social_feed.read_more
	}, options);

	return this.each(function()
	{
		var self = jQuery(this),
			text_start = self.text().slice(0, settings.showChars),
			text_end = self.text().slice(settings.showChars);

		if(text_end.length > 0)
		{
			self.addClass('shorten-shortened').html(text_start + "<span class='shorten-clipped hide'>" + text_end + "</span><span class='shorten-ellipsis form_button wp-block-button'>" + settings.ellipsis + "<div><a href='#' class='shorten-more-link wp-block-button__link'>" + settings.moreText + "</a></div></span>");
		}
	});
};

jQuery(function($)
{
	var dom_obj = $(".widget.social_feed");

	dom_obj.find(".text").shorten();

	dom_obj.find("img").removeClass('hide').on("error", function()
	{
		jQuery(this).parents(".image").children("a").html(script_social_feed.image_fallback);
	});

	$(document).on('click', ".shorten-more-link", function(e)
	{
		var dom_ellipsis = jQuery(e.currentTarget).parents(".shorten-ellipsis");

		dom_ellipsis.addClass('hide').siblings(".shorten-clipped").removeClass('hide').animate(
		{
			opacity: 1
		}, 500, function()
		{
			dom_ellipsis.parent(".shorten-shortened").removeClass('shorten-shortened');
		});

		return false;
	});
});