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

        $apiauth = array('Username' => $this->omnipress_username, 'Password' => $this->omnipress_password);
        $header  = new SoapHeader('http://sma-promail/', 'AuthenticationHeader', $apiauth);
        $this->client  = new SoapClient("https://rhu027.veracore.com/pmomsws/order.asmx?wsdl");
        $this->client->__setSoapHeaders($header);
    }

    /**
     * @param array $order
     * @return array
     */
    public function pushOrder($order = [])
    {

        $request   = new stdClass();
        $order_obj = new stdClass();

        //iterate the loop and push the order to the omnipress

        $order_id        = $order['order']['order_id'];
        $customer_id     = $order['order']['customer_id'];
        $customer_name   = $order['order']['customer_name'];
        $address         = $order['order']['address'];
        $city            = $order['order']['city'];
        $state           = $order['order']['state'];
        $postal_code     = $order['order']['postal_code'];
        $carrier_code    = $order['order']['carrier_code'];
        $product_code    = $order['order_products'][0]['product_code'];
        $product_name    = $order['order_products'][0]['product_name'];
        $shipping_method = $order['order_products'][0]['shipping_method'];

        /*
         * API process start
         * */

        //Set header parameters
        $header_obj     = new stdClass();
        $header_obj->ID = $order_id;

        //Set Classification parameters
        $classification               = new stdClass();
        $classification->CustomerCode = $customer_id;
        $classification->Vendor       = $customer_name;

        //Set ShipTo Parameters
        $ship_to_obj         = new stdClass();
        $order_ship_to_obj   = new stdClass();
        $freight_carrier_obj = new stdClass();

        $freight_carrier_obj->Name = 'UPS';

        $order_ship_to_obj->FreightCarrier  = $freight_carrier_obj;
        $order_ship_to_obj->Flag            = 'OrderedBy'; //choose either one of Other, OrderedBy
        $order_ship_to_obj->FullName        = $customer_name;
        $order_ship_to_obj->CityStateZip    = $address . " " . $city . " " . $state . " " . $postal_code;
        $order_ship_to_obj->CompoundAddress = $address . " " . $city . " " . $state . " " . $postal_code;

        $ship_to_obj->OrderShipTo = $order_ship_to_obj;

        //Set BillTo Parameters
        $bill_to_obj               = new stdClass();
        $bill_to_obj->Flag         = 'OrderedBy'; //choose either one of Other, OrderedBy, DoNotUse, ShipTo
        $bill_to_obj->FullName     = $customer_name;
        $bill_to_obj->CityStateZip = $address . " " . $city . " " . $state . " " . $postal_code;

        //Set offers parameters
        $offers_obj          = new stdClass();
        $offer_ordered_obj   = new stdClass();
        $offer_id_obj        = new stdClass();
        $Offer_id_header_obj = new stdClass();

        $Offer_id_header_obj->ID = $product_code;
        $offer_id_obj->Header    = $Offer_id_header_obj;

        //Added static values
        $offer_ordered_obj->Quantity         = 1;
        $offer_ordered_obj->UnitPrice        = 1;
        $offer_ordered_obj->ShippingHandling = 0;
        $offer_ordered_obj->Discounts        = 0;
        $offer_ordered_obj->ShipToKey        = 'test';
        $offer_ordered_obj->Offer            = $offer_id_obj;

        $offers_obj->OfferOrdered = $offer_ordered_obj;


        //Assigned all the object to the final prepare order data
        $order_obj->Header         = $header_obj;
        $order_obj->Classification = $classification;
        $order_obj->ShipTo         = $ship_to_obj;
        $order_obj->BillTo         = $bill_to_obj;
        $order_obj->Offers         = $offers_obj;

        //Send the request to the omnipress
        $request->order = $order_obj;

        try {
            $result = $this->client->AddOrder($request);
            return array(
                'success' => true,
            );
        } catch (Exception $e) {
            /*print_r($client->__getLastRequest());
            print_r($client->__getLastRequestHeaders());*/
            return array(
                'success' => false,
                'error_message' => $e->getMessage()
            );
        }

    }

}