<?php
/**
* SimpleEmailServiceMessage PHP class
*
* @link https://github.com/daniel-zahariev/php-aws-ses
* @package AmazonSimpleEmailService
*/
final class SimpleEmailServiceMessage {

	// these are public for convenience only
	// these are not to be used outside of the SimpleEmailService class!
	public $to, $cc, $bcc, $replyto, $recipientsCharset;
	public $from, $returnpath;
	public $subject, $messagetext, $messagehtml;
	public $subjectCharset, $messageTextCharset, $messageHtmlCharset;
	public $attachments = array(), $customHeaders = array();

	public function __construct() {
		$this->to = array();
		$this->cc = array();
		$this->bcc = array();
		$this->replyto = array();
		$this->recipientsCharset = 'UTF-8';

		$this->from = null;
		$this->returnpath = null;

		$this->subject = null;
		$this->messagetext = null;
		$this->messagehtml = null;

		$this->subjectCharset = 'UTF-8';
		$this->messageTextCharset = 'UTF-8';
		$this->messageHtmlCharset = 'UTF-8';
	}

	/**
	* addTo, addCC, addBCC, and addReplyTo have the following behavior:
	* If a single address is passed, it is appended to the current list of addresses.
	* If an array of addresses is passed, that array is merged into the current list.
	*/
	public function addTo($to) {
		if(!is_array($to)) {
			$this->to[] = $to;
		}
		else {
			$this->to = array_unique(array_merge($this->to, $to));
		}

		return $this;
	}

	public function addCC($cc) {
		if(!is_array($cc)) {
			$this->cc[] = $cc;
		}
		else {
			$this->cc = array_merge($this->cc, $cc);
		}

		return $this;
	}

	public function addBCC($bcc) {
		if(!is_array($bcc)) {
			$this->bcc[] = $bcc;
		}
		else {
			$this->bcc = array_merge($this->bcc, $bcc);
		}

		return $this;
	}

	public function addReplyTo($replyto) {
		if(!is_array($replyto)) {
			$this->replyto[] = $replyto;
		}
		else {
			$this->replyto = array_merge($this->replyto, $replyto);
		}

		return $this;
	}

	public function setFrom($from) {
		$this->from = $from;

		return $this;
	}

	public function setReturnPath($returnpath) {
		$this->returnpath = $returnpath;

		return $this;
	}

	public function setRecipientsCharset($charset) {
		$this->recipientsCharset = $charset;

		return $this;
	}

	public function setSubject($subject) {
		$this->subject = $subject;

		return $this;
	}

	public function setSubjectCharset($charset) {
		$this->subjectCharset = $charset;

		return $this;
	}

	public function setMessageFromString($text, $html = null) {
		$this->messagetext = $text;
		$this->messagehtml = $html;

		return $this;
	}

	public function setMessageFromFile($textfile, $htmlfile = null) {
		if(file_exists($textfile) && is_file($textfile) && is_readable($textfile)) {
			$this->messagetext = file_get_contents($textfile);
		} else {
			$this->messagetext = null;
		}
		if(file_exists($htmlfile) && is_file($htmlfile) && is_readable($htmlfile)) {
			$this->messagehtml = file_get_contents($htmlfile);
		} else {
			$this->messagehtml = null;
		}

		return $this;
	}

	public function setMessageFromURL($texturl, $htmlurl = null) {
		if($texturl !== null) {
			$this->messagetext = file_get_contents($texturl);
		} else {
			$this->messagetext = null;
		}
		if($htmlurl !== null) {
			$this->messagehtml = file_get_contents($htmlurl);
		} else {
			$this->messagehtml = null;
		}

		return $this;
	}

	public function setMessageCharset($textCharset, $htmlCharset = null) {
		$this->messageTextCharset = $textCharset;
		$this->messageHtmlCharset = $htmlCharset;

		return $this;
	}

	/**
	 * Add custom header - this works only with SendRawEmail
	 *
	 * @param string $header Your custom header
	 * @return SimpleEmailServiceMessage $this
	 * @link( Restrictions on headers, http://docs.aws.amazon.com/ses/latest/DeveloperGuide/header-fields.html)
	 */
	public function addCustomHeader($header) {
		$this->customHeaders[] = $header;

		return $this;
	}

	/**
	 * Add email attachment by directly passing the content
	 *
	 * @param string $name      The name of the file attachment as it will appear in the email
	 * @param string $data      The contents of the attachment file
	 * @param string $mimeType  Specify custom MIME type
	 * @param string $contentId Content ID of the attachment for inclusion in the mail message
	 * @param string $attachmentType    Attachment type: attachment or inline
	 * @return SimpleEmailServiceMessage $this
	 */
	public function addAttachmentFromData($name, $data, $mimeType = 'application/octet-stream', $contentId = null, $attachmentType = 'attachment') {
		$this->attachments[$name] = array(
			'name' => $name,
			'mimeType' => $mimeType,
			'data' => $data,
			'contentId' => $contentId,
			'attachmentType' => ($attachmentType == 'inline' ? 'inline; filename="' . $name . '"' : $attachmentType),
		);

		return $this;
	}

	/**
	 * Add email attachment by passing file path
	 *
	 * @param string $name      The name of the file attachment as it will appear in the email
	 * @param string $path      Path to the attachment file
	 * @param string $mimeType  Specify custom MIME type
	 * @param string $contentId Content ID of the attachment for inclusion in the mail message
	 * @param string $attachmentType    Attachment type: attachment or inline
	 * @return  boolean Status of the operation
	 */
	public function addAttachmentFromFile($name, $path, $mimeType = 'application/octet-stream', $contentId = null, $attachmentType = 'attachment') {
		if (file_exists($path) && is_file($path) && is_readable($path)) {
			$this->addAttachmentFromData($name, file_get_contents($path), $mimeType, $contentId, $attachmentType);
			return true;
		}
		return false;
	}


	/**
	 * Get the existence of attached inline messages
	 *
	 * @return boolean
	 */
	public function hasInlineAttachments()
	{
		foreach($this->attachments as $attachment) {
			if($attachment['attachmentType'] != 'attachment')
				return true;
		}
		return false;
	}

	/**
	 * Get the raw mail message
	 *
	 * @return string
	 */
	public function getRawMessage()
	{
		$boundary = uniqid(rand(), true);
		$raw_message = (count($this->customHeaders) > 0 ? join("\n", $this->customHeaders) . "\n" : '');
		$raw_message .= 'To:' . $this->encodeRecipients($this->to) . "\n";
		$raw_message .= 'From:' . $this->encodeRecipients($this->from) . "\n";
		if(!empty($this->replyto)) $raw_message .= 'Reply-To:' . $this->encodeRecipients($this->replyto) . "\n";

		if (!empty($this->cc)) {
			$raw_message .= 'CC: ' . $this->encodeRecipients($this->cc) . "\n";
		}
		if (!empty($this->bcc)) {
			$raw_message .= 'BCC: ' . $this->encodeRecipients($this->bcc) . "\n";
		}

		if($this->subject != null && strlen($this->subject) > 0) {
			$raw_message .= 'Subject: =?' . $this->subjectCharset . '?B?' . base64_encode($this->subject) . "?=\n";
		}

		$raw_message .= 'MIME-Version: 1.0' . "\n";
		$raw_message .= 'Content-type: ' . ($this->hasInlineAttachments() ? 'multipart/related' : 'Multipart/Mixed') . '; boundary="' . $boundary . '"' . "\n";
		$raw_message .= "\n--{$boundary}\n";
		$raw_message .= 'Content-type: Multipart/Alternative; boundary="alt-' . $boundary . '"' . "\n";

		if($this->messagetext != null && strlen($this->messagetext) > 0) {
			$charset = empty($this->messageTextCharset) ? '' : "; charset=\"{$this->messageTextCharset}\"";
			$raw_message .= "\n--alt-{$boundary}\n";
			$raw_message .= 'Content-Type: text/plain' . $charset . "\n\n";
			$raw_message .= $this->messagetext . "\n";
		}

		if($this->messagehtml != null && strlen($this->messagehtml) > 0) {
			$charset = empty($this->messageHtmlCharset) ? '' : "; charset=\"{$this->messageHtmlCharset}\"";
			$raw_message .= "\n--alt-{$boundary}\n";
			$raw_message .= 'Content-Type: text/html' . $charset . "\n\n";
			$raw_message .= $this->messagehtml . "\n";
		}
		$raw_message .= "\n--alt-{$boundary}--\n";

		foreach($this->attachments as $attachment) {
			$raw_message .= "\n--{$boundary}\n";
			$raw_message .= 'Content-Type: ' . $attachment['mimeType'] . '; name="' . $attachment['name'] . '"' . "\n";
			$raw_message .= 'Content-Disposition: ' . $attachment['attachmentType'] . "\n";
			if(!empty($attachment['contentId'])) {
				$raw_message .= 'Content-ID: ' . $attachment['contentId'] . '' . "\n";
			}
			$raw_message .= 'Content-Transfer-Encoding: base64' . "\n";
			$raw_message .= "\n" . chunk_split(base64_encode($attachment['data']), 76, "\n") . "\n";
		}

		$raw_message .= "\n--{$boundary}--\n";

		return base64_encode($raw_message);
	}

	/**
	 * Encode recipient with the specified charset in `recipientsCharset`
	 *
	 * @return string            Encoded recipients joined with comma
	 */
	public function encodeRecipients($recipient)
	{
		if (is_array($recipient)) {
			return join(', ', array_map(array($this, 'encodeRecipients'), $recipient));
		}

		if (preg_match("/(.*)<(.*)>/", $recipient, $regs)) {
			$recipient = '=?' . $this->recipientsCharset . '?B?'.base64_encode($regs[1]).'?= <'.$regs[2].'>';
		}

		return $recipient;
	}

	/**
	* Validates whether the message object has sufficient information to submit a request to SES.
	* This does not guarantee the message will arrive, nor that the request will succeed;
	* instead, it makes sure that no required fields are missing.
	*
	* This is used internally before attempting a SendEmail or SendRawEmail request,
	* but it can be used outside of this file if verification is desired.
	* May be useful if e.g. the data is being populated from a form; developers can generally
	* use this function to verify completeness instead of writing custom logic.
	*
	* @return boolean
	*/
	public function validate() {
		if(count($this->to) == 0)
			return false;
		if($this->from == null || strlen($this->from) == 0)
			return false;
		// messages require at least one of: subject, messagetext, messagehtml.
		if(($this->subject == null || strlen($this->subject) == 0)
			&& ($this->messagetext == null || strlen($this->messagetext) == 0)
			&& ($this->messagehtml == null || strlen($this->messagehtml) == 0))
		{
			return false;
		}

		return true;
	}
}