# php-mailing-api

Very light weight PHP API covering most important parts of [RFC-4021](https://tools.ietf.org/html/rfc4021), the worldwide standard at this moment for email sending, along with [RFC-6376](https://tools.ietf.org/html/rfc6376) for digital signatures.

It comes with just three classes, all belonging to Lucinda\Mail namespace:

- [Address](https://github.com/aherne/php-mailing-api/blob/old/src/Address.php): encapsulates an email address, composed by value of email and name of user associated with it 
- [DKIM](https://github.com/aherne/php-mailing-api/blob/old/src/DKIM.php): creates a DKIM-Signature header using on a heavily refactored version of [php-mail-signature](https://github.com/louisameline/php-mail-signature) classes
- [Exception](https://github.com/aherne/php-mailing-api/blob/old/src/Exception.php): encapsulates any logical error that prevents email from being sent
- [Message](https://github.com/aherne/php-mailing-api/blob/old/src/Message.php): encapsulates an email message

The entire logic of email message is encapsulated by class **[Message](https://github.com/aherne/php-mailing-api/blob/old/src/Message.php)** via following public methods:

| Method | Description |
| --- | --- |
| [__construct](#__construct) | Constructs an email message by subject and body |
| [addAttachment](#addAttachment) | Adds an attachment to message body |
| [setFrom](#setFrom) | Sets message sender's address, as seen in recipients' mail boxes |
| [setSender](#setSender) | Sets message sender's address, as seen by recipients' mail server  |
| [setReplyTo](#setReplyTo) | Adds address message receiver should reply to |
| [addTo](#addTo) | Adds address to send mail to (**mandatory**) |
| [addBCC](#addBCC) | Adds address to discreetly send a copy of message to (invisible to others) |
| [addCC](#addCC) | Adds address to publicly send a copy of message to |
| [setContentType](#setContentType) | Sets message body content type (**strongly recommended**) |
| [setDate](#setDate) | Sets time message was sent at as date header |
| [setMessageID](#setMessageID) | Sets domain name in order to generate a message ID (**strongly recommended**) |
| [setSignature](#setSignature) | Sets a DKIM-Signature header is to be generated (**strongly recommended**)  |
| [addCustomHeader](#addCustomHeader) | Sets a custom header to send in email message |
| [send](#send) | Sends message to destination |

Simple example:

```php
$message = new \Lucinda\Mail\Message("test subject", "<p>Hello, <strong>world</strong>!</p>");
$message->setMessageID("example.com"); // recommended to prevent message being labeled as spam
$message->setDate(time()); // signals that message was written now
$message->setContentType("text/html", "UTF-8"); // recommended, unless message is ASCII plaintext
$message->addTo(new Address("receiver@asd.com")); // mandatory
$message->setReplyTo(new Address("sender@example.com", "John Doe")); // recommended if message can be replied to
$message->send();
```

## __construct<a href="__construct"></a>

This method constructs a mail message by following arguments:

| Name | Type | Description |
| --- | --- | --- |
| $subject | string | Subject of email message (**mandatory**). Must be single-lined! |
| $body | string |  Body of email message (**mandatory**). Can be multi-lined! |

## addAttachment<a href="addAttachment"></a>

Adds an attachment to message body based on argument:

| Name | Type | Description |
| --- | --- | --- |
| $path | string | Absolute disk path of file to be attached. |

Example:

```php
$message->addAttachment("/foo/bar/baz.jpg");
```

## setFrom<a href="setFrom"></a>

Sets address of message author as seen by recipients (see: [from](https://tools.ietf.org/html/rfc4021#section-2.1.2) header) based on argument:

| Name | Type | Description |
| --- | --- | --- |
| $address | Address | Address that appears as message author |

Usually calling this method is not necessary, unless developer wants a different *from* address from that default.

Example:

```php
$message->setFrom(new \Lucinda\Mail\Address("sender@example.com", "Example.com Team"));
```

## setSender<a href="setSender"></a>

Sets address of message author as seen by destination mail server (see: [from](https://tools.ietf.org/html/rfc4021#section-2.1.3) header) based on argument:

| Name | Type | Description |
| --- | --- | --- |
| $address | Address | Address that appears as message author |

Should be same as *from* address, unless mail server is sending messages on behalf of someone else.

Example:

```php
$message->setSender(new \Lucinda\Mail\Address("sender@example.com", "Example.com Team"));
```

## setReplyTo<a href="setReplyTo"></a>

Sets address to send message replies to (see: [Reply-To](https://tools.ietf.org/html/rfc4021#section-2.1.4) header) based on argument:

| Name | Type | Description |
| --- | --- | --- |
| $address | Address | Address to send reply to |

Should always be set IF we desire messages to be replied to.

Example:

```php
$message->setReplyTo(new \Lucinda\Mail\Address("sender@example.com", "Example.com Team"));
```

## addTo<a href="addTo"></a>

Adds an address to send message to (see: [To](https://tools.ietf.org/html/rfc4021#section-2.1.5) header) based on argument:

| Name | Type | Description |
| --- | --- | --- |
| $address | Address | Address to send message to |

At least one address must always be set!

Example:

```php
$message->addTo(new \Lucinda\Mail\Address("destination@server.com"));
```

## addCC<a href="addCC"></a>

Adds an address to send a copy of message to allowing others to notice (see: [Cc](https://tools.ietf.org/html/rfc4021#section-2.1.6) header) based on argument:

| Name | Type | Description |
| --- | --- | --- |
| $address | Address | Address to send copy of message to |

Example:

```php
$message->addCC(new \Lucinda\Mail\Address("destination@server.com"));
```

## addBCC<a href="addBCC"></a>

Adds an address to send a copy of message to without others to notice (see: [Bcc](https://tools.ietf.org/html/rfc4021#section-2.1.7) header):

| Name | Type | Description |
| --- | --- | --- |
| $address | Address | Address to send copy of message to |

Example:

```php
$message->addBCC(new \Lucinda\Mail\Address("destination@server.com"));
```

## setContentType<a href="setContentType"></a>

Sets message body's content type and character set by following arguments (see: [Content-Type](https://tools.ietf.org/html/rfc4021#section-2.2.5) header):

| Name | Type | Description |
| --- | --- | --- |
| $contentType | string | Message body content type |
| $charset | string | Character set of message body |

Example:

```php
$message->setContentType("text/html", "UTF-8");
```

## setDate<a href="setDate"></a>

Sets custom date message was sent at based on argument (see: [Date](https://tools.ietf.org/html/rfc4021#section-2.1.1) header):

| Name | Type | Description |
| --- | --- | --- |
| $date | int | UNIX time at which message was written |

Usually it is not necessary to send this header unless you want to trick receiver(s) it originated at a different date.

Example:

```php
$message->setDate(time()-1000);
```

## setMessageID<a href="setMessageID"></a>

Sets unique ID of message to send based on argument (see: [Message-ID](https://tools.ietf.org/html/rfc4021#section-2.1.8) header):

| Name | Type | Description |
| --- | --- | --- |
| $domainName | string | Your server's domain name. |

It is **strongly recommended** to send this header in order to prevent having your message labeled as spam by recipient mail servers!

Example:

```php
$message->setMessageID("example.com");
```

## setSignature<a href="setSignature"></a>

Sets a digital signature of message to send based on arguments (see: [DKIM-Signature](https://tools.ietf.org/html/rfc6376) header):

| Name | Type | Description |
| --- | --- | --- |
| $rsaPrivateKey | string | RSA private key to sign messages with |
| $rsaPassphrase | string | Password RSA private key was created with, if any (use "" if none) |
| $domainName | string | Your server's domain name (same as that used by *Message-ID*). |
| $dnsSelector | string | Name of specific DKIM public key record in your DNS |
| $signedHeaders | array | List of headers that participate in signature |

In order to prevent having your message labeled as spam by recipient mail servers, setting this header is **strongly recommended**! To get values for params above, follow this guide:

- Go to [https://tools.socketlabs.com/dkim/generator](https://tools.socketlabs.com/dkim/generator)
    - fill *hostname*, which will be value of **$domainName** above (eg: "example.com")
    - write a **$dnsSelector** "subdomain" (eg: "dkim")
    - hit on GENERATE button
- Create a DNS TXT record for **$selector**._domainkey.**$domainName**. where value is string shown under "Step 2: Create your public DNS record"
- Store private key shown under "Step 3: Save the private key to your SMTP server" section somewhere. This will become value of **$rsaPrivateKey**
- Since you generated a key without a password **$rsaPassphrase** will always be an empty ""
- Now finally you must choose list of header names to generate signature from (eg: ["From", "Message-ID", "Content-Type"]) using **$signedHeaders**. See: [recommendations](http://dkim.org/specs/rfc4871-dkimbase.html#choosing-header-fields) for what should be chosen!

The algorithm used in generating DKIM-Signature header has been taken from [php-mail-signature](https://github.com/louisameline/php-mail-signature) and refactored completely because original was chaotic and poorly programmed. The end result was class [DKIM](https://github.com/aherne/php-mailing-api/blob/old/src/DKIM.php)!

Example:

```php
$message->setSignature("-----BEGIN RSA PRIVATE KEY----- ... -----END RSA PRIVATE KEY-----", "", "example.com", "dkim", [
    "From",
    "Reply-To",
    "Subject",
    "To"
]);
```

## addCustomHeader<a href="addCustomHeader"></a>

Adds a [RFC-4021](https://tools.ietf.org/html/rfc4021) header not covered by commands above using following arguments:

| Name | Type | Description |
| --- | --- | --- |
| $name | string | Name of header |
| $value | string | Value of header |

Example:

```php
$message->addCustomHeader("List-Unsubscribe", "<mailto: unsubscribe@example.com>");
```

Above command adds a [List-Unsubscribe](https://tools.ietf.org/html/rfc4021#section-2.1.37) header  which allows users to unsubscribe from mailing lists by a button click.

## send<a href="send"></a>

Packs message body and headers, compiles latter with DKIM-Signature (if available) and sends mail to destination.

Example:

```php
$message->send();
```
