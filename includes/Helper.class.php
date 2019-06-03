<?php
require_once(F_INCLUDES . "Logger.class.php");
require_once(F_INCLUDES . "Omnipress.class.php");

/**
 * Class Helper
 */
class Helper
{

	/**
	 * @var
	 */
	protected $_db;

	/**
	 * @var
	 */
	public $log;

	/**
	 * @var
	 */
	protected $cron_id;

	/**
	 * @var
	 */
	public $cron_unique_id;

    /**
     * @var Omnipress
     */
    protected $omnipress_obj;

    /**
     * @var bool
     */
    public $is_error;

	/**
	 * @param $db
	 */
	public function __construct($db)
	{
		$this->_db = $db;
        $this->is_error = false;
	}

	/**
	 * Insert Blank line in log file
	 */
	public function addBlankLineInLogFile()
	{
		$this->log->putLog(' ');
	}

	/**
	 * Start Cron
	 * 1. Create Cron file
	 * 2. Add Cron Log in DB
	 */
	public function startCron()
	{
		/* Create email attachments directory if not exists */
		if (!file_exists(F_EMAIL_ATTACHMENTS)) {
			mkdir(F_EMAIL_ATTACHMENTS, 0777, true);
		}

		$current_date         = date('Y-m-d H:i:s');
		$this->cron_unique_id = strtotime($current_date);

		// Prepare the Log file for the email. Log file name = Cron unique id name
		$this->log = new Logger(F_LOGS . $this->cron_unique_id . ".txt");

		$this->cron_id = $this->addCronLog(array('unique_id' => $this->cron_unique_id, 'started_at' => $current_date, 'end_at' => '0000-00-00 00:00:00'));
		if ($this->cron_id) {
			$this->log->putLog('Added cron log. Cron Unique Id: ' . $this->cron_unique_id . " | Cron Start Date: " . $current_date);
		} else {
			$this->log->putLog('Error adding cron log. Cron Unique Id: ' . $this->cron_unique_id . " | Cron Start Date: " . $current_date);
		}
		$this->addBlankLineInLogFile();
	}

	/**
	 * End Cron
	 * 1. Update Cron End Date in DB
	 */
	public function endCron()
	{
		$current_date = date('Y-m-d H:i:s');
		$cron_id      = $this->endCronLog(array('id' => $this->cron_id, 'end_at' => $current_date));
		if ($cron_id) {
			$this->log->putLog('Ended cron. Cron Unique Id: ' . $this->cron_unique_id . " | Cron End Date: " . $current_date);
		} else {
			$this->log->putLog('Error ending cron. Cron Unique Id: ' . $this->cron_unique_id . " | Cron End Date: " . $current_date);
		}
	}

	/**
	 * Add Cron Log data in DB
	 *
	 * @param array $data
	 * @return bool
	 */
	public function addCronLog($data = array())
	{
		try {
			$sql
				 = "INSERT INTO cron_logs (
                            unique_id,
                            started_at,
                            end_at
                        ) VALUES (
                            :unique_id,
                            :started_at,
                            :end_at
                        )";
			$stm = $this->_db->prepare($sql);

			$stm->bindParam(":unique_id", $unique_id);
			$stm->bindParam(":started_at", $started_at);
			$stm->bindParam(":end_at", $end_at);

			$unique_id  = $data['unique_id'];
			$started_at = $data['started_at'];
			$end_at     = $data['end_at'];

			$res = $stm->execute();

			if (!$res) {
				return false;
			} else {
				return $this->_db->lastInsertId();
			}
		}
		catch (Exception $e) {
			$this->log->putLog($e->getMessage());
			return false;
		}
	}

	/**
	 * Update end_at dat of Cron Log in DB
	 *
	 * @param array $data
	 * @return bool
	 */
	public function endCronLog($data = array())
	{
		try {
			$sql = "UPDATE cron_logs SET end_at = :end_at WHERE id=:id";
			$stm = $this->_db->prepare($sql);

			$stm->bindParam(":end_at", $end_at);
			$stm->bindParam(":id", $id);

			$end_at = $data['end_at'];
			$id     = $data['id'];

			$res = $stm->execute();

			if (!$res) {
				return false;
			} else {
				return $id;
			}
		}
		catch (Exception $e) {
			$this->log->putLog($e->getMessage());
			return false;
		}
	}

	/**
	 * Get config value by key from DB
	 *
	 * @param string $key
	 * @return bool
	 */
	public function getConfigValueByKey($key = '')
	{
		try {
			$sql = "SELECT *, COUNT(id) AS count FROM config WHERE `key` = :key";
			$stm = $this->_db->prepare($sql);

			$stm->bindParam(":key", $key);

			$res = $stm->execute();

			if ($res) {
				$row = $stm->fetch(PDO::FETCH_ASSOC);
				return $row['count'] > 0 ? $row['value'] : false;
			} else {
				return false;
			}
		}
		catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Get email by unique_id where is_processed = 1
	 *
	 * @param string $email_unique_id
	 * @return bool
	 */
	public function getProcessedEmail($email_unique_id = '')
	{
		try {
			$sql = "SELECT *, COUNT(id) AS count FROM emails WHERE `unique_id` = :unique_id AND is_processed = 1";
			$stm = $this->_db->prepare($sql);

			$stm->bindParam(":unique_id", $email_unique_id);

			$res = $stm->execute();

			if ($res) {
				$row = $stm->fetch(PDO::FETCH_ASSOC);
				return $row['count'] > 0 ? $row : false;
			} else {
				return false;
			}
		}
		catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Add Email data into db
	 *
	 * @param array $data
	 * @return bool
	 */
	public function addEmail($data = array())
	{
		try {
			$sql
				 = "INSERT INTO emails (
                            cron_id,
                            unique_id,
                            subject,
                            date,
                            is_processed
                        ) VALUES (
                            :cron_id,
                            :unique_id,
                            :subject,
                            :date,
                            :is_processed
                        ) ON DUPLICATE KEY UPDATE
                        	`id` = LAST_INSERT_ID(`id`),
                        	date = :date,
							is_processed = :is_processed";
			$stm = $this->_db->prepare($sql);

			$stm->bindParam(":cron_id", $cron_id);
			$stm->bindParam(":unique_id", $unique_id);
			$stm->bindParam(":subject", $subject);
			$stm->bindParam(":date", $date);
			$stm->bindParam(":is_processed", $is_processed);

			$cron_id      = $data['cron_id'];
			$unique_id    = $data['unique_id'];
			$subject      = $data['subject'];
			$date         = $data['date'];
			$is_processed = $data['is_processed'];

			$res = $stm->execute();

			if (!$res) {
				return false;
			} else {
				return $this->_db->lastInsertId();
			}
		}
		catch (Exception $e) {
			$this->log->putLog($e->getMessage());
			return false;
		}
	}

	/**
	 * Set is_processed = 1 for Email in DB
	 *
	 * @param array $data
	 * @return bool
	 */
	public function updateEmailAfterProcessing($data = array())
	{
		try {
			$sql = "UPDATE emails SET is_processed = :is_processed WHERE id=:id";
			$stm = $this->_db->prepare($sql);

			$stm->bindParam(":id", $id);
			$stm->bindParam(":is_processed", $is_processed);

			$id           = $data['id'];
			$is_processed = $data['is_processed'];

			$res = $stm->execute();

			if (!$res) {
				return false;
			} else {
				return $data['id'];
			}
		}
		catch (Exception $e) {
			$this->log->putLog($e->getMessage());
			return false;
		}
	}

	/**
	 * Add Order data into DB
	 *
	 * @param array $data
	 * @return bool
	 */
	public function addOrder($data = array())
	{
		try {
			$sql
				 = "INSERT INTO `order` (
							email_id,
                            order_id,
                            order_date,
                            company_name,
                            customer_id,
                            customer_name,
                            address,
                            city,
                            state,
                            postal_code,
                            phone_number,
                            email_address,
                            carrier_code
                        ) VALUES (
                        	:email_id,
                            :order_id,
                            :order_date,
                            :company_name,
                            :customer_id,
                            :customer_name,
                            :address,
                            :city,
                            :state,
                            :postal_code,
                            :phone_number,
                            :email_address,
                            :carrier_code
                        ) ON DUPLICATE KEY UPDATE
                        	`id` = LAST_INSERT_ID(`id`),
                        	email_id = :email_id,
                        	order_date = :order_date,
							company_name = :company_name,
							customer_id = :customer_id,
							customer_name = :customer_name,
							address = :address,
							city = :city,
							state = :state,
							postal_code = :postal_code,
							phone_number = :phone_number,
							email_address = :email_address,
							carrier_code = :carrier_code
							";
			$stm = $this->_db->prepare($sql);

			$stm->bindParam(":email_id", $email_id);
			$stm->bindParam(":order_id", $order_id);
			$stm->bindParam(":order_date", $order_date);
			$stm->bindParam(":company_name", $company_name);
			$stm->bindParam(":customer_id", $customer_id);
			$stm->bindParam(":customer_name", $customer_name);
			$stm->bindParam(":address", $address);
			$stm->bindParam(":city", $city);
			$stm->bindParam(":state", $state);
			$stm->bindParam(":postal_code", $postal_code);
			$stm->bindParam(":phone_number", $phone_number);
			$stm->bindParam(":email_address", $email_address);
			$stm->bindParam(":carrier_code", $carrier_code);

			$email_id      = $data['email_id'];
			$order_id      = $data['order_id'];
			$order_date    = $data['order_date'];
			$company_name  = $data['company_name'];
			$customer_id   = $data['customer_id'];
			$customer_name = $data['customer_name'];
			$address       = $data['address'];
			$city          = $data['city'];
			$state         = $data['state'];
			$postal_code   = $data['postal_code'];
			$phone_number  = $data['phone_number'];
			$email_address = $data['email_address'];
			$carrier_code  = $data['carrier_code'];

			$res = $stm->execute();
			if (!$res) {
				return false;
			} else {
				return $this->_db->lastInsertId();
			}
		}
		catch (Exception $e) {
			$this->log->putLog($e->getMessage());
			return false;
		}
	}

	/**
	 * Add Order Product data into DB
	 *
	 * @param array $data
	 * @return bool
	 */
	public function addOrderProduct($data = array())
	{
		try {
			$sql
				 = "INSERT INTO `order_products` (
							email_id,
                            order_id,
                            product_code,
                            product_name,
                            quantity,
                            shipping_method
                        ) VALUES (
                        	:email_id,
                            :order_id,
                            :product_code,
                            :product_name,
                            :quantity,
                            :shipping_method
                        ) ON DUPLICATE KEY UPDATE
                        	`id` = LAST_INSERT_ID(`id`),
                        	product_code = :product_code,
                        	product_name = :product_name,
							quantity = :quantity,
							shipping_method = :shipping_method
							";
			$stm = $this->_db->prepare($sql);

			$stm->bindParam(":email_id", $email_id);
			$stm->bindParam(":order_id", $order_id);
			$stm->bindParam(":product_code", $product_code);
			$stm->bindParam(":product_name", $product_name);
			$stm->bindParam(":quantity", $quantity);
			$stm->bindParam(":shipping_method", $shipping_method);

			$email_id        = $data['email_id'];
			$order_id        = $data['order_id'];
			$product_code    = $data['product_code'];
			$product_name    = $data['product_name'];
			$quantity        = $data['quantity'];
			$shipping_method = $data['shipping_method'];

			$res = $stm->execute();
			if (!$res) {
				return false;
			} else {
				return $this->_db->lastInsertId();
			}
		}
		catch (Exception $e) {
			$this->log->putLog($e->getMessage());
			return false;
		}
	}

	/**
	 * Get Orders where product_code is Book.
	 *
	 * @return bool
	 */
	public function getBookOrders()
	{
		try {
			$book_product_codes = $this->getConfigValueByKey('book_product_codes');
			$orders             = array();
			if ($book_product_codes) {
				$book_product_codes = explode(", ", $book_product_codes);
				$sql
									= "
							SELECT
							  o.*
							FROM
							  `order` AS o
							  LEFT JOIN order_products AS op
								ON op.`order_id` = o.`order_id`
							WHERE op.`product_code` IN ('" . implode("', '", $book_product_codes) . "')
							GROUP BY o.`order_id`
				";
				$stm                = $this->_db->prepare($sql);

				$res = $stm->execute();

				if ($res) {
					$rows = $stm->fetchAll(PDO::FETCH_ASSOC);
					if ($rows) {
						foreach ($rows as $index => $row) {
							$orders[$index]['order']          = $row;
							$orders[$index]['order_products'] = $this->getOrderProducts($row['order_id']);
						}
						return $orders;
					} else {
						return false;
					}
				} else {
					return false;
				}
			}

		}
		catch (Exception $e) {
			$this->log->putLog($e->getMessage());
			return false;
		}
	}

	/**
	 * Get the products by Order Id
	 *
	 * @param int $order_id
	 * @return bool
	 */
	public function getOrderProducts($order_id = 0)
	{
		try {
			$sql = " SELECT * FROM order_products WHERE order_id = :order_id ";
			$stm = $this->_db->prepare($sql);

			$stm->bindParam(":order_id", $order_id);

			$res = $stm->execute();

			if ($res) {
				$rows = $stm->fetchAll(PDO::FETCH_ASSOC);
				if ($rows) {
					return $rows;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
		catch (Exception $e) {
			$this->log->putLog($e->getMessage());
			return false;
		}
	}

	/**
	 * @param array $emails
	 */
	public function processOrderSearchEmails($emails = array())
	{
		if ($emails) {
			foreach ($emails as $email_index => $email) {

				$email_subject = $email['overview'][0]->subject;
				$email_date    = date('Y-m-d H:i:s', $email['unique_id']);

				// Check email is already processed or not. Process if not processed.
				$is_email_already_processed = $this->getProcessedEmail($email['unique_id']);

				if (!$is_email_already_processed) {

					// Prepare the Log file for the email. Log file name = Email unique id name
					//$this->log = new Logger(F_LOGS . $email['unique_id'] . ".txt");

					if ($attachments = $email['attachments']) {

						// Add Email in DB
						$add_email_data = array('cron_id' => $this->cron_id, 'unique_id' => $email['unique_id'], 'subject' => $email_subject, 'date' => $email_date, 'is_processed' => 0);
						$email_id       = $this->addEmail($add_email_data);
						if ($email_id) {
							$this->log->putLog('Added Email. Subject: ' . $email_subject . " | Date: " . $email_date);
						} else {
							$this->log->putLog('Error adding email. Subject: ' . $email_subject . " | Date: " . $email_date);
						}
						$this->addBlankLineInLogFile();

						// Loop through attachments
						foreach ($attachments as $attachment) {
							$path_info = pathinfo(F_EMAIL_ATTACHMENTS . $attachment);
							$zip       = new ZipArchive;

							if ($zip->open(F_EMAIL_ATTACHMENTS . $attachment) === true) {
								// EXTRACT ZIP FOLDER
								$zip->extractTo(F_EMAIL_ATTACHMENTS . $path_info['filename']);
								$zip->close();

								$files = glob(F_EMAIL_ATTACHMENTS . $path_info['filename'] . "/*.csv");
								foreach ($files as $file) {

									if (($handle = fopen($file, "r")) !== FALSE) {
										$index             = 0;
										$populated_headers = array();
										$populated_data    = array();
										while (($data = fgetcsv($handle, 1000, ",")) !== false) {
											$num = count($data);
											for ($c = 0; $c < $num; $c++) {
												if ($index == 0) {
													$populated_headers[] = strtolower(trim($data[$c]));
												} else {
													$populated_data[$index][$populated_headers[$c]] = $data[$c];
												}
											}
											$index++;
										}
										fclose($handle);

										if ($populated_data) {
											foreach ($populated_data as $row_index => $row_data) {
												$order_data = array('email_id' => $email_id, 'order_id' => 0, 'order_date' => '', 'company_name' => '', 'customer_id' => '', 'customer_name' => '', 'address' => '', 'city' => '', 'state' => '', 'postal_code' => '', 'phone_number' => '', 'email_address' => '', 'carrier_code' => '');
												if (array_key_exists('number', $row_data)) {
													$order_data['order_id'] = $row_data['number'];
												}
												if (array_key_exists('created on', $row_data)) {
													$order_data['order_date'] = date('Y-m-d H:i:s', strtotime($row_data['created on']));
												}
												if (array_key_exists('company name', $row_data)) {
													$order_data['company_name'] = $row_data['company name'];
												}
												if (array_key_exists('customer name', $row_data)) {
													$order_data['customer_name'] = $row_data['customer name'];
												}
												if (array_key_exists('customer id', $row_data)) {
													$order_data['customer_id'] = $row_data['customer id'];
												}
												if (array_key_exists('shipping address line1', $row_data)) {
													$order_data['address'] = $row_data['shipping address line1'];
												}
												if (array_key_exists('shipping address city', $row_data)) {
													$order_data['city'] = $row_data['shipping address city'];
												}
												if (array_key_exists('shipping address state', $row_data)) {
													$order_data['state'] = $row_data['shipping address state'];
												}
												if (array_key_exists('shipping address zip', $row_data)) {
													$order_data['postal_code'] = $row_data['shipping address zip'];
												}
												$order_id = $this->addOrder($order_data);

												if ($order_id) {
													$this->log->putLog("Added/Updated Order: " . $order_data['order_id']);
												} else {
													$this->log->putLog("Failed to add/update Order: " . $order_data['order_id']);
												}
											}
										}

									} else {
										$this->log->putLog("Could not open file: " . $file);
									}

								}
							}
							$this->addBlankLineInLogFile();
						}

						// Update emails table for is_processed = 1
						$update_email_data = array('id' => $email_id, 'is_processed' => 1);
						$email_id          = $this->updateEmailAfterProcessing($update_email_data);
						if ($email_id) {
							$this->log->putLog('Email updated after processing. Subject: ' . $email_subject . " | Id: " . $email_id);
						} else {
							$this->log->putLog('Error updating email after processing. Subject: ' . $email_subject . " | Id: " . $email_id);
						}
					} else {
                        $this->log->putLog('No attachments found for Email. Subject: ' . $email_subject . " | Date: " . $email_date);
                    }
				} else {
					$this->log->putLog('Email already processed. Subject: ' . $email_subject . " | Date: " . $email_date);
				}

				$this->addBlankLineInLogFile();
			}
		}
	}

	/**
	 * @param array $emails
	 */
	public function processMerchandisePurchasesEmails($emails = array())
	{
		if ($emails) {
			foreach ($emails as $email_index => $email) {

				$email_subject = $email['overview'][0]->subject;
				$email_date    = date('Y-m-d H:i:s', $email['unique_id']);

				// Check email is already processed or not. Process if not processed.
				$is_email_already_processed = $this->getProcessedEmail($email['unique_id']);

				if (!$is_email_already_processed) {

					// Prepare the Log file for the email. Log file name = Email unique id name
					//$this->log = new Logger(F_LOGS . $email['unique_id'] . ".txt");

					if ($attachments = $email['attachments']) {

						// Add Email in DB
						$add_email_data = array('cron_id' => $this->cron_id, 'unique_id' => $email['unique_id'], 'subject' => $email_subject, 'date' => $email_date, 'is_processed' => 0);
						$email_id       = $this->addEmail($add_email_data);
						if ($email_id) {
							$this->log->putLog('Added Email. Subject: ' . $email_subject . " | Date: " . $email_date);
						} else {
							$this->log->putLog('Error adding email. Subject: ' . $email_subject . " | Date: " . $email_date);
						}
						$this->addBlankLineInLogFile();

						// Loop through attachments
						foreach ($attachments as $attachment) {
							$path_info = pathinfo(F_EMAIL_ATTACHMENTS . $attachment);
							$zip       = new ZipArchive;

							if ($zip->open(F_EMAIL_ATTACHMENTS . $attachment) === true) {
								// EXTRACT ZIP FOLDER
								$zip->extractTo(F_EMAIL_ATTACHMENTS . $path_info['filename']);
								$zip->close();

								$files = glob(F_EMAIL_ATTACHMENTS . $path_info['filename'] . "/*.csv");
								foreach ($files as $file) {

									if (($handle = fopen($file, "r")) !== FALSE) {
										$index             = 0;
										$populated_headers = array();
										$populated_data    = array();
										while (($data = fgetcsv($handle, 1000, ",")) !== false) {
											$num = count($data);
											for ($c = 0; $c < $num; $c++) {
												if ($index == 0) {
													$populated_headers[] = strtolower(trim($data[$c]));
												} else {
													$populated_data[$index][$populated_headers[$c]] = $data[$c];
												}
											}
											$index++;
										}
										fclose($handle);

										if ($populated_data) {
											foreach ($populated_data as $row_index => $row_data) {
												$order_product_data = array('email_id' => $email_id, 'order_id' => 0, 'product_code' => '', 'product_name' => '', 'product_id' => '', 'quantity' => '', 'shipping_method' =>'');
												if (array_key_exists('order number', $row_data)) {
													$order_product_data['order_id'] = $row_data['order number'];
												}
												if (array_key_exists('product code', $row_data)) {
													$order_product_data['product_code'] = $row_data['product code'];
												}
												if (array_key_exists('product name', $row_data)) {
													$order_product_data['product_name'] = $row_data['product name'];
												}
												if (array_key_exists('item id', $row_data)) {
													$order_product_data['product_id'] = $row_data['item id'];
												}
												if (array_key_exists('quantity', $row_data)) {
													$order_product_data['quantity'] = $row_data['quantity'];
												}
                                                if (array_key_exists('shipping method', $row_data)) {
                                                    $order_product_data['shipping_method'] = $row_data['shipping method'];
                                                }
												$order_product_id = $this->addOrderProduct($order_product_data);

												if ($order_product_id) {
													$this->log->putLog("Added/Updated Order Product: Order ID=" . $order_product_data['order_id'] . " | Product Code=" . $order_product_data['product_code']);
												} else {
													$this->log->putLog("Failed to add/update Order Product: Order ID=" . $order_product_data['order_id'] . " | Product Code=" . $order_product_data['product_code']);
												}
											}
										}

									} else {
										$this->log->putLog("Could not open file: " . $file);
									}
								}
							}
							$this->addBlankLineInLogFile();
						}

						// Update emails table for is_processed = 1
						$update_email_data = array('id' => $email_id, 'is_processed' => 1);
						$email_id          = $this->updateEmailAfterProcessing($update_email_data);
						if ($email_id) {
							$this->log->putLog('Email updated after processing. Subject: ' . $email_subject . " | Id: " . $email_id);
						} else {
							$this->log->putLog('Error updating email after processing. Subject: ' . $email_subject . " | Id: " . $email_id);
						}
					} else {
                        $this->log->putLog('No attachments found for Email. Subject: ' . $email_subject . " | Date: " . $email_date);
                    }
				} else {
					$this->log->putLog('Email already processed. Subject: ' . $email_subject . " | Date: " . $email_date);
				}

				$this->addBlankLineInLogFile();
			}
		}
	}

	/**
	 *
	 */
	public function processOrders()
	{
		$orders = $this->getBookOrders();
		if ($orders) {
            $omnipress_username = $this->getConfigValueByKey('omnipress_username');
            $omnipress_password = $this->getConfigValueByKey('omnipress_password');

		    $this->omnipress_obj = new Omnipress($omnipress_username, $omnipress_password);
			$this->log->putLog('Starting pushing Book Orders on Omnipress');
			//Call omnipress API to push orders into the Omnipress. iterate the loop and push the order to the omnipress
            foreach ($orders as $order) {
                $push_order_response = $this->omnipress_obj->pushOrder($order);

                if($push_order_response['success']){
                    $this->log->putLog("Added/Updated Order Product: Order ID=" . $order['order']['order_id'] . " | Product Code=" . $order['order_products'][0]['product_code']);
                }else{
                    $this->is_error = true;
                    $this->log->putLog("Failed to add/update Order Product: Order ID=" . $order['order']['order_id'] . " | Product Code=" . $order['order_products'][0]['product_code']);
                    $this->log->putLog("Omnipress API Error Message= " . $push_order_response['error_message']);
                }
            }
			$this->log->putLog('Completed pushing Book Orders on Omnipress');
		} else {
			$this->log->putLog('No Book Orders found to push on Omnipress');
		}
		$this->addBlankLineInLogFile();
	}

    /**
     * To fetch the cron logs records
     * @return bool
     */
    public function getCronLogs()
    {
        try {
            $prior_days_cron_logs = $this->getConfigValueByKey('flush_data_prior_days');
            $current_date         = date('Y-m-d H:i:s');
            $sql                  = " SELECT *, DATEDIFF('$current_date', DATE_FORMAT(FROM_UNIXTIME(unique_id), '%Y-%m-%d %H:%i:%s')) as days_before FROM omnipress.cron_logs HAVING days_before > " . $prior_days_cron_logs;
            $stm                  = $this->_db->prepare($sql);
            $res                  = $stm->execute();
            if ($res) {
                $rows = $stm->fetchAll(PDO::FETCH_ASSOC);
                if ($rows) {
                    return $rows;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } catch (Exception $e) {
            $this->log->putLog($e->getMessage());
            return false;
        }
    }

    /**
     * To delete the cron logs record
     * @param $id
     * @return bool
     */
    public function deleteCronLog($id)
    {
        try {
            $sql = " DELETE FROM cron_logs WHERE id = :id ";
            $stm = $this->_db->prepare($sql);
            $stm->bindParam(":id", $id);
            $res = $stm->execute();
            if ($res) {
                $this->log->putLog('Deleted cron data');
                return true;
            } else {
                $this->log->putLog('Error Deleting cron data');
                return false;
            }

        } catch (Exception $e) {
            $this->log->putLog($e->getMessage());
            return false;
        }
    }

    /**
     * @param $id
     * @return bool
     */
    public function getEmailsByCronId($id)
    {
        try {
            $sql = " SELECT * FROM emails WHERE cron_id = :id ";
            $stm = $this->_db->prepare($sql);
            $stm->bindParam(":id", $id);
            $res = $stm->execute();
            if ($res) {
                $rows = $stm->fetchAll(PDO::FETCH_ASSOC);
                if ($rows) {
                    return $rows;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } catch (Exception $e) {
            $this->log->putLog($e->getMessage());
            return false;
        }
    }

    /**
     * To delete the old data from the cron, orders and order_product table
     * @return bool
     */
    public function flushData()
    {
        try {

            $prior_days_cron_logs = $this->getConfigValueByKey('flush_data_prior_days');
            $flush_start_date       = date('m-d-Y', strtotime("-" . $prior_days_cron_logs . " days"));

            $current_date         = date('Y-m-d H:i:s');
            $this->cron_unique_id = strtotime($current_date);

            // Prepare the Log file for the email. Log file name = Cron unique id name
            $this->log = new Logger(F_LOGS . $this->cron_unique_id . "_flush_data.txt");

            $this->log->putLog('Starting flushing data before: ' . $flush_start_date);
            $this->addBlankLineInLogFile();

            $cronLogs = $this->getCronLogs();
            if ($cronLogs && is_array($cronLogs) && count($cronLogs)) {
                foreach ($cronLogs as $log) {

                    $this->log->putLog('Starting flushing data for Cron Id: ' . $log['id'] . ' | Cron Date: ' . date('m-d-Y H:i:s', $log['unique_id']));

                    //Delete the log file from the logs directory
                    $logFile            = F_LOGS . $log['unique_id'] . ".txt";
                    $deleteFileResponse = $this->deleteFile($logFile);
                    if ($deleteFileResponse)
                        $this->log->putLog('Deleted log file: ' . $log['unique_id'] . '.txt');
                    else
                        $this->log->putLog('Error in deleting log file : ' . $log['unique_id'] . '.txt');

                    //Delete directory or ZIP file from the email-attachments directory
                    $emails = $this->getEmailsByCronId($log['id']);
                    if ($handle = opendir(F_EMAIL_ATTACHMENTS)) {
                        while (false !== ($entry = readdir($handle))) {
                            if ($emails && is_array($emails) && count($emails)) {
                                foreach ($emails as $email) {
                                    if (strtok($entry, '-') == $email['unique_id']) {
                                        //Delete the ZIP files from the email_attachments directory
                                        $deleteFileResponse = $this->deleteFile(F_EMAIL_ATTACHMENTS . $entry . "zip");
                                        if ($deleteFileResponse)
                                            $this->log->putLog('Deleted email attachment zip: ' . $entry);
                                        else
                                            $this->log->putLog('Error in deleting email attachment zip : ' . $entry);

                                        //Delete the local directories from the email_attachments directory
                                        $deleteDirectoryResponse = $this->deleteDirectory(F_EMAIL_ATTACHMENTS . $entry);
                                        if ($deleteDirectoryResponse)
                                            $this->log->putLog('Deleted email attachment directory : ' . $entry);
                                        else
                                            $this->log->putLog('Error in deleting email attachment directory : ' . $entry);

                                    }
                                }
                            }
                        }
                        closedir($handle);
                    }

                    //Delete records from the Cronlogs tables
                    $this->deleteCronLog($log['id']);
                    $this->log->putLog('Completed flushing data for Cron Id: ' . $log['id'] . ' | Cron Date: ' . date('m-d-Y H:i:s', $log['unique_id']));
                    $this->addBlankLineInLogFile();
                }
            } else {
				$this->log->putLog('No data found to flush before : ' . $flush_start_date);
			}

            $this->log->putLog('Completed flushing data before : ' . $flush_start_date);
            $this->addBlankLineInLogFile();

            return true;
        } catch (Exception $e) {
            $this->log->putLog($e->getMessage());
            return false;
        }
    }

    /**
     * To delete files, directory from logs and email-attachments directories
     * @param $dir
     * @return bool|string
     */
    public function deleteFile($dir)
    {
        try {
            if (file_exists($dir) && !is_dir($dir)) {
                return unlink($dir);
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param $dir
     * @return bool
     */
    public function deleteDirectory($dir)
    {
        try {

            if (file_exists($dir) && !is_dir($dir)) {
                return unlink($dir);
            }
            foreach (scandir($dir) as $item) {
                if ($item == '.' || $item == '..') {
                    continue;
                }

                if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                    return false;
                }
            }
            return rmdir($dir);
        } catch (Exception $e) {
            return false;
        }
    }

}