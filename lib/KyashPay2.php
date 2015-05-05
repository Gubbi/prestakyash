<?php 
class KyashPay
{
	private static $baseUri = 'https://api.kyash.in/v1';
	public $key = '';
	public $secret = '';
	public $hmac = false;
	public $logger = NULL;
	
	public function __construct($key,$secret,$hmac=false)
    {
		$this->key = $key;
		$this->secret = $secret;
		$this->hmac = $hmac;
	}
	
	public function setLogger($object)
    {
		$this->logger = $object;
	}
	
	public function getPaymentPoints($pincode)
	{
		$url = self::$baseUri.'/paymentpoints/'.$pincode;
		
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_USERPWD, $this->key.':'.$this->secret);
		$response = curl_exec($curl);
        if (curl_error($curl)) 
		{
            $error = "Paymentpoints Connection Error: ".curl_error($curl);
            $response = array("status" => "error", "message" => $error);
        }
		else
		{
			$response = $this->modifiedJsonDecode($response, true);
		}
        curl_close($curl);
		$this->logger->write('Request2: '.$url);
		if($this->logger && is_object($this->logger) && method_exists($this->logger,'write'))
		{
			$this->logger->write('Request: '.$url);
			$this->logger->write('Response: '.json_encode($response));
		}
        return $response;
    }
	
	public function createKyashCode($data)
	{
		$url = self::$baseUri.'/kyashcodes/';
		
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_USERPWD, $this->key.':'.$this->secret);
		curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		$response = curl_exec($curl);
        if (curl_error($curl)) 
		{
            $error = "CreateKyashCode Connection Error: ".curl_error($curl);
            $response = array("status" => "error", "message" => $error);
        }
		else
		{
			$response = $this->modifiedJsonDecode($response, true);
		}
        curl_close($curl);
		
		if($this->logger && is_object($this->logger) && method_exists($this->logger,'write'))
		{
			$this->logger->write('Request: '.$url.' => '.$data);
			$this->logger->write('Response: '.json_encode($response));
		}
        return $response;
    }
	
	public function cancel($kyash_code)
	{
		$url = self::$baseUri.'/kyashcodes/'.$kyash_code.'/cancel';
		$params = "reason=requested_by_customer";
		
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_USERPWD, $this->key.':'.$this->secret);
		curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		$response = curl_exec($curl);
        if (curl_error($curl)) 
		{
            $error = "Cancel Connection Error: ".curl_error($curl);
            $response = array("status" => "error", "message" => $error);
        }
		else
		{
			$response = $this->modifiedJsonDecode($response, true);
		}
        curl_close($curl);
		
		if($this->logger && is_object($this->logger) && method_exists($this->logger,'write'))
		{
			$this->logger->write('Request: '.$url.' => '.$params);
			$this->logger->write('Response: '.json_encode($response));
		}
        return $response;
    }
	
	public function capture($kyash_code)
	{
		$url = self::$baseUri.'/kyashcodes/'.$kyash_code.'/capture';
		$params = "completion_expected_by=".strtotime("+3 day");
		$params .= "&details=shipment completed";
		
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_USERPWD, $this->key.':'.$this->secret);
		curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		$response = curl_exec($curl);
        if (curl_error($curl)) 
		{
            $error = "Capture Connection Error: ".curl_error($curl);
            $response = array("status" => "error", "message" => $error);
        }
		else
		{
			$response = $this->modifiedJsonDecode($response, true);
		}
        curl_close($curl);
		
		if($this->logger && is_object($this->logger) && method_exists($this->logger,'write'))
		{
			$this->logger->write('Request: '.$url.' => '.$params);
			$this->logger->write('Response: '.json_encode($response));
		}
        return $response;
    }
	
	public function handler($params,$kyash_code,$kyash_status,$url,$order)
    {
		$this->logger->write($params);
                $this->logger->write($_REQUEST);

		$protocol = $this->getProtocol();
		
		if ($protocol == 'https')
		{
			if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) 
			{
				$httpd_username = filter_var($_SERVER['PHP_AUTH_USER'],FILTER_SANITIZE_STRING,FILTER_FLAG_ENCODE_HIGH|FILTER_FLAG_ENCODE_LOW);
				$httpd_password = filter_var($_SERVER['PHP_AUTH_PW'],FILTER_SANITIZE_STRING,FILTER_FLAG_ENCODE_HIGH|FILTER_FLAG_ENCODE_LOW);
				if ($httpd_username == $this->key && $httpd_password == $this->secret) 
				{
					$order_id = trim($params['order_id']);
					$code = trim($params['kyash_code']);
					$status = trim($params['status']);
					$phone = trim($params['paid_by']);
					
					if($kyash_code != $kyash)
					{
						if($this->logger && is_object($this->logger) && method_exists($this->logger,'write'))
						{
							$this->logger->write("Handler: HTTP/1.1 500 Kyash code not match");
						}
						header("HTTP/1.1 500 Kyash code not match");
					}
					else
					{
						if($status == 'paid')
						{
							if($kyash_status == 'pending')
							{
								$comment = "The customer ($phone) has made the payment on Kyash payshop point";
								$order->update('paid',$comment);
							
								if($this->logger && is_object($this->logger) && method_exists($this->logger,'write'))
								{
									$this->logger->write("Handler: HTTP/1.1 200 Success - Paid");
								}
							}
						}
						else if($status == 'expired')
						{
							if($kyash_status == 'pending')
							{
								$comment = "The order was canceled since kyash payment was expired.";
								$order->update('expired',$comment);
								
								if($this->logger && is_object($this->logger) && method_exists($this->logger,'write'))
								{
									$this->logger->write("Handler: HTTP/1.1 200 Success - Expired");
								}
							}
						}
						else
						{
							if($this->logger && is_object($this->logger) && method_exists($this->logger,'write'))
							{
								$this->logger->write("Handler: HTTP/1.1 500 Status is neither paid nor canceled");
							}
							header("HTTP/1.1 500 Kyash code not match");
						}
					}
				} 
				else
				{
					if($this->logger && is_object($this->logger) && method_exists($this->logger,'write'))
					{
						$this->logger->write("Handler: HTTP/1.1 401 Unauthorized");
						
					}
					header("HTTP/1.1 401 Unauthorized");
				}
			}
			else
			{
				if($this->logger && is_object($this->logger) && method_exists($this->logger,'write'))
				{
					$this->logger->write("Handler: HTTP/1.1 401 Unauthorized");
					
				}
				header("HTTP/1.1 401 Unauthorized");
			}
		}
		else
		{
			$headers = getallheaders();
			$this->logger->write($headers);
			$authorization = isset($headers['Authorization']) ? $headers['Authorization'] : '';
			if($this->logger && is_object($this->logger) && method_exists($this->logger,'write'))
			{
				$this->logger->write('Authorization:'.$authorization);
			}
			
			if(!empty($authorization))
			{
				$order_id = trim($params['order_id']);
				$code = trim($params['kyash_code']);
				$status = trim($params['status']);
				$phone = trim($params['paid_by']);
				$amount = trim($params['amount']);
				$timestamp = trim($_REQUEST['timestamp']);
				$nonce= trim($_REQUEST['nonce']);
				
				//prepare normalized request string
				if($status === 'paid') {
					$normalized_request_string = urlencode(utf8_encode('amount').'='.utf8_encode($amount));
					$normalized_request_string .= urlencode('&'.utf8_encode('kyash_code').'='.utf8_encode($code));
					$normalized_request_string .= urlencode('&'.utf8_encode('nonce').'='.utf8_encode($nonce));
					$normalized_request_string .= urlencode('&'.utf8_encode('order_id').'='.utf8_encode($order_id));
					$normalized_request_string .= urlencode('&'.utf8_encode('paid_by').'='.utf8_encode($phone));
					$normalized_request_string .= urlencode('&'.utf8_encode('status').'='.utf8_encode($status));
					$normalized_request_string .= urlencode('&'.utf8_encode('timestamp').'='.utf8_encode($timestamp));
				}
				else {
					$normalized_request_string = urlencode(utf8_encode('kyash_code').'='.utf8_encode($code));
					$normalized_request_string .= urlencode('&'.utf8_encode('nonce').'='.utf8_encode($nonce));
					$normalized_request_string .= urlencode('&'.utf8_encode('order_id').'='.utf8_encode($order_id));
					$normalized_request_string .= urlencode('&'.utf8_encode('status').'='.utf8_encode($status));
					$normalized_request_string .= urlencode('&'.utf8_encode('timestamp').'='.utf8_encode($timestamp));
				}

				//prepare request signature
				$request = urlencode('POST');
				$request .= '&'.urlencode($url);
				$request .= '&'.$normalized_request_string;
				if($this->logger && is_object($this->logger) && method_exists($this->logger,'write'))
				{
					$this->logger->write('Normalized request string:'.$request);
				}
				$hmac = hash_hmac('sha256', $request, $this->hmac, true);
				$signature = base64_encode($hmac);
					
				$auth_value = $this->key.":".$signature;
				$b64_auth_value = base64_encode($auth_value);
				if($this->logger && is_object($this->logger) && method_exists($this->logger,'write'))
				{
					$this->logger->write('Prepared signature: HMAC '.$b64_auth_value);
				}
				
				$prepared_signature = "HMAC ".$b64_auth_value;
                                $this->logger->write($authorization.'\n'.$prepared_signature);
				if($authorization === $prepared_signature)
				{
					if($kyash_code != $code)
					{
						if($this->logger && is_object($this->logger) && method_exists($this->logger,'write'))
						{
							$this->logger->write($kyash_code);
							$this->logger->write($code);
							$this->logger->write("HTTP/1.1 500 Kyash code not match");
						}
						header("HTTP/1.1 500 Kyash code not match");
					}
					else
					{
						if($status == 'paid')
						{
							if($kyash_status == 'pending')
							{
								$comment = "The customer ($phone) has made the payment on Kyash payshop point";
								$order->update('paid',$comment);
								if($this->logger && is_object($this->logger) && method_exists($this->logger,'write'))
								{
									$this->logger->write("HTTP/1.1 200 paid success");
								}
							}
						}
						else if($status == 'expired')
						{
							if($kyash_status == 'pending')
							{
								$comment = "The order was canceled since kyash payment was expired.";
								$order->update('expired',$comment);
								if($this->logger && is_object($this->logger) && method_exists($this->logger,'write'))
								{
									$this->logger->write("HTTP/1.1 200 expired success");
								}
							}
						}
						else
						{
							if($this->logger && is_object($this->logger) && method_exists($this->logger,'write'))
							{
								$this->logger->write("HTTP/1.1 500 Kyash code not match 2");
							}
							header("HTTP/1.1 500 Kyash code not match");
						}
					}
				}
				else
				{
					if($this->logger && is_object($this->logger) && method_exists($this->logger,'write'))
					{
						$this->logger->write("HTTP/1.1 401 Unauthorized - Signature doest not match");
					}
					header("HTTP/1.1 401 Unauthorized - Signature doest not match");
				}
			}
			else
			{
				if($this->logger && is_object($this->logger) && method_exists($this->logger,'write'))
				{
					$this->logger->write("HTTP/1.1 401 Unauthorized - Signature not found in the header or damaged");
				}
				header("HTTP/1.1 401 Unauthorized - Signature not found in the header or damaged");
			}
		}
		exit;
    }
	
	public function modifiedJsonDecode($json,$assoc=false)
    {
		$search = array ("/\s\s+/","/(:(\s|)\')/",'/(\'(\s|):)/','/(,(\s|)\')/', '/(\'(\s|),)/', '/({(\s|)\')/', '/(\'(\s|)})/');
		$replace = array (" ", ':"', '":', ',"', '",', '{"', '"}');
		$json = preg_replace($search, $replace, $json);
        return json_decode($this->removeTrailingCommas(utf8_encode($json)),$assoc);
    }
   
    public function removeTrailingCommas($json)
    {
        $json=preg_replace('/,\s*([\]}])/m', '$1', $json);
        return $json;
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
