TWITTER ARCHIVE IMPORT:

* Place your downloaded json archive files (data/js/tweets/[year]_[month].js) in the archive folder
* On new tweet nest setups:
  - after maintenance/loaduser.php run maintenance/loadarchive.php
* On existing instances:
  - run upgrade.php
  - run maintenance/loadarchive.php

The importer keeps track of it progress in maintenance/loadarchivelog.txt if it's writable. Should the script dies for some reason (php time limit e.g.), just run it again.

===

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