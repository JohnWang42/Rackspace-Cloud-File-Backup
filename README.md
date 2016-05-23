# Rackspace Cloud Files Backup #

Script to archive your site files and DB and push them to a Rackspace Cloud Files container.

### How do I get set up? ###

* Drop files into the directory you want to backup (e.g. /var/www/mywebsite.com)
* Install [Composer](https://getcomposer.org/) and run the `composer install` command
* Edit the config.php file with the appropriate information.
* Set a cronjob to execute backup.php. Save output to a log file for diagnosing issues.
* Example: `0 2 1 * * php /var/www/mywebsite.com/backup.php > /var/www/mywebsite.com/backup.log`