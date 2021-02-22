<?php

abstract class TwocheckoutAbstarctModuleFrontController  extends ModuleFrontController
{
    /** @var string module name */
    public $name = 'twocheckout';

    /** @var  array Contain ajax response. */
    public $jsonValues;

    /** @var  array  POST and GET values defined in init function */
    public $request;

    /** @var  string Contain redirect URL.. */
    public $redirectUrl;

    /** @var Array containing errors*/
    public $errors;

    /**
     * @see ModuleFrontController::run
     */
    public function run() {
        $this->init();
        if ( $this->checkAccess() ) {
            $this->postProcess();
        }

        if (!empty($this->redirectUrl)) {
            $this->redirectWithNotifications($this->redirectUrl);
        }
    }

    public function init() {
        parent::init();
    }
}
