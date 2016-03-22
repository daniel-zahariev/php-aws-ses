**Amazon Simple Email Service provides a simple way to send e-mails without having to maintain your own mail server.  Those PHP classes use the REST-based interface to that service.**

****

> This repository is a fork from version 0.8.2 of the [original classes](http://www.orderingdisorder.com/aws/ses/) developed by **Dan Myers**
> Read the [old docs here](README_old.md)

****

### Installation
Install the latest version with

	composer require daniel-zahariev/php-aws-ses

### Basic Usage

```php
<?php
	
require_once 'vendor/autoload.php';

$m = new SimpleEmailServiceMessage();
$m->addTo('recipient@example.com');
$m->setFrom('user@example.com');
$m->setSubject('Hello, world!');
$m->setMessageFromString('This is the message body.');

$ses = new SimpleEmailService('AccessKey', 'SecretKey');
print_r($ses->sendEmail($m));

// Successful response should print something similar to:
//Array(
//     [MessageId] => 0000012dc5e4b4c0-b2c566ad-dcd0-4d23-bea5-f40da774033c-000000
//     [RequestId] => 4953a96e-29d4-11e0-8907-21df9ed6ffe3
//)

```

### Recipients

```php
<?php

$m = new SimpleEmailServiceMessage();
// Add many Recipients
$m->addTo(array('dwight@example.com', 'angela@example.com'));

// You can either add one by one or pass an array to 'To' and 'CC'
$m->addCC('holly@example.com');
$m->addCC(array('kelly@example.com', 'ryan@example.com'));

// And 'BCC' and 'Reply-To' as well
$m->addBCC('michael@example.com');
$m->addBCC(array('kevin@example.com', 'oscar@example.com'));
$m->addReplyTo('andy@example.com');
$m->addReplyTo(array('stanley@example.com', 'erin@example.com'));


// Also add names to any of the Recipients lists
$m->addTo('Jim Carrey <jim@example.com>');

```

### Message body

```php
<?php

// Additionally you can set the content of the email via:
$m->setMessageFromFile('/path/to/some/file.txt');
$m->setMessageFromURL('http://example.com/somefile.txt');

// And have both Text and HTML version with:
$m->setMessageFromString($text, $html);
$m->setMessageFromFile($textfilepath, $htmlfilepath);
$m->setMessageFromURL($texturl, $htmlurl);

// Remember that setMessageFromString, setMessageFromFile, and setMessageFromURL are mutually exclusive. 
// If you call more than one, then whichever call you make last will be the message used.

// You can also set the encoding of the Subject and the Message Body
$m->setSubjectCharset('ISO-8859-1');
$m->setMessageCharset('ISO-8859-1');

```
The default is UTF-8 if you do not specify a charset, which is usually the right setting. You can read more information in the [SES API documentation](http://docs.amazonwebservices.com/ses/latest/APIReference/API_Content.html)

### Attachments

```php
<?php

$m->addAttachmentFromData('my_text_file.txt', 'Simple content', 'text/plain');
$m->addAttachmentFromFile('my_PFD_file.pdf', '/path/to/pdf/file', 'application/pdf');

// SendRawEmail is explicitly used when there are attachments:
$ses->sendEmail($m);
// Sending raw email can be enforsed with:
$ses->sendEmail($m, $use_raw_request = true);

// Now you can add an inline file in the message
$m->addAttachmentFromFile('logo.png','path/to/logo.png','application/octet-stream', '<logo.png>' , 'inline');
// and use it in the html version of the e-mail: <img src='cid:logo.png' />

```

### API Endpoints
Few [Regions and Amazon SES endpoints](http://docs.aws.amazon.com/ses/latest/DeveloperGuide/regions.html) are available and they can be used like this:

```php
<?php
$region_endpoint = SimpleEmailService::AWS_US_EAST_1;
$ses = new SimpleEmailService('AccessKey', 'SecretKey', $region_endpoint);

```

### Helper Methods

```php
<?php
// Get the addresses that have been verified in your AWS SES account
$ses->listVerifiedEmailAddresses();
// Delete a verified address
$ses->deleteVerifiedEmailAddress('user@example.com');
// Send a confirmation email in order to verify a new email
$ses->verifyEmailAddress('user@example.com');

// Get Send Quota
$ses->getSendQuota();
// Get Send Statistics
$ses->getSendStatistics()

```
See the documentation on [GetSendQuota](http://docs.amazonwebservices.com/ses/latest/APIReference/API_GetSendQuota.html) and [GetSendStatistics](http://docs.amazonwebservices.com/ses/latest/APIReference/API_GetSendStatistics.html) for more information on these calls.

### Errors
By default when Amazon SES API returns an error it will be triggered with [`trigger_error`](http://php.net/manual/en/function.trigger-error.php):

```php
<?php
// Set the default behaviour for handling errors
$trigger_error = true;
$ses = new SimpleEmailService('AccessKey', 'SecretKey', $region_endpoint, $trigger_error);

// Or overwrite the main setting on a single call
$use_raw_request = false;
$trigger_error = false;
$ses->sendEmail($m, $use_raw_request, $trigger_error);

```


### Changelog
v.0.8.6

- Removed dummy code
- Removed version from source files

v.0.8.5

 - A few issues are fixed #9, #10, #10
 - Pull request for Adding an inline file is merged
 - Pull request for fixing a 'From: ' field error with Raw messages is merged
 - Composer file added and submited to Packagist.org
 - Triggering an error is now optional (on by default)
 - Added class constants in `SimpleEmailService` for easy selection of region API endpoint

v.0.8.4

 - Added method `addCustomHeader` to class `SimpleEmailServiceMessage` for adding custom headers when the `SendRawEmail` call is used (#7)
 - `SendRawEmail` method can be enforced with a new parameter of `sendEmail` function
 - Recipients are now base64 encoded by default when the format is `Name <Email>` (#3)
 - Most of the notices should be cleared now (#5)


v.0.8.3

 - Made automatic use of `SendRawEmail` REST API call when there are attachments

v.0.8.2.

 - Inital impport

### Todo List

 - Fully document the class methods with phpdoc tags
 - Build documentation with phpDocumentor
 - Move examples to files
 - Make a [Composer](https://packagist.org/) package
 - Allow X-Headers usage
