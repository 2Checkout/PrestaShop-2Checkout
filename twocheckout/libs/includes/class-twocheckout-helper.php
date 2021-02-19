<?php

class Twocheckout_Helper
{

	private $mobile_detect;

	public function __construct() {
		$this->mobile_detect = new MobileDetect();
	}

	public function getDeviceType()
	{
		$device_type = TWOCO_ENV_TRANSACTION_MODE_COMPUTER_DEVICE;
		if($this->mobile_detect->isMobile()) {
			$device_type = TWOCO_ENV_TRANSACTION_MODE_MOBILE_DEVICE;
		} elseif($this->mobile_detect->isTablet()) {
			$device_type = TWOCO_ENV_TRANSACTION_MODE_TABLET_DEVICE;
		}

		return $device_type;
	}

	/**
	 * Used strictly for unit tests
	 * 
	 * @param $class
	 */
	public function setMobileDetectClass($class)
	{
		$this->mobile_detect = $class;
	}
}
