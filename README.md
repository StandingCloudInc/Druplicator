Standing Cloud's Druplicator
============================

* Author:    Nicholas Henry (<nickh@standingcloud.com>)
* Date:      Tue Dec 20 16:26:43 UTC 2011
* Last mod.: Mon Dec 20 09:26:43 2011 -0700
* Version:   0.0.2.3
* Website:   <http://www.druplicator.com/>
* GitHub:    <https://github.com/StandingCloudInc/Druplicator>

LICENSE: You may not use, copy, or modify this software without the
permission of Standing Cloud, Inc. Contact us at support@standingcloud.com
to request permission.

The 'Druplicator' automatically finds your Drupal site's
database credentials and then creates an archive of your database and all of
your files and then presents you with an easy way to download this archive.
Once downloaded, the script asks you to allow it to clean up after itself
for security reasons.

Here is the list of changes that I added to the original version:

* I've created a restore script that works on Standing Cloud servers.

Usage
-----

First, upload druplicator.php to your Drupal site's home directory.  Then,
simply visit http://www.yoursite.com/druplicator.php with any web browser.
Finally, download the archive, upload it to a Standing Cloud server's
${HOME}/tmp/ directory and then run druplicator.sh.


Contributors
------------

* Nicholas Henry, alias [nshenry03][1]
* Jason Hand, alias [jasonhand][2]
* Amy Kokta, alias [akokta][3]

[1]: https://github.com/nshenry03
[2]: https://github.com/jasonhand
[3]: https://github.com/akokta


TODO
----

1. Check for multi-site and handle (even if 'handle' is just warn or errorout at this time).
2. Check for/handle lack of common commands (tar/touch/mysqldump/etc...).
3. Add support to other operating systems: Windows, Mac.
4. Any ideas? Tell me!
