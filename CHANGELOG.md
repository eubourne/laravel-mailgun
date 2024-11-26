# Changelog

## v1.1.0

* **Enhanced Mail Sending Workflow:**

  Overridden Laravel's `MailManager` and `Mailer` classes to respect the `$mailer` property
on `Mailable` classes, allowing seamless mailer assignment directly within the mailable or
via the `$mailable->mailer($mailerName)` method.

* **Improved Test Email Details:**

  Updated the `mail:send` Artisan command to include the mailer name and queue name
in the test email content, aiding in better debugging and configuration validation.

## v1.0.0

Initial commit.
