<?php
/**
* SimpleEmailServiceRequest PHP class
*
* @link https://github.com/daniel-zahariev/php-aws-ses
* @package AmazonSimpleEmailService
* @version v0.9.1
*/
final class SimpleEmailServiceRequest
{
	private $ses, $verb, $parameters = array();

	// CURL request handler that can be reused
	protected $curl_handler = null;

	// Holds the response from calling AWS's API
	protected $response;

	//
	public static $curlOptions = array();

	/**
	* Constructor
	*
	* @param SimpleEmailService $ses The SimpleEmailService object making this request
	* @param string $verb HTTP verb
	* @return void
	*/
	public function __construct($ses, $verb = 'GET') {
		$this->ses = $ses;
		$this->verb = $verb;
		$this->response = (object) array('body' => '', 'code' => 0, 'error' => false);
	}

	/**
	* Set HTTP method
	*
	* @return SimpleEmailServiceRequest $this
	*/
	public function setVerb($verb) {
		$this->verb = $verb;
		return $this;
	}

	/**
	* Set request parameter
	*
	* @param string  $key Key
	* @param string  $value Value
	* @param boolean $replace Whether to replace the key if it already exists (default true)
	* @return SimpleEmailServiceRequest $this
	*/
	public function setParameter($key, $value, $replace = true) {
		if(!$replace && isset($this->parameters[$key])) {
			$temp = (array)($this->parameters[$key]);
			$temp[] = $value;
			$this->parameters[$key] = $temp;
		} else {
			$this->parameters[$key] = $value;
		}

		return $this;
	}

	/**
	* Get the params for the reques
	*
	* @return array $params
	*/
	public function getParametersEncoded() {
		$params = array();

		foreach ($this->parameters as $var => $value) {
			if(is_array($value)) {
				foreach($value as $v) {
					$params[] = $var.'='.$this->__customUrlEncode($v);
				}
			} else {
				$params[] = $var.'='.$this->__customUrlEncode($value);
			}
		}

		sort($params, SORT_STRING);

		return $params;
	}

	/**
	* Clear the request parameters
	* @return SimpleEmailServiceRequest $this
	*/
	public function clearParameters() {
		$this->parameters = array();
		return $this;
	}

	/**
	* Instantiate and setup CURL handler for sending requests.
	* Instance is cashed in `$this->curl_handler`
	*
	* @return resource $curl_handler
	*/
	protected function getCurlHandler() {
		if (!empty($this->curl_handler))
			return $this->curl_handler;

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERAGENT, 'SimpleEmailService/php');

		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, ($this->ses->verifyHost() ? 2 : 0));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, ($this->ses->verifyPeer() ? 1 : 0));
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
		curl_setopt($curl, CURLOPT_WRITEFUNCTION, array(&$this, '__responseWriteCallback'));
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

		foreach(self::$curlOptions as $option => $value) {
			curl_setopt($curl, $option, $value);
		}

		$this->curl_handler = $curl;

		return $this->curl_handler;
	}

	/**
	* Get the response
	*
	* @return object | false
	*/
	public function getResponse() {

		// must be in format 'Sun, 06 Nov 1994 08:49:37 GMT'
		$date = gmdate('D, d M Y H:i:s e');
		$query = implode('&', $this->getParametersEncoded());
		$auth = 'AWS3-HTTPS AWSAccessKeyId='.$this->ses->getAccessKey();
		$auth .= ',Algorithm=HmacSHA256,Signature='.$this->__getSignature($date);
		$url = 'https://'.$this->ses->getHost().'/';

		$headers = array();
		$headers[] = 'Date: ' . $date;
		$headers[] = 'Host: ' . $this->ses->getHost();
		$headers[] = 'X-Amzn-Authorization: ' . $auth;

		$curl_handler = $this->getCurlHandler();
		curl_setopt($curl_handler, CURLOPT_CUSTOMREQUEST, $this->verb);

		// Request types
		switch ($this->verb) {
			case 'GET':
			case 'DELETE':
				$url .= '?'.$query;
				break;

			case 'POST':
				curl_setopt($curl_handler, CURLOPT_POSTFIELDS, $query);
				$headers[] = 'Content-Type: application/x-www-form-urlencoded';
				break;
		}
		curl_setopt($curl_handler, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl_handler, CURLOPT_URL, $url);


		// Execute, grab errors
		if (curl_exec($curl_handler)) {
			$this->response->code = curl_getinfo($curl_handler, CURLINFO_HTTP_CODE);
		} else {
			$this->response->error = array(
				'curl' => true,
				'code' => curl_errno($curl_handler),
				'message' => curl_error($curl_handler),
			);
		}

		// cleanup for reusing the current instance for multiple requests
		curl_setopt($curl_handler, CURLOPT_POSTFIELDS, '');
		$this->parameters = array();

		// Parse body into XML
		if ($this->response->error === false && !empty($this->response->body)) {
			$this->response->body = simplexml_load_string($this->response->body);

			// Grab SES errors
			if (!in_array($this->response->code, array(200, 201, 202, 204))
				&& isset($this->response->body->Error)) {
				$error = $this->response->body->Error;
				$output = array();
				$output['curl'] = false;
				$output['Error'] = array();
				$output['Error']['Type'] = (string)$error->Type;
				$output['Error']['Code'] = (string)$error->Code;
				$output['Error']['Message'] = (string)$error->Message;
				$output['RequestId'] = (string)$this->response->body->RequestId;

				$this->response->error = $output;
				unset($this->response->body);
			}
		}

		$response = $this->response;
		$this->response = (object) array('body' => '', 'code' => 0, 'error' => false);

		return $response;
	}

	/**
	* Destroy any leftover handlers
	*/
	public function __destruct() {
		if (!empty($this->curl_handler))
			@curl_close($this->curl_handler);
	}

	/**
	* CURL write callback
	*
	* @param resource $curl CURL resource
	* @param string $data Data
	* @return integer
	*/
	private function __responseWriteCallback(&$curl, &$data) {
		if (!isset($this->response->body)) {
			$this->response->body = $data;
		} else {
			$this->response->body .= $data;
		}

		return strlen($data);
	}

	/**
	* Contributed by afx114
	* URL encode the parameters as per http://docs.amazonwebservices.com/AWSECommerceService/latest/DG/index.html?Query_QueryAuth.html
	* PHP's rawurlencode() follows RFC 1738, not RFC 3986 as required by Amazon. The only difference is the tilde (~), so convert it back after rawurlencode
	* See: http://www.morganney.com/blog/API/AWS-Product-Advertising-API-Requires-a-Signed-Request.php
	*
	* @param string $var String to encode
	* @return string
	*/
	private function __customUrlEncode($var) {
		return str_replace('%7E', '~', rawurlencode($var));
	}

	/**
	* Generate the auth string using Hmac-SHA256
	*
	* @internal Used by SimpleEmailServiceRequest::getResponse()
	* @param string $string String to sign
	* @return string
	*/
	private function __getSignature($string) {
		return base64_encode(hash_hmac('sha256', $string, $this->ses->getSecretKey(), true));
	}
}
