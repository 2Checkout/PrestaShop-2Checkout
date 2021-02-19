<?php
/**
 * Manage errors.
 */
class TwocheckoutErrorModuleFrontController extends ModuleFrontController
{
    /**
     * @see ModuleFrontController::init()
     */
    public function init()
    {
        parent::init();
        $this->values['error_msg'] = Tools::getvalue('err');
        $this->values['warn_msg'] = Tools::getvalue('warn');
        //if not set it will always retry
        $this->values['no_retry'] = Tools::getvalue('no_retry');
    }
    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        Context::getContext()->smarty->assign(array(
            'error_msg' => $this->values['error_msg'],
            'warn_msg' => $this->values['warn_msg'],
            'show_retry' => (Context::getContext()->cart->nbProducts() > 0 && !$this->values['no_retry']) ? true : false,
        ));

        $this->setTemplate('module:twocheckout/views/templates/front/payment_error.tpl');
    }
}
