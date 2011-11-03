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
 * @author     Jason Hand     <jason.hand@standingcloud.com>
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
$path_parts = pathinfo(DRUPAL_ROOT);
runSystemCommand("Creating the initial archive to get things started...", "touch " . DRUPAL_ROOT . "/" . ARCHIVE);
runSystemCommand("Creating the archive...", "tar --directory='" . $path_parts['dirname'] . "' --exclude='" . ARCHIVE . "' -cjf " . DRUPAL_ROOT . "/" . ARCHIVE . " " . $path_parts['basename']);


/* Cleanup/Remove the Database Dump */
runSystemCommand("Removing Database Dump...", "rm -f " . DRUPAL_ROOT . "/" . SQL_FILE);

/* If debugging, exit here */
if(DEBUG) {
	exit;
}
?>

<html>
	<head>
		<title><?php echo SITE_TITLE; ?></title>
		<style type="text/css">
			.message {
			    font-size: 20px;
				border: 1px solid;
				margin: 10px 0px;
				padding:15px 10px 15px 50px;
				background-repeat: no-repeat;
				background-position: 10px center;
				color: #9F6000;
				background-color: #99CCFF;
				background-image: url('http://trewsoft.com/images/warning.png');
			}
		</style>
		<style type="text/css">
			.support {
				border: 1px solid;
				margin: 0px 0px;
				padding:5px 5px 5px 5px;
				background-repeat: no-repeat;
				background-position: 5px center;
				color: #9F6000;
				background-color: #FFFFFF;
			}
		</style>
		
		<style type="text/css">
			.buttons {
				border: 1px solid;
				margin: 0px 0px;
				padding:5px 5px 5px 5px;
				background-repeat: no-repeat;
				background-position: 5px center;
				color: #9F6000;
				background-color: #6699CC;
			}
		</style>
	</head>
	<body bgcolor=003366>
		<img src="http://standingcloud.assistly.com/customer/portal/attachments/16587">
		<img src="http://standingcloud.assistly.com/customer/portal/attachments/10589">
		
		<div class="message">Thank you for using the Standing Cloud <?php echo SITE_TITLE; ?> archive utility to create a backup of your Drupal Site.
		<br>
			Please take a moment to package and download your Drupal deployment by pressing the green button below. Once you have downloaded the
			archive file, please make sure that you run the cleanup script by pressing the red button.
			<br>
			This will remove the backup and Standing Cloud <?php echo SITE_TITLE; ?> archive utility from your web server.
	    </div>
	    
        <div class="buttons" align=center>
           <span 30x>
        	<a href="<?php echo ARCHIVE; ?>"><img src="http://standingcloud.assistly.com/customer/portal/attachments/16435"></a>
            <a href="?cleanupAfterDrupalSiteSucker=true"><img src="http://standingcloud.assistly.com/customer/portal/attachments/16434"></a>
        </div>
        
        <div class="support" align=center>
                 <td>Questions?</td>
                 <br>
                 <td><a href="http://support.standingcloud.com/customer/portal/chats/new"target="_blank"><img src="http://standingcloud.assistly.com/customer/portal/attachments/15073"></td></a>
                 <td><a href="http://support.standingcloud.com/customer/portal/emails/new"target="_blank"><img src="http://standingcloud.assistly.com/customer/portal/attachments/15074"></td></a>
                 <td><a href="http://support.standingcloud.com/customer/portal/questions/new"target="_blank"><img src="http://standingcloud.assistly.com/customer/portal/attachments/15072"></td></a>
                 <p>
                 alpha release
                 <br>
                 © 2011 Standing Cloud, Inc. All rights reserved.
                 </p>
    </body>
</html>
