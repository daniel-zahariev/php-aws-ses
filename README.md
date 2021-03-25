**Amazon Simple Email Service provides a simple way to send e-mails without having to maintain your own mail server. Those PHP classes use the REST-based interface to that service.**

[![Build Status](https://travis-ci.org/daniel-zahariev/php-aws-ses.svg?branch=master)](https://travis-ci.org/daniel-zahariev/php-aws-ses)
[![CircleCI](https://circleci.com/gh/daniel-zahariev/php-aws-ses/tree/master.svg?style=svg)](https://circleci.com/gh/daniel-zahariev/php-aws-ses/tree/master)

---

> This repository is a fork from version 0.8.2 of the [original classes](http://www.orderingdisorder.com/aws/ses/) developed by **Dan Myers**
> Read the [old docs here](README_old.md)

---

## Table of Contents

*   [Installation](#installation)
*   [Basic Usage](#basic-usage)
*   [Recipients](#recipients)
*   [Message body](#message-body)
*   [Attachments](#attachments)
*   [Sending Bulk Messages](#sending-bulk-messages)
*   [API Endpoints](#api-endpoints)
*   [Helper Methods](#helper-methods)

### Installation

Install the latest version with

    composer require daniel-zahariev/php-aws-ses

### Basic Usage

```php
<?php

require_once 'vendor/autoload.php';

$m = new SimpleEmailServiceMessage();
$m->addTo('Recipient Name <recipient@example.com>');
$m->setFrom('Sender <user@example.com>');
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

### Configuration Set and Message Tags

```php
<?php

// Set the configuration set
$m->setConfigurationSet('myConfigurationSet');

// Reset the configuration set
$m->setConfigurationSet(null);


// Set message tag
$m->setMessageTag('key', 'value');

// Get message tag
$tag = $m->getMessageTag('key');

// Remove message tag
$m->removeMessageTag('key');

// Set message tags in bulk - performs merge with current tags
$m->setMessageTags(array('key1' => 'value1', 'key2' => 'value2'));

// Get message tags
$tags = $m->getMessageTags();

// Remove all message tags
$m->removeMessageTags();
```

### Sending Bulk Messages

When hundreds of emails have to be sent in bulk it's best to use the Bulk mode which essentially reuses a CURL handler and reduces the number of SSL handshakes and this gives a better performance.

```php
<?php

// Enable bulk sending mode (reuse of CURL handler)
$ses->setBulkMode(true);

// Send the messages
foreach($messages as $message) {
	$ses->sendEmail($message);
}

// Disable bulk sending mode
$ses->setBulkMode(false);
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

### Request Signature Version

You can configure which version of the request signature should be used. [Version 4](https://docs.aws.amazon.com/general/latest/gr/sigv4_signing.html) is now supported and used by default.

```php
<?php

$signature_version = SimpleEmailService::REQUEST_SIGNATURE_V4;
$ses = new SimpleEmailService('AccessKey', 'SecretKey', $region_endpoint, $trigger_error, $signature_version);
```



### Changelog

v.0.9.5
*   Fix for arrays in request query params (#83) 

v.0.9.4
*   Fix for PHP8 errors (#81)

v.0.9.3

*   AWS Signature Version 4 is the default one (thanks to [VincentNikkelen](https://github.com/VincentNikkelen))
*   Added support for more regions (thanks to [VincentNikkelen](https://github.com/VincentNikkelen))  

v.0.9.2

*   Added support for AWS Signature Version 4

v.0.9.1

*   Added support for AWS SES Configuration Sets and Message Tags
*   Added caching mechanism in `SimpleEmailServiceMessage` to speed up bulk sending mode

v.0.9.0

*   Add parameter for raw message encoding

v.0.8.9

*   Merge pull request 32 from hlev/remove-to-requirement

v.0.8.8

*   Issues fixed: #24, #25, #30, #31
*   added a method `setBulkMode` in `SimpleEmailService` which can enable reuse of `SimpleEmailServiceRequest` object for bulk sending of requests to AWS SES
*   new methods in `SimpleEmailService`: `getVerifyPeer`, `setVerifyPeer`, `getVerifyHost`, `setVerifyHost`, `getBulkMode`, `setBulkMode`, `getRequestHandler` (protected)
*   methods marked as deprecated in `SimpleEmailService`: `enableVerifyHost`, `enableVerifyPeer`, `verifyHost`, `verifyPeer`
*   new methods in `SimpleEmailServiceMessage`: `clearTo`, `clearCC`, `clearBCC`, `clearReplyTo`, `clearRecipients`
*   new methods in `SimpleEmailServiceRequest`: `setVerb`, `clearParameters`, `getCurlHandler` (protected)
*   updated `validate` method in `SimpleEmailServiceMessage`
*   added some phpDocumentor blocks

v.0.8.7

*   Minor updates

v.0.8.6

*   Removed dummy code
*   Removed version from source files

v.0.8.5

*   A few issues are fixed #9, #10, #10
*   Pull request for Adding an inline file is merged
*   Pull request for fixing a 'From: ' field error with Raw messages is merged
*   Composer file added and submited to Packagist.org
*   Triggering an error is now optional (on by default)
*   Added class constants in `SimpleEmailService` for easy selection of region API endpoint

v.0.8.4

*   Added method `addCustomHeader` to class `SimpleEmailServiceMessage` for adding custom headers when the `SendRawEmail` call is used (#7)
*   `SendRawEmail` method can be enforced with a new parameter of `sendEmail` function
*   Recipients are now base64 encoded by default when the format is `Name <Email>` (#3)
*   Most of the notices should be cleared now (#5)

v.0.8.3

*   Made automatic use of `SendRawEmail` REST API call when there are attachments

v.0.8.2.

*   Inital impport

### Todo List

*   Fully document the class methods with phpdoc tags
*   Build documentation with phpDocumentor
*   Move examples to files
*   Make a [Composer](https://packagist.org/) package
*   Allow X-Headers usage

## Contributors

### Code Contributors

This project exists thanks to all the people who contribute. [[Contribute](CONTRIBUTING.md)].
<a href="https://github.com/daniel-zahariev/php-aws-ses/graphs/contributors"><img src="https://opencollective.com/php-aws-ses/contributors.svg?width=890&button=false" /></a>

### Financial Contributors

Become a financial contributor and help us sustain our community. [[Contribute](https://opencollective.com/php-aws-ses/contribute)]

#### Individuals

<a href="https://opencollective.com/php-aws-ses"><img src="https://opencollective.com/php-aws-ses/individuals.svg?width=890"></a>

#### Organizations

Support this project with your organization. Your logo will show up here with a link to your website. [[Contribute](https://opencollective.com/php-aws-ses/contribute)]

<a href="https://opencollective.com/php-aws-ses/organization/0/website"><img src="https://opencollective.com/php-aws-ses/organization/0/avatar.svg"></a>
<a href="https://opencollective.com/php-aws-ses/organization/1/website"><img src="https://opencollective.com/php-aws-ses/organization/1/avatar.svg"></a>
<a href="https://opencollective.com/php-aws-ses/organization/2/website"><img src="https://opencollective.com/php-aws-ses/organization/2/avatar.svg"></a>
<a href="https://opencollective.com/php-aws-ses/organization/3/website"><img src="https://opencollective.com/php-aws-ses/organization/3/avatar.svg"></a>
<a href="https://opencollective.com/php-aws-ses/organization/4/website"><img src="https://opencollective.com/php-aws-ses/organization/4/avatar.svg"></a>
<a href="https://opencollective.com/php-aws-ses/organization/5/website"><img src="https://opencollective.com/php-aws-ses/organization/5/avatar.svg"></a>
<a href="https://opencollective.com/php-aws-ses/organization/6/website"><img src="https://opencollective.com/php-aws-ses/organization/6/avatar.svg"></a>
<a href="https://opencollective.com/php-aws-ses/organization/7/website"><img src="https://opencollective.com/php-aws-ses/organization/7/avatar.svg"></a>
<a href="https://opencollective.com/php-aws-ses/organization/8/website"><img src="https://opencollective.com/php-aws-ses/organization/8/avatar.svg"></a>
<a href="https://opencollective.com/php-aws-ses/organization/9/website"><img src="https://opencollective.com/php-aws-ses/organization/9/avatar.svg"></a>

### License

MIT License

Copyright (c) 2011 Dan Mayers
Copyright (c) 2014 Daniel Zahariev

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
