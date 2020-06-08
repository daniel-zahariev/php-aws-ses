<?php
/**
* SimpleEmailServiceRequest PHP class
*
* @link https://github.com/daniel-zahariev/php-aws-ses
* @package AmazonSimpleEmailService
* @version v0.9.1
*/
class SimpleEmailServiceRequest
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
	public function __construct(SimpleEmailService $ses = null, $verb = 'GET') {
		$this->ses = $ses;
		$this->verb = $verb;
		$this->response = (object) array('body' => '', 'code' => 0, 'error' => false);
	}


	/**
	* Set SES class
	*
	* @param SimpleEmailService $ses
	* @return SimpleEmailServiceRequest $this
	*/
	public function setSES(SimpleEmailService $ses) {
		$this->ses = $ses;

		return $this;
	}

	/**
	* Set HTTP method
	*
	* @param string $verb
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
		$query = implode('&', $this->getParametersEncoded());
		$url = 'https://'.$this->ses->getHost().'/';

		$date = new DateTime( 'UTC' );
		$amzdate = $date->format( 'Ymd\THis\Z');
		$date2 = new DateTime( 'UTC' );
		$amzdate2 = $date2->format( 'Ymd' );

		$authorization_header = $this->getAuthHeaderAWS4($amzdate, $amzdate2, $query);

		$headers = array();
		$headers[] = 'X-amz-date: ' . $amzdate;
		$headers[] = 'Host: ' . $this->ses->getHost();
		$headers[] = 'Authorization: ' . $authorization_header;

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
	 * @param string $key
	 * @param string $dateStamp
	 * @param string $regionName
	 * @param string $serviceName
	 * @param string $algo
	 * @return string
	 */
	private function __getSignatureKey($key, $dateStamp, $regionName, $serviceName, $algo) {
		$kDate = hash_hmac($algo, $dateStamp, 'AWS4' . $key, true);
		$kRegion = hash_hmac($algo, $regionName, $kDate, true);
		$kService = hash_hmac($algo, $serviceName, $kRegion, true);

		return hash_hmac($algo,'aws4_request', $kService, true);
	}

	/**
	 * Implementation of AWS Signature Version 4
	 * @see https://docs.aws.amazon.com/general/latest/gr/sigv4_signing.html
	 * @param string $amzdate
	 * @param string $amzdate2
	 * @param string $query
	 * @return string
	 */
	private function getAuthHeaderAWS4($amzdate, $amzdate2, $query) {
		$algo = 'sha256';
		$algorithm = 'AWS4-HMAC-' . strtoupper($algo);
		$host_parts = explode('.', $this->ses->getHost());
		$service = $host_parts[0];
		$region = $host_parts[1];

		$canonical_uri = '/';
		if($this->verb === 'POST') {
			$canonical_querystring = '';
			$payload_data = $query;
		} else {
			$canonical_querystring = $query;
			$payload_data = '';
		}

		// ************* TASK 1: CREATE A CANONICAL REQUEST *************
		$canonical_headers_list = [
			'host:' . $this->ses->getHost(),
			'x-amz-date:' . $amzdate
		];

		$canonical_headers = implode("\n", $canonical_headers_list) . "\n";
		$signed_headers = 'host;x-amz-date';
		$payload_hash = hash($algo, $payload_data, false);
		$canonical_request = implode("\n", array(
			$this->verb,
			$canonical_uri,
			$canonical_querystring,
			$canonical_headers,
			$signed_headers,
			$payload_hash
		));

		// ************* TASK 2: CREATE THE STRING TO SIGN*************
		$credential_scope = $amzdate2 . '/' . $region . '/' . $service . '/' . 'aws4_request';
		$string_to_sign = implode("\n", array(
			$algorithm,
			$amzdate,
			$credential_scope,
			hash($algo, $canonical_request, false)
		));

		// ************* TASK 3: CALCULATE THE SIGNATURE *************
		// Create the signing key using the function defined above.
		$signing_key = $this->__getSignatureKey($this->ses->getSecretKey(), $amzdate2, $region, $service, $algo);

		// Sign the string_to_sign using the signing_key
		$signature = hash_hmac($algo, $string_to_sign, $signing_key, false);

		// ************* TASK 4: ADD SIGNING INFORMATION TO THE REQUEST *************
		return $algorithm . ' ' . implode(', ', array(
			'Credential=' . $this->ses->getAccessKey() . '/' . $credential_scope,
			'SignedHeaders=' . $signed_headers ,
			'Signature=' . $signature
		));
	}
}
