<?php
	// TWEET NEST
	// Config file
	
	// Change all of these variables as you see fit, to match your likings!
	// Please note: If there's '' around values, don't remove them. If there aren't, don't add any! :)

	$config = array(
		// REQUIRED VALUES ------------------------------------
        'consumer_key'       => '', // Your Twitter app consumer key
        'consumer_secret'    => '', // Your Twitter app consumer secret
		'twitter_screenname' => '', // Twitter screen name of the one whose tweets are being recorded
		'your_tw_screenname' => '', // Your Twitter screen name -- the one we log in as. Usually the same as above, but does not have to be.
        'twitter_token'      => '', // Twitter oauth secret token (usually filled in automatically)
        'twitter_token_secr' => '', // Twitter oauth token (usually filled in automatically)
		'timezone'           => '', // Choose one of these: http://php.net/manual/en/timezones.php
		'path'               => '', // Where's your twitter installation on your domain? No end slash, please.
		// Database values
		'db'                 => array(
			'hostname'       => '', // The hostname of your database server. Usually 'localhost'
			'username'       => '', // The username to log in to your database
			'password'       => '', // The password to log in to your database
			'database'       => '', // The database name
			'table_prefix'   => '', // The prefix to table names in the database
			'charset'	 => ''  // The charset of your database
		),
		
		// OPTIONAL -------------------------------------------
		// If you want to access your maintenance PHP files by HTTP, specify an admin password.
		'maintenance_http_password' => '', 
		// UNLESS YOU HAVE SSL, IT WILL BE SENT IN CLEAR TEXT, SO MAYBE NOT YOUR TWITTER (or anything else) PASSWORD!
		
		'follow_me_button'   => true, // Display 'follow me' button?
		'smartypants'        => true, // Use SmartyPants to perfect punctuation inside tweets?
		
		'css' => 'styles/streamlined/styles.css.php', // What CSS file should we use?
		
		'style' => array(
			// Style
			// You can use color values below like in a CSS file; #xxx, rgb(xx,xx,xx), whatever you want.
			// However, you can always specify 'profile' as value and that colour be grabbed from your Twitter profile page
			// (this only works with the default CSS file ... or other PHP-based CSS files that are specifically compatible)
			'text_color' => '#333',
			'link_color' => '#27b',
			'content_background_color' => 'profile', // 'profile' is always #fff
			// Top
			'top_background_color' => '#c0deed', // Top colors and images are the same as profile background colors and images
			'top_background_image' => 'clouds.png', // Relative to CSS folder or absolute
			'top_background_image_tile' => true, // true or false or 'profile'
			'top_bar_background_color' => '#ddeef6', // 'profile' is sidebar fill color
			// Tweet
			'tweet_border_color' => 'profile', // 'profile' is always #eee
			'tweet_meta_text_color' => 'profile', // 'profile' is always #999
			
			// The below styles are styles that cannot be named 'profile' as there's no equivalent Twitter profile color value for them.
			'page_title_color' => '#000',
			'link_color_hover' => '#003c70',
			// Top
			'top_background_image_position' => '0 -80px', // x, y
			'top_text_color' => '#567',
			'top_screenname_color' => '#456',
			'top_realname_color' => '#234',
			'top_image_border_color' => '#9ab',
			'top_bar_text_color' => '#888',
			'top_bar_highlight_color' => '#444',
			'top_follow_background_color' => '#aad1e3',
			'top_follow_text_color' => '#fff',
			'top_follow_background_color_hover' => '#88b6cb',
			// Tweet
			'tweet_meta_link_color' => '#777', // Only used on link hover
			'tweet_image_border' => true,
			'tweet_image_border_color' => '#eee',
			'tweet_image_border_color_hover' => '#ccc',
			'tweet_image_shadow_color' => '#ddd', // Shadow will only show if there's a border
			'tweet_image_shadow_color_hover' => '#bbb',
			// Search box
			'search_background_color' => 'content_background_color',
			'search_text_color' => '#666',
			'search_placeholder_text_color' => '#ccc',
			'search_border_color' => 'search_placeholder_text_color',
			// Months sidebar graph
			'months_border_color' => 'tweet_border_color',
			'months_text_color' => '#777',
			'months_text_color_hover' => '#444',
			'months_highlighted_text_color' => 'months_text_color_hover',
			'months_selected_text_color' => '#fff',
			'months_background_color' => 'transparent',
			'months_background_color_hover' => '#fafafa',
			'months_highlighted_background_color' => '#f4fafc',
			'months_selected_background_color' => '#aad1e3',
			'months_number_color' => '#aaa',
			'months_highlighted_number_color' => '#888',
			'months_selected_number_color' => '#d1f4fc',
			'months_graph_color' => '#f7f7f7',
			'months_graph_color_hover' => '#f3f3f3',
			'months_highlighted_graph_color' => '#e7f3f8',
			'months_selected_graph_color' => '#70aecb',
			// Days in month graph
			'days_date_color' => 'tweet_meta_text_color',
			'days_weekend_color' => '#f7f7f7',
			'days_graph_color' => '#8ec1da',
			'days_graph_retweets_color' => '#aad1e3',
			'days_graph_replies_color' => '#cae4f0',
			'days_selected_color' => 'months_selected_graph_color',
			'days_selected_text_color' => 'months_selected_text_color',
			'days_zero_color' => '#eee',
			// Footer
			'footer_border_color' => 'tweet_border_color',
			'footer_text_color' => '#777',
			'footer_link_color' => '#444',
		),
		
		// For any style, you can also explicitly use one of the below Twitter profile colour values
		// profile_background_color
		// profile_text_color
		// profile_link_color
		// profile_sidebar_fill_color
		// profile_sidebar_border_color
		// .. or any one of the other variable names seen above
	);
	global $config;
