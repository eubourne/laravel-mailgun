<p style="text-align: center"><img src="/assets/laravel-mailgun-card.jpg" alt="Laravel Mailgun"></p>

# Laravel Mailgun

[![Latest Version on Packagist](https://img.shields.io/packagist/v/eubourne/laravel-mailgun.svg?style=flat-square)](https://packagist.org/packages/eubourne/laravel-mailgun)

**Laravel Mailgun** is a custom Laravel Mailgun driver that enables you to configure individual domains for
multiple mailers, providing enhanced flexibility and precise control over email delivery.

- [Features](#features)
- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Utilities](#utilities)
- [License](#license)
- [Contributing](#contributing)
- [Contact](#contact)
---

## Features
- **Per-mailer Domain Configuration:** Assign unique domains to specific mailers, ideal for multi-domain email setups.
- **Powered by the Mailgun API:** Utilizes the official Mailgun API for reliable, secure email delivery.
- **Helper tools:** Includes a user-friendly Artisan command to send test emails, making it easy to verify 
email configurations and troubleshoot deliverability issues.
---

## Installation

To install the package, run:
```bash
composer require eubourne/laravel-mailgun
```

This package supports [Laravel's package auto-discovery](https://medium.com/@taylorotwell/package-auto-discovery-in-laravel-5-5-ea9e3ab20518) feature,
so no manual service provider registration is required.

## Basic Usage

### 1. Configure Mailgun API Settings
In your `config/services.php` file, add the Mailgun API credentials:
```php  
'mailgun-api' => [
    'secret' => env('MAILGUN_SECRET'),
    'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
],
```

### 2. Define a Mailer
Update or create a new mailer in your `config/mail.php` file, specifying the `mailgun-api` transport:
```php
'mailgun-tx' => [
    'transport' => env('MAIL_TX_TRANSPORT', 'mailgun-api'),
    'from' => [
        'address' => env('MAIL_TX_FROM_ADDRESS', env('MAIL_FROM_ADDRESS')),
        'name' => env('MAIL_FROM_NAME')
    ],
    'domain' => env('MAILGUN_DOMAIN'),
    'logger' => env('MAIL_LOG_CHANNEL'),
],
```

### 3. Set Environment Variables
Add the required Mailgun-specific values to your `.env` file.

### 4. Use the Mailer
When sending a mailable, specify your custom mailer:
```php
Mail::mailer('mailgun-tx')->to($user)->send(new OrderShipped($order));
```

#### Setting a Default Mailer for a Mailable
If you want to always send a specific mailable using a particular mailer,
you can define it with a public `$mailer` property in your mailable class:

```php
class OrderPlaced extends Mailable
{
    public $mailer = 'transactional';
    ...
}
```

Alternatively, you can use the `mailer($mailerName)` method on your mailable 
instance to set the mailer dynamically at runtime:
```php
$mailable = new OrderPlaced();
$mailable->mailer('transactional');
Mail::to($user)->send($mailable);
```

**Important Notes:**

* The mailer specified with the `mailer` property or `mailer()` method
will override the mailer used to initiate the send operation. 
* For instance, the following code will send the email using the `transactional` mailer,
even though the `promotional` mailer is used to send the email:
```php
$mailable = new OrderPlaced();
$mailable->mailer('transactional');
Mail::mailer('promotional')->to($user)->send($mailable);
```
This flexibility allows you to set a default mailer for each mailable while retaining the ability to override it dynamically.

---

## Utilities
The package comes with a helpful Artisan command for testing email configuration and deliverability:
```bash
php artisan mail:test {email}
```

### Whitelist Addresses
Before sending test emails, whitelist the recipient addresses by adding a `whitelist` key to your `config/mail.php` file:
```php
'whitelist' => ['john.doe@gmail.com']
```

### Example Usage
Send a test email to a whitelisted address:
```bash
php artisan mail:test john.doe@gmail.com
```

Specify a mailer for the test:
```bash
php artisan mail:test john.doe@gmail.com --mailer=mailgun-tx
```

Send the email through a specific queue:
```bash
php artisan mail:test john.doe@gmail.com --queue=mail
```
---

## License
This package is open-source and available for free under the [MIT license](http://opensource.org/licenses/MIT).

---

## Contributing
Feel free to submit issues or pull requests to help improve this package.

---

## Contact
For more information or support, please reach out via GitHub or email.
