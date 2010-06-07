<?php if($web){ ?>
</pre>
<?php if(!empty($config['maintenance_http_password'])){ ?><p>Use the following command to run this page in your server&#8217;s command line: <code>php <?php echo s($_SERVER['DOCUMENT_ROOT'] . $_SERVER['SCRIPT_NAME']); ?></code></p><?php echo "\n"; } ?>
</body>
</html>
<?php } else { echo "\n"; }