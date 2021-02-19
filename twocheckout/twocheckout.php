<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

require_once 'libs/interfaces/interface-2co-arrayable.php';
require_once 'libs/interfaces/interface-2co-jsonable.php';
require_once 'libs/abstract/abstract-2co-entity.php';

require_once 'libs/class-twocheckout-settings.php';

require_once 'libs/includes/class-mobile-detect.php';
require_once 'libs/includes/class-twocheckout-checkout.php';
require_once 'libs/includes/class-twocheckout-customer.php';
require_once 'libs/includes/class-twocheckout-helper.php';
require_once 'libs/includes/class-twocheckout-logger.php';
require_once 'libs/includes/class-twocheckout-refund.php';

require_once 'TwoCheckoutApi.php';

if ( ! defined( '_PS_VERSION_' ) ) {
    exit;
}

/**
 * Class Twocheckout - safe payment method
 */
class Twocheckout extends \PaymentModule {
    const DEBUG_MODE = false;
    const REFUND_REASON = 'Other';
    public $tcoApi;
    public $details;
    public $owner;
    public $name;
    public $address;
    public $bootstrap;
    public $is_eu_compatible;
    public $extra_mail_vars;
    public $confirmUninstall;
    public $tco_settings;
    public $logger;
    protected $_html = '';
    protected $_postErrors = [];
    /**
     * @var
     */
    private $module;

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

    /**
     * Twocheckout constructor.
     */
    public function __construct() {
        $this->name                   = 'twocheckout';
        $this->tab                    = 'payments_gateways';
        $this->version                = '1.0.0';
        $this->ps_versions_compliancy = [ 'min' => '1.7', 'max' => _PS_VERSION_ ];
        $this->author                 = '2Checkout by Verifone';
        $this->controllers            = [ 'validation' ];
        $this->is_eu_compatible       = 1;
        $this->currencies             = true;
        $this->currencies_mode        = 'checkbox';

        $this->logger       = new Twocheckout_Logger( __FILE__ );
        $this->tcoApi       = new TwoCheckoutApi();
        $this->tco_settings = new Twocheckout_Settings();
        $this->tcoApi->setSecretKey( $this->tco_settings->getSecretKey() );
        $this->tcoApi->setSellerId( $this->tco_settings->getSid() );
        $this->tcoApi->setSecretWord( $this->tco_settings->getSecretWord() );

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l( '2checkout' );
        $this->description = $this->l( '2checkout - Simple & safe payment solutions' );

        if ( ! count( Currency::checkPaymentCurrencies( $this->id ) ) ) {
            $this->warning = $this->l( 'No currency has been set for this module.' );
        }
        $this->confirmUninstall = $this->l( 'Are you sure you want to uninstall 2Checkout payment modules?' );
    }

    /**
     * default style for API form
     * @return string
     */
    private function getDefaultStyle() {
        return '{
                    "margin": "0",
                    "fontFamily": "Helvetica, sans-serif",
                    "fontSize": "1rem",
                    "fontWeight": "400",
                    "lineHeight": "1.5",
                    "color": "#212529",
                    "textAlign": "left",
                    "backgroundColor": "#FFFFFF",
                    "*": {
                        "boxSizing": "border-box"
                    },
                    ".no-gutters": {
                        "marginRight": 0,
                        "marginLeft": 0
                    },
                    ".row": {
                        "display": "flex",
                        "flexWrap": "wrap"
                    },
                    ".col": {
                        "flexBasis": "0",
                        "flexGrow": "1",
                        "maxWidth": "100%",
                        "padding": "0",
                        "position": "relative",
                        "width": "100%"
                    },
                    "div": {
                        "display": "block"
                    },
                    ".field-container": {
                        "paddingBottom": "14px"
                    },
                    ".field-wrapper": {
                        "paddingRight": "25px"
                    },
                    ".input-wrapper": {
                        "position": "relative"
                    },
                    "label": {
                        "display": "inline-block",
                        "marginBottom": "9px",
                        "color": "#313131",
                        "fontSize": "14px",
                        "fontWeight": "300",
                        "lineHeight": "17px"
                    },
                    "input": {
                        "overflow": "visible",
                        "margin": 0,
                        "fontFamily": "inherit",
                        "display": "block",
                        "width": "100%",
                        "height": "42px",
                        "padding": "10px 12px",
                        "fontSize": "18px",
                        "fontWeight": "400",
                        "lineHeight": "22px",
                        "color": "#313131",
                        "backgroundColor": "#FFF",
                        "backgroundClip": "padding-box",
                        "border": "1px solid #CBCBCB",
                        "borderRadius": "3px",
                        "transition": "border-color .15s ease-in-out,box-shadow .15s ease-in-out",
                        "outline": 0
                    },
                    "input:focus": {
                        "border": "1px solid #5D5D5D",
                        "backgroundColor": "#FFFDF2"
                    },
                    ".is-error input": {
                        "border": "1px solid #D9534F"
                    },
                    ".is-error input:focus": {
                        "backgroundColor": "#D9534F0B"
                    },
                    ".is-valid input": {
                        "border": "1px solid #1BB43F"
                    },
                    ".is-valid input:focus": {
                        "backgroundColor": "#1BB43F0B"
                    },
                    ".validation-message": {
                        "color": "#D9534F",
                        "fontSize": "10px",
                        "fontStyle": "italic",
                        "marginTop": "6px",
                        "marginBottom": "-5px",
                        "display": "block",
                        "lineHeight": "1"
                    },
                    ".card-expiration-date": {
                        "paddingRight": ".5rem"
                    },
                    ".is-empty input": {
                        "color": "#EBEBEB"
                    },
                    ".lock-icon": {
                        "top": "calc(50% - 7px)",
                        "right": "10px"
                    },
                    ".valid-icon": {
                        "top": "calc(50% - 8px)",
                        "right": "-25px"
                    },
                    ".error-icon": {
                        "top": "calc(50% - 8px)",
                        "right": "-25px"
                    },
                    ".card-icon": {
                        "top": "calc(50% - 10px)",
                        "left": "10px",
                        "display": "none"
                    },
                    ".is-empty .card-icon": {
                        "display": "block"
                    },
                    ".is-focused .card-icon": {
                        "display": "none"
                    },
                    ".card-type-icon": {
                        "right": "30px",
                        "display": "block"
                    },
                    ".card-type-icon.visa": {
                        "top": "calc(50% - 14px)"
                    },
                    ".card-type-icon.mastercard": {
                        "top": "calc(50% - 14.5px)"
                    },
                    ".card-type-icon.amex": {
                        "top": "calc(50% - 14px)"
                    },
                    ".card-type-icon.discover": {
                        "top": "calc(50% - 14px)"
                    },
                    ".card-type-icon.jcb": {
                        "top": "calc(50% - 14px)"
                    },
                    ".card-type-icon.dankort": {
                        "top": "calc(50% - 14px)"
                    },
                    ".card-type-icon.cartebleue": {
                        "top": "calc(50% - 14px)"
                    },
                    ".card-type-icon.diners": {
                        "top": "calc(50% - 14px)"
                    },
                    ".card-type-icon.elo": {
                        "top": "calc(50% - 14px)"
                    }
                }';
    }

    /**
     * install the module
     * @return bool|string
     */
    public function install() {

        if ( parent::install()
             && $this->registerHook( 'displayHeader' )
             && $this->registerHook( 'paymentOptions' )
             && $this->registerHook( 'displayOrderConfirmation' )
             && $this->registerHook( 'actionProductCancel' )
             && $this->registerHook( 'displayBackOfficeHeader' )
             && $this->registerHook( 'displayAdminOrder' )
             && $this->registerHook( 'displayAdminOrderTop' )
        ) {

            Configuration::updateValue( 'TWOCHECKOUT_STYLE', $this->getDefaultStyle() ); // to have a starting point
            Configuration::updateValue( 'TWOCHECKOUT_IPN_URL',
                $this->context->link->getModuleLink( 'twocheckout', 'ipn' ) );

            return true;
        }

        return false;
    }

    /**
     * uninstall the module and deletes the config keys
     * @return bool
     */
    function uninstall() {
        Configuration::deleteByName( 'TWOCHECKOUT_SID' );
        Configuration::deleteByName( 'TWOCHECKOUT_SECRET_KEY' );
        Configuration::deleteByName( 'TWOCHECKOUT_DEMO' );
        Configuration::deleteByName( 'TWOCHECKOUT_TYPE' );
        Configuration::deleteByName( 'TWOCHECKOUT_IPN_URL' );
        Configuration::deleteByName( 'TWOCHECKOUT_SECRET_WORD' );
        Configuration::deleteByName( 'TWOCHECKOUT_STYLE_DEFAULT_MODE' );
        Configuration::deleteByName( 'TWOCHECKOUT_STYLE' );

        return $this->unregisterHook( 'displayHeader' )
               && $this->unregisterHook( 'paymentOptions' )
               && $this->unregisterHook( 'displayOrderConfirmation' )
               && $this->unregisterHook( 'actionProductCancel' )
               && $this->unregisterHook( 'displayBackOfficeHeader' )
               && $this->unregisterHook( 'displayAdminOrder' )
               && $this->unregisterHook( 'displayAdminOrderTop' )
               && parent::uninstall();
    }

    /**
     * show the settings page, also saves and validates the form on submit
     * @return string
     */
    public function getContent() {
        $output = null;

        if ( Tools::isSubmit( 'submit' . $this->name ) ) {
            $merchantId        = strval( Tools::getValue( 'TWOCHECKOUT_SID' ) );
            $buyLinkSecretWord = strval( Tools::getValue( 'TWOCHECKOUT_SECRET_WORD' ) );
            $secretKey         = strval( Tools::getValue( 'TWOCHECKOUT_SECRET_KEY' ) );
            $inline            = strval( Tools::getValue( 'TWOCHECKOUT_TYPE' ) );
            $demoMode          = strval( Tools::getValue( 'TWOCHECKOUT_DEMO' ) );
            $style             = strval( Tools::getValue( 'TWOCHECKOUT_STYLE' ) );
            $styleMode         = strval( Tools::getValue( 'TWOCHECKOUT_STYLE_DEFAULT_MODE' ) );

            if (
                ( ! $merchantId || empty( $merchantId ) || ! Validate::isGenericName( $merchantId ) )
                && ( ! $buyLinkSecretWord || empty( $buyLinkSecretWord ) || ! Validate::isGenericName( $buyLinkSecretWord ) )
                && ( ! $secretKey || empty( $secretKey ) || ! Validate::isGenericName( $secretKey ) )
                && ( ! $inline || empty( $inline ) || ! Validate::isGenericName( $inline ) )
                && ( ! $demoMode || empty( $demoMode ) || ! Validate::isGenericName( $demoMode ) )
            ) {
                $output .= $this->displayError( $this->l( 'Invalid Configuration value' ) );
            } else {
                Configuration::updateValue( 'TWOCHECKOUT_SID', $merchantId );
                Configuration::updateValue( 'TWOCHECKOUT_SECRET_WORD', $buyLinkSecretWord );
                Configuration::updateValue( 'TWOCHECKOUT_SECRET_KEY', $secretKey );
                Configuration::updateValue( 'TWOCHECKOUT_TYPE', $inline );
                Configuration::updateValue( 'TWOCHECKOUT_DEMO', $demoMode );
                Configuration::updateValue( 'TWOCHECKOUT_STYLE_DEFAULT_MODE', $styleMode );
                Configuration::updateValue( 'TWOCHECKOUT_STYLE', $style );
                $output .= $this->displayConfirmation( $this->l( 'Settings updated' ) );
            }
        }

        return $output . $this->displayForm();
    }

    /**
     * creates the form for the module settings (admin area)
     * @return string
     */
    private function displayForm() {
        // Get default language
        $defaultLang = (int) Configuration::get( 'PS_LANG_DEFAULT' );

        // Init Fields form array
        $fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $this->l( 'Settings' ),
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l( 'Seller ID(Merchant Code)' ),
                    'name'     => 'TWOCHECKOUT_SID',
                    'size'     => 200,
                    'required' => true,
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l( 'Buy Link Secret Word' ),
                    'name'     => 'TWOCHECKOUT_SECRET_WORD',
                    'size'     => 200,
                    'required' => true,
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l( 'Secret Key' ),
                    'name'     => 'TWOCHECKOUT_SECRET_KEY',
                    'size'     => 200,
                    'required' => true,
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l( 'IPN Url' ),
                    'name'     => 'TWOCHECKOUT_IPN_URL',
                    'size'     => 200,
                    'value'    => $this->context->link->getModuleLink( 'twocheckout', 'ipn' ),
                    'desc'     => $this->l( 'Copy this link to your 2checkout account under the IPN section' ),
                    'readonly' => true,
                ],
                [
                    'type'     => 'radio',
                    'label'    => $this->l( 'Cart type' ),
                    'name'     => 'TWOCHECKOUT_TYPE',
                    'class'    => 't',
                    'required' => true,
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'api',
                            'value' => 2,
                            'label' => $this->l( 'API' ),
                        ],
                        [
                            'id'    => 'yes',
                            'value' => 0,
                            'label' => $this->l( 'Convert Plus' ),
                        ],
                        [
                            'id'    => 'no',
                            'value' => 1,
                            'label' => $this->l( 'Inline' ),
                        ]
                    ],
                ],
                [
                    'type'     => 'radio',
                    'label'    => $this->l( 'Demo Mode' ),
                    'name'     => 'TWOCHECKOUT_DEMO',
                    'class'    => 't',
                    'required' => true,
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'yes',
                            'value' => 1,
                            'label' => $this->l( 'Yes' ),
                        ],
                        [
                            'id'    => 'no',
                            'value' => 0,
                            'label' => $this->l( 'No' ),
                        ]
                    ],
                ],
                [
                    'type'     => 'radio',
                    'label'    => $this->l( 'Use default style for API' ),
                    'name'     => 'TWOCHECKOUT_STYLE_DEFAULT_MODE',
                    'class'    => 't',
                    'required' => true,
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'YES',
                            'value' => 1,
                            'label' => $this->l( 'YES, use default' ),
                        ],
                        [
                            'id'    => 'NO',
                            'value' => 0,
                            'label' => $this->l( 'NO, use my custom style' ),
                        ]
                    ],
                ],
                [
                    'type'  => 'textarea',
                    'label' => $this->l( 'Custom style for API form' ),
                    'name'  => 'TWOCHECKOUT_STYLE',
                    'desc'  => $this->l( 'IMPORTANT! This is the styling object that styles your form.
                     Do not remove or add new classes. You can modify the existing ones. Use
                      double quotes for all keys and values! - VALID JSON FORMAT REQUIRED (validate 
                      json before save here: https://jsonlint.com/ ).' )
                ],
            ],
            'submit' => [
                'title' => $this->l( 'Update settings' ),
                'class' => 'btn btn-default pull-right',
            ],
        ];

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module          = $this;
        $helper->name_controller = $this->name;
        $helper->token           = Tools::getAdminTokenLite( 'AdminModules' );
        $helper->currentIndex    = AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        $helper->default_form_language    = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Title and toolbar
        $helper->title          = $this->displayName;
        $helper->show_toolbar   = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action  = 'submit' . $this->name;
        $helper->toolbar_btn    = [
            'save' => [
                'desc' => $this->l( 'Save' ),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                          '&token=' . Tools::getAdminTokenLite( 'AdminModules' ),
            ],
            'back' => [
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite( 'AdminModules' ),
                'desc' => $this->l( 'Back to list' ),
            ],
        ];

        // Load current value
        $helper->fields_value['TWOCHECKOUT_SID']                = Configuration::get( 'TWOCHECKOUT_SID' );
        $helper->fields_value['TWOCHECKOUT_SECRET_WORD']        = Configuration::get( 'TWOCHECKOUT_SECRET_WORD' );
        $helper->fields_value['TWOCHECKOUT_SECRET_KEY']         = Configuration::get( 'TWOCHECKOUT_SECRET_KEY' );
        $helper->fields_value['TWOCHECKOUT_TYPE']               = Configuration::get( 'TWOCHECKOUT_TYPE' );
        $helper->fields_value['TWOCHECKOUT_DEMO']               = Configuration::get( 'TWOCHECKOUT_DEMO' );
        $helper->fields_value['TWOCHECKOUT_IPN_URL']            = Configuration::get( 'TWOCHECKOUT_IPN_URL' );
        $helper->fields_value['TWOCHECKOUT_STYLE']              = Configuration::get( 'TWOCHECKOUT_STYLE' );
        $helper->fields_value['TWOCHECKOUT_STYLE_DEFAULT_MODE'] = Configuration::get( 'TWOCHECKOUT_STYLE_DEFAULT_MODE' );

        return $helper->generateForm( $fieldsForm );
    }

    /**
     * @param $params
     *
     * @return mixed
     */

    public function hookHeader( $params ) {
        $returnContent = '';

        $allValues = Tools::getAllValues();
        $resources = [];
        $link      = '';

        if ( Tools::getValue( 'controller' ) == "order" ) {
            if ( ! $this->checkActiveModule() ) {
                return;
            }
            $cart = isset( $params['cart'] ) ? $params['cart'] : $this->context->cart;

            if ( $this->tco_settings->getPaymentType() == 1 ) {
                //load scripts for INLINE
                $this->context->controller->registerJavascript( $this->name . '-gen-url-context',
                    'modules/' . $this->name . '/views/assets/js/inline_validate.js'
                );
                Media::addJsDef( array(
                    'reloadWhenInlineClose' => version_compare( _PS_VERSION_, '1.7.7', '>=' )
                ) );
                $resources[] = _PS_BASE_URL_ . __PS_BASE_URI__ . 'modules/' . $this->name . '/views/assets/js/inline_validate.js' . '?v=' . $this->version;

            } elseif ( $this->tco_settings->getPaymentType() == 2 ) {
                //No script needed to load for this.

            } else {
                //load convert plus scripts
                $this->context->controller->registerJavascript( $this->name . '-gen-url-context',
                    'modules/' . $this->name . '/views/assets/js/cp_validate.js'
                );
                $resources[] = _PS_BASE_URL_ . __PS_BASE_URI__ . 'modules/' . $this->name . '/views/assets/js/cp_validate.js' . '?v=' . $this->version;
            }

            if ( ! empty( $resources ) ) {
                $link = $this->context->link->getModuleLink( $this->name, 'generateUrl',
                    [ 'module_name' => $this->name, 'cart_id' => $cart->id ], true );
                Media::addJsDef( array(
                    'tco_verify_url' => $link
                ) );
                $this->context->smarty->assign( 'resources', $resources );
            }

            $returnContent .= $this->context->smarty->fetch( 'module:' . $this->name . '/views/templates/front/prefetch.tpl' );
        }

        return $returnContent;
    }

    public function hookDisplayOrderConfirmation( $params ) {
        if ( ( ! isset( $params['order'] ) || $params['order']->module != $this->name ) || ! $this->active ) {
            return false;
        }

        $order = $params['order'] ?? null;
        $cart  = $params['cart'] ?? null;
        $this->smarty->assign( [ 'order' => $order, 'cart' => $cart ] );

        return $this->fetch( 'module:twocheckout/views/templates/hook/payment_return.tpl' );
    }

    /**
     * @param $params
     *
     * @return PaymentOption[]|void|void[]
     */
    public function hookPaymentOptions( $params ) {
        if ( ! $this->active || ! $this->checkCurrency( $params['cart'] ) ) {
            return;
        }
        // we clear the cache for every change we make
        Tools::clearSmartyCache();
        Tools::clearXMLCache();
        Media::clearCache();
        Tools::generateIndex();

        if ( Configuration::get( 'TWOCHECKOUT_TYPE' ) == 2 ) { // api with 2payJs
            return [ $this->getApiPaymentOption( $params['cart'] ) ];
        } elseif ( Configuration::get( 'TWOCHECKOUT_TYPE' ) == 1 ) { // inline
            return [ $this->getInlinePaymentOption() ];
        } else { // Convert+
            return [ $this->getConvertPaymentOption() ];
        }
    }

    /**
     * @param $orderReturn
     */
    public function hookActionProductCancel( $params ) {
        $apiResponse = [];
        $order       = isset( $params['order'] ) ? $params['order'] : null;
        if ( Validate::isLoadedObject( $order ) && $order->module === $this->name ) {
            if ( $order->getCurrentOrderState()->id != Configuration::get( 'PS_OS_REFUND' ) ) {
                $orderPayment = OrderPayment::getByOrderReference( $order->reference );
                $Refno        = null;
                foreach ( $orderPayment as $payment ) {
                    $invoice = $payment->getOrderInvoice( $order->id );
                    if ( $invoice ) {
                        $Refno = trim( $payment->transaction_id );
                    }
                }

                //Prepare comment and get only the 150 accepted characters.
                $refundCommentStr = Tools::getValue( 'tco-refund-comment', '' );
                $refundComment    = trim( substr( $refundCommentStr, 0, 150 ) );
                $refundReason     = self::REFUND_REASON;

                if ( ! empty( $Refno ) ) {
                    $orderData = $this->tcoApi->call( "orders/" . $Refno . "/", [], 'GET' );
                    if ( $order->total_paid == $orderData["GrossPrice"] ) {
                        // Refund Details
                        $refundDetails = [
                            "amount"  => $order->total_paid,
                            "comment" => $refundComment,
                            "reason"  => $refundReason
                        ];
                    } else {
                        $lineItems = $orderData["Items"];
                        usort( $lineItems, "cmpPrices" );
                        $lineitemReference = $lineItems[0]["LineItemReference"];
                        if ( $lineItems[0]['Price']['GrossPrice'] >= $params['amount'] ) {
                            // Refund Item Details
                            $itemsArray[] = [
                                "Quantity"          => "1",
                                "LineItemReference" => $lineitemReference,
                                "Amount"            => $order->total_paid
                            ];

                            // Refund Details
                            $refundDetails = [
                                "amount"  => $order->total_paid,
                                "comment" => $refundComment,
                                "reason"  => $refundReason,
                                "items"   => $itemsArray
                            ];
                        } else {
                            return [
                                'status'  => 'error',
                                'rawdata' => 'Partial refund amount cannot exceed the highest priced item. Please login to your 2Checkout admin to issue the partial refund manually.',
                                'transid' => $Refno,
                            ];
                        }
                    }

                    if ( ! empty( $Refno ) ) {
                        $apiResponse = $this->tcoApi->call( 'orders/' . $Refno . '/refund/', $refundDetails, 'POST' );
                    }
                }

                if ( isset( $apiResponse['error_code'] ) ) {
                    error_log( sprintf(
                        'Error Refunding Invoice with error code: "%s"',
                        isset( $apiResponse['error_code'] ) ? $apiResponse['error_code'] : 'An unknown error occurred'
                    ), 0 );

                    $error_msg = $this->l( 'Error Refunding Invoice for order "' . $order->reference . '" with transaction id "'
                                           . $Refno . '". Message: ' . $apiResponse['message'] . ' ' );

                    $this->context->controller->errors['2co_refund_error'] = $error_msg;
                } else {
                    $order->setCurrentState( Configuration::get( 'PS_OS_REFUND' ) );
                }
            }
        }
    }

    public function hookDisplayAdminOrderTop( $params ) {
        $id_order = isset( $params['id_order'] ) ? $params['id_order'] : null;
        $order    = new Order( (int) $id_order );
        if ( Validate::isLoadedObject( $order ) && $order->module === $this->name ) {
            $errors = $this->getAdminOrderPage2CoRefundErrors( $params );

            if ( ! empty( $errors ) ) {
                $this->context->controller->errors[] = $errors;
            }

            $return = $this->disablePartialRefund();
            $return .= $this->getRefundCommentBox();
            $return .= $this->getTotalItemsForRefund( $order );

            return $return;
        }
    }

    public function hookDisplayAdminOrder( $params ) {
        if ( version_compare( _PS_VERSION_, '1.7.7', '>=' ) ) {
            return false;
        }
        $id_order = isset( $params['id_order'] ) ? $params['id_order'] : null;
        $order    = new Order( (int) $id_order );
        if ( Validate::isLoadedObject( $order ) && $order->module === $this->name ) {

            $errors = $this->getAdminOrderPage2CoRefundErrors( $params );

            if ( ! empty( $errors ) ) {
                $this->context->controller->errors[] = $errors;
            }
            $return = $this->disablePartialRefund();
            $return .= $this->getRefundCommentBox();
            $return .= $this->getTotalItemsForRefund( $order );

            return $return;
        }
    }

    private function getRefundCommentBox() {
        $this->context->smarty->assign(
            [
                'tco_refund_comment_box'    => $this->trans( 'Refund Comment...' ),
                'tco_refund_max_length_str' => $this->trans( '*Max 150 characters.' ),
                'tco_refund_max_length'     => 150
            ] );

        if ( version_compare( _PS_VERSION_, '1.7.7', '>=' ) ) {
            return $this->context->smarty->fetch( 'module:twocheckout/views/templates/hook/refundCommentBox17x.tpl' );

        }

        return $this->context->smarty->fetch( 'module:twocheckout/views/templates/hook/refundCommentBox.tpl' );
    }

    private function getTotalItemsForRefund( $order ) {
        $products    = $order->getProducts();
        $prods_array = [];
        foreach ( $products as $prod_id => $params ) {
            $prods_array[ $prod_id ] = $params['product_quantity'];
        }
        $notAllProductsAlert = $this->getTranslator()->trans( 'Please select all products!' );
        $hasPaidShipping     = $this->hasPaidShipping( $order->getShipping() );
        $shippingMsgAlert    = $this->getTranslator()->trans( 'Order does not have free shipping. Please include that in full refund!' );
        $this->context->smarty->assign(
            [
                'tco_refund_products_list' => json_encode( $prods_array ),
                'not_all_products_alert'   => $notAllProductsAlert,
                'has_paid_shipping'        => $hasPaidShipping,
                'shipping_msg_alert'       => $shippingMsgAlert
            ] );

        if ( version_compare( _PS_VERSION_, '1.7.7', '>=' ) ) {
            return $this->context->smarty->fetch( 'module:twocheckout/views/templates/hook/refundItemsCorrection17x.tpl' );
        }

        return $this->context->smarty->fetch( 'module:twocheckout/views/templates/hook/refundItemsCorrection.tpl' );
    }

    private function getAdminOrderPage2CoRefundErrors( $params ) {
        $twocheckout_msg = '';
        if ( isset( $this->context->controller->errors['2co_refund_error'] ) ) {
            $twocheckout_msg = $this->context->controller->errors['2co_refund_error'];
        }

        return $twocheckout_msg;
    }

    private function disablePartialRefund() {

        if ( version_compare( _PS_VERSION_, '1.7.7', '>=' ) ) {
            return $this->context->smarty->fetch( 'module:twocheckout/views/templates/hook/disablePartialRefund17x.tpl' );
        }

        return $this->context->smarty->fetch( 'module:twocheckout/views/templates/hook/disablePartialRefund.tpl' );
    }

    /**
     * @param $cart
     *
     * @return bool
     */
    public function checkCurrency( $cart ) {
        $currency_order    = new Currency( $cart->id_currency );
        $currencies_module = $this->getCurrency( $cart->id_currency );

        if ( is_array( $currencies_module ) ) {
            foreach ( $currencies_module as $currency_module ) {
                if ( $currency_order->id == $currency_module['id_currency'] ) {
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * @return PaymentOption|void
     */
    public function getInlinePaymentOption() {
        if ( ! $this->active ) {
            return;
        }
        $newOption = new PaymentOption();
        $newOption->setCallToActionText( 'Pay with 2Checkout' )
                  ->setAction( 'javascript:TwocheckoutInlineCheckout()' );

        return $newOption;
    }

    /**
     * @return PaymentOption|void
     */
    public function getConvertPaymentOption() {
        if ( ! $this->active ) {
            return;
        }
        $newOption = new PaymentOption();
        $newOption->setCallToActionText( 'Pay with 2Checkout' )
                  ->setAction( 'javascript:TwocheckoutCPCheckout()' );

        return $newOption;
    }

    /**
     * 2payJS->API payment method
     * @return PaymentOption
     * @throws SmartyException
     */
    public function getApiPaymentOption( $cart ) {
        if ( ! $this->active ) {
            return;
        }
        $newApiOption = new PaymentOption();
        $newApiOption->setCallToActionText($this->l('Pay with 2Checkout'))
                     ->setAction('javascript:Twocheckout2PayJs()')
                     ->setBinary(true)
                     ->setForm($this->generateApiForm($cart));

        return $newApiOption;
    }

    /**
     * genereates the form for the payment option (2payJs)
     * @return string
     * @throws SmartyException
     */
    protected function generateApiForm( $cart ) {

        // get style and remove newlines
        if ( Configuration::get( 'TWOCHECKOUT_STYLE_DEFAULT_MODE' ) ) {
            $style = trim( preg_replace( '/\s\s+/', ' ', $this->getDefaultStyle() ) );
        } else {
            $style = trim( preg_replace( '/\s\s+/', ' ', Configuration::get( 'TWOCHECKOUT_STYLE' ) ) );
        }
        $this->context->smarty->assign( [
            'action'   => $this->context->link->getModuleLink( $this->name, 'generateUrl',
                [ 'module_name' => $this->name, 'cart_id' => $cart->id ], true ),
            'sellerId' => Configuration::get( 'TWOCHECKOUT_SID' ),
            'style'    => $style,
            'script'   => Media::getMediaPath( _PS_MODULE_DIR_ . $this->name . '/views/assets/js/twocheckout.js' ),
            'css'      => Media::getMediaPath( _PS_MODULE_DIR_ . $this->name . '/views/assets/css/twocheckout.css' ),
            'spinner'  => Media::getMediaPath( _PS_MODULE_DIR_ . $this->name . '/views/assets/images/spinner.gif' ),
        ] );

        return $this->context->smarty->fetch( 'module:twocheckout/views/templates/front/payment_form.tpl' );
    }

    /**
     * generates the form for the payment option (convert+ & inline)
     * @return string
     * @throws SmartyException
     */
    protected function generateInlineForm() {
        $this->context->smarty->assign( [
            'action' => $this->context->link->getModuleLink( $this->name, 'validation', [], true ),
        ] );

        return $this->context->smarty->fetch( 'module:twocheckout/views/templates/hook/inline_payment_options.tpl' );
    }

    /**
     * generates the form for the payment option (convert+ & inline)
     * @return string
     * @throws SmartyException
     */
    protected function generateForm() {
        $this->context->smarty->assign( [
            'action' => $this->context->link->getModuleLink( $this->name, 'validation', [], true ),
        ] );

        return $this->context->smarty->fetch( 'module:twocheckout/views/templates/hook/payment_options.tpl' );
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


    /**
     * @param        $delivery
     * @param string $stateCode
     * @param string $countryIsoCode
     * @param string $email
     *
     * @return array
     */
    public function getBillingDetails( $delivery, string $stateCode, string $countryIsoCode, string $email = '' ) {

        $address = [
            'Address1'    => $delivery->address1,
            'City'        => $delivery->city,
            'State'       => $stateCode,
            'CountryCode' => $countryIsoCode,
            'Email'       => $email,
            'FirstName'   => $delivery->firstname,
            'LastName'    => $delivery->lastname,
            'Phone'       => $delivery->phone,
            'Zip'         => $delivery->postcode,
            'Company'     => $delivery->company,
            'FiscalCode'  => $delivery->vat_number
        ];

        if ( $delivery->address2 ) {
            $address['Address2'] = $delivery->address2;
        }

        return $address;
    }

    /**
     * for safety reasons we only send one Item with the grand total and the Cart_id as ProductName (identifier)
     * sending products order as ONE we dont have to calculate the total fee of the order (product price, tax, discounts etc)
     *
     * @param int $cart_id
     * @param float $total
     *
     * @return array
     */
    public function getItem( int $cart_id, float $total ) {
        $items[] = [
            'Code'             => null,
            'Quantity'         => 1,
            'Name'             => 'Cart_' . $cart_id,
            'Description'      => 'N/A',
            'RecurringOptions' => null,
            'IsDynamic'        => true,
            'Tangible'         => false,
            'PurchaseType'     => 'PRODUCT',
            'Price'            => [
                'Amount' => number_format( $total, 2, '.', '' ),
                'Type'   => 'CUSTOM'
            ]
        ];

        return $items;
    }

    /**
     * @param string $token
     * @param string $currency
     * @param int $cartId
     * @param int $orderId
     *
     * @return array
     */
    public function getPaymentDetails( string $token, string $currency, int $cartId ) {
        return [
            'Type'          => Configuration::get( 'TWOCHECKOUT_DEMO' ) == 1 ? 'TEST' : 'EES_TOKEN_PAYMENT',
            'Currency'      => strtolower( $currency ),
            'CustomerIP'    => $this->getCustomerIp(),
            'PaymentMethod' => [
                'EesToken'           => $token,
                'Vendor3DSReturnURL' => $this->context->link->getModuleLink( 'twocheckout', 'redirect3ds',
                    [ 'action' => 'success', 'cart' => $cartId ], true ),
                'Vendor3DSCancelURL' => $this->context->link->getModuleLink( 'twocheckout', 'redirect3ds',
                    [ 'action' => 'cancel', 'cart' => $cartId ], true )
            ]
        ];
    }

    /**
     * @param $has3ds
     *
     * @return string|null
     */
    public function hasAuthorize3DS( $has3ds ) {
        if ( isset( $has3ds ) && isset( $has3ds['Href'] ) && ! empty( $has3ds['Href'] ) ) {

            return $has3ds['Href'] . '?avng8apitoken=' . $has3ds['Params']['avng8apitoken'];
        }

        return null;
    }

    /**
     * get customer ip or returns a default ip
     * @return mixed|string
     */
    public function getCustomerIp() {
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            //ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            //ip pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) === false ) {
            return $ip;
        }

        return '1.0.0.1';
    }

    public function checkActiveModule() {
        $active  = false;
        $modules = Hook::getHookModuleExecList( 'paymentOptions' );
        if ( empty( $modules ) ) {
            return;
        }
        foreach ( $modules as $module ) {
            if ( $module['module'] == $this->name ) {
                $active = true;
            }
        }

        return $active;
    }

    public function hasPaidShipping( $shippingArr ) {
        $total = 0;
        foreach ( $shippingArr as $shipping => $valuesArr ) {
            $total += $valuesArr['shipping_cost_tax_incl'];
        }

        return $total > 0;
    }
}
