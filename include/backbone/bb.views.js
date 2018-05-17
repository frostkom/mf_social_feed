var feed_interval;

var SocialView = Backbone.View.extend(
{
	el: jQuery("body"),

	initialize: function()
	{
		if(script_social_feed_views.debug == 'yes')
		{
			this.displayDebug("Initiated");
		}

		if(jQuery(".widget.social_feed").length > 0)
		{
			this.model.on("change:response_feeds", this.show_feeds, this);
			this.model.on("change:response_posts", this.show_posts, this);

			if(jQuery(".widget.social_feed").find(".sf_posts li").length == 0)
			{
				this.loadFeeds();
			}
		}
	},

	events:
	{
		"click ul.sf_feeds a" : 'change_tab',
		"click .shorten-more-link" : 'show_more'
	},

	displayDebug: function(text)
	{
		if(script_social_feed_views.debug == 'yes')
		{
			jQuery(".social_debug").append("<p>" + text + " " + this.getTime() + "</p>").show();
		}
	},

	getTime: function()
	{
		var now = new Date();

		return now.getHours() + ':' + (now.getMinutes() < 10 ? "0" + now.getMinutes() : now.getMinutes()) + ':' + (now.getSeconds() < 10 ? "0" + now.getSeconds() : now.getSeconds());
	},

	loadFeeds: function()
	{
		this.displayDebug("Loading Feeds");

		var reload = 0,
			self = this;

		jQuery(".widget.social_feed").find(".section").each(function()
		{
			var dom_obj = jQuery(this),
				action_type = "type=posts";

			if(dom_obj.attr('data-social_reload') && dom_obj.attr('data-social_reload') > 0 && dom_obj.attr('data-social_reload') < reload)
			{
				reload = dom_obj.attr('data-social_reload') * 60 * 1000;
			}

			if(typeof dom_obj.attr('id') != 'undefined'){					action_type += "&feed_id=" + dom_obj.attr('id');}
			if(typeof dom_obj.attr('data-social_feeds') != 'undefined'){	action_type += "&feeds=" + dom_obj.attr('data-social_feeds');}
			if(typeof dom_obj.attr('data-social_amount') != 'undefined'){	action_type += "&amount=" + dom_obj.attr('data-social_amount');}
			if(typeof dom_obj.attr('data-social_filter') != 'undefined'){	action_type += "&filter=" + dom_obj.attr('data-social_filter');}
			if(typeof dom_obj.attr('data-social_likes') != 'undefined'){	action_type += "&likes=" + dom_obj.attr('data-social_likes');}

			self.loadPage(action_type);
		});

		if(reload > 0)
		{
			clearInterval(feed_interval);

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
			sf_posts = self.parents(".sf_feeds").siblings(".sf_posts");

		if(typeof feed_id != 'undefined')
		{
			sf_posts.children("li." + feed_id).removeClass('hide').siblings("li:not(." + feed_id + ")").addClass('hide');
		}

		else
		{
			sf_posts.children("li").removeClass('hide');
		}

		self.parent("li").addClass('active').siblings("li").removeClass('active');

		return false;
	},

	show_more: function(e)
	{
		var dom_ellipsis = jQuery(e.currentTarget).parent(".shorten-ellipsis");

		dom_ellipsis.addClass('hide').siblings(".shorten-clipped").removeClass('hide').animate(
		{
			opacity: 1
		}, 500, function()
		{
			dom_ellipsis.parent(".shorten-shortened").removeClass('shorten-shortened');
		});

		return false;
	},

	show_feeds: function()
	{
		var response = this.model.get('response_feeds'),
			amount = response.length,
			html = "",
			dom_obj = jQuery("#" + this.model.get('feed_id') + ".section .sf_feeds");

		dom_obj.addClass('hide');

		if(amount > 0)
		{
			html += _.template(jQuery("#template_feed_all").html())("");

			for(var i = 0; i < amount; i++)
			{
				html += _.template(jQuery("#template_feed").html())(response[i]);
			}

			dom_obj.html(html).removeClass('hide');
		}
	},

	show_posts: function()
	{
		this.displayDebug("Display Posts");

		var response = this.model.get('response_posts'),
			amount = response.length,
			html = "",
			dom_obj = jQuery("#" + this.model.get('feed_id') + ".section");

		dom_obj.find(".fa-spinner").addClass('hide');

		if(amount < 3){		dom_obj.find(".sf_posts").addClass('one_column');}
		else{				dom_obj.find(".sf_posts").removeClass('one_column');}

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

		dom_obj.find(".sf_posts").html(html).removeClass('hide');

		dom_obj.find(".sf_posts.show_read_more .text").shorten();
	}
});

var mySocialView = new SocialView({model: new SocialModel()});