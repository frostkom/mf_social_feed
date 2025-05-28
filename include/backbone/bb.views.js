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
		"click ul.sf_feeds a": "change_tab",
		"click .shorten-more-link": "show_more",
		"click .load_more_posts": "load_more_posts",
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

		var self = this;

		jQuery(".widget.social_feed").find(".section").each(function()
		{
			var dom_obj = jQuery(this),
				arr_data = {action: 'api_social_feed_posts'};

			if(typeof dom_obj.attr('id') != 'undefined'){						arr_data.feed_id = dom_obj.attr('id');}
			if(typeof dom_obj.attr('data-social_feeds') != 'undefined'){		arr_data.feeds = dom_obj.data('social_feeds');}
			if(typeof dom_obj.attr('data-social_filter') != 'undefined'){		arr_data.filter = dom_obj.data('social_filter');}
			if(typeof dom_obj.data('social_amount') != 'undefined'){			arr_data.amount = dom_obj.data('social_amount');}
			if(typeof dom_obj.data('social_load_more_posts') != 'undefined'){	arr_data.load_more_posts = dom_obj.data('social_load_more_posts');}

			self.loadPage(arr_data);
		});
	},

	loadPage: function(arr_data)
	{
		this.model.getPage(arr_data);
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

	load_more_posts: function(e)
	{
		var dom_obj = jQuery(e.currentTarget).parents(".widget.social_feed").find(".section"),
			dom_amount = dom_obj.data('social_amount') || 0;

		if(dom_amount > 0)
		{
			dom_obj.data({'social_amount': (parseInt(dom_amount) * 2)});

			this.loadFeeds();
		}

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
			var dom_template = jQuery("#template_feed").html();

			html += _.template(jQuery("#template_feed_all").html())("");

			for(var i = 0; i < amount; i++)
			{
				html += _.template(dom_template)(response[i]);
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

		if(amount < 3){	dom_obj.find(".sf_posts").addClass('one_column');}
		else{			dom_obj.find(".sf_posts").removeClass('one_column');}

		if(amount > 0)
		{
			var dom_template = jQuery("#template_feed_post").html();

			for(var i = 0; i < amount; i++)
			{
				html += _.template(dom_template)(response[i]);
			}
		}

		else
		{
			html = _.template(jQuery("#template_feed_message").html())("");
		}

		dom_obj.find(".sf_posts").html(html).removeClass('hide');

		dom_obj.find(".sf_posts.show_read_more .text").shorten();

		/* Just in case image errors have occured, it has been cached through JS and then retrieved by someone else, we have to first display images and then check again */
		dom_obj.find(".sf_posts img").removeClass('hide').on("error", function()
		{
			jQuery(this).addClass('hide');
		});

		if(this.model.get('has_more_posts'))
		{
			dom_obj.find(".load_more_posts").removeClass('hide');
		}

		else
		{
			dom_obj.find(".load_more_posts").addClass('hide');
		}
	}
});

var mySocialView = new SocialView({model: new SocialModel()});