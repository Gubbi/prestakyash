<?php
class KyashPaypointsModuleFrontController extends ModuleFrontController
{
	public function postProcess()
	{
		$this->display_column_right = false;
		$this->display_column_left = false;
		$this->display_header = false;
		$this->display_footer = false;
		
		$pincode = Tools::getValue('postcode');
		$success = Tools::getValue('success');
		
		$api = $this->module->getKyashApiInstance(Configuration::get('kyash_public_api_id'),Configuration::get('kyash_api_secret'), Configuration::get('kyash_callback_secret'), Configuration::get('kyash_hmac_secret'));
		$api->setLogger($this->module);
		$response = $api->getPaymentPoints($pincode);
		if(isset($response['status']) && $response['status'] == 'error')
		{
			$this->context->smarty->assign('error',$response['message']);
			$this->setTemplate('error.tpl');
			$this->display();
		}
		else
		{
			$this->context->smarty->assign('payments',$response);
			if($success)
			{
				$this->setTemplate('payment_points_success.tpl');
			}
			else
			{
				$this->setTemplate('payment_points.tpl');
			}
			$this->display();
		}
		exit;
	}
}