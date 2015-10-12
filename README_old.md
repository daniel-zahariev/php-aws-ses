### Old Documentation

****

### Example usages


A bit of example code will be a good demonstration of how simple this class is to use. Please read through these examples, and then feel free leave a comment if you have any questions or suggestions.

First, you’ll need to create a SimpleEmailService class object:

    require_once('ses.php');
    $ses = new SimpleEmailService('Access Key Here', 'Secret Key Here');

If this is your first time using Simple Email Service, you will need to request verification of at least one e-mail address, so you can send messages:

    print_r($ses->verifyEmailAddress('user@example.com'));
    -------
    Array
    (
      [RequestId] => 1b086469-291d-11e0-85af-df1284f62f28
    )

Every request you make to SimpleEmailService will return a request id. This id may be useful if you need to contact AWS about any problems.  For brevity, I will omit the request id if it is the only value returned from a service call.

After you’ve requested verification, you’ll get an e-mail at that address with a link. Click the link to get your address approved.  Once you’ve done that, you can use it as the ‘From’ address in the e-mails you send through SES.  If you don’t have production access yet, you’ll also need to request verification for all addresses you want to send mail to.

If you want to see what addresses have been verified on your account, it’s easy:

    print_r($ses->listVerifiedEmailAddresses());
    -------
    Array
    (
      [RequestId] => 77128e89-291d-11e0-986f-43f07db0572a
      [Addresses] => Array
        (
          [0] => user@example.com
          [1] => recipient@example.com
        )
    )

Removing an address from the verified address list is just as easy:

    $ses->deleteVerifiedEmailAddress('user@example.com');

This call will return a request id if you need it.

The only thing left to do is send an e-mail, so let’s try it. First, you’ll need a SimpleEmailServiceMessage object.  Then, you’ll want to set various properties on the message.  For example:

    $m = new SimpleEmailServiceMessage();
    $m->addTo('recipient@example.com');
    $m->setFrom('user@example.com');
    $m->setSubject('Hello, world!');
    $m->setMessageFromString('This is the message body.');

    print_r($ses->sendEmail($m));
    -------
    Array
    (
      [MessageId] => 0000012dc5e4b4c0-b2c566ad-dcd0-4d23-bea5-f40da774033c-000000
      [RequestId] => 4953a96e-29d4-11e0-8907-21df9ed6ffe3
    )

And that’s all there is to it!

There are a few more things you can do with this class. You can make two informational queries, try these out:

    print_r($ses->getSendQuota());
    print_r($ses->getSendStatistics());

For brevity I will not show the output of those two API calls. This information will help you keep track of the health of your account. See the Simple Email Service documentation on [GetSendQuota](http://docs.amazonwebservices.com/ses/latest/APIReference/API_GetSendQuota.html) and [GetSendStatistics](http://docs.amazonwebservices.com/ses/latest/APIReference/API_GetSendStatistics.html) for more information on these calls.

You can set multiple to/cc/bcc addresses, either individually or all at once:

$m->addTo('jim@example.com');
$m->addTo(array('dwight@example.com', 'angela@example.com'));
$m->addCC('holly@example.com');
$m->addCC(array('kelly@example.com', 'ryan@example.com'));
$m->addBCC('michael@example.com');
$m->addBCC(array('kevin@example.com', 'oscar@example.com'));

These calls are cumulative, so in the above example, three addresses end up in each of the To, CC, and BCC fields.

You can also set one or more Reply-To addresses:

$m->addReplyTo('andy@example.com');
$m->addReplyTo(array('stanley@example.com', 'erin@example.com'));

You can set a return path address:

    $m->setReturnPath('noreply@example.com');

You can use the contents of a file as the message text instead of a string:

    $m->setMessageFromFile('/path/to/some/file.txt');
    // or from a URL, if allow_url_fopen is enabled:
    $m->setMessageFromURL('http://example.com/somefile.txt');

If you have both a text version and an HTML version of your message, you can set both:

    $m->setMessageFromString($text, $html);

Or from a pair of files instead:

    $m->setMessageFromFile($textfilepath, $htmlfilepath);
    // or from a URL, if allow_url_fopen is enabled:
    $m->setMessageFromURL($texturl, $htmlurl);

Remember that setMessageFromString, setMessageFromFile, and setMessageFromURL are mutually exclusive. If you call more than one, then whichever call you make last will be the message used.

Finally, if you need to specify the character set used in the subject or message:

$m->setSubjectCharset('ISO-8859-1');
$m->setMessageCharset('ISO-8859-1');

The default is UTF-8 if you do not specify a charset, which is usually the right setting. You can read more information in the [SES API documentation](http://docs.amazonwebservices.com/ses/latest/APIReference/API_Content.html).

****
This library ~~does not support~~ **now supports** the `SendRawEmail` call, which means you ~~cannot~~ **can send emails with attachments** ~~or custom headers, and unfortunately I will be unable to add that support~~.

The `SendRawEmail` call is used automatically when there are attachments:

    $m->addAttachmentFromData('my_text_file.txt', 'Simple content', 'text/plain');
    $m->addAttachmentFromFile('my_PFD_file.pdf', '/path/to/pdf/file', 'application/pdf');
    // SendRawEmail call will now be used:
    $ses->sendEmail($m);

