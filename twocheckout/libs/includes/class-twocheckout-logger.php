<?php

/**
 * Class Twocheckout_Logger
 */
class Twocheckout_Logger {

    private $file;

	/**
	 * Twocheckout_Logger constructor.
	 */
	public function __construct($file) {
        $this->file = $file;
	}

	/**
	 * Logging method
	 * Info warning level
	 * @param string $message
	 */
	public function log( $message, $line = __LINE__) {
        PrestaShopLogger::addLog( sprintf( $message.' at line %s in file %s',
            $line,
             $this->file) );
	}

    /**
     * Logging method
     * Error warning level
     * @param string $message
     */
    public function error( $message, $line = __LINE__) {
        PrestaShopLogger::addLog( sprintf( $message.' at line %s in file %s',
            $line,
            $this->file), 3 );
    }
}
