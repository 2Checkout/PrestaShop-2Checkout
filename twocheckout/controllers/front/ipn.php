<?php

/**
 * Class TwocheckoutValidationModuleFrontController
 */
class TwocheckoutIpnModuleFrontController extends ModuleFrontController
{
    /**
     * Ipn Constants
     *
     * Not all are used, however they should be left here
     * for future reference
     */
    const ORDER_CREATED = 'ORDER_CREATED';
    const FRAUD_STATUS_CHANGED = 'FRAUD_STATUS_CHANGED';
    const INVOICE_STATUS_CHANGED = 'INVOICE_STATUS_CHANGED';
    const REFUND_ISSUED = 'REFUND_ISSUED';
    //Order Status Values:
    const ORDER_STATUS_PENDING = 'PENDING';
    const ORDER_STATUS_PAYMENT_AUTHORIZED = 'PAYMENT_AUTHORIZED';
    const ORDER_STATUS_SUSPECT = 'SUSPECT';
    const ORDER_STATUS_INVALID = 'INVALID';
    const ORDER_STATUS_COMPLETE = 'COMPLETE';
    const ORDER_STATUS_REFUND = 'REFUND';
    const ORDER_STATUS_REVERSED = 'REVERSED';
    const ORDER_STATUS_PURCHASE_PENDING = 'PURCHASE_PENDING';
    const ORDER_STATUS_PAYMENT_RECEIVED = 'PAYMENT_RECEIVED';
    const ORDER_STATUS_CANCELED = 'CANCELED';
    const ORDER_STATUS_PENDING_APPROVAL = 'PENDING_APPROVAL';
    const FRAUD_STATUS_APPROVED = 'APPROVED';
    const FRAUD_STATUS_DENIED = 'DENIED';
    const FRAUD_STATUS_REVIEW = 'UNDER REVIEW';
    const FRAUD_STATUS_PENDING = 'PENDING';
    const PAYMENT_METHOD = 'tco_checkout';

    /**
     * @var string
     */
    private $secretKey;

    /**
     * @var \Order
     */
    private $order;

    /**
     * TwocheckoutIpnModuleFrontController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->secretKey = Configuration::get('TWOCHECKOUT_SECRET_KEY');
    }

    /**
     * @throws \Exception
     */
    public function initContent()
    {
        // I can't believe prestashop doesn't have a more reliable way
        // to check if a request is post...
        if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
            die;
        }

        // This may seem like a bad idea but it's not
        // There's not echoing of data, no sql queries, no other calls, no js, no nothing
        $params = $_REQUEST;

        if (!isset($params['REFNOEXT']) && (!isset($params['REFNO']) && empty($params['REFNO']))) {
            throw new Exception(sprintf('Cannot identify order: "%s".',
                $params['REFNOEXT']));
        }

        if (!$this->isIpnResponseValid($params, $this->secretKey)) {
            throw new Exception(sprintf('MD5 hash mismatch for 2Checkout IPN with date: "%s".',
                $params['IPN_DATE']));
        }

        $this->order = new Order((int)$params['REFNOEXT']);

        if (!Validate::isLoadedObject($this->order)) {
            throw new Exception(sprintf('Unable to load order with orderId %s. IPN failed.',
                $params['REFNOEXT']));
        }

        // do not wrap this in a try catch
        // it's intentionally left out so that the exceptions will bubble up
        // and kill the script if one should arise
        $this->_processFraud($params);

        if (!$this->_isFraud($params)) {
            $this->_processOrderStatus($params);
        }

        // bestest way to return a response ever, go prestashop!
        echo $this->_calculateIpnResponse(
            $params,
            $this->secretKey
        );
        die;
    }


    /**
     * @param $params
     *
     * @return bool
     */
    protected function _isFraud($params)
    {
        return (isset($params['FRAUD_STATUS']) && $params['FRAUD_STATUS'] === self::FRAUD_STATUS_DENIED);
    }

    /**
     * @param $params
     * @param $secretKey
     *
     * @return bool
     */
    public function isIpnResponseValid($params, $secretKey)
    {
        $result = '';
        $receivedHash = $params['HASH'];
        foreach ($params as $key => $val) {

            if ($key != "HASH") {
                if (is_array($val)) {
                    $result .= $this->arrayExpand($val);
                } else {
                    $size = strlen(stripslashes($val));
                    $result .= $size . stripslashes($val);
                }
            }
        }

        if (isset($params['REFNO']) && !empty($params['REFNO'])) {
            $calcHash = $this->hmac($secretKey, $result);
            if ($receivedHash === $calcHash) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $ipnParams
     * @param $secret_key
     *
     * @return string
     */
    private function _calculateIpnResponse($ipnParams, $secret_key)
    {
        $resultResponse = '';
        $ipnParamsResponse = [];
        // we're assuming that these always exist, if they don't then the problem is on avangate side
        $ipnParamsResponse['IPN_PID'][0] = $ipnParams['IPN_PID'][0];
        $ipnParamsResponse['IPN_PNAME'][0] = $ipnParams['IPN_PNAME'][0];
        $ipnParamsResponse['IPN_DATE'] = $ipnParams['IPN_DATE'];
        $ipnParamsResponse['DATE'] = date('YmdHis');

        foreach ($ipnParamsResponse as $key => $val) {
            $resultResponse .= $this->arrayExpand((array)$val);
        }

        return sprintf(
            '<EPAYMENT>%s|%s</EPAYMENT>',
            $ipnParamsResponse['DATE'],
            $this->hmac($secret_key, $resultResponse)
        );
    }

    /**
     * @param $array
     *
     * @return string
     */
    private function arrayExpand($array)
    {
        $retval = '';
        foreach ($array as $key => $value) {
            $size = strlen(stripslashes($value));
            $retval .= $size . stripslashes($value);
        }
        return $retval;
    }

    /**
     * @param $key
     * @param $data
     *
     * @return string
     */
    private function hmac($key, $data)
    {
        $b = 64; // byte length for md5
        if (strlen($key) > $b) {
            $key = pack("H*", md5($key));
        }

        $key = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad;
        $k_opad = $key ^ $opad;

        return md5($k_opad . pack("H*", md5($k_ipad . $data)));
    }

    /**
     * @param $params
     *
     * @throws \PrestaShopException
     * @throws \Exception
     */
    private function _processOrderStatus($params)
    {
        $orderStatus = $params['ORDERSTATUS'];
        if (!empty($orderStatus)) {
            switch (trim($orderStatus)) {
                case self::ORDER_STATUS_PENDING:
                case self::ORDER_STATUS_PURCHASE_PENDING:
                    if(!$this->_isOrderCompleted())
                        $this->order->setCurrentState(Configuration::get('PS_OS_PREPARATION'));
                    break;
                case self::ORDER_STATUS_PENDING_APPROVAL:
                case self::ORDER_STATUS_PAYMENT_AUTHORIZED:
                    if(!$this->_isOrderCompleted()) {
                        $this->order->setCurrentState(Configuration::get('PS_OS_PREPARATION'));
                    }
                    break;

                case self::ORDER_STATUS_COMPLETE:
                    $this->order->setCurrentState(Configuration::get('PS_OS_PAYMENT'));
                    $this->_createTransactionId($params);
                    $this->_isChargeBack($params);
                    break;

                case self::ORDER_STATUS_REFUND:
                    $this->order->setCurrentState(Configuration::get('PS_OS_REFUND'));
                    break;

                default:
                    throw new Exception('Cannot handle Ipn message type for message');
            }

            $this->order->save();
        }
    }

    private function _isOrderCompleted(){
        return $this->order->getCurrentOrderState() == Configuration::get('PS_OS_PAYMENT');
    }

    /**
     * @param $params
     */
    private function _createTransactionId($params)
    {
        $orderPayment = OrderPayment::getByOrderReference($this->order->reference);
        foreach($orderPayment as $payment)
        {
            $payment->transaction_id = $params['REFNO'];
            $payment->save();
        }
    }

    /**
     * @param $params
     *
     * @throws \PrestaShopException
     */
    private function _processFraud($params)
    {

        if (isset($params['FRAUD_STATUS'])) {
            switch (trim($params['FRAUD_STATUS'])) {
                case self::FRAUD_STATUS_DENIED:
                    $this->order->setCurrentState(Configuration::get('PS_OS_ERROR'));
                    break;

                case self::FRAUD_STATUS_APPROVED:
                    $this->order->setCurrentState(Configuration::get('PS_OS_PREPARATION'));
                    break;
            }

            $this->order->save();
        }
    }

    /**
     * check for Chargeback resolution and code & add a message (private) to order
     * @param $params
     */
    private function _isChargeBack($params)
    {
        $reasons = [
            'UNKNOWN'                  => 'Unknown', //default
            'MERCHANDISE_NOT_RECEIVED' => 'Order not fulfilled/not delivered',
            'DUPLICATE_TRANSACTION'    => 'Duplicate order',
            'FRAUD / NOT_RECOGNIZED'   => 'Fraud/Order not recognized',
            'FRAUD'                    => 'Fraud',
            'CREDIT_NOT_PROCESSED'     => 'Agreed refund not processed',
            'NOT_RECOGNIZED'           => 'New/renewal order not recognized',
            'AUTHORIZATION_PROBLEM'    => 'Authorization problem',
            'INFO_REQUEST'             => 'Information request',
            'CANCELED_RECURRING'       => 'Recurring payment was canceled',
            'NOT_AS_DESCRIBED'         => 'Product(s) not as described/not functional'
        ];

        // we need to mock up a message with some params in order to add this note
        if (!empty($params['CHARGEBACK_RESOLUTION']) && !empty($params['CHARGEBACK_REASON_CODE'])) {
            $thread = new CustomerThreadCore();
            $thread->email = 'ChargeBack@2checkout.com';
            $thread->status = 'open';
            $thread->token = $this->order->reference; // we need to fill this too (i chose the reference)
            $thread->id_shop = $this->order->id_shop;
            $thread->id_lang = $this->order->id_lang;
            $thread->id_contact = 0;
            $thread->id_customer = $this->order->id_customer;
            $thread->id_order = $this->order->id;
            $thread->save();

            $why = $reasons[trim($params['CHARGEBACK_REASON_CODE'])] ?? $reasons['UNKNOWN'];
            $message = '2Checkout chargeback status is ' . $params['CHARGEBACK_RESOLUTION'];
            $message .= '. Reason: ' . $why . '!';

            $orderMessage = new CustomerMessageCore();
            $orderMessage->id_customer_thread = $thread->id;
            $orderMessage->ip_address = null;
            $orderMessage->message = $message;
            $orderMessage->private = true;
            $orderMessage->save();
        }
    }

}
