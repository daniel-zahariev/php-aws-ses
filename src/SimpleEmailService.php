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
*/
class SimpleEmailService
{
	/**
	 * @link(AWS SES regions, http://docs.aws.amazon.com/ses/latest/DeveloperGuide/regions.html)
	 */
	const AWS_US_EAST_1 = 'email.us-east-1.amazonaws.com';
	const AWS_US_WEST_2 = 'email.us-west-2.amazonaws.com';
	const AWS_EU_WEST1 = 'email.eu-west-1.amazonaws.com';

	protected $__accessKey; // AWS Access key
	protected $__secretKey; // AWS Secret key
	protected $__host;
	protected $__trigger_errors;

	public function getAccessKey() { return $this->__accessKey; }
	public function getSecretKey() { return $this->__secretKey; }
	public function getHost() { return $this->__host; }

	protected $__verifyHost = true;
	protected $__verifyPeer = true;

	// verifyHost and verifyPeer determine whether curl verifies ssl certificates.
	// It may be necessary to disable these checks on certain systems.
	// These only have an effect if SSL is enabled.
	public function verifyHost() { return $this->__verifyHost; }
	public function enableVerifyHost($enable = true) { $this->__verifyHost = $enable; }
	public function verifyPeer() { return $this->__verifyPeer; }
	public function enableVerifyPeer($enable = true) { $this->__verifyPeer = $enable; }

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

	/**
	* Lists the email addresses that have been verified and can be used as the 'From' address
	*
	* @return array An array containing two items: a list of verified email addresses, and the request id.
	*/
	public function listVerifiedEmailAddresses() {
		$rest = new SimpleEmailServiceRequest($this, 'GET');
		$rest->setParameter('Action', 'ListVerifiedEmailAddresses');

		$rest = $rest->getResponse();
		if($rest->error === false && $rest->code !== 200) {
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		}
		if($rest->error !== false) {
			$this->__triggerError('listVerifiedEmailAddresses', $rest->error);
			return false;
		}

		$response = array();
		if(!isset($rest->body)) {
			return $response;
		}

		$addresses = array();
		foreach($rest->body->ListVerifiedEmailAddressesResult->VerifiedEmailAddresses->member as $address) {
			$addresses[] = (string)$address;
		}

		$response['Addresses'] = $addresses;
		$response['RequestId'] = (string)$rest->body->ResponseMetadata->RequestId;

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
		$rest = new SimpleEmailServiceRequest($this, 'POST');
		$rest->setParameter('Action', 'VerifyEmailAddress');
		$rest->setParameter('EmailAddress', $email);

		$rest = $rest->getResponse();
		if($rest->error === false && $rest->code !== 200) {
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		}
		if($rest->error !== false) {
			$this->__triggerError('verifyEmailAddress', $rest->error);
			return false;
		}

		$response['RequestId'] = (string)$rest->body->ResponseMetadata->RequestId;
		return $response;
	}

	/**
	* Removes the specified email address from the list of verified addresses.
	*
	* @param string $email The email address to remove
	* @return array The request id for this request.
	*/
	public function deleteVerifiedEmailAddress($email) {
		$rest = new SimpleEmailServiceRequest($this, 'DELETE');
		$rest->setParameter('Action', 'DeleteVerifiedEmailAddress');
		$rest->setParameter('EmailAddress', $email);

		$rest = $rest->getResponse();
		if($rest->error === false && $rest->code !== 200) {
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		}
		if($rest->error !== false) {
			$this->__triggerError('deleteVerifiedEmailAddress', $rest->error);
			return false;
		}

		$response['RequestId'] = (string)$rest->body->ResponseMetadata->RequestId;
		return $response;
	}

	/**
	* Retrieves information on the current activity limits for this account.
	* See http://docs.amazonwebservices.com/ses/latest/APIReference/API_GetSendQuota.html
	*
	* @return array An array containing information on this account's activity limits.
	*/
	public function getSendQuota() {
		$rest = new SimpleEmailServiceRequest($this, 'GET');
		$rest->setParameter('Action', 'GetSendQuota');

		$rest = $rest->getResponse();
		if($rest->error === false && $rest->code !== 200) {
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		}
		if($rest->error !== false) {
			$this->__triggerError('getSendQuota', $rest->error);
			return false;
		}

		$response = array();
		if(!isset($rest->body)) {
			return $response;
		}

		$response['Max24HourSend'] = (string)$rest->body->GetSendQuotaResult->Max24HourSend;
		$response['MaxSendRate'] = (string)$rest->body->GetSendQuotaResult->MaxSendRate;
		$response['SentLast24Hours'] = (string)$rest->body->GetSendQuotaResult->SentLast24Hours;
		$response['RequestId'] = (string)$rest->body->ResponseMetadata->RequestId;

		return $response;
	}

	/**
	* Retrieves statistics for the last two weeks of activity on this account.
	* See http://docs.amazonwebservices.com/ses/latest/APIReference/API_GetSendStatistics.html
	*
	* @return array An array of activity statistics.  Each array item covers a 15-minute period.
	*/
	public function getSendStatistics() {
		$rest = new SimpleEmailServiceRequest($this, 'GET');
		$rest->setParameter('Action', 'GetSendStatistics');

		$rest = $rest->getResponse();
		if($rest->error === false && $rest->code !== 200) {
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		}
		if($rest->error !== false) {
			$this->__triggerError('getSendStatistics', $rest->error);
			return false;
		}

		$response = array();
		if(!isset($rest->body)) {
			return $response;
		}

		$datapoints = array();
		foreach($rest->body->GetSendStatisticsResult->SendDataPoints->member as $datapoint) {
			$p = array();
			$p['Bounces'] = (string)$datapoint->Bounces;
			$p['Complaints'] = (string)$datapoint->Complaints;
			$p['DeliveryAttempts'] = (string)$datapoint->DeliveryAttempts;
			$p['Rejects'] = (string)$datapoint->Rejects;
			$p['Timestamp'] = (string)$datapoint->Timestamp;

			$datapoints[] = $p;
		}

		$response['SendDataPoints'] = $datapoints;
		$response['RequestId'] = (string)$rest->body->ResponseMetadata->RequestId;

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

		$rest = new SimpleEmailServiceRequest($this, 'POST');
		$action = !empty($sesMessage->attachments) || $use_raw_request ? 'SendRawEmail' : 'SendEmail';
		$rest->setParameter('Action', $action);

		if($action == 'SendRawEmail') {
			// echo $sesMessage->getRawMessage();return;
			$rest->setParameter('RawMessage.Data', $sesMessage->getRawMessage());
		} else {
			$i = 1;
			foreach($sesMessage->to as $to) {
				$rest->setParameter('Destination.ToAddresses.member.'.$i, $sesMessage->encodeRecipients($to));
				$i++;
			}

			if(is_array($sesMessage->cc)) {
				$i = 1;
				foreach($sesMessage->cc as $cc) {
					$rest->setParameter('Destination.CcAddresses.member.'.$i, $sesMessage->encodeRecipients($cc));
					$i++;
				}
			}

			if(is_array($sesMessage->bcc)) {
				$i = 1;
				foreach($sesMessage->bcc as $bcc) {
					$rest->setParameter('Destination.BccAddresses.member.'.$i, $sesMessage->encodeRecipients($bcc));
					$i++;
				}
			}

			if(is_array($sesMessage->replyto)) {
				$i = 1;
				foreach($sesMessage->replyto as $replyto) {
					$rest->setParameter('ReplyToAddresses.member.'.$i, $sesMessage->encodeRecipients($replyto));
					$i++;
				}
			}

			$rest->setParameter('Source', $sesMessage->encodeRecipients($sesMessage->from));

			if($sesMessage->returnpath != null) {
				$rest->setParameter('ReturnPath', $sesMessage->returnpath);
			}

			if($sesMessage->subject != null && strlen($sesMessage->subject) > 0) {
				$rest->setParameter('Message.Subject.Data', $sesMessage->subject);
				if($sesMessage->subjectCharset != null && strlen($sesMessage->subjectCharset) > 0) {
					$rest->setParameter('Message.Subject.Charset', $sesMessage->subjectCharset);
				}
			}


			if($sesMessage->messagetext != null && strlen($sesMessage->messagetext) > 0) {
				$rest->setParameter('Message.Body.Text.Data', $sesMessage->messagetext);
				if($sesMessage->messageTextCharset != null && strlen($sesMessage->messageTextCharset) > 0) {
					$rest->setParameter('Message.Body.Text.Charset', $sesMessage->messageTextCharset);
				}
			}

			if($sesMessage->messagehtml != null && strlen($sesMessage->messagehtml) > 0) {
				$rest->setParameter('Message.Body.Html.Data', $sesMessage->messagehtml);
				if($sesMessage->messageHtmlCharset != null && strlen($sesMessage->messageHtmlCharset) > 0) {
					$rest->setParameter('Message.Body.Html.Charset', $sesMessage->messageHtmlCharset);
				}
			}
		}

		$rest = $rest->getResponse();
		if($rest->error === false && $rest->code !== 200) {
			$response = array(
				'code' => $rest->code,
				'error' => array('Error' => array('message' => 'Unexpected HTTP status')),
			);
			return $response;
		}
		if($rest->error !== false) {
			if (($this->__trigger_errors && ($trigger_error !== false)) || $trigger_error === true) {
				$this->__triggerError('sendEmail', $rest->error);
				return false;
			}
			return $rest;
		}

		$response = array(
			'MessageId' => (string)$rest->body->{"{$action}Result"}->MessageId,
			'RequestId' => (string)$rest->body->ResponseMetadata->RequestId,
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
}