<?php

class Twocheckout_Refund{

	/**
	 * @var \Order
	 */
	private $order;

	/**
	 * @var array
	 */
	protected $refund_params;
	private $amount;
	private $reason;
	private $transaction_id;

	public function __construct(
		$amount,
		$reason, //or comment
        $transaction_id,
		Order $order
	) {
		$this->order    = $order;
		$this->amount   = $amount;
		$this->reason  = $reason;
        $this->transaction_id = $transaction_id;
		$this->buildData();
	}

	/**
	 * @throws \Exception
	 */
	private function buildData() {
		$created_at = $this->order->date_add;
        $format = 'Y-m-d H:i:s';
        $createdDateTime = DateTime::createFromFormat($format, $created_at);

		if(!$createdDateTime instanceof DateTime) {
			throw new Exception('There was an error in the application, please contact the site administrator');
		}

		$this->refund_params = [
            'id' => "{$this->transaction_id}",
            'referenceId' => "{$this->order->id_cart}",
			'amount' => "{$this->amount}",
            'reason' => $this->reason,
			'createdDateTime' => "{$createdDateTime->format( 'Y-m-d\TH:i:s\Z' )}"
		];
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return $this->refund_params;
	}

	/**
	 * @return false|string
	 */
	public function toJson() {
		return json_encode($this->refund_params);
	}

}
