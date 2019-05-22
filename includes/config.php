<?php
# Display errors
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
set_time_limit(3000);
echo "<pre>";

define("DBHOST", "localhost");
define("DBUSER", "root");
define("DBPASS", "mysql");
define("DBTYPE", "mysql");
define("DBNAME", "omnipress");

define("W_ROOT", "http://localhost/omnipress-push-order");
define("F_ROOT", "D:/Ampps/www/omnipress-push-order/");
define("F_INCLUDES", F_ROOT . "includes/");
define("F_EMAIL_ATTACHMENTS", F_ROOT . "email-attachments/");
define("F_LOGS", F_ROOT . "logs/");

# Include the needed files
require_once(F_INCLUDES . "Database.class.php");
require_once(F_INCLUDES . "Helper.class.php");

# Create the needed objects
$db         = Database::getConnection();
$helper_obj = new Helper($db);

global $global;
$global->db         = $db;
$global->helper_obj = $helper_obj;

# Include the needed files
require_once(F_INCLUDES . "Mail.class.php");

# Create the needed objects
$mail_obj = new Mail();

# Start the session
if (strlen(session_id()) < 1) {
	session_start();
}

ob_start();