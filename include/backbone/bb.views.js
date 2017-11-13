var feed_interval;

var PageView = Backbone.View.extend(
{
	el: jQuery('body'),

	initialize: function()
	{
		if(jQuery(".widget.social_feed").length > 0)
		{
			this.on_load_social_feed();

			if(typeof collect_on_load == 'function')
			{
				collect_on_load('myPageView.on_load_social_feed');
			}
		}
	},

	events:
	{
		"click .widget.social_feed ul.sf_feeds a" : 'change_tab',
		"click .widget.social_feed .shorten-more-link" : 'show_more'
	},

	on_load_social_feed: function()
	{
		this.model.on("change:response_feeds", this.show_feeds, this);
		this.model.on("change:response_posts", this.show_posts, this);

		if(jQuery(".widget.social_feed .sf_posts li").length == 0)
		{
			this.loadFeeds();
		}
	},

	loadFeeds: function()
	{
		var dom_obj = jQuery(".widget.social_feed .section"),
			reload = (dom_obj.attr('data-social_reload') || 0) * 60 * 1000,
			action_type = "type=posts&time=" + Date.now();

		if(typeof dom_obj.attr('data-social_feeds') != 'undefined'){	action_type += "&feeds=" + dom_obj.attr('data-social_feeds');}
		if(typeof dom_obj.attr('data-social_amount') != 'undefined'){	action_type += "&amount=" + dom_obj.attr('data-social_amount');}
		if(typeof dom_obj.attr('data-social_filter') != 'undefined'){	action_type += "&filter=" + dom_obj.attr('data-social_filter');}
		if(typeof dom_obj.attr('data-social_likes') != 'undefined'){	action_type += "&likes=" + dom_obj.attr('data-social_likes');}

		this.loadPage(action_type);

		if(reload > 0)
		{
			clearInterval(feed_interval);

			var self = this;

			feed_interval = setInterval(function()
			{
				self.loadFeeds();
			}, reload);
		}
	},

	loadPage: function(tab_active)
	{
		this.model.getPage(tab_active);
	},

	change_tab: function(e)
	{
		var self = jQuery(e.currentTarget),
			feed_id = self.attr('id'),
			sf_posts = self.parents('.sf_feeds').siblings('.sf_posts');

		if(typeof feed_id != 'undefined')
		{
			sf_posts.children("li." + feed_id).removeClass('hide').siblings("li:not(." + feed_id + ")").addClass('hide');
		}

		else
		{
			sf_posts.children('li').removeClass('hide');
		}

		self.parent('li').addClass('active').siblings('li').removeClass('active');

		return false;
	},

	show_more: function(e)
	{
		var dom_ellipsis = jQuery(e.currentTarget).parent('.shorten-ellipsis');

		dom_ellipsis.addClass('hide').siblings('.shorten-clipped').removeClass('hide').animate(
		{
			opacity: 1
		}, 500, function()
		{
			dom_ellipsis.parent('.shorten-shortened').removeClass('shorten-shortened');
		});

		return false;
	},

	show_feeds: function()
	{
		jQuery(".widget.social_feed .section .fa-spinner").addClass('hide');

		var response = this.model.get('response_feeds'),
			amount = response.length,
			html = "",
			dom_obj = jQuery(".widget.social_feed .section .sf_feeds");
		
		dom_obj.addClass('hide');

		if(amount > 0)
		{
			html += _.template(jQuery("#template_feed_all").html())("");

			for(var i = 0; i < amount; i++)
			{
				html += _.template(jQuery("#template_feed").html())(response[i]);
			}
		}

		dom_obj.html(html).removeClass('hide');
	},

	show_posts: function()
	{
		jQuery(".widget.social_feed .section .fa-spinner").addClass('hide');

		var response = this.model.get('response_posts'),
			amount = response.length,
			html = "",
			dom_obj = jQuery(".widget.social_feed .section .sf_posts");

		if(amount < 3){		dom_obj.addClass('one_column');}
		else{				dom_obj.removeClass('one_column');}

		if(amount > 0)
		{
			for(var i = 0; i < amount; i++)
			{
				html += _.template(jQuery("#template_feed_post").html())(response[i]);
			}
		}

		else
		{
			html = _.template(jQuery("#template_feed_message").html())("");
		}

		dom_obj.html(html).removeClass('hide');

		dom_obj.find("p").shorten();
	}
});

var myPageView = new PageView({model: new PageModel()});

Backbone.history.start();