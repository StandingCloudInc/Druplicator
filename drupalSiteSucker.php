<?php

define('DEBUG', false);
define('DRUPAL_ROOT', getcwd());

require_once DRUPAL_ROOT . '/sites/default/settings.php';
$default_db = $databases['default']['default'];

if($_GET["deleteEverything"] == "true")
{
	echo "Deleting Everything!!";

	// Delete tarArchive
	$command = "rm -f " . DRUPAL_ROOT . "/tarArchive.tar.bz2";
	system($command,$return_value);
	($return_value == 0) or die("returned an error: $command");

	// Delete Self...
	unlink(__FILE__);
	exit;
}

function outputDebug($msg)
{ 
	if(DEBUG) echo "DEBUG: ${msg}\n";
}

outputDebug("Database Information:");
outputDebug("  Driver: "    . $default_db['driver']);
outputDebug("  Database: "  . $default_db['database']);
outputDebug("  Username: "  . $default_db['username']);
outputDebug("  Password: "  . $default_db['password']);
outputDebug("  Host: "      . $default_db['host']);
outputDebug("  Prefix: "    . $default_db['prefix']);
outputDebug("  Collation: " . $default_db['collation']);

outputDebug("Dumping Database...");
$command = "mysqldump --complete-insert --host='" . $default_db['host'] . "' --user='" . $default_db['username'] . "' --password='" . $default_db['password'] . "' --databases '" . $default_db['database'] . "' > " . DRUPAL_ROOT ."/mysqlDatabaseDump.sql";
outputDebug("  ${command}");
system($command,$return_value);
($return_value == 0) or die("returned an error: $command");

outputDebug("Creating Archive...");
$command = "touch " . DRUPAL_ROOT . "/tarArchive.tar.bz2 .";
system($command,$return_value);
$command = "tar --exclude='tarArchive.tar.bz2' -cjf " . DRUPAL_ROOT . "/tarArchive.tar.bz2 .";
outputDebug("  ${command}");
system($command,$return_value);
($return_value == 0) or die("returned an error: $command");

outputDebug("Removing Database Dump...");
$command = "rm -f " . DRUPAL_ROOT ."/mysqlDatabaseDump.sql";
outputDebug("  ${command}");
system($command,$return_value);
($return_value == 0) or die("returned an error: $command");

?>

<html>
	<head><title>Drupal Site Sucker</title></head>
	<body>
		<h1>Drupal Site Sucker</h1>
		<p>
			Hello, you have just used Standing Cloud&#39;s to create a backup of your Drupal Site.
			Please take a moment to <a href="tarArchive.tar.bz2">download your backup</a> and then please make sure that you <a href="?deleteEverything=true">delete everything</a> from your web server.
		</p>
	</body>
</html>
