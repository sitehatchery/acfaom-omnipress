<?php

/**
 * Class Omnipress
 */
class Omnipress
{

	/**
	 * @var mixed
	 */
	protected $omnipress_username;
	/**
	 * @var mixed
	 */
	protected $omnipress_password;

	/**
	 * @var SoapClient
	 */
	protected $client;

	/**
	 * Omnipress constructor.
	 */
	public function __construct($omnipress_username, $omnipress_password)
	{
		$this->omnipress_password = $omnipress_password;
		$this->omnipress_username = $omnipress_username;

		$apiauth      = array('Username' => $this->omnipress_username, 'Password' => $this->omnipress_password);
		$header       = new SoapHeader('http://sma-promail/', 'AuthenticationHeader', $apiauth);
		$this->client = new SoapClient("https://rhu027.veracore.com/pmomsws/order.asmx?wsdl");
		$this->client->__setSoapHeaders($header);
	}

	/**
	 * @param array $order
	 * @return array
	 */
	public function pushOrder($order = [])
	{
		$order_id               = $order['order']['order_id'];
		$customer_id            = $order['order']['customer_id'];
		$customer_name          = $order['order']['customer_name'];
		$address                = $order['order']['address'];
		$city                   = $order['order']['city'];
		$state                  = $order['order']['state'];
		$postal_code            = $order['order']['postal_code'];
		$product_code           = $order['order_products'][0]['product_code'];
		$shipping_method        = $order['order_products'][0]['shipping_method'];
		$customer_name_exploded = explode(' ', $customer_name, 2);
		$customer_first_name    = $customer_name_exploded[0];
		$customer_last_name     = isset($customer_name_exploded[1]) ? $customer_name_exploded[1] : '';

		//Set header parameters
		$header_obj     = new stdClass();
		$header_obj->ID = $order_id;

		//Set Classification parameters
		$classification               = new stdClass();
		$classification->CustomerCode = $customer_id;

		//Set Ordered By
		$ordered_by_obj             = new stdClass();
		$ordered_by_obj->FirstName  = $customer_first_name;
		$ordered_by_obj->LastName   = $customer_last_name;
		$ordered_by_obj->Address1   = $address;
		$ordered_by_obj->City       = $city;
		$ordered_by_obj->State      = $state;
		$ordered_by_obj->PostalCode = $postal_code;

		//Set ShipTo Parameters
		$ship_to_obj                               = new stdClass();
		$order_ship_to_obj                         = new stdClass();
		$freight_carrier_obj                       = new stdClass();
		$freight_carrier_obj->Name                 = $order['shipping_info']['carrier'];
		$order_ship_to_obj->FirstName              = $customer_first_name;
		$order_ship_to_obj->LastName               = $customer_last_name;
		$order_ship_to_obj->Address1               = $address;
		$order_ship_to_obj->City                   = $city;
		$order_ship_to_obj->State                  = $state;
		$order_ship_to_obj->PostalCode             = $postal_code;
		$order_ship_to_obj->Flag                   = 'Other'; //choose either one of Other, OrderedBy
		$order_ship_to_obj->Key                    = '0';
		$order_ship_to_obj->FreightCarrier         = $freight_carrier_obj;
		$order_ship_to_obj->FreightCode            = $order['shipping_info']['carrier_code'];
		$order_ship_to_obj->FreightCodeDescription = $shipping_method;
		$ship_to_obj->OrderShipTo                  = $order_ship_to_obj;

		//Set offers parameters
		$offers_obj                 = new stdClass();
		$Offer_id_header_obj        = new stdClass();
		$Offer_id_header_obj->ID    = $product_code;
		$offer_id_obj               = new stdClass();
		$offer_id_obj->Header       = $Offer_id_header_obj;
		$order_ship_to_key_obj      = new stdClass();
		$order_ship_to_key_obj->Key = '0';

		//Set Offer Ordered
		$offer_ordered_obj                   = new stdClass();
		$offer_ordered_obj->Offer            = $offer_id_obj;
		$offer_ordered_obj->Quantity         = 1;
		$offer_ordered_obj->OrderShipTo      = $order_ship_to_key_obj;
		$offer_ordered_obj->UnitPrice        = 0;
		$offer_ordered_obj->ShippingHandling = 0;
		$offer_ordered_obj->Discounts        = 0;
		$offers_obj->OfferOrdered            = $offer_ordered_obj;

		//Assigned all the object to the final prepare order data
		$order_obj                 = new stdClass();
		$order_obj->Header         = $header_obj;
		$order_obj->Classification = $classification;
		$order_obj->OrderedBy      = $ordered_by_obj;
		$order_obj->ShipTo         = $ship_to_obj;
		$order_obj->Offers         = $offers_obj;

		//Send the request to the omnipress
		$request        = new stdClass();
		$request->order = $order_obj;

		try {
			$this->client->AddOrder($request);
			return array(
				'success' => true,
			);
		}
		catch (Exception $e) {
			/*print_r($client->__getLastRequest());
			print_r($client->__getLastRequestHeaders());*/
			return array(
				'success'       => false,
				'error_message' => json_encode($order_obj) . "\n" . $e->getMessage()
			);
		}

	}

}