<?php
define( 'TWOCO_ENV_CHECKOUT_EVENT_TYPE_TRANSACTION_SUCCESS', 'COMPLETE' );
define( 'TWOCO_ENV_CHECKOUT_EVENT_TYPE_TRANSACTION_AUTHORIZED', 'AUTHRECEIVED' );
define( 'TWOCO_ENV_CHECKOUT_EVENT_TYPE_TRANSACTION_FAILED', 'TRANSACTION_FAILED' );
define( 'TWOCO_ENV_CHECKOUT_EVENT_TYPE_TRANSACTION_DECLINED', 'TRANSACTION_DECLINED' );
define( 'TWOCO_ENV_TRANSACTION_MODE_MOBILE_DEVICE', 'P' );
define( 'TWOCO_ENV_TRANSACTION_MODE_COMPUTER_DEVICE', 'S' );
define( 'TWOCO_ENV_TRANSACTION_MODE_TABLET_DEVICE', 'T' );

/**
 * Class Twocheckout_Settings
 */
class Twocheckout_Settings {

    public $twocheckout_sid;
    public $twocheckout_secret_key;
    public $twocheckout_demo;
    public $twocheckout_type;
    public $twocheckout_ipn_url;
    public $twocheckout_secret_word;
    public $twocheckout_style_default_mode;
    public $twocheckout_style;

	public function __construct() {
	    $this->setSid(Configuration::get('TWOCHECKOUT_SID'));
	    $this->setSecretKey(Configuration::get('TWOCHECKOUT_SECRET_KEY'));
	    $this->setDemo(Configuration::get('TWOCHECKOUT_DEMO'));
	    $this->setPaymentType(Configuration::get('TWOCHECKOUT_TYPE'));
	    $this->setIpnUrl(Configuration::get('TWOCHECKOUT_IPN_URL'));
	    $this->setSecretWord(Configuration::get('TWOCHECKOUT_SECRET_WORD'));
	    $this->setStyleDefaultMode(Configuration::get('TWOCHECKOUT_STYLE_DEFAULT_MODE'));
	    $this->setStyle(Configuration::get('TWOCHECKOUT_STYLE'));
    }

    public function getSid() {
		return (string)$this->twocheckout_sid;
	}

    public function getSecretKey() {
        return (string)$this->twocheckout_secret_key;
    }

    public function getDemo() {
        return $this->twocheckout_demo;
    }

    public function getPaymentType() {
        return (int)$this->twocheckout_type;
    }

    public function getIpnUrl() {
        return (string)$this->twocheckout_ipn_url;
    }

    public function getSecretWord() {
        return (string)$this->twocheckout_secret_word;
    }

    public function getStyleDefaultMode() {
        return (string)$this->twocheckout_style_default_mode;
    }

    public function getStyle() {
        return (string)$this->twocheckout_style;
    }

    /**
     * SETTERS
     * */

    public function setSid($sid) {
        $this->twocheckout_sid = $sid;
    }

    public function setSecretKey($secretKey) {
        $this->twocheckout_secret_key = $secretKey;
    }

    public function setDemo($demo) {
        $this->twocheckout_demo = $demo;
    }

    public function setPaymentType($type) {
        $this->twocheckout_type = (int) $type;
    }

    public function setIpnUrl($IpnUrl) {
        $this->twocheckout_ipn_url = $IpnUrl;
    }

    public function setSecretWord($secretWord) {
        $this->twocheckout_secret_word = $secretWord;
    }

    public function setStyleDefaultMode($style_default_mode) {
        $this->twocheckout_style_default_mode = $style_default_mode;
    }

    public function setStyle($style) {
        $this->twocheckout_style = $style;
    }
}
