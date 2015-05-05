<?php
class KyashValidationModuleFrontController extends ModuleFrontController
{
	public function initContent()
	{
		$this->display_column_right = false;
		parent::initContent();
	}
	
	public function postProcess()
	{
		$errors = array();
		$cart = $this->context->cart;
		if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
			Tools::redirect('index.php?controller=order&step=1');

		$authorized = false;
		foreach (Module::getPaymentModules() as $module)
			if ($module['name'] == 'kyash')
			{
				$authorized = true;
				break;
			}
		if (!$authorized)
			die($this->module->l('This payment method is not available.', 'validation'));

		$customer = new Customer($cart->id_customer);
		if (!Validate::isLoadedObject($customer))
			Tools::redirect('index.php?controller=order&step=1');
		
		$api = $this->module->getKyashApiInstance(Configuration::get('kyash_public_api_id'),Configuration::get('kyash_api_secret'), Configuration::get('kyash_callback_secret'), Configuration::get('kyash_hmac_secret'));
		$api->setLogger($this->module);
		$params = $this->getOrderParams($cart);
		$response = $api->createKyashCode($params);
		if(isset($response['status']) && $response['status'] == 'error')
		{
			$errors[] = $response['message'];
			$this->context->smarty->assign('errors2',$errors);
			$this->setTemplate('payment_execution.tpl');
		}
		else
		{
			$currency = $this->context->currency;
			$total = (float)$cart->getOrderTotal(true, Cart::BOTH);
			$paymentName = $this->module->displayName.', Kyash Code: '.$response['id'];
			$this->module->validateOrder($cart->id, Configuration::get('PS_OS_PREPARATION'), $total, $paymentName, NULL, NULL, (int)$currency->id, false, $customer->secure_key);
			$this->module->addKyashOrder($cart->id,$this->module->currentOrder,$response['id'],$response['expires_on']);
			Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
		}
	}
	
	public function getOrderParams($cart)
	{
		$total = (float)$cart->getOrderTotal(true, Cart::BOTH);
		$invoice_address = new Address($cart->id_address_invoice);
		$delivery_address = new Address($cart->id_address_delivery);
		$customer = new Customer($cart->id_customer);
		
		$address1 = $invoice_address->address1;
		if ($invoice_address->address2) {
			$address1 .= ','.$invoice_address->address2;
		}
		
		$address2 = $delivery_address->address1;
		if ($delivery_address->address2) {
			$address2 .= ','.$delivery_address->address2;
		}
		
		$billing_phone = $invoice_address->phone;
		if(empty($billing_phone))
		{
			$billing_phone = $invoice_address->phone_mobile; 
		}
		
		$shipping_phone = $delivery_address->phone;
		if(empty($shipping_phone))
		{
			$shipping_phone = $delivery_address->phone_mobile; 
		}
		
		$billing_state = new State($invoice_address->id_state);
		$delivery_state = new State($delivery_address->id_state);
		
        $params = array (
			'order_id' => 'P'.$cart->id,
			'amount' => $total,
			'billing_contact.first_name' => $invoice_address->firstname,
			'billing_contact.last_name' => $invoice_address->lastname,
			'billing_contact.email' => $customer->email,
			'billing_contact.address' => $address1,
			'billing_contact.city' => $invoice_address->city,
			'billing_contact.state' => $billing_state->name,
			'billing_contact.pincode' => $invoice_address->postcode,
            'billing_contact.phone' => $billing_phone,
            'shipping_contact.first_name' => $delivery_address->firstname,
			'shipping_contact.last_name' => $delivery_address->lastname,
			'shipping_contact.address' => $address2,
			'shipping_contact.city' => $delivery_address->city,
			'shipping_contact.state' => $delivery_state->name,
            'shipping_contact.pincode' => $delivery_address->postcode,
            'shipping_contact.phone' => $shipping_phone
        );
		
		return http_build_query($params);
    }
}
