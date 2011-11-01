<?php

/**
 * Site Sucker For Drupal
 *
 * The 'Site Sucker For Drupal Script' automatically finds your Drupal site's
 * database credentials and then creates an archive of your database and all of
 * your files and then presents you with an easy way to download this archive.
 * Once downloaded, the script asks you to allow it to clean up after itself
 * for security reasons.
 *
 * PHP version 5
 *
 * LICENSE: You may not use, copy, or modify this software without the
 * permission of Standing Cloud, Inc. Contact us at support@standingcloud.com
 * to request permission.
 *
 * @author     Nicholas Henry <nichk@standingcloud.com>
 * @copyright  2011 Standing Cloud, Inc.
 */

/**
 * Define constants
 */
define('ARCHIVE', 'tarArchive.tar.bz2');
define('SQL_FILE', 'mysqlDatabaseDump.sql');
define('SITE_TITLE', 'Drupal Site Sucker');

/**
 * Turn debugging on/off.
 *   Set this value to 'true' if you want to turn debugging on.
 *   Set this value to 'false' if you want to turn debugging off.
 */
define('DEBUG', false);


/**
 * Include the site's configuration file and set the default_db (to make life 
 * easier).
 */
define('DRUPAL_ROOT', getcwd());
require_once DRUPAL_ROOT . '/sites/default/settings.php';
$default_db = $databases['default']['default'];


/**
 * FUNCTION: outputDebug
 *
 * Outputs debugging information, formatted for CLI/Browser use. It
 * will not output anything if debugging has been disabled.
 *
 * @param string $arg1 the debug message that should be displayed.
 *
 * @return void
 */
function outputDebug($msg)
{ 
	if(DEBUG) {
		if(defined('STDIN') )
			echo "DEBUG: ${msg}\n";
		else
			echo "DEBUG: ${msg}<br />";
	}
}


/**
 * FUNCTION: runSystemCommand
 *
 * @param string $arg1 the debug message that describes the command to run.

 * @param string $arg2 the command to run
 *
 * @return void
 */
function runSystemCommand($msg,$cmd)
{ 
	outputDebug($msg);
	outputDebug("  ${cmd}");
	system($cmd,$return_value);
	($return_value == 0) or die("System call returned an error: $cmd");
}


/* =============================================================================
   =                       START MAIN SCRIPT LOGIC
   ============================================================================= */

/**
 * Check to see if we are supposed to cleanup.  If so, cleanup and then exit ASAP.
 */
if($_GET["cleanupAfterDrupalSiteSucker"] == "true")
{
	echo "Cleaning up after the Drupal Site Sucker Script!";
	runSystemCommand("Deleting the Archive...", "rm -f " . DRUPAL_ROOT . "/" . ARCHIVE);

	// Delete Self (i.e. this script)...
	unlink(__FILE__);

	// Quit here, do not keep going down the script
	exit;
}


/**
 * Before we get too far, output some debugging information that may be helpful
 * if we run into problems.
 */
outputDebug("Database Information:");
outputDebug("  Driver: "    . $default_db['driver']);
outputDebug("  Database: "  . $default_db['database']);
outputDebug("  Username: "  . $default_db['username']);
outputDebug("  Password: "  . $default_db['password']);
outputDebug("  Host: "      . $default_db['host']);
outputDebug("  Prefix: "    . $default_db['prefix']);
outputDebug("  Collation: " . $default_db['collation']);


/* Backup the Database */
runSystemCommand(
	"Dumping Database...",
	"mysqldump --complete-insert --host='" . $default_db['host'] . "' --user='" . $default_db['username'] . "' --password='" . $default_db['password'] . "' --databases '" . $default_db['database'] . "' > " . DRUPAL_ROOT . "/" . SQL_FILE
);


/* Create the Archive */
runSystemCommand("Creating the initial archive to get things started...", "touch " . DRUPAL_ROOT . "/" . ARCHIVE);
runSystemCommand("Creating the archive...", "tar --exclude='" . ARCHIVE . "' -cjf " . DRUPAL_ROOT . "/" . ARCHIVE . " " . DRUPAL_ROOT);


/* Cleanup/Remove the Database Dump */
runSystemCommand("Removing Database Dump...", "rm -f " . DRUPAL_ROOT . "/" . SQL_FILE);

?>

<html>
	<head>
		<title><?php echo SITE_TITLE; ?></title>
		<style type="text/css">
			.warning {
				border: 1px solid;
				margin: 10px 0px;
				padding:15px 10px 15px 50px;
				background-repeat: no-repeat;
				background-position: 10px center;
				color: #9F6000;
				background-color: #FEEFB3;
				background-image: url('http://trewsoft.com/images/warning.png');
			}
		</style>
	</head>
	<body>
		<h1><?php echo SITE_TITLE; ?></h1>

		<p>
			Thank you for using Standing Cloud&#39;s &#39;<?php echo SITE_TITLE; ?>&#39; to create a backup of your Drupal Site.
			Please take a moment to <a href="<?php echo ARCHIVE; ?>">download your backup</a>. Once you have downloaded your
			backup, please make sure that you <a href="?cleanupAfterDrupalSiteSucker=true">run the cleanup script</a> to remove
			the backup and Standing Cloud&#39;s &#39;<?php echo SITE_TITLE; ?>&#39; from your web server.
		</p>

		<div class="warning">WARNING: Make sure that you run the cleanup script so that others may not use the &#39;<?php echo SITE_TITLE; ?>&#39; to gain access to your site&#39;s.</div>

	</body>
</html>
