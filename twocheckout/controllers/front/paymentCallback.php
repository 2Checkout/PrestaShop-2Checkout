<?php
require_once 'abstract.php';

/**
 * Class TwocheckoutPaymentCallbackModuleFrontController
 */
class TwocheckoutPaymentCallbackModuleFrontController extends TwocheckoutAbstarctModuleFrontController {

    /* @var $method */
    protected $method;

    public $errors;

    public $currentOrder;

    public $order;

    public $trns;

    public function init() {
        parent::init();
        $this->request = Tools::getAllValues();

    }

    public function postProcess() {
        $params = $this->request;

        try {
            $module_id = $params['id_module'];
            $cart_id   = $params['id_cart'];
            $ref_no    = isset( $params['refno'] ) ? $params['refno'] : null;

            if ( ! $module_id ) {
                $msg = sprintf( $this->trans( 'Twocheckout Payment Callback - No module name found. Invalid!' ) );
                throw new PrestaShopException( $msg );
            }

            if ( ! $cart_id ) {
                $msg = sprintf( $this->trans( 'Twocheckout Payment Callback - No cart found. Invalid!' ) );
                throw new PrestaShopException( $msg );
            }
            $this->module = Module::getInstanceById( $module_id );

            /** @var $order \Order */
            if ( $ref_no ) {
                $cart     = new Cart( $cart_id );
                $customer = new Customer( $cart->id_customer );

                if ( ! $customer ) {
                    $msg = sprintf( $this->trans( 'Twocheckout Payment Callback - Invalid customer!' ) );
                    throw new PrestaShopException( $msg );
                }
                $orderData = $this->module->tcoApi->call( "orders/" . $ref_no . "/", [], 'GET' );
                if ( isset( $orderData['RefNo'] ) && isset( $orderData['ExternalReference'] ) ) {

                    /**
                     * We can create order now!
                     */
                    $payment_currency_id        = $cart->id_currency;
                    $payment_currency           = Currency::getCurrencyInstance( $payment_currency_id );
                    $payment_currency_precision = $payment_currency->precision;
                    $amount_paid                = (string) $orderData['GrossPrice'];
                    $transaction_detail         = array(
                        'payment_status'   => $orderData['Status'],
                        'payment_method'   => '2Checkout - ' . $this->getPaymentMethod(),
                        'date_transaction' => $orderData['FinishDate'],
                        'transaction_id'   => $orderData['RefNo'],
                    );

                    $this->order = $this->validateTwocheckoutOrder(
                        $cart->id,
                        Configuration::get( 'PS_OS_PREPARATION' ),
                        $amount_paid,
                        $this->module->name,
                        'Order Created',
                        $transaction_detail,
                        null,
                        false,
                        $params['key'],
                        null,
                        $payment_currency_precision
                    );

                    $history           = new OrderHistory();
                    $history->id_order = (int) $this->order->id;

                    if ( ! $this->order->current_state ) {
                        $this->module->logger->log( sprintf( $this->trans( 'Changing order "%s" status to "Processing"' ),
                            $this->order->reference ),
                            __LINE__ );
                        $history->changeIdOrderState( (int) Configuration::get( 'PS_OS_PREPARATION' ), $this->order,
                            true );
                        $history->save();
                    }


                    if ( $this->order->hasInvoice() && isset( $orderData['RefNo'] ) && ! empty( $orderData['RefNo'] ) ) {
                        $invoice = $this->order->getInvoicesCollection()->getFirst();
                        //save REFNO to payment
                        if ( $invoice ) {
                            $payment                 = OrderPayment::getByInvoiceId( $invoice->id );
                            $firstPayment            = $payment->getFirst();
                            $payment                 = new OrderPayment( $firstPayment->id );
                            $payment->transaction_id = $orderData['RefNo'];
                            $payment->id_currency    = $payment_currency_id;
                            $payment->save();
                        }
                    }

                    if ( $orderData['Status'] == TWOCO_ENV_CHECKOUT_EVENT_TYPE_TRANSACTION_SUCCESS ||
                        $orderData['Status'] == TWOCO_ENV_CHECKOUT_EVENT_TYPE_TRANSACTION_AUTHORIZED ) {
                        $this->module->logger->log( sprintf( $this->trans( 'Changing order "%s" status to "Payment accepted"' ),
                            $this->order->reference ),
                            __LINE__ );
                        $history->changeIdOrderState( Configuration::get( 'PS_OS_PAYMENT' ), $this->order, true );
                        $history->save();
                        $this->redirectUrl = 'index.php?controller=order-confirmation&id_cart=' . $cart->id . '&id_module='
                                             . $this->module->id . '&id_order=' . $this->order->id . '&key=' . $customer->secure_key;
                    } elseif ( TWOCO_ENV_CHECKOUT_EVENT_TYPE_TRANSACTION_FAILED == $orderData['Status'] || TWOCO_ENV_CHECKOUT_EVENT_TYPE_TRANSACTION_DECLINED == $orderData['Status'] ) {

                        $this->module->logger->log( sprintf( $this->trans( 'Changing order "%s" status to "ERROR". OrderData: %s' ),
                            $this->order->reference, json_encode( $orderData ) ),
                            __LINE__ );
                        $history->changeIdOrderState( Configuration::get( '_PS_OS_ERROR_' ), $this->order, true );
                        $history->save();
                        $this->errors['err'] = sprintf( $this->trans( 'Payment failed for order ref: %s, please try again or contact the merchant!' ),
                            $this->order->getUniqReference() );
                    } else {
                        $this->errors['warn'] = sprintf( $this->trans( 'Payment didn\'t finalize yet, recheck the order details or contact the 
                    merchant! Order reference: "%s" and Payment transaction id:"%s"' ),
                            $this->order->getUniqReference(),
                            $orderData['RefNo'] );
                    }
                    if ( ! empty( $this->errors ) ) {
                        $this->redirectUrl = Context::getContext()->link->getModuleLink( $this->name, 'error',
                            $this->errors );
                    }
                }
            } else {
                $msg = sprintf( $this->trans( 'Twocheckout Payment Callback - Fatal error, cannot identify refno!' ) );
                throw new PrestaShopException( $msg );
            }

            $this->order->save();

        } catch ( Exception $e ) {
            $this->errors['err'] = $this->trans( 'Payment failed, please try again or contact the merchant!' );
            $this->module->logger->log( sprintf( $this->trans( 'Exception handling payment callback: %s' ),
                $e->getMessage() ), __LINE__ );
            $this->redirectUrl = Context::getContext()->link->getModuleLink( $this->name, 'error', $this->errors );
        }
    }

    public function validateTwocheckoutOrder(
        $id_cart,
        $id_order_state,
        $amount_paid,
        $payment_method = 'Unknown',
        $message = null,
        $transaction = array(),
        $currency_special = null,
        $dont_touch_amount = false,
        $secure_key = false,
        Shop $shop = null,
        $precision = 2
    ) {
        if ( $this->needConvert() ) {
            $amount_paid_curr = Tools::ps_round( Tools::convertPrice( $amount_paid, new Currency( $currency_special ),
                true ), $precision );
        } else {
            $amount_paid_curr = Tools::ps_round( $amount_paid, $precision );
        }
        $amount_paid = Tools::ps_round( $amount_paid, $precision );

        $cart     = new Cart( (int) $id_cart );
        $total_ps = (float) $cart->getOrderTotal( true, Cart::BOTH );
        if ( $amount_paid_curr > $total_ps + 0.10 || $amount_paid_curr < $total_ps - 0.10 ) {
            $total_ps = $amount_paid_curr;
        }

        try {
            $this->module->validateOrder(
                (int) $id_cart,
                (int) $id_order_state,
                (float) $total_ps,
                $payment_method,
                $message,
                $transaction,
                $currency_special,
                $dont_touch_amount,
                $secure_key
            //$shop
            );
        } catch ( Exception $e ) {


            $msg = sprintf( $this->trans( 'Order validation error : "%s"; File: "%s"; Line: "%s";' ), $e->getMessage(),
                $e->getFile(), $e->getLine() );
            $this->module->logger->log( $msg, __LINE__ );

            $this->currentOrder = (int) Order::getIdByCartId( (int) $id_cart );

            if ( $this->currentOrder == false ) {
                $msg = $this->trans( 'Order validation error : ' . $e->getMessage() . '. ',
                    array(), 'Modules.Twocheckout.Admin' );
            }
            throw new PrestaShopException( $msg );
        }

        $order = new Order( $this->module->currentOrder );
        $order->setInvoice( true );

        if ( isset( $amount_paid_curr ) && $amount_paid_curr != 0 && $order->total_paid != $amount_paid_curr && $this->isOneOrder( $order->reference ) ) {
            $order->total_paid          = $amount_paid_curr;
            $order->total_paid_real     = $amount_paid_curr;
            $order->total_paid_tax_incl = $amount_paid_curr;
            $order->update();

            $sql = 'UPDATE `' . _DB_PREFIX_ . 'order_payment`
		    SET `amount` = ' . (float) $amount_paid_curr . '
		    WHERE  `order_reference` = "' . pSQL( $order->reference ) . '"';
            Db::getInstance()->execute( $sql );
        }
        $order->save();

        return $order;
    }

    /**
     * Check if we need convert currency
     * @return boolean|integer currency id
     */
    public function needConvert() {
        $currency_mode = Currency::getPaymentCurrenciesSpecial( $this->module->id );
        $mode_id       = $currency_mode['id_currency'];
        if ( $mode_id == - 2 ) {
            return (int) Configuration::get( 'PS_CURRENCY_DEFAULT' );
        } elseif ( $mode_id == - 1 ) {
            return false;
        } elseif ( $mode_id != $this->context->currency->id ) {
            return (int) $mode_id;
        } else {
            return false;
        }
    }

    public function isOneOrder( $order_reference ) {
        $query = new DBQuery();
        $query->select( 'COUNT(*)' );
        $query->from( 'orders' );
        $query->where( 'reference = "' . pSQL( $order_reference ) . '"' );
        $countOrders = (int) DB::getInstance()->getValue( $query );

        return $countOrders == 1;
    }

    public function getPaymentMethod() {
        $payment_type_nr = (int) $this->module->tco_settings->getPaymentType();
        $paymentLabel    = $payment_type_nr == 2 ? "2PayJs API" : ( $payment_type_nr == 1 ) ? "Inline" : "Convert Plus";

        return $paymentLabel;
    }
}
