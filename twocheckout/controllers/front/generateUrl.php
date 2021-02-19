<?php

require_once 'abstract.php';

/**
 * Class TwocheckoutGenerateUrlModuleFrontController
 */
class TwocheckoutGenerateUrlModuleFrontController extends TwocheckoutAbstarctModuleFrontController {

    /**
     * @var array
     */
    private $_signParams = [
        'return-url',
        'return-type',
        'expiration',
        'order-ext-ref',
        'item-ext-ref',
        'lock',
        'cust-params',
        'customer-ref',
        'customer-ext-ref',
        'currency',
        'prod',
        'price',
        'qty',
        'tangible',
        'type',
        'opt',
        'coupon',
        'description',
        'recurrence',
        'duration',
        'renewal-price',
    ];

    /* @var $method */
    protected $method;

    public function postProcess() {

        $response   = null;
        $redirectTo = '';

        try {
            $module_name = Tools::getValue( 'module_name' );
            $cart_id     = Tools::getValue( 'cart_id' );

            if ( ! $module_name ) {
                $msg = sprintf( $this->trans( 'Invalid module name in Generate Url controller' ) );
                throw new PrestaShopException( $msg );
            }
            $this->module = Module::getInstanceByName( $module_name );

            /**
             * Get current cart object from session
             */
            $cart = ! empty( $cart_id ) ? $cart_id : $this->context->cart;

            if ( ! is_object( $cart ) ) {
                $cart = new Cart( $cart_id );
            }
            $authorized = false;

            /**
             * Verify if this module is enabled and if the cart has
             * a valid customer, delivery address and invoice address
             */
            if ( ! $this->module->active || $cart->id_customer == 0 || $cart->id_address_delivery == 0
                 || $cart->id_address_invoice == 0 ) {
                Tools::redirect( 'index.php?controller=order&step=1' );
            }

            /**
             * Verify if this payment module is authorized
             */
            foreach ( Module::getPaymentModules() as $module ) {
                if ( $module['name'] == 'twocheckout' ) {
                    $authorized = true;
                    break;
                }
            }

            if ( ! $authorized ) {
                die( $this->trans( 'This payment method is not available.' ) );
            }

            /** @var CustomerCore $customer */
            $customer = new Customer( $cart->id_customer );

            /**
             * Check if this is a valid customer account
             */
            if ( ! Validate::isLoadedObject( $customer ) ) {
                Tools::redirect( 'index.php?controller=order&step=1' );
            }


            //Check if live / test accounts passwords are set
            if ( ! $this->module->tco_settings->getSid()
                 || ! $this->module->tco_settings->getSecretWord() ) {
                $msg = sprintf( $this->trans( 'Seller ID OR Secret Word is missing! Please check module configuration.' ) );
                throw new PrestaShopException( $msg );
            }

            $languageIsoCode = Language::getIsoById( $cart->id_lang );
            $source          = 'PRESTASHOP_' . str_replace( '.', '_', _PS_VERSION_ );
            $currency        = Currency::getCurrency( $cart->id_currency );
            $tco_customer    = new Twocheckout_Customer( array( 'cart' => $cart, 'customer' => $customer ),
                $this->module->tco_settings );

            $returnUrl = $this->context->link->getModuleLink( $this->module->name, 'paymentCallback',
                [
                    'id_cart'   => $cart->id,
                    'id_module' => (int) $this->module->id,
                    'key'       => $customer->secure_key,
                ],
                true );

            switch ( (int) $this->module->tco_settings->getPaymentType() ) {
                case 2: //2payJs
                    $currencyIso         = strtolower( $currency['iso_code'] );
                    $invoice             = $tco_customer->getAddresses()['invoice'];
                    $billingAddressData  = $tco_customer->createBillingDetails();

                    $billingState = ( isset($billingAddressData['state']) && !empty($billingAddressData['state']) ) ?
                        $billingAddressData['state'] : '';
                    $billingCountry = ( isset($billingAddressData['country']) && !empty($billingAddressData['country']) ) ?
                        $billingAddressData['country'] : '';
                    $orderParams         = [
                        'Currency'          => $currencyIso,
                        'Language'          => $languageIsoCode,
                        'Country'           => $billingAddressData['country'],
                        'CustomerIP'        => $this->module->getCustomerIp(),
                        'Source'            => $source,
                        'ExternalReference' => $cart->id,
                        'Items'             => $this->module->getItem( $cart->id, $cart->getOrderTotal() ),
                        'BillingDetails'    => $this->module->getBillingDetails( $invoice, $billingState,
                            $billingCountry,
                            $customer->email ),
                        'PaymentDetails'    => $this->module->getPaymentDetails( Tools::getValue( 'ess_token' ),
                            $currencyIso,
                            $cart->id ),
                    ];

                    $apiResponse = $this->module->tcoApi->call( 'orders', $orderParams );

                    if ( ! isset( $apiResponse['RefNo'] ) ) {
                        $result = [ 'status' => false, 'errors' => $apiResponse['message'], 'redirect' => null ];
                    } elseif ( $apiResponse['Errors'] ) {
                        $errorMessage = '';
                        foreach ( $apiResponse['Errors'] as $key => $value ) {
                            $errorMessage .= $value . PHP_EOL;
                        }
                        $result = [ 'status' => false, 'error' => $errorMessage, 'redirect' => null ];

                    } else {
                        $returnUrl       = $this->context->link->getModuleLink( $this->module->name, 'paymentCallback',
                            [
                                'id_cart'   => $cart->id,
                                'id_module' => (int) $this->module->id,
                                'key'       => $customer->secure_key,
                                'refno'     => $apiResponse['RefNo']
                            ],
                            true );
                        $hasAuthorize3ds = $this->module->hasAuthorize3DS(
                            $apiResponse['PaymentDetails']['PaymentMethod']['Authorize3DS'] );
                        $redirectTo      = $hasAuthorize3ds ?? $returnUrl;
                        $result          = [ 'status' => true, 'errors' => null, 'redirect' => $redirectTo ];
                    }
                    exit( json_encode( $result ) );

                case 1: //inline

                    $inlineLinkParams             = [];
                    $billingAddressData           = $tco_customer->createBillingDetails();
                    $shippingAddressData          = $tco_customer->getShippingDetails();
                    $productData[]                = [
                        'type'     => 'PRODUCT',
                        'name'     => 'Cart_' . $cart->id,
                        'price'    => $cart->getOrderTotal(),
                        'tangible' => 0,
                        'qty'      => 1,
                    ];
                    $inlineLinkParams['products'] = ( $productData );

                    $inlineLinkParams['currency']      = strtolower( $currency['iso_code'] );
                    $inlineLinkParams['language']      = $languageIsoCode;
                    $inlineLinkParams['return-method'] = [
                        'type' => 'redirect',
                        'url'  => $returnUrl
                    ];

                    $inlineLinkParams['test']             = $this->module->tco_settings->getDemo();
                    $inlineLinkParams['order-ext-ref']    = $cart->id;
                    $inlineLinkParams['return-url']       = $returnUrl;
                    $inlineLinkParams['customer-ext-ref'] = $customer->email;
                    $inlineLinkParams['src']              = $source;
                    $inlineLinkParams['dynamic']          = 1;
                    $inlineLinkParams['merchant']         = $this->module->tco_settings->getSid();
                    $inlineLinkParams                     = array_merge( $inlineLinkParams, $billingAddressData );
                    $inlineLinkParams                     = array_merge( $inlineLinkParams, $shippingAddressData );
                    $inlineLinkParams['signature']        = $this->module->tcoApi->getInlineSignature(
                        $inlineLinkParams );

                    //Don't remove! This is used only for tcoInline js object.
                    $inlineLinkParams['shipping_address'] = ( $shippingAddressData );
                    $inlineLinkParams['billing_address']  = ( $billingAddressData );
                    $inlineLinkParams['mode']             = 'DYNAMIC';
                    $inlineLinkParams['url_data']         = [
                        'type' => 'redirect',
                        'url'  => $returnUrl
                    ];


                    $response = array( 'status' => 'success', 'inline_params' => json_encode( $inlineLinkParams ) );

                    break;
                default: //Convert+
                    $buyLinkParams             = [];
                    $buyLinkParams             = array_merge( $buyLinkParams, $tco_customer->createBillingDetails(),
                        $tco_customer->getShippingDetails() );
                    $buyLinkParams['prod']     = 'Cart_' . $cart->id;
                    $buyLinkParams['price']    = $cart->getOrderTotal();
                    $buyLinkParams['qty']      = 1;
                    $buyLinkParams['type']     = 'PRODUCT';
                    $buyLinkParams['tangible'] = 0;
                    $buyLinkParams['src']      = $source;
                    // url NEEDS a protocol(http or https)
                    $buyLinkParams['return-url']       = $returnUrl;
                    $buyLinkParams['return-type']      = 'redirect';
                    $buyLinkParams['expiration']       = time() + ( 3600 * 5 );
                    $buyLinkParams['order-ext-ref']    = $cart->id;
                    $buyLinkParams['item-ext-ref']     = date( 'YmdHis' );
                    $buyLinkParams['customer-ext-ref'] = $customer->email;
                    $buyLinkParams['currency']         = strtolower( $currency['iso_code'] );
                    $buyLinkParams['language']         = $languageIsoCode;
                    $buyLinkParams['test']             = (int) $this->module->tco_settings->getDemo();
                    // sid in this case is the merchant code
                    $buyLinkParams['merchant']  = $this->module->tco_settings->getSid();
                    $buyLinkParams['dynamic']   = 1;
                    $buyLinkParams['signature'] = $this->generateSignature(
                        $buyLinkParams,
                        $this->module->tco_settings->getSecretWord()
                    );

                    $redirectTo = 'https://secure.2checkout.com/checkout/buy/?' . ( http_build_query( $buyLinkParams ) );
                    //Success :) !
                    $response = array( 'status' => 'success', 'redirect_link' => $redirectTo );
            }
        } catch ( Exception $e ) {
            $this->module->logger->log( sprintf( $this->trans( 'Error: %s' ), $e->getMessage() ), __LINE__ );
            $this->errors['err'] = $this->trans( 'We encountered an error, please check Prestashop logs.' );
        }

        //Errors :( !
        if ( ! empty( $this->errors ) ) {
            $response = array(
                'success'       => false,
                'redirect_link' => Context::getContext()->link->getModuleLink
                ( $this->name, 'error', $this->errors )
            );
        }

        // Classic json response
        header( 'Content-Type: application/json' );
        die( json_encode( $response ) );
    }

    /**
     * @param      $params
     * @param      $secretWord
     * @param bool $fromResponse
     *
     * @return string
     */
    public function generateSignature(
        $params,
        $secretWord,
        $fromResponse = false
    ) {

        if ( ! $fromResponse ) {
            $signParams = array_filter( $params, function ( $k ) {
                return in_array( $k, $this->_signParams );
            }, ARRAY_FILTER_USE_KEY );
        } else {
            $signParams = $params;
            if ( isset( $signParams['signature'] ) ) {
                unset( $signParams['signature'] );
            }
        }

        ksort( $signParams ); // order by key
        // Generate Hash
        $string = '';
        foreach ( $signParams as $key => $value ) {
            $string .= strlen( $value ) . $value;
        }

        return bin2hex( hash_hmac( 'sha256', $string, $secretWord, true ) );
    }
}
