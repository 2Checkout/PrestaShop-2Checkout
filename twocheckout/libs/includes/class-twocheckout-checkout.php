<?php

/**
 * Class Twocheckout_Checkout
 */
class Twocheckout_Checkout {

	/**
	 * @var string
	 */
	private $customer_id;

	/**
	 * @var \Twocheckout_Helper
	 */
	private $helper;

	/**
	 * @var \Twocheckout_Settings
	 */
	private $settings;

	/**
	 * @var \Order
	 */
	private $order;

    /**
     * @var Array
     */
	private $params;

	/**
	 * @var array
	 */
	protected $data;


	protected $precision;

	/**
	 * Twocheckout_Checkout constructor.
	 *
	 * @param \Order                      $order
	 * @param \Twocheckout_Settings $settings
	 * @param \Twocheckout_Helper        $helper
	 * @param string                         $customer_id
	 *
	 * @throws \Exception
	 */
	public function __construct(
		$params,
		Twocheckout_Settings $settings,
		Twocheckout_Helper $helper,
		$customer_id = ''
	) {

        $this->params = $params;
		$this->cart       = $params['cart'];
		$this->customer_id = $customer_id;
		$this->helper      = $helper;
		$this->settings    = $settings;
		$this->precision   = $this->getCurrencyPrecision( $params['currency']->iso_code);

		$this->buildData();
	}

	/**
	 * @throws \Exception
	 */
	private function buildData() {
		$customer_data = [];
		if ( $this->customer_id !== '' ) {
			$customer_data['customer'] = $this->customer_id;
		}

		$configurations = [
			'configurations' => [
				'card' => [
					'cvvRequired'       => $this->settings->getCvvOnPayment(),
					'captureNow'        => true,
					'paymentContractId' => $this->settings->getPaymentContractId(),
				],
			],
		];

		if ( $this->settings->is3dsEnabled() ) {
			$configurations['configurations']['card']['threedSecure'] = [
				'threeDSContractId' => $this->settings->get3dsContractId(),
				'enabled'           => $this->settings->is3dsEnabled(),
				'transactionMode'   => $this->helper->getDeviceType(),
			];
		}
        $cart_total_paid = (float) Tools::ps_round((float) $this->cart->getOrderTotal(true, Cart::BOTH), 2);
		$amount = $this->transformToCents($cart_total_paid, $this->precision);
		$params = [
			'amount'            => $amount,
			'currencyCode'      => strtoupper( $this->params['currency']->iso_code ),
			'entityId'          => $this->settings->getEntityId(),
			// must be a string
			'merchantReference' => sprintf( '%s', $this->cart->id ),
			'returnUrl'         => $this->params['returnUrl']
		];

		$this->data = $params;
		$this->data = array_merge( $this->data, $customer_data );
		$this->data = array_merge( $this->data, $configurations );
	}

	public function transformToCents(float $inputPrice, $precision){
        $dollars = preg_replace('/[^\\d.]+/', '', $inputPrice); // get rid of currency sign
        $cents_as_float = $dollars* pow(10, $precision); //multiply by 100, it becomes float
        $cents_as_string = (string)$cents_as_float; //convert float to string
        $cents = (int) $cents_as_string; //convert string to integer
        return $cents;
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

	public function getCurrencyPrecision($currencyCode){
        $payment_currency_id        = Currency::getIdByIsoCode( $currencyCode );
        $payment_currency           = Currency::getCurrencyInstance( $payment_currency_id );
        $payment_currency_precision = $payment_currency->precision;
        return $payment_currency_precision;
    }

}
