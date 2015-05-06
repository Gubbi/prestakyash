<?php
class KyashHandlerModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $api = $this->module->getKyashApiInstance(Configuration::get('kyash_public_api_id'),Configuration::get('kyash_api_secret'), Configuration::get('kyash_callback_secret'), Configuration::get('kyash_hmac_secret'));
        $api->setLogger($this->module);

        $p_order_id = substr($_REQUEST['order_id'],1);
        $order_id = $this->module->getKyashOrderByCart($p_order_id,'id_order');
        $order = new Order( $order_id );

        if(!$order)
        {
            $this->module->log("HTTP/1.1 500 Order is not found");
            header("HTTP/1.1 500 Order is not found");
            exit;
        }
        else
        {
            $url = $this->context->link->getModuleLink('kyash', 'handler');
            $updater = new KyashUpdater($order,$this->module,$order_id);
            $api->callback_handler($updater,$this->module->getKyashOrder($order_id,'kyash_code'),$this->module->getKyashOrder($order_id,'kyash_status'),$url);
        }
    }
}

class KyashUpdater
{
    public $order = NULL;
    public $kyash = NULL;
    public $order_id = NULL;

    public function __construct($order,$kyash,$order_id)
    {
        $this->order = $order;
        $this->kyash = $kyash;
        $this->order_id = $order_id;
    }

    public function update($status,$comment)
    {
        if($status == 'paid')
        {
            $this->order->setCurrentState(Configuration::get('PS_OS_PAYMENT'),1);
            $this->kyash->updateKyashOrder($this->order_id,'kyash_status','paid');
        }
        else if($status == 'expired')
        {
            $this->order->setCurrentState(Configuration::get('PS_OS_CANCELED'),1);
            $this->kyash->updateKyashOrder($this->order_id,'kyash_status','expired');
        }
    }
}
