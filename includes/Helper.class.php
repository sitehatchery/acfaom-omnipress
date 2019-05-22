<?php
require_once(F_INCLUDES . "Logger.class.php");

class Helper
{

	protected $_db;

	public function __construct($db)
	{
		$this->_db = $db;
	}

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

	public function addEmail($data = array())
	{
		try {
			$sql
				 = "INSERT INTO emails (
                            unique_id,
                            subject,
                            date,
                            is_processed
                        ) VALUES (
                            :unique_id,
                            :subject,
                            :date,
                            :is_processed
                        ) ON DUPLICATE KEY UPDATE
                        	`id` = LAST_INSERT_ID(`id`),
                        	date = :date,
							is_processed = :is_processed";
			$stm = $this->_db->prepare($sql);

			$stm->bindParam(":unique_id", $unique_id);
			$stm->bindParam(":subject", $subject);
			$stm->bindParam(":date", $date);
			$stm->bindParam(":is_processed", $is_processed);

			foreach ($data as $key => $value) {
				$$key = $value;
			}

			$res = $stm->execute();

			if (!$res) {
				return false;
			} else {
				return $this->_db->lastInsertId();
			}
		}
		catch (Exception $e) {
			return false;
		}
	}

	public function updateEmailAfterProcessing($data = array())
	{
		try {
			$sql = "UPDATE emails SET is_processed = :is_processed WHERE id=:id";
			$stm = $this->_db->prepare($sql);

			$stm->bindParam(":id", $id);
			$stm->bindParam(":is_processed", $is_processed);

			foreach ($data as $key => $value) {
				$$key = $value;
			}

			$res = $stm->execute();

			if (!$res) {
				return false;
			} else {
				return $this->_db->lastInsertId();
			}
		}
		catch (Exception $e) {
			return false;
		}
	}

	public function addOrder($data = array())
	{
		try {
			$sql
				 = "INSERT INTO `order` (
                            order_id,
                            order_date,
                            company_name,
                            customer_name,
                            address,
                            city,
                            state,
                            postal_code,
                            phone_number,
                            email_address,
                            carrier_code
                        ) VALUES (
                            :order_id,
                            :order_date,
                            :company_name,
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
                        	order_date = :order_date,
							company_name = :company_name,
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

			$stm->bindParam(":order_id", $order_id);
			$stm->bindParam(":order_date", $order_date);
			$stm->bindParam(":company_name", $company_name);
			$stm->bindParam(":customer_name", $customer_name);
			$stm->bindParam(":address", $address);
			$stm->bindParam(":city", $city);
			$stm->bindParam(":state", $state);
			$stm->bindParam(":postal_code", $postal_code);
			$stm->bindParam(":phone_number", $phone_number);
			$stm->bindParam(":email_address", $email_address);
			$stm->bindParam(":carrier_code", $carrier_code);

			foreach ($data as $key => $value) {
				$$key = $value;
			}

			$res = $stm->execute();
			if (!$res) {
				return false;
			} else {
				return $this->_db->lastInsertId();
			}
		}
		catch (Exception $e) {
			print_r($e->getMessage());
			return false;
		}
	}

	public function addOrderProduct($data = array())
	{
		try {
			$sql
				 = "INSERT INTO `order_products` (
                            order_id,
                            product_name,
                            product_id,
                            quantity
                        ) VALUES (
                            :order_id,
                            :product_name,
                            :product_id,
                            :quantity
                        ) ON DUPLICATE KEY UPDATE
                        	`id` = LAST_INSERT_ID(`id`),
                        	product_name = :product_name,
							quantity = :quantity
							";
			$stm = $this->_db->prepare($sql);

			$stm->bindParam(":order_id", $order_id);
			$stm->bindParam(":product_name", $product_name);
			$stm->bindParam(":product_id", $product_id);
			$stm->bindParam(":quantity", $quantity);

			foreach ($data as $key => $value) {
				$$key = $value;
			}

			$res = $stm->execute();
			if (!$res) {
				return false;
			} else {
				return $this->_db->lastInsertId();
			}
		}
		catch (Exception $e) {
			print_r($e->getMessage());
			return false;
		}
	}

	public function processOrderSearchEmails($emails = array())
	{
		if ($emails) {
			foreach ($emails as $email_index => $email) {

				// Check email is already processed or not. Process if not processed.
				$is_email_already_processed = $this->getProcessedEmail($email['unique_id']);

				if (!$is_email_already_processed) {

					// Prepare the Log file for the email. Log file name = Email unique id name
					$log = new Logger(F_LOGS . $email['unique_id'] . ".txt");

					if ($attachments = $email['attachments']) {

						// Add Email in DB
						$add_email_data = array('unique_id' => $email['unique_id'], 'subject' => $email['overview'][0]->subject, 'date' => date('Y-m-d H:i:s', $email['unique_id']), 'is_processed' => 0);
						$email_id       = $this->addEmail($add_email_data);

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
												$order_data = array('order_id' => 0, 'order_date' => '', 'company_name' => '', 'customer_name' => '', 'address' => '', 'city' => '', 'state' => '', 'postal_code' => '', 'phone_number' => '', 'email_address' => '', 'carrier_code' => '');
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
													$log->putLog("Added/Updated Order: " . $order_data['order_id']);
												} else {
													$log->putLog("Failed to add/update Order: " . $order_data['order_id']);
												}
											}
										}

									} else {
										$log->putLog("Could not open file: " . $file);
									}

								}
							}

							/*
							 * todo
							 * 3. Update emails table for is_processed = 1
							 *
							 */
						}

						$update_email_data = array('id' => $email_id, 'is_processed' => 1);
						$email_id          = $this->updateEmailAfterProcessing($update_email_data);
					}
				}
			}
		}
	}

	public function processMerchandisePurchasesEmails($emails = array())
	{
		if ($emails) {
			foreach ($emails as $email_index => $email) {

				// Check email is already processed or not. Process if not processed.
				$is_email_already_processed = $this->getProcessedEmail($email['unique_id']);

				if (!$is_email_already_processed) {

					// Prepare the Log file for the email. Log file name = Email unique id name
					$log = new Logger(F_LOGS . $email['unique_id'] . ".txt");

					if ($attachments = $email['attachments']) {

						// Add Email in DB
						$add_email_data = array('unique_id' => $email['unique_id'], 'subject' => $email['overview'][0]->subject, 'date' => date('Y-m-d H:i:s', $email['unique_id']), 'is_processed' => 0);
						$email_id       = $this->addEmail($add_email_data);

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
												$order_product_data = array('order_id' => 0, 'product_name' => '', 'product_id' => '', 'quantity' => '');
												if (array_key_exists('order number', $row_data)) {
													$order_product_data['order_id'] = $row_data['order number'];
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
												$order_product_id = $this->addOrderProduct($order_product_data);

												if ($order_product_id) {
													$log->putLog("Added/Updated Order Product: Order ID=" . $order_product_data['order_id'] . " | Product Name=" . $order_product_data['product_name']);
												} else {
													$log->putLog("Failed to add/update Order Product: Order ID=" . $order_product_data['order_id'] . " | Product Name=" . $order_product_data['product_name']);
												}
											}
										}

									} else {
										$log->putLog("Could not open file: " . $file);
									}
								}
							}

							/*
							 * todo
							 * 3. Update emails table for is_processed = 1
							 *
							 */
						}

						$update_email_data = array('id' => $email_id, 'is_processed' => 1);
						$email_id          = $this->updateEmailAfterProcessing($update_email_data);
					}
				}
			}
		}
	}

}