<?php
if (!defined('_PS_VERSION_'))
	exit;

include_once _PS_MODULE_DIR_.'kyash/lib/KyashPay.php';
class Kyash extends PaymentModule
{
	private $_html = '';
	private $_postErrors = array();

	/*
	* Module constructor
	*/
	public function __construct()
	{
		$this->name = 'kyash';
		$this->tab = 'payments_gateways';
		$this->version = '0.1.0';
		$this->author = 'Kyash';
		$this->controllers = array('validation');

		$this->bootstrap = true;
		parent::__construct();	

		$this->displayName = $this->l('Kyash - Pay at a nearby Shop');
		$this->description = $this->l('Accept payments for your products via Kyash Payments');
	}

	/*
	* Module Installer
	*/
	public function install()
	{
		if (!parent::install() || !$this->registerHook('payment') || !$this->registerHook('paymentReturn') || !$this->registerHook('actionOrderStatusPostUpdate')  || !$this->registerHook('displayInvoice') )
			return false;
		$instructions = '<p>Please pay at any of the authorized outlets before expiry. </p><p>You need to mention only the KyashCode and may be asked for your mobile number during payment. No other details needed. </p><p>Please wait for the confirmation SMS after payment. Remember to take a payment receipt.</p> <p>You can verify the payment status anytime by texting this KyashCode to +91 9243710000</p>';
		Configuration::updateValue('kyash_instructions',$instructions,true);
		$this->createTable();
		return true;
	}

	/*
	* Module Uninstaller
	*/
	public function uninstall()
	{
		if (!parent::uninstall())
			return false;
		$this->deleteTable();
		return true;
	}
	
	/*
	* This will create transaction table on installation
	*/
	public function createTable()
	{
		$sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'kyash_order`(
			`id_cart` int(10) unsigned NOT NULL,
			`id_order` int(10) unsigned NOT NULL,
			`kyash_code` varchar(200),
			`kyash_status` varchar(100)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

		return Db::getInstance()->execute($sql);
	}
	
	/*
	* This will delete transaction table on uninstallation
	*/
	public function deleteTable()
	{
		$sql = 'DROP TABLE `'._DB_PREFIX_.'kyash_order`';
		return Db::getInstance()->execute($sql);
	}	
	
	/*
	* This function validates admin configuration save details
	*/
	private function _postValidation()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			if (!Tools::getValue('public_api_id'))
				$this->_postErrors[] = $this->l('Public API ID is required.');
			if (!Configuration::get('kyash_api_secret') && !Tools::getValue('api_secret'))
				$this->_postErrors[] = $this->l('API secret is required.');
			if (!Configuration::get('kyash_callback_secret') && !Tools::getValue('callback_secret'))
				$this->_postErrors[] = $this->l('Callback secret is required.');
			if (!Configuration::get('kyash_hmac_secret') && !Tools::getValue('hmac_secret'))
				$this->_postErrors[] = $this->l('HMAC secret is required.');
		}
	}

	/*
	* This function processes admin configuration save details
	*/
	private function _postProcess()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			Configuration::updateValue('kyash_public_api_id', Tools::getValue('public_api_id'));
			if(Tools::getValue('api_secret'))
				Configuration::updateValue('kyash_api_secret', Tools::getValue('api_secret'));
			if(Tools::getValue('callback_secret'))
				Configuration::updateValue('kyash_callback_secret', Tools::getValue('callback_secret'));
			if(Tools::getValue('hmac_secret'))
				Configuration::updateValue('kyash_hmac_secret', Tools::getValue('hmac_secret'));
			Configuration::updateValue('kyash_instructions', Tools::getValue('instructions'),true);
		}
		$this->_html .= $this->displayConfirmation($this->l('Settings updated'));
	}

	/*
	* This prestashop function will render the module admin configuration
	*/
	public function getContent()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			$this->_postValidation();
			if (!count($this->_postErrors))
				$this->_postProcess();
			else
				foreach ($this->_postErrors as $err)
					$this->_html .= $this->displayError($err);
		}
		else
			$this->_html .= '<br />';
		
		$this->_html .= $this->renderForm();

		return $this->_html;
	}

	/*
	* This will display the Kyash as one of the payment option in checkout page.
	*/
	public function hookPayment($params)
	{
		if (!$this->active)
			return;

		$address = new Address($this->context->cart->id_address_invoice);
		$postcode = $address->postcode;
		if(empty($postcode))
		{
			$postcode = 'Enter Pincode';
		}
		$this->smarty->assign(array(
			'this_path' => $this->_path,
			'img_path' => _PS_IMG_,
			'postcode' => $postcode
		));
		return $this->display(__FILE__, 'payment.tpl');
	}

	/*
	* This will display on the order confirmation page
	*/
	public function hookPaymentReturn($params)
	{
		if (!$this->active)
			return;

		$state = $params['objOrder']->getCurrentState();
		if ($state == Configuration::get('PS_OS_PREPARATION') || $state == Configuration::get('PS_OS_OUTOFSTOCK'))
		{
			$this->smarty->assign(array(
				'total_to_pay' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
				'status' => 'ok',
				'id_order' => $params['objOrder']->id
			));
			if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference))
				$this->smarty->assign('reference', $params['objOrder']->reference);
			
			$kyash_code = $this->getKyashOrder($params['objOrder']->id,'kyash_code');
			$address = new Address($params['objOrder']->id_address_invoice);
			$postcode = $address->postcode;
			if(empty($postcode))
			{
				$postcode = 'Enter Pincode';
			}
			
			$this->smarty->assign(array(
				'this_path' => $this->_path,
				'img_path' => _PS_IMG_,
				'postcode' => $postcode,
				'kyash_code' => $kyash_code,
				'instructions' => Configuration::get('kyash_instructions')
			));
			
		}
		else
			$this->smarty->assign('status', 'failed');
		return $this->display(__FILE__, 'payment_return.tpl');
	}

	public function hookActionOrderStatusPostUpdate($params)
	{
		if (!$this->active)
			return;
		
		$id_order = $params['id_order'];
		$order_state = $params['newOrderStatus'];
		
		if($id_order > 0)
		{
			$kyash_code = $this->getKyashOrder($id_order,'kyash_code');
			$kyash_status = $this->getKyashOrder($id_order,'kyash_status');
			$id_cart = $this->getKyashOrder($id_order,'id_cart');
			$api = $this->getKyashApiInstance(Configuration::get('kyash_public_api_id'),Configuration::get('kyash_api_secret'));
			$api->setLogger($this);
			
			if (!empty($kyash_code)) 
			{
				if($order_state->id == Configuration::get('PS_OS_CANCELED'))
				{
					if($kyash_status == 'pending' || $kyash_status == 'paid')
					{
						$response = $api->cancel($kyash_code);
						if(isset($response['status']) && $response['status'] == 'error')
						{
							Configuration::updateValue('kyash_order_view_error',$response['message']);
						}
						else
						{
							$this->updateKyashOrder($id_order,'kyash_status','cancelled');
							$message = 'Kyash payment collection has been cancelled for this order.';
							Configuration::updateValue('kyash_order_view_success',$message);
						}
					}
					else if($kyash_status == 'captured')
					{
						$message = 'Customer payment has already been transferred to you. Refunds if any, are to be handled by you.';
						Configuration::updateValue('kyash_order_view_success',$message);
					}
				}
				else if($order_state->id == Configuration::get('PS_OS_SHIPPING'))
				{
					if($kyash_status == 'pending')
					{
						$response = $api->cancel($kyash_code);
						if(isset($response['status']) && $response['status'] == 'error')
						{
							Configuration::updateValue('kyash_order_view_error',$response['message']);
						}
						else
						{
							$this->updateKyashOrder($id_order,'kyash_status','cancelled');
							$message = 'You have shipped before Kyash payment was done. Kyash payment collection has been cancelled for this order.';
							Configuration::updateValue('kyash_order_view_success',$message);
						}
					}
					else if($kyash_status == 'paid')
					{
						$response = $api->capture($kyash_code);
						if(isset($response['status']) && $response['status'] == 'error')
						{
							Configuration::updateValue('kyash_order_view_error',$response['message']);
						}
						else
						{
							$this->updateKyashOrder($id_order,'kyash_status','captured');
							$message = 'Kyash payment has been successfully captured.';
							Configuration::updateValue('kyash_order_view_success',$message);
						}
					}
				}
			}
		}
	}
	
	public function hookDisplayInvoice($params)
	{
		if (!$this->active)
			return;
		
		$error = Configuration::get('kyash_order_view_error');
		$success = Configuration::get('kyash_order_view_success');

		if(!empty($error))
		{
			echo $this->displayError($error);
			Configuration::updateValue('kyash_order_view_error','');
		}
		
		if(!empty($success))
		{
			echo $this->displayConfirmation($success);
			Configuration::updateValue('kyash_order_view_success','');
		}
	}

	/*
	* This will render admin configuration form
	*/
	public function renderForm()
	{
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Kyash Settings'),
					'icon' => 'icon-cogs'
				),
				'input' => array(
					array(
						'type' => 'text',
						'label' => $this->l('Public API ID'),
						'desc' => $this->l('This is a unique public identifier of the Merchant sent with all API requests. This ID can be made public and only one ID is generated per Merchant.'),
						'name' => 'public_api_id',
					),
					array(
						'type' => 'password',
						'label' => $this->l('API Secret'),
						'desc' => $this->l('You authenticate to Kyash server by using one of these API Secrets in the request. This detail should be treated as a secret and never to be shared. You can manage them from your account and have multiple API Secrets active at one time.'),
						'name' => 'api_secret',
					),
					array(
						'type' => 'password',
						'label' => $this->l('Callback Secret'),
						'desc' => $this->l('Used by the Kyash Server to authenticate itself during API callbacks over HTTPS. This detail should be treated as a secret and never to be shared. Only one Callback Secret is generated per Merchant. It can be changed from your account.'),
						'name' => 'callback_secret',
					),
					array(
						'type' => 'password',
						'label' => $this->l('HMAC Secret'),
						'desc' => $this->l('Used by the Kyash Server to authenticate itself during API callbacks over HTTP. This detail should be treated as a secret and never to be shared. Only one HMAC Secret is generated per Merchant. It can be changed from your account.'),
						'name' => 'hmac_secret',
					),
					array(
						'type' => 'text',
						'label' => $this->l('Callback URL'),
						'desc' => $this->l('When customer makes a payment at one of our Payment Points, Kyash will notify the Merchant Server using a HTTP request, in real time. The Merchant needs to implement a callback handler which will authenticate the information sent from Kyash and use the information as needed. The callback URL can be configured in your Kyash Account Settings at My Account -> Merchant. The request sent from Kyash will use POST request method.'),
						'name' => 'callback_url',
						'readonly' => 'readonly'
					),
					array(
						'type' => 'textarea',
						'label' => $this->l('Instructions'),
						'desc' => $this->l('Instructions on how to make the payment displayed on thank you page'),
						'name' => 'instructions'
					),
				),
				'submit' => array(
					'title' => $this->l('Save'),
				)
			),
		);
		
		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table =  $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();
		$helper->id = (int)Tools::getValue('id_carrier');
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'btnSubmit';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}
	
	/*
	* This will retrieve  module configuration values
	*/
	public function getConfigFieldsValues()
	{
		return array(
			'public_api_id' => Tools::getValue('public_api_id', Configuration::get('kyash_public_api_id')),
			'api_secret' => Tools::getValue('api_secret', Configuration::get('kyash_api_secret')),
			'call_secret' => Tools::getValue('callback_secret', Configuration::get('kyash_callback_secret')),
			'hmac_secret' => Tools::getValue('hmac_secret', Configuration::get('kyash_hmac_secret')),
			'instructions' => Tools::getValue('instructions', Configuration::get('kyash_instructions')),
			'callback_url' => $this->context->link->getModuleLink('kyash', 'handler'),
		);
	}
	
	public function log($content, $date = true)
	{
		$filename = _PS_MODULE_DIR_.'kyash/kyash.log';
		$fp = fopen($filename, 'a+');
		if($date)
		{
			fwrite($fp, date("Y-m-d H:i:s").": ");
		}
		fwrite($fp, print_r($content, TRUE));
		fwrite($fp, "\n");
		fclose($fp);
	}
	
	public function write($content)
	{
		$this->log($content);
	}
	
	public function updateOrder($id_order,$column,$value)
	{
		$sql = "UPDATE `"._DB_PREFIX_."orders` SET $column='$value' WHERE id_order=".(int)($id_order);
		return Db::getInstance()->execute($sql);
	}
	
	public function addKyashOrder($id_cart,$id_order,$code)
	{
		$sql = 'INSERT INTO `'._DB_PREFIX_.'kyash_order` (`id_cart`, `id_order`, `kyash_code`, `kyash_status`) VALUES('.(int)($id_cart).','.(int)($id_order).', "'.pSQL($code).'","pending")';
		return Db::getInstance()->execute($sql);
	}
	
	public function updateKyashOrder($id_order,$column,$value)
	{
		$sql = "UPDATE `"._DB_PREFIX_."kyash_order` SET $column='$value' WHERE id_order=".(int)($id_order);
		return Db::getInstance()->execute($sql);
	}
	
	public function getKyashOrder($id_order,$column)
	{
		$sql = "SELECT $column FROM `"._DB_PREFIX_."kyash_order` WHERE id_order=".(int)($id_order);
		return Db::getInstance()->getValue($sql);
	}
	
	public function getKyashOrderByCart($id_cart,$column)
	{
		$sql = "SELECT $column FROM `"._DB_PREFIX_."kyash_order` WHERE id_cart=".(int)($id_cart);
		$value = Db::getInstance()->getValue($sql);
if($column == 'id_order' && empty($value))
{
$value = Db::getInstance()->getValue('SELECT id_order FROM '._DB_PREFIX_.'orders where id_cart='.$id_cart);
}
return $value;
	}
	
	public function updateKyashOrderByCart($id_cart,$column,$value)
	{
		$sql = "UPDATE `"._DB_PREFIX_."kyash_order` SET $column='$value' WHERE id_cart=".(int)($id_cart);
		return Db::getInstance()->execute($sql);
	}
	
	public function getKyashApiInstance($key,$secret,$hmac=false)
	{
		return new KyashPay($key,$secret,$hmac);
	}
	
	public function getProtocol()
	{
		$protocol = 'http';
		if((isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&  $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) 
		{
  			$protocol = 'https';
		}
		return $protocol;
	}
}
