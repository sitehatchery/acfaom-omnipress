<?php
// We need to have good security so that someone can't simply enter the cron URL and so create an run the script.
//if (php_sapi_name() !='cli') exit;

// Include config file
require_once("includes/config.php");

// Start the cron
$helper_obj->startCron();

// Get Order Search Emails and process it
$imap_search_filter_for_order_search_emails = $helper_obj->getConfigValueByKey('imap_search_filter_for_order_search_emails');
$emails                                     = $mail_obj->getImapEmails($imap_search_filter_for_order_search_emails);
$helper_obj->processOrderSearchEmails($emails);

// Get Merchandise Purchases Emails and process it
$imap_search_filter_for_merchandise_purchases_emails = $helper_obj->getConfigValueByKey('imap_search_filter_for_merchandise_purchases_emails');
$emails                                              = $mail_obj->getImapEmails($imap_search_filter_for_merchandise_purchases_emails);
$helper_obj->processMerchandisePurchasesEmails($emails);

// Process stored Orders
$helper_obj->processOrders();

/*
 * Todo
 * 1. Push that to Omnipress
 * 2. Send emial if something goes wrong
 */

// End the cron
$helper_obj->endCron();

//Send email if omnipress API fails
if($helper_obj->is_error){
    $mail_obj->sendErrorMessage($helper_obj->cron_unique_id);
}