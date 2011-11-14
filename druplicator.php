<?php

/**
 * Druplicator
 *
 * The 'Druplicator' automatically finds your Drupal site's
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
define('SITE_TITLE', 'Druplicator');
define('SCRIPT_TITLE', 'druplicator.php');
define('BACKUP_DIR_NAME', 'standing_clouds_druplicator');

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
 * Every server is different...  We will try to write to these directories...
 */
$possiblyWritableDirectories = array(
	DRUPAL_ROOT . "/sites/default/files",
	DRUPAL_ROOT,
	"/tmp"
);


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
if($_GET["cleanupAfterDruplicator"] == "true")
{
	?>
	<html>
	<head>
		<title><?php echo SITE_TITLE; ?></title>
	</head>
	<body>
	<p>Cleaning up after the Druplicator Script!</p>

	<?php
	outputDebug("Looping through possible directories, looking for files and folders to clean up...");
	foreach($possiblyWritableDirectories  as $dir) {
		if(is_dir("${dir}/" . BACKUP_DIR_NAME)) {
			if(is_file("${dir}/" . BACKUP_DIR_NAME . "/" . ARCHIVE)) { unlink("${dir}/" . BACKUP_DIR_NAME . "/" . ARCHIVE); }
			if(is_file("${dir}/" . BACKUP_DIR_NAME . "/" . SQL_FILE)) { unlink("${dir}/" . BACKUP_DIR_NAME . "/" . SQL_FILE); }
			rmdir("${dir}/" . BACKUP_DIR_NAME);
		}
	}

	// Delete Self (i.e. this script)...
	unlink(__FILE__);

	?>

	<p>DONE - Cleaning up after the Druplicator Script!  <a href="/">Click here to return to your homepage</a>.</p>
	</html>

	<?php
	// Quit here, do not keep going down the script
	exit;
}


/**
 * Every server is different...  Loop through the list of $possiblyWritableDirectories and
 * figure out which directories we can write to, and which directories we can't...
 */

/* Figure out where we can store our backups to... */
$WRITE_DIR='';
foreach($possiblyWritableDirectories  as $dir) {
	if(is_dir("${dir}/" . BACKUP_DIR_NAME)) {
		$WRITE_DIR= "${dir}/" . BACKUP_DIR_NAME;
		outputDebug("WRITE_DIR already exists and = ${WRITE_DIR}");
		break;
	} else {
		if(mkdir("${dir}/" . BACKUP_DIR_NAME)) {
		    $WRITE_DIR= "${dir}/" . BACKUP_DIR_NAME;
			outputDebug("WRITE_DIR = ${WRITE_DIR}");
			break;
		} else {
			outputDebug("Could not create directory: ${dir}/" . BACKUP_DIR_NAME);
		}
	}
} unset($dir);

/* Display error and exit if we can't write to any of these directories... */
if(empty($WRITE_DIR)) {
    echo 'ERROR: Could not find a directory to backup to.';
	exit(1);
}

/* Figure out if the directory we can write to is inside of Drupal's root directory */
$pos = strpos($WRITE_DIR,DRUPAL_ROOT);
if($pos === false) {
	outputDebug("WRITE_DIR was NOT in DRUPAL_ROOT");
	$WRITEDIR_IN_DRUPALROOT = false; // needle NOT found in haystack
} else {
	outputDebug("WRITE_DIR was in DRUPAL_ROOT");
	$WRITEDIR_IN_DRUPALROOT = true; // needle found in haystack
} unset($pos);


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
	"mysqldump --complete-insert --host='" . $default_db['host'] . "' --user='" . $default_db['username'] . "' --password='" . $default_db['password'] . "' --databases '" . $default_db['database'] . "' > ${WRITE_DIR}/" . SQL_FILE
);

/* Create the Archive */
$path_parts = pathinfo(DRUPAL_ROOT);
if ($WRITEDIR_IN_DRUPALROOT) {
	runSystemCommand("Creating the archive...", "tar --directory='" . $path_parts['dirname'] . "' --exclude='" . ARCHIVE . "' --exclude='" . SCRIPT_TITLE . "' -cjf ${WRITE_DIR}/" . ARCHIVE . " " . $path_parts['basename']);
} else {
	runSystemCommand("Creating the archive - write dir is outside of drupal root...", "tar --directory='" . $path_parts['dirname'] . "' --exclude='" . ARCHIVE . "' --exclude='" . SCRIPT_TITLE . "' -cjf ${WRITE_DIR}/" . ARCHIVE . " " . $path_parts['basename'] . " ${WRITE_DIR}");
}

/* Cleanup/Remove the Database Dump */
outputDebug("Removing Database Dump...");
unlink("${WRITE_DIR}/" . SQL_FILE);

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
		<img src="http://standingcloud.assistly.com/customer/portal/attachments/17250">
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
			<?php if ($WRITEDIR_IN_DRUPALROOT) { ?>
				<a href="<?php echo str_replace(DRUPAL_ROOT, '', $WRITE_DIR) . "/" . ARCHIVE; ?>"><img src="http://standingcloud.assistly.com/customer/portal/attachments/16435"></a>
			<?php } else { ?>
				<p>Because of your server&#39;s permissions, we were unable to store your backup inside of Drupal&#39;s root folder; therefore, you cannot simply download the file with your web browser.  Please use FTP or some other program to access your archive: <em><?php echo "${WRITE_DIR}/" . ARCHIVE; ?></em></p>
			<?php } ?>
				<a href="?cleanupAfterDruplicator=true"><img src="http://standingcloud.assistly.com/customer/portal/attachments/16434"></a>
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
                 &copy; 2011 Standing Cloud, Inc. All rights reserved.
                 </p>
    </body>
</html>
