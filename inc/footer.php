			</div>
			<div id="secondary">
				<?php echo displayMonths(); ?>
			</div></div>
		</div>
		<div id="footer">
			&copy; <?php echo date("Y") . " <a href=\"http://twitter.com/" . s($config['twitter_screenname']) . "\">" . s($author['realname']) . "</a>"; ?>, powered by <a href="http://pongsocket.com/tweetnest/">Tweet Nest</a>
		</div>
	</div>
</body>
</html>
<?php if($startTime){ echo "<!-- " . round((microtime(true) - $startTime), 5) . " s -->\n"; } ?>