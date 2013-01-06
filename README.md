## Twitter archive import

* Place your downloaded json archive files (data/js/tweets/[year]\_[month].js) directly in the archive folder (archive/[year]\_[month].js)
* On new tweet nest setups:
	- follow the tweet nest instructions from http://pongsocket.com/tweetnest/#installation 
	- right after the maintenance/loaduser.php step, run maintenance/loadarchive.php
* On existing instances:
	- be sure not to overwrite your inc/config.php or you will have to setup your instance again
	- run upgrade.php
	- run maintenance/loadarchive.php

The importer keeps track of its progress in maintenance/loadarchivelog.txt if it's writable. Should the script die for some reason (php time limit e.g.), just run it again.

If you have a large archive (10k+ tweets), I would recommend to do the one-time import via cli (php -f maintenance/loadarchive.php)

---

HI! THIS IS TWEET NEST.

Tweet Nest is a browsable, searchable and easily customizable archive and backup for your tweets, made in PHP. It runs on a web server. It requires the following:

* PHP 5.2 or higher with cURL enabled (or 5.1 with the PECL JSON module installed in addition)
* MySQL 4.1 or higher

To figure out how to install it, please point your browser to:

http://pongsocket.com/tweetnest/

And go to the "Installation" section.

Thanks!

Andy Graulund
pongsocket.com