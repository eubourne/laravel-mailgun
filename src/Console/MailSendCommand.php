<?php

namespace EuBourne\LaravelMailgun\Console;

use EuBourne\LaravelMailgun\Mail\TestMail;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use function Laravel\Prompts\text;

class MailSendCommand extends CommandAbstract implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:send
                            {address : The email address to send the email to}
                            {--subject= : The subject of the email}
                            {--body= : The body of the email}
                            {--from= : The email address to send the email from}
                            {--cc=* : The email address(es) to CC}
                            {--bcc=* : The email address(es) to BCC}
                            {--tag=* : The tag(s) to add}
                            {--queue= : The queue to dispatch the email to}
                            {--mailer= : The mailer to use}
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send test email message';

    /**
     * Configuration repository.
     *
     * @var Repository
     */
    protected Repository $config;

    public function __construct()
    {
        parent::__construct();

        $this->config = App::make('config');
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $input = $this->validate($this->getInputData());
            ['address' => $address, 'mailer' => $mailer, 'queue' => $queue] = $input;

            $this->displayMailingInfo($input);

            $mail = (new TestMail($input))->mailer($mailer);

            if ($queue) {
                Mail::to($address)->queue($mail->onQueue($queue));
                $this->components->info('Email to ' . $address . ' has been queued to ' . $queue);
            } else {
                Mail::to($address)->send($mail);
                $this->components->success('Email has been sent to ' . $address);
            }
        } catch (\InvalidArgumentException $e) {
            $this->displayWhitelistInfo($e->getMessage());
        } catch (\Exception $e) {
            $this->components->error($e->getMessage());
        }
    }

    /**
     * Get the input data from the command arguments and options.
     *
     * @return array
     */
    protected function getInputData(): array
    {
        $mailer = $this->option('mailer') ?? $this->config->get('mail.default');

        return [
            'address' => $this->argument('address'),
            'subject' => $this->option('subject') ?? "{$this->config->get('app.name')} Test Email",
            'body' => $this->option('body') ?? 'This is a test email',
            'from' => $this->parseEmailAddress($this->option('from'))
                ?: $this->getDefaultFromAddress($mailer),
            'cc' => $this->option('cc') ?? [],
            'bcc' => $this->option('bcc') ?? [],
            'tag' => $this->option('tag') ?? [],
            'mailer' => $mailer,
            'queue' => $this->option('queue') ?? null,
        ];
    }

    /**
     * Validate the input data.
     *
     * @param array $data
     * @return array
     */
    protected function validate(array $data): array
    {
        $rules = [
            'address' => 'required|email:rfc',
            'subject' => 'required|string',
            'body' => 'required|string',
            'from.address' => 'required|email:rfc',
            'cc' => 'array',
            'cc.*' => 'email:rfc',
            'bcc' => 'array',
            'bcc.*' => 'email:rfc',
            'tag' => 'array',
            'mailer' => 'required',
        ];

        while (true) {
            try {
                Validator::validate($data, $rules);
            } catch (ValidationException $e) {
                $errors = $e->validator->errors()->messages();

                // Get the first error
                $field = array_key_first($errors);

                // Request the user to input the correct value
                $validationRules = $rules[preg_replace('/\.\d/', '.*', $field)];
                $isRequired = str_contains($validationRules, 'required');

                $value = text(
                    label: $errors[$field][0],
                    default: Arr::get($data, $field) ?? '',
                    validate: $validationRules,
                    hint: $isRequired ? '' : 'Leave the field empty to skip'
                );

                // Update the data array with the new value
                Arr::set($data, $field, $value);

                // Revalidate the data
                continue;
            }

            break;
        }

        // Check that all specified addresses are whitelisted
        $addresses = array_filter([$data['address'], ...$data['cc'], ...$data['bcc']]);
        foreach ($addresses as $address) {
            if (!$this->isAddressAllowed($address)) {
                throw new \InvalidArgumentException("Address '{$address}' is not allowed to send emails to.");
            }
        }

        // Return the validated data
        return array_map(
            fn(string|array|null $value) => is_array($value)
                ? array_filter($value)
                : ($value ? trim($value) : null),
            $data);
    }

    /**
     * Parse the email address input.
     *
     * @param string|null $input
     * @return array|null
     */
    protected function parseEmailAddress(string|null $input): array|null
    {
        if (!$input) {
            return null;
        }

        $matches = [];

        // Regex to match "John Doe <email@address.com>" format
        if (preg_match('/^(.*)<(.+@.+\..+)>$/', $input, $matches)) {
            return [
                'name' => trim($matches[1]),  // Capture group 1: John Doe
                'address' => trim($matches[2]) // Capture group 2: email@address.com
            ];
        }

        // If no match, assume input is just the email address
        return [
            'name' => '',
            'address' => trim($input)
        ];
    }

    /**
     * Get the default from address for the mailer.
     *
     * @param string $mailer
     * @return array|null
     */
    protected function getDefaultFromAddress(string $mailer): array|null
    {
        return $this->config->get('mail.mailers.' . $mailer . '.from')
            ?: $this->config->get('mail.from');
    }

    /**
     * Display the mailing information.
     *
     * @param array $input
     * @return void
     */
    protected function displayMailingInfo(array $input): void
    {
        ['address' => $address, 'subject' => $subject, 'from' => $from, 'cc' => $cc, 'bcc' => $bcc, 'tag' => $tags, 'mailer' => $mailer, 'queue' => $queue] = $input;

        $transport = $this->config->get('mail.mailers.' . $mailer . '.transport')
            ?: $this->config->get('mail.mailers.failover.transport');

        $this->newLine();

        $this->components->twoColumnDetail('Mailer', $mailer);
        $this->components->twoColumnDetail('Transport', $transport);

        if ($queue) {
            $this->components->twoColumnDetail('Queue', $this->format(text: $queue, color: static::QUEUE));
        }

        $this->newLine();
        $this->line($this->format('  Email Details', static::MUTED));
        $this->components->twoColumnDetail('Subject', $this->format(text: $subject, color: static::SUBJECT, bold: true));
        $this->components->twoColumnDetail('From', $this->formatEmail($from['address'], $from['name'], static::ADDRESS));
        $this->components->twoColumnDetail('To', $this->formatEmail(address: $address, color: static::ADDRESS));

        if (count($cc)) {
            $this->components->twoColumnDetail('CC', implode(', ', array_map(
                fn(string $email) => $this->formatEmail(address: $email, color: static::SECONDARY_ADDRESS),
                $cc
            )));
        }

        if (count($bcc)) {
            $this->components->twoColumnDetail('BCC', implode(', ', array_map(
                fn(string $email) => $this->formatEmail(address: $email, color: static::SECONDARY_ADDRESS),
                $bcc
            )));
        }

        if (count($tags)) {
            $this->components->twoColumnDetail('Tags', implode(', ', array_map(
                fn(string $email) => $this->format(text: $email, color: static::TAGS),
                $tags
            )));
        }
    }

    /**
     * Format the email address.
     *
     * @param string $address
     * @param string|null $name
     * @param string|null $color
     * @param bool $bold
     * @return string
     */
    protected function formatEmail(string $address, ?string $name = null, string $color = null, bool $bold = false): string
    {
        return $name
            ? "{$name} <{$this->format(text: $address, color: $color, bold: $bold)}>"
            : $this->format(text: $address, color: $color, bold: $bold);
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array<string, string>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'address' => fn() => text(
                label: 'What email address should the email be sent to?',
                validate: 'required|email:rfc'
            )
        ];
    }

    /**
     * Check if the address is allowed to send emails.
     *
     * @param string $address
     * @return bool
     */
    protected function isAddressAllowed(string $address): bool
    {
        $whitelist = $this->config->get('mail.whitelist', []);
        return in_array($address, $whitelist);
    }

    protected function displayWhitelistInfo(string $errorMessage): void
    {
        $this->components->error($errorMessage);

        $this->line("  To be able to send emails to this address, add it to the whitelist in your `{$this->format('config/mail.php', static::COLOR_GREEN)}`:");
        $this->line("  '{$this->format('whitelist', static::COLOR_GREEN)}' => [");
        $this->line("    {$this->format('<address1>', static::COLOR_GREEN)}, {$this->format('<address2>', static::COLOR_GREEN)}, ...");
        $this->line("  ],");
        $this->newLine();
    }
}
