<?php
require 'vendor/autoload.php';
require 'config.php';
use OpenCloud\Rackspace;

date_default_timezone_set('America/New_York');
$wwwName = $containerName;
$containerName .= '-backup';

$client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array(
	'username' => $username,
	'apiKey'   => $key
));

try {
	$client->authenticate();
	echo 'Authenticated with token: '.$client->getToken()."\n";
	// connect to cloud files container
	$service = $client->objectStoreService(null, 'IAD', 'publicURL');
	$container = $service->createContainer($containerName);
	echo 'Container created: '.$containerName."\n";
	$container = $service->getContainer($containerName);

	if($dbhost != '') {
		// compress site files
		echo date("H:i:s").' Archiving directory '.$directory."/\n";
		echo shell_exec('zip -r file_archive.zip /var/www/'.$wwwName.'/'.$directory.'/*');
		if(!file_exists("file_archive.zip")) {
			echo date("H:i:s")." Directory backup failed!\n";
		}else{
			echo date("H:i:s")." Directory archived\n";
		}
		// connect to DB
		$dbconnect = mysqli_connect($dbhost,$dbuser,$dbpassword);
		if(!$dbconnect) {
			echo "Error: Unable to connect to MySQL." . PHP_EOL;
			echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
			echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
			die();
		} else {
			// Dump the mysql database
			echo date("H:i:s")." -- Starting database dump...\n";
			if(preg_match('/mariadb/',$dbhost)) {
				shell_exec("mariadump -h $dbhost -u $dbuser --password='$dbpassword' $dbname > db_backup.sql");
			} else {
				shell_exec("mysqldump -h $dbhost -u $dbuser --password='$dbpassword' $dbname > db_backup.sql");
			}
			echo date("H:i:s")." -- Database dump complete!\n";
		}
		// combine db and site files
		echo shell_exec('zip archive.zip file_archive.zip db_backup.sql');
		// delete uncompressed files
		shell_exec('unlink file_archive.zip');
		shell_exec('unlink db_backup.sql');
	} else {
		// zip just the site files
		shell_exec('zip -rj archive.zip /var/www/'.$wwwName.'/'.$directory.'/*');
		echo 'Directory archived: '.$directory."\n";
	}

	// upload file to container
	$container->uploadObject($containerName.'-'.date("Y-m-d--H-i-s").'.zip', fopen('archive.zip', 'r+'));
	echo "Archive Uploaded\n";
	shell_exec('unlink archive.zip');
} catch(Exception $e) {
	echo "Backup Failed!\n";
	echo $e->getMessage()."\n";
	die();
}