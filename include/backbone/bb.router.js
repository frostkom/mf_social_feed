var App = Backbone.Router.extend(
{
	routes: {
		"*actions": "the_rest"
	},
	the_rest: function(action_type)
	{
		/*myPageView.loadPage(action_type);*/
	}
});

var app = new App();