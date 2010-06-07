<?php
	error_reporting(0);
	if($returnCSS){
		ob_start();
	} else {
		require "../../inc/preheader.php";
		header("Content-type: text/css");
	}
?>
/*
 * TWEET ARCHIVE
 * Default CSS; "Streamlined"
 * Some values in this CSS are dynamically generated based on
 * user settings and Twitter profile.
 */

body {
	margin: 0;
	color: <?php echo css("text_color"); ?>;
	background-color: <?php echo css("content_background_color"); ?>;
	font-family: "Helvetica Neue", Helvetica, sans-serif;
	font-size: x-small;
	voice-family: "\"}\"";
	font-size: small;
} html>body { font-size: small; }

#container {
	font-size: 117%;
}

strong { font-weight: bold;   }
em     { font-style:  italic; }

a {
	color: <?php echo css("link_color"); ?>;
}

a:hover, a.hoverin {
	color: <?php echo css("link_color_hover"); ?>;
}

a img {
	border-width: 0;
}

h1, h2, h3, h4, h5, h6 {
	color: <?php echo css("page_title_color"); ?>;
}

#content {
	position: relative;
	margin: 30px 46px;
	overflow: hidden;
}

#top #author {
	position: relative;
	background-color: <?php echo css("top_background_color"); ?>;
	background-image: <?php echo css("top_background_image"); ?>;
	background-repeat: <?php echo css("top_background_image_tile"); ?>;
	background-position: <?php echo css("top_background_image_position"); ?>;
	color: <?php echo css("top_text_color"); ?>;
	padding: 20px 46px;
	min-height: 52px;
}

#top #author h2, #top #author p {
	margin: 0 0 0 65px;
}

#top #author h2 {
	font-size: 132%;
	font-weight: normal;
	margin-top: .1em;
	margin-bottom: .1em;
}

#top #author h2, #top #author h2 a {
	color: <?php echo css("top_screenname_color"); ?>;
	text-decoration: none;
}

#top #author h2 a:hover {
	text-decoration: underline;
}

#top #author h2 strong {
	color: <?php echo css("top_realname_color"); ?>;
}

#top #author h2 img {
	position: absolute;
	top: 20px;
	left: 46px;
	border: 2px solid <?php echo css("top_image_border_color"); ?>;
	background-color: <?php echo css("top_image_border_color"); ?>;
}

#top #info {
	position: relative;
	font-size: 85%;
	background-color: <?php echo css("top_bar_background_color"); ?>;
	color: <?php echo css("top_bar_text_color"); ?>;
	overflow: hidden;
}

#top #info a, #top #info strong {
	color: <?php echo css("top_bar_highlight_color"); ?>;
}

#top #info p {
	margin: 10px 46px;
}

#top #info strong a {
	text-decoration: none;
}

#top #info strong a:hover {
	text-decoration: underline;
}

#top #info p.follow {
	position: absolute;
	top: 5px;
	right: 46px;
	text-align: right;
	margin: 0;
}

#top #info p.follow a {
	display: block;
	background-color: <?php echo css("top_follow_background_color"); ?>;
	color: <?php echo css("top_follow_text_color"); ?>;
	font-weight: bold;
	text-decoration: none;
	border-radius: 5px;
	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;
	-o-border-radius: 5px;
	-khtml-border-radius: 5px;
	padding: 5px 12px;
}

#top #info p.follow a:hover {
	background-color: <?php echo css("top_follow_background_color_hover"); ?>;
}

#primary {
	width: 74%;
	float: left;
	position: relative;
}

#secondary {
	margin-left: 76%;
	font-size: 85%;
}

#primary h1, #content h1 {
	margin-top: 0;
}

.tweet {
	clear: left;
	overflow: hidden;
	border: 1px solid <?php echo css("tweet_border_color"); ?>;
	border-width: 1px 0;
	margin: 0 0 -1px;
	padding: 15px 0;
}

.tweet p {
	margin: 0;
}

.tweet .rt {
	font-weight: bold;
	background: transparent url(retweet.gif) no-repeat left center;
	padding: 0 0 0 23px;
}

.tweet .fav {
	float: right;
	width: 15px;
	height: 15px;
	background: transparent url(fav.png) no-repeat;
	margin: 0 0 10px 20px;
	font-size: 85%;
}

.tweet .fav span {
	position: absolute;
	left: -9999px;
	width: 8000px;
}

p.meta {
	font-size: 85%;
	color: <?php echo css("tweet_meta_text_color"); ?>;
	margin: .4em 0 0;
}

p.meta a {
	text-decoration: none;
	color: <?php echo css("tweet_meta_text_color"); ?>;
}

p.meta a:hover {
	text-decoration: underline;
	color: <?php echo css("tweet_meta_link_color"); ?>;
}

.tweet .pic {
	float: left;
	display: block;
	margin: 0 15px 5px 0;
<?php if($config['style']['tweet_image_border']){ ?>	border-bottom: 1px solid <?php echo css("tweet_image_shadow_color"); ?>;<?php echo "\n"; } ?>
}

.tweet .pic img {
	display: block;
<?php if($config['style']['tweet_image_border']){ ?>	border: 4px solid <?php echo css("tweet_image_border_color"); ?>;<?php echo "\n"; } ?>
	max-width: 150px;
	max-height: 150px;
}

<?php if($config['style']['tweet_image_border']){ ?>.tweet a.pic:hover, .tweet a.pic.hoverin {
	border-color: <?php echo css("tweet_image_shadow_color_hover"); ?>;
}<?php echo "\n"; } ?>

<?php if($config['style']['tweet_image_border']){ ?>.tweet a.pic:hover img, .tweet a.pic.hoverin img {
	border-color: <?php echo css("tweet_image_border_color_hover"); ?>;
}<?php echo "\n"; } ?>

#search {
	position: absolute;
	top: .5em;
	right: 0;
	text-align: right;
}

#search input {
	border: 1px solid <?php echo css("search_border_color"); ?>;
	background: <?php echo css("search_background_color"); ?> url(search.png) no-repeat 7px 5px;
	font: 85% "Helvetica Neue", Helvetica, sans-serif;
	color: <?php echo css("search_text_color"); ?>;
	padding: 3px 7px 3px 24px;
	border-radius: 12px;
	-moz-border-radius: 12px;
	-webkit-border-radius: 12px;
	-o-border-radius: 12px;
	-khtml-border-radius: 12px;
}

#search input.empty {
	color: <?php echo css("search_placeholder_text_color"); ?>;
}

#sorter {
	position: absolute;
	top: -45px;
	right: 0;
	text-align: right;
	font-size: 72%;
	color: <?php echo css("tweet_meta_text_color"); ?>;
}

#sorter a {
	background-color: <?php echo css("months_background_color"); ?>;
	color: <?php echo css("months_text_color"); ?>;
	padding: 4px 7px;
	border: 1px solid <?php echo css("months_border_color"); ?>;
	border-width: 1px 0;
	text-decoration: none;
}

#sorter a:hover {
	text-decoration: underline;
}

#sorter a.first {
	border-width: 1px;
	margin-left: 5px;
	border-top-left-radius: 6px;
	border-bottom-left-radius: 6px;
	-moz-border-radius-topleft: 6px;
	-moz-border-radius-bottomleft: 6px;
	-webkit-border-top-left-radius: 6px;
	-webkit-border-bottom-left-radius: 6px;
}

#sorter a.last {
	border-right-width: 1px;
	border-top-right-radius: 6px;
	border-bottom-right-radius: 6px;
	-moz-border-radius-topright: 6px;
	-moz-border-radius-bottomright: 6px;
	-webkit-border-top-right-radius: 6px;
	-webkit-border-bottom-right-radius: 6px;
}

#sorter a.selected {
	background-color: <?php echo css("months_background_color_hover"); ?>;
	font-weight: bold;
}

#sorter span {
	display: none;
}

.nextprev {
	margin-top: 20px;
	font-size: 85%;
	overflow: hidden;
	text-align: center;
}

.nextprev .prev {
	float: left;
	text-align: left;
}

.nextprev .next {
	float: right;
	text-align: right;
}

.truncated {
	margin-top: 25px;
	font-size: 118%;
	color: <?php echo css("tweet_meta_text_color"); ?>;
}

.truncated strong {
	color: <?php echo css("text_color"); ?>;
}

ul#months, #months ul {
	padding: 0;
	list-style-type: none;
	border-top: 1px solid <?php echo css("months_border_color"); ?>;
}

#months li a {
	display: block;
	position: relative;
	border-bottom: 1px solid <?php echo css("months_border_color"); ?>;
	margin: 0;
	padding: 5px 10px;
	background-color: <?php echo css("months_background_color"); ?>;
	color: <?php echo css("months_text_color"); ?>;
	text-decoration: none;
}

#months li a .m {
	position: relative;
	z-index: 3;
}

#months li a .m span.b {
	color: <?php echo css("months_number_color"); ?>;
}

#months li a .n {
	position: absolute;
	right: 10px;
	color: <?php echo css("months_number_color"); ?>;
	z-index: 2;
}

#months li a .n strong {
	color: <?php echo css("months_highlighted_number_color"); ?>;
}

#months li a .p {
	display: block;
	position: absolute;
	background-color: <?php echo css("months_graph_color"); ?>;
	top: 0;
	left: 0;
	bottom: 0;
	z-index: 1;
}

#months li a:hover {
	text-decoration: none;
	background-color: <?php echo css("months_background_color_hover"); ?>;
}

#months li a:hover .p {
	background-color: <?php echo css("months_graph_color_hover"); ?>;
}

#months li a:hover .m {
	text-decoration: underline;
	color: <?php echo css("months_text_color_hover"); ?>;
}

#months li a:hover .ms {
	text-decoration: none;
}

#months li a:hover .ms span.a {
	text-decoration: underline;
}

#months li.highlighted a {
	background-color: <?php echo css("months_highlighted_background_color"); ?>;
}

#months li.highlighted a .p {
	background-color: <?php echo css("months_highlighted_graph_color"); ?>;
}

#months li.highlighted a .m {
	color: <?php echo css("months_highlighted_text_color"); ?>;
}

#months li.selected a {
	background-color: <?php echo css("months_selected_background_color"); ?>;
	border-bottom-width: 0;
	padding-bottom: 6px;
	color: <?php echo css("months_selected_text_color"); ?>;
}

#months li.selected a .m, #months li.selected a:hover .m, #months li.selected a .n strong, #months li.selected a:hover .n strong {
	color: <?php echo css("months_selected_text_color"); ?>;
}

#months li.selected a .p, #months li.selected a:hover .p {
	background-color: <?php echo css("months_selected_graph_color"); ?>;
}

#months li.selected a .n, #months li.selected a:hover .n {
	color: <?php echo css("months_selected_number_color"); ?>;
}

#months li.selected {
	margin-left: -8px;
	padding-left: 8px;
	background: <?php echo css("months_selected_graph_color"); ?> url(pointmask.png) no-repeat left center;
}

#months li.meta {
	margin: 10px 0 0;
	padding: 5px 10px;
	color: <?php echo css("tweet_meta_text_color"); ?>;
	font-size: 85%;
}

#months li.home a .m {
	background: transparent url(home.png) no-repeat right center;
	padding: 0 17px 0 0;
}

#months li.search a .m {
	background: transparent url(search.png) no-repeat right center;
	padding: 0 18px 0 0;
}

#months li.fav a .m {
	background: transparent url(fav-m.png) no-repeat right center;
	padding: 0 17px 0 0;
}

#days {
	display: table;
	table-layout: fixed;
	width: 100%;
	border-spacing: 3px 0;
	margin: 0 0 2em;
}

#days .dr {
	display: table-row;
}

#days .d {
	display: table-cell;
	vertical-align: bottom;
	text-align: center;
	position: relative;
}

#days .d a {
	display: block;
	text-decoration: none;
}

#days .d .p {
	display: block;
	position: relative;
	z-index: 1;
	background-color: <?php echo css("days_graph_color"); ?>;
	color: <?php echo css("days_selected_text_color"); ?>;
}

#days .d .p .r, #days .d .p .rt {
	display: block;
}

#days .d .p .r {
	background-color: <?php echo css("days_graph_replies_color"); ?>;
}

#days .d .p .rt {
	background-color: <?php echo css("days_graph_retweets_color"); ?>;
}

#days .d .p .n {
	position: absolute;
	left: 0;
	right: 0;
	bottom: 5px;
}

#days .d .m {
	position: relative;
	z-index: 3;
	display: block;
	color: <?php echo css("days_date_color"); ?>;
	font-size: 85%;
	padding: 5px 0;
	margin: 3px 0 0;
}

#days .d .mm {
	background-color: <?php echo css("days_weekend_color"); ?>;
}

#days .d .ms {
	background: <?php echo css("days_selected_color"); ?> url(pointdmask.png) no-repeat center bottom;
	color: <?php echo css("days_selected_text_color"); ?>;
	font-weight: bold;
	padding-bottom: 15px; /* usual 5px + 10px */
	margin-bottom: -10px;
}

#days .d .z {
	position: relative;
	color: <?php echo css("days_zero_color"); ?>;
	z-index: 2;
}

#footer {
	clear: both;
	margin: 40px 0 20px;
	padding: 20px 46px 0;
	border-top: 1px solid <?php echo css("footer_border_color"); ?>;
	font-size: 85%;
	color: <?php echo css("footer_text_color"); ?>;
}

#footer a {
	color: <?php echo css("footer_text_color"); ?>;
	text-decoration: none;
}

#footer a:hover {
	color: <?php echo css("footer_link_color"); ?>;
	text-decoration: underline;
}
<?php
	if($returnCSS){
		$css = ob_get_clean();
	}
?>