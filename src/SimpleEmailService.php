<?php
/**
*
* Copyright (c) 2014, Daniel Zahariev.
* Copyright (c) 2011, Dan Myers.
* Parts copyright (c) 2008, Donovan Schonknecht.
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*
* - Redistributions of source code must retain the above copyright notice,
*   this list of conditions and the following disclaimer.
* - Redistributions in binary form must reproduce the above copyright
*   notice, this list of conditions and the following disclaimer in the
*   documentation and/or other materials provided with the distribution.
*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
* AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
* IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
* ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
* LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
* CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
* SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
* INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
* CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
* ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
* POSSIBILITY OF SUCH DAMAGE.
*
* This is a modified BSD license (the third clause has been removed).
* The BSD license may be found here:
* http://www.opensource.org/licenses/bsd-license.php
*
* Amazon Simple Email Service is a trademark of Amazon.com, Inc. or its affiliates.
*
* SimpleEmailService is based on Donovan Schonknecht's Amazon S3 PHP class, found here:
* http://undesigned.org.za/2007/10/22/amazon-s3-php-class
*
* @copyright 2014 Daniel Zahariev
* @copyright 2011 Dan Myers
* @copyright 2008 Donovan Schonknecht
*/

/**
* SimpleEmailService PHP class
*
* @link https://github.com/daniel-zahariev/php-aws-ses
* @package AmazonSimpleEmailService
* @version v0.8.8
*/
class SimpleEmailService
{
	/**
	 * @link(AWS SES regions, http://docs.aws.amazon.com/ses/latest/DeveloperGuide/regions.html)
	 */
	const AWS_US_EAST_1 = 'email.us-east-1.amazonaws.com';
	const AWS_US_WEST_2 = 'email.us-west-2.amazonaws.com';
	const AWS_EU_WEST1 = 'email.eu-west-1.amazonaws.com';

	// AWS SES Target host of region
	protected $__host;

	// AWS SES Access key
	protected $__accessKey;

	// AWS Secret key
	protected $__secretKey;

	// Enable/disable
	protected $__trigger_errors;

	// Controls the reuse of CURL hander for sending a bulk of messages
	protected $__bulk_sending_mode = false;

	// Optionally reusable SimpleEmailServiceRequest instance
	protected $__ses_request = null;

	// Controls CURLOPT_SSL_VERIFYHOST setting for SimpleEmailServiceRequest's curl handler
	protected $__verifyHost = true;

	// Controls CURLOPT_SSL_VERIFYPEER setting for SimpleEmailServiceRequest's curl handler
	protected $__verifyPeer = true;

	/**
	* Constructor
	*
	* @param string $accessKey Access key
	* @param string $secretKey Secret key
	* @param string $host Amazon Host through which to send the emails
	* @param boolean $trigger_errors Trigger PHP errors when AWS SES API returns an error
	* @return void
	*/
	public function __construct($accessKey = null, $secretKey = null, $host = self::AWS_US_EAST_1, $trigger_errors = true) {
		if ($accessKey !== null && $secretKey !== null) {
			$this->setAuth($accessKey, $secretKey);
		}
		$this->__host = $host;
		$this->__trigger_errors = $trigger_errors;
	}

	/**
	* Set AWS access key and secret key
	*
	* @param string $accessKey Access key
	* @param string $secretKey Secret key
	* @return SimpleEmailService $this
	*/
	public function setAuth($accessKey, $secretKey) {
		$this->__accessKey = $accessKey;
		$this->__secretKey = $secretKey;

		return $this;
	}

	// DEPRECATED
	public function enableVerifyHost($enable = true) { $this->__verifyHost = (bool)$enable; return $this; }
	// DEPRECATED
	public function enableVerifyPeer($enable = true) { $this->__verifyPeer = (bool)$enable; return $this; }
	// DEPRECATED
	public function verifyHost() { return $this->__verifyHost; }
	// DEPRECATED
	public function verifyPeer() { return $this->__verifyPeer; }


	/**
	* Get AWS target host
	* @return boolean
	*/
	public function getHost() { return $this->__host; }

	/**
	* Get AWS SES auth access key
	* @return string
	*/
	public function getAccessKey() { return $this->__accessKey; }

	/**
	* Get AWS SES auth secret key
	* @return string
	*/
	public function getSecretKey() { return $this->__secretKey; }

	/**
	* Get the verify peer CURL mode
	* @return boolean
	*/
	public function getVerifyPeer() { return $this->__verifyPeer; }

	/**
	* Get the verify host CURL mode
	* @return boolean
	*/
	public function getVerifyHost() { return $this->__verifyHost; }

	/**
	* Get bulk email sending mode
	* @return boolean
	*/
	public function getBulkMode() { return $this->__bulk_sending_mode; }


	/**
	* Enable/disable CURLOPT_SSL_VERIFYHOST for SimpleEmailServiceRequest's curl handler
	* verifyHost and verifyPeer determine whether curl verifies ssl certificates.
	* It may be necessary to disable these checks on certain systems.
	* These only have an effect if SSL is enabled.
	*
	* @param boolean $enable New status for the mode
	* @return SimpleEmailService $this
	*/
	public function setVerifyHost($enable = true) {
		$this->__verifyHost = (bool)$enable;
		return $this;
	}

	/**
	* Enable/disable CURLOPT_SSL_VERIFYPEER for SimpleEmailServiceRequest's curl handler
	* verifyHost and verifyPeer determine whether curl verifies ssl certificates.
	* It may be necessary to disable these checks on certain systems.
	* These only have an effect if SSL is enabled.
	*
	* @param boolean $enable New status for the mode
	* @return SimpleEmailService $this
	*/
	public function setVerifyPeer($enable = true) {
		$this->__verifyPeer = (bool)$enable;
		return $this;
	}

	/**
	* Enable/disable bulk email sending mode
	*
	* @param boolean $enable New status for the mode
	* @return SimpleEmailService $this
	*/
	public function setBulkMode($enable = true) {
		$this->__bulk_sending_mode = (bool)$enable;
		// clear request handler on disable
		if (!$enable) $this->__ses_request = null;
		return $this;
	}

	/**
	* Lists the email addresses that have been verified and can be used as the 'From' address
	*
	* @return array An array containing two items: a list of verified email addresses, and the request id.
	*/
	public function listVerifiedEmailAddresses() {
		$ses_request = $this->getRequestHandler('GET');
		$ses_request->setParameter('Action', 'ListVerifiedEmailAddresses');

		$ses_response = $ses_request->getResponse();
		if($ses_response->error === false && $ses_response->code !== 200) {
			$ses_response->error = array('code' => $ses_response->code, 'message' => 'Unexpected HTTP status');
		}
		if($ses_response->error !== false) {
			$this->__triggerError('listVerifiedEmailAddresses', $ses_response->error);
			return false;
		}

		$response = array();
		if(!isset($ses_response->body)) {
			return $response;
		}

		$addresses = array();
		foreach($ses_response->body->ListVerifiedEmailAddressesResult->VerifiedEmailAddresses->member as $address) {
			$addresses[] = (string)$address;
		}

		$response['Addresses'] = $addresses;
		$response['RequestId'] = (string)$ses_response->body->ResponseMetadata->RequestId;

		return $response;
	}

	/**
	* Requests verification of the provided email address, so it can be used
	* as the 'From' address when sending emails through SimpleEmailService.
	*
	* After submitting this request, you should receive a verification email
	* from Amazon at the specified address containing instructions to follow.
	*
	* @param string $email The email address to get verified
	* @return array The request id for this request.
	*/
	public function verifyEmailAddress($email) {
		$ses_request = $this->getRequestHandler('POST');
		$ses_request->setParameter('Action', 'VerifyEmailAddress');
		$ses_request->setParameter('EmailAddress', $email);

		$ses_response = $ses_request->getResponse();
		if($ses_response->error === false && $ses_response->code !== 200) {
			$ses_response->error = array('code' => $ses_response->code, 'message' => 'Unexpected HTTP status');
		}
		if($ses_response->error !== false) {
			$this->__triggerError('verifyEmailAddress', $ses_response->error);
			return false;
		}

		$response['RequestId'] = (string)$ses_response->body->ResponseMetadata->RequestId;
		return $response;
	}

	/**
	* Removes the specified email address from the list of verified addresses.
	*
	* @param string $email The email address to remove
	* @return array The request id for this request.
	*/
	public function deleteVerifiedEmailAddress($email) {
		$ses_request = $this->getRequestHandler('DELETE');
		$ses_request->setParameter('Action', 'DeleteVerifiedEmailAddress');
		$ses_request->setParameter('EmailAddress', $email);

		$ses_response = $ses_request->getResponse();
		if($ses_response->error === false && $ses_response->code !== 200) {
			$ses_response->error = array('code' => $ses_response->code, 'message' => 'Unexpected HTTP status');
		}
		if($ses_response->error !== false) {
			$this->__triggerError('deleteVerifiedEmailAddress', $ses_response->error);
			return false;
		}

		$response['RequestId'] = (string)$ses_response->body->ResponseMetadata->RequestId;
		return $response;
	}

	/**
	* Retrieves information on the current activity limits for this account.
	* See http://docs.amazonwebservices.com/ses/latest/APIReference/API_GetSendQuota.html
	*
	* @return array An array containing information on this account's activity limits.
	*/
	public function getSendQuota() {
		$ses_request = $this->getRequestHandler('GET');
		$ses_request->setParameter('Action', 'GetSendQuota');

		$ses_response = $ses_request->getResponse();
		if($ses_response->error === false && $ses_response->code !== 200) {
			$ses_response->error = array('code' => $ses_response->code, 'message' => 'Unexpected HTTP status');
		}
		if($ses_response->error !== false) {
			$this->__triggerError('getSendQuota', $ses_response->error);
			return false;
		}

		$response = array();
		if(!isset($ses_response->body)) {
			return $response;
		}

		$response['Max24HourSend'] = (string)$ses_response->body->GetSendQuotaResult->Max24HourSend;
		$response['MaxSendRate'] = (string)$ses_response->body->GetSendQuotaResult->MaxSendRate;
		$response['SentLast24Hours'] = (string)$ses_response->body->GetSendQuotaResult->SentLast24Hours;
		$response['RequestId'] = (string)$ses_response->body->ResponseMetadata->RequestId;

		return $response;
	}

	/**
	* Retrieves statistics for the last two weeks of activity on this account.
	* See http://docs.amazonwebservices.com/ses/latest/APIReference/API_GetSendStatistics.html
	*
	* @return array An array of activity statistics.  Each array item covers a 15-minute period.
	*/
	public function getSendStatistics() {
		$ses_request = $this->getRequestHandler('GET');
		$ses_request->setParameter('Action', 'GetSendStatistics');

		$ses_response = $ses_request->getResponse();
		if($ses_response->error === false && $ses_response->code !== 200) {
			$ses_response->error = array('code' => $ses_response->code, 'message' => 'Unexpected HTTP status');
		}
		if($ses_response->error !== false) {
			$this->__triggerError('getSendStatistics', $ses_response->error);
			return false;
		}

		$response = array();
		if(!isset($ses_response->body)) {
			return $response;
		}

		$datapoints = array();
		foreach($ses_response->body->GetSendStatisticsResult->SendDataPoints->member as $datapoint) {
			$p = array();
			$p['Bounces'] = (string)$datapoint->Bounces;
			$p['Complaints'] = (string)$datapoint->Complaints;
			$p['DeliveryAttempts'] = (string)$datapoint->DeliveryAttempts;
			$p['Rejects'] = (string)$datapoint->Rejects;
			$p['Timestamp'] = (string)$datapoint->Timestamp;

			$datapoints[] = $p;
		}

		$response['SendDataPoints'] = $datapoints;
		$response['RequestId'] = (string)$ses_response->body->ResponseMetadata->RequestId;

		return $response;
	}


	/**
	* Given a SimpleEmailServiceMessage object, submits the message to the service for sending.
	*
	* @param SimpleEmailServiceMessage $sesMessage An instance of the message class
	* @param boolean $use_raw_request If this is true or there are attachments to the email `SendRawEmail` call will be used
	* @param boolean $trigger_error Optionally overwrite the class setting for triggering an error (with type check to true/false)
	* @return array An array containing the unique identifier for this message and a separate request id.
	*         Returns false if the provided message is missing any required fields.
	*  @link(AWS SES Response formats, http://docs.aws.amazon.com/ses/latest/DeveloperGuide/query-interface-responses.html)
	*/
	public function sendEmail($sesMessage, $use_raw_request = false , $trigger_error = null) {
		if(!$sesMessage->validate()) {
			$this->__triggerError('sendEmail', 'Message failed validation.');
			return false;
		}

		$ses_request = $this->getRequestHandler('POST');
		$action = !empty($sesMessage->attachments) || $use_raw_request ? 'SendRawEmail' : 'SendEmail';
		$ses_request->setParameter('Action', $action);

		if($action == 'SendRawEmail') {
			// echo $sesMessage->getRawMessage();return;
			$ses_request->setParameter('RawMessage.Data', $sesMessage->getRawMessage());
		} else {
			$i = 1;
			foreach($sesMessage->to as $to) {
				$ses_request->setParameter('Destination.ToAddresses.member.'.$i, $sesMessage->encodeRecipients($to));
				$i++;
			}

			if(is_array($sesMessage->cc)) {
				$i = 1;
				foreach($sesMessage->cc as $cc) {
					$ses_request->setParameter('Destination.CcAddresses.member.'.$i, $sesMessage->encodeRecipients($cc));
					$i++;
				}
			}

			if(is_array($sesMessage->bcc)) {
				$i = 1;
				foreach($sesMessage->bcc as $bcc) {
					$ses_request->setParameter('Destination.BccAddresses.member.'.$i, $sesMessage->encodeRecipients($bcc));
					$i++;
				}
			}

			if(is_array($sesMessage->replyto)) {
				$i = 1;
				foreach($sesMessage->replyto as $replyto) {
					$ses_request->setParameter('ReplyToAddresses.member.'.$i, $sesMessage->encodeRecipients($replyto));
					$i++;
				}
			}

			$ses_request->setParameter('Source', $sesMessage->encodeRecipients($sesMessage->from));

			if($sesMessage->returnpath != null) {
				$ses_request->setParameter('ReturnPath', $sesMessage->returnpath);
			}

			if($sesMessage->subject != null && strlen($sesMessage->subject) > 0) {
				$ses_request->setParameter('Message.Subject.Data', $sesMessage->subject);
				if($sesMessage->subjectCharset != null && strlen($sesMessage->subjectCharset) > 0) {
					$ses_request->setParameter('Message.Subject.Charset', $sesMessage->subjectCharset);
				}
			}


			if($sesMessage->messagetext != null && strlen($sesMessage->messagetext) > 0) {
				$ses_request->setParameter('Message.Body.Text.Data', $sesMessage->messagetext);
				if($sesMessage->messageTextCharset != null && strlen($sesMessage->messageTextCharset) > 0) {
					$ses_request->setParameter('Message.Body.Text.Charset', $sesMessage->messageTextCharset);
				}
			}

			if($sesMessage->messagehtml != null && strlen($sesMessage->messagehtml) > 0) {
				$ses_request->setParameter('Message.Body.Html.Data', $sesMessage->messagehtml);
				if($sesMessage->messageHtmlCharset != null && strlen($sesMessage->messageHtmlCharset) > 0) {
					$ses_request->setParameter('Message.Body.Html.Charset', $sesMessage->messageHtmlCharset);
				}
			}
		}

		$ses_response = $ses_request->getResponse();
		if($ses_response->error === false && $ses_response->code !== 200) {
			$response = array(
				'code' => $ses_response->code,
				'error' => array('Error' => array('message' => 'Unexpected HTTP status')),
			);
			return $response;
		}
		if($ses_response->error !== false) {
			if (($this->__trigger_errors && ($trigger_error !== false)) || $trigger_error === true) {
				$this->__triggerError('sendEmail', $ses_response->error);
				return false;
			}
			return $ses_response;
		}

		$response = array(
			'MessageId' => (string)$ses_response->body->{"{$action}Result"}->MessageId,
			'RequestId' => (string)$ses_response->body->ResponseMetadata->RequestId,
		);
		return $response;
	}

	/**
	* Trigger an error message
	*
	* {@internal Used by member functions to output errors}
	* @param  string $functionname The name of the function that failed
	* @param array $error Array containing error information
	* @return  void
	*/
	public function __triggerError($functionname, $error)
	{
		if($error == false) {
			trigger_error(sprintf("SimpleEmailService::%s(): Encountered an error, but no description given", $functionname), E_USER_WARNING);
		}
		else if(isset($error['curl']) && $error['curl'])
		{
			trigger_error(sprintf("SimpleEmailService::%s(): %s %s", $functionname, $error['code'], $error['message']), E_USER_WARNING);
		}
		else if(isset($error['Error']))
		{
			$e = $error['Error'];
			$message = sprintf("SimpleEmailService::%s(): %s - %s: %s\nRequest Id: %s\n", $functionname, $e['Type'], $e['Code'], $e['Message'], $error['RequestId']);
			trigger_error($message, E_USER_WARNING);
		}
		else {
			trigger_error(sprintf("SimpleEmailService::%s(): Encountered an error: %s", $functionname, $error), E_USER_WARNING);
		}
	}

	/**
	*
	*/
	protected function getRequestHandler($verb) {
		if ($this->__bulk_sending_mode && !empty($this->__ses_request)) {
			$this->__ses_request->setVerb($verb);
			return $this->__ses_request;
		}
		$this->__ses_request = new SimpleEmailServiceRequest($this, $verb);

		return $this->__ses_request;
	}
}
