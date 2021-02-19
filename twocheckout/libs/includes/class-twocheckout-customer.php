<?php

/**
 * Class Twocheckout_Customer
 */
class Twocheckout_Customer {

    /**
     * @var \Order
     */
    private $cart, $address, $customer;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var \Twocheckout_Settings
     */
    private $settings;

    private $shipping, $invoice;

    /**
     * Twocheckout_Customer constructor.
     *
     * @param \Order $order
     * @param \Twocheckout_Settings $settings
     */
    public function __construct(
        $params,
        Twocheckout_Settings $settings
    ) {
        $this->cart     = $params['cart'];
        $this->address  = $this->getAddresses();
        $this->customer = $params['customer'];
        $this->settings = $settings;
        $this->data     = $this->buildData();
    }

    public function getAddresses() {
        $addressArr = [];
        if ( isset( $this->cart->id_address_invoice ) ) {
            $addressArr['invoice'] = new Address( intval( $this->cart->id_address_invoice ) );
        }

        if ( isset( $this->cart->id_address_delivery ) ) {
            $addressArr['shipping'] = new Address( intval( $this->cart->id_address_delivery ) );
        }

        return $addressArr;
    }

    /**
     * @return array
     */
    private function buildData() {
        $data = [];

        $data = array_merge( $data, [ 'invoice' => $this->getBillingDetails() ] );

        return array_merge( $data, [ 'shipping' => $this->getShippingDetails() ] );
    }

    /**
     * @return array
     */

    public function getShippingDetails() {
        // use billing details if no shipping details are found
        if ( ! isset( $this->address['shipping'] ) ) {
            return $this->createBillingDetails();
        }

        $shipping_addr = $this->address['shipping'];

        $shipping = [
            'ship-name'    => substr( $shipping_addr->firstname, 0, 50 ) . ' ' . substr( $shipping_addr->lastname, 0,
                    50 ),
            'ship-address' => substr( $shipping_addr->address1, 0, 100 ),
            'ship-city'    => substr( $shipping_addr->city, 0, 50 ),
            'ship-country' => Country::getIsoById( $shipping_addr->id_country ),
            'ship-email'   => substr( $this->customer->email, 0, 150 )
        ];

        if ( State::getNameById( $shipping_addr->id_state ) ) {
            $shipping['ship-state'] = substr( State::getNameById( $shipping_addr->id_state ), 0, 50 );
        }

        if ( ! empty( $shipping_addr->address2 ) ) {
            $shipping['ship-address2'] = substr( $shipping_addr->address2, 0, 100 );
        }

        return $shipping;
    }

    /**
     * @return array
     */


    public function createBillingDetails() {
        $invoice = $this->address['invoice'];
        $billing = [
            'address' => substr( $invoice->address1, 0, 100 ),
            'city'    => substr( $invoice->city, 0, 50 ),
            'country' => strtoupper( Country::getIsoById( $invoice->id_country ) ),
            'name'    => substr( $invoice->firstname, 0, 50 ) . ' ' . substr( $invoice->lastname, 0, 50 ),
            'phone'   => preg_replace( '/[^0-9]+/', '', substr( $invoice->phone, 0, 20 ) ),
            'zip'     => substr( $invoice->postcode, 0, 10 ),
            'email'   => substr( $this->customer->email, 0, 150 )
        ];
        if ( ! empty( $invoice->company ) ) {
            $billing['company-name'] = substr( $invoice->company, 0, 100 );
        }

        if ( State::getNameById( $invoice->id_state ) ) {
            $billing['state'] = substr( State::getNameById( $invoice->id_state ), 0, 50 );
        }

        if ( ! empty( $invoice->address2 ) ) {
            $billing['address2'] = substr( $invoice->address2, 0, 100 );
        }

        if( !empty($invoice->vat_number)){
            $billing['fiscal-code'] = $invoice->vat_number;
        }

        return $billing;
    }

    /**
     * @return array
     */
    private function getBillingDetails() {
        return [
            'billing' => $this->createBillingDetails(),
        ];
    }

    /**
     * @return array
     */
    public function toArray() {
        return $this->data;
    }

    /**
     * @return false|string
     */
    public function toJson() {
        return json_encode( $this->data );
    }

}
