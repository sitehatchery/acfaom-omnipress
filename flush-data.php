<?php

// We need to have good security so that someone can't simply enter the cron URL and so create an run the script.
if (php_sapi_name() !='cli') exit;

// Include config file
require_once("includes/config.php");

// Start the cron
$helper_obj->flushData();