<?php

// Include config file
require_once("includes/config.php");

// Get Order Search Emails and process it
$imap_search_filter_for_order_search_emails = $helper_obj->getConfigValueByKey('imap_search_filter_for_order_search_emails');
$emails                                     = $mail_obj->getImapEmails($imap_search_filter_for_order_search_emails);
$helper_obj->processOrderSearchEmails($emails);

// Get Merchandise Purchases Emails and process it
$imap_search_filter_for_merchandise_purchases_emails = $helper_obj->getConfigValueByKey('imap_search_filter_for_merchandise_purchases_emails');
$emails                                     = $mail_obj->getImapEmails($imap_search_filter_for_merchandise_purchases_emails);
$helper_obj->processMerchandisePurchasesEmails($emails);