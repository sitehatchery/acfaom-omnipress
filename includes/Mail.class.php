<?php

/**
 * Class Mail
 */
class Mail
{

	/**
	 * @var string
	 */
	public $hostname;
	/**
	 * @var
	 */
	public $username;
	/**
	 * @var
	 */
	public $password;

	/**
	 *
	 */
	public function __construct()
	{
		global $global;
		/* connect to gmail with your credentials */
		$hostname       = $global->helper_obj->getConfigValueByKey('email_host');
		$this->hostname = '{' . $hostname . ':993/imap/ssl}INBOX';
		$this->username = $global->helper_obj->getConfigValueByKey('email_username');# e.g somebody@gmail.com
		$this->password = $global->helper_obj->getConfigValueByKey('email_password');
	}

	/**
	 * Gmail attachment extractor.
	 *
	 * Downloads attachments from Gmail and saves it to a file.
	 * Uses PHP IMAP extension, so make sure it is enabled in your php.ini,
	 * extension=php_imap.dll
	 *
	 * @param string $imap_search_by
	 * @return array
	 */
	public function getImapEmails($imap_search_by = '')
	{
		$return_emails = array();

		/* try to connect */
		$inbox = imap_open($this->hostname, $this->username, $this->password) or die('Cannot connect to Gmail: ' . imap_last_error());

		/* get all new emails. If set to 'ALL' instead
		 * of 'NEW' retrieves all the emails, but can be
		 * resource intensive, so the following variable,
		 * $max_emails, puts the limit on the number of emails downloaded.
		 *
		 */
		$emails = imap_search($inbox, $imap_search_by);

		/* useful only if the above search is set to 'ALL' */
		$max_emails = 16;


		/* if any emails found, iterate through each email */
		if ($emails) {

			$count = 1;

			/* put the newest emails on top */
			rsort($emails);

			/* for every email... */
			foreach ($emails as $email_index => $email_number) {

				/* get information specific to this email */
				$overview = imap_fetch_overview($inbox, $email_number, 0);

                $return_emails[$email_index]['overview']    = $overview;
                $return_emails[$email_index]['unique_id']   = strtotime($overview[0]->date);
                $return_emails[$email_index]['attachments'] = array();

				/* get mail message, not actually used here.
				   Refer to http://php.net/manual/en/function.imap-fetchbody.php
				   for details on the third parameter.
				 */
				$message = imap_fetchbody($inbox, $email_number, 2);

				/* get mail structure */
				$structure = imap_fetchstructure($inbox, $email_number);

				$attachments = array();

				/* if any attachments found... */
				if (isset($structure->parts) && count($structure->parts)) {
					for ($i = 0; $i < count($structure->parts); $i++) {
						$attachments[$i] = array(
							'is_attachment' => false,
							'filename'      => '',
							'name'          => '',
							'attachment'    => ''
						);

						if ($structure->parts[$i]->ifdparameters) {
							foreach ($structure->parts[$i]->dparameters as $object) {
								if (strtolower($object->attribute) == 'filename') {
									$attachments[$i]['is_attachment'] = true;
									$attachments[$i]['filename']      = $object->value;
								}
							}
						}

						if ($structure->parts[$i]->ifparameters) {
							foreach ($structure->parts[$i]->parameters as $object) {
								if (strtolower($object->attribute) == 'name') {
									$attachments[$i]['is_attachment'] = true;
									$attachments[$i]['name']          = $object->value;
								}
							}
						}

						if ($attachments[$i]['is_attachment']) {
							$attachments[$i]['attachment'] = imap_fetchbody($inbox, $email_number, $i + 1);

							/* 3 = BASE64 encoding */
							if ($structure->parts[$i]->encoding == 3) {
								$attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
							} /* 4 = QUOTED-PRINTABLE encoding */
							elseif ($structure->parts[$i]->encoding == 4) {
								$attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
							}
						}
					}
				}

				/* iterate through each attachment and save it */
				foreach ($attachments as $attachment) {
					if ($attachment['is_attachment'] == 1) {
						$filename = $attachment['name'];
						if (empty($filename)) $filename = $attachment['filename'];

						if (empty($filename)) $filename = time() . ".dat";

						/* prefix the email number to the filename in case two emails
						 * have the attachment with the same file name.
						 */
						//echo "./" . $email_number . "-" . $filename;
						$attachment_name = $return_emails[$email_index]['unique_id'] . "-" . $email_number . "-" . $filename;
						$fp              = fopen(F_EMAIL_ATTACHMENTS . $attachment_name, "w+");
						fwrite($fp, $attachment['attachment']);
						fclose($fp);

						$return_emails[$email_index]['attachments'][] = $attachment_name;
					}

				}

				if ($count++ >= $max_emails) break;
			}

		}

		/* close the connection */
		imap_close($inbox);

		return $return_emails;
	}

}