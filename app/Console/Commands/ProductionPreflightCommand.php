<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ProductionPreflightCommand extends Command
{
    protected $signature = 'pebbles:preflight';

    protected $description = 'Run production readiness checks for Pebbles (OK / WARN / FAIL).';

    private int $failures = 0;

    public function handle(): int
    {
        $this->info('Pebbles production preflight');
        $this->newLine();

        $this->checkAppKey();
        $this->checkDebugInProduction();
        $this->checkStorageLink();
        $this->checkQueueConnection();
        $this->checkMailer();
        $this->checkPaymentProofDisk();

        $this->newLine();

        if ($this->failures > 0) {
            $this->error("Preflight finished with {$this->failures} FAIL(s).");

            return self::FAILURE;
        }

        $this->info('Preflight finished with no failures.');

        return self::SUCCESS;
    }

    private function checkAppKey(): void
    {
        $key = (string) config('app.key');

        if ($key === '') {
            $this->failCheck('APP_KEY', 'Application key is not set.');

            return;
        }

        $this->ok('APP_KEY', 'Application key is set.');
    }

    private function checkDebugInProduction(): void
    {
        $isProduction = app()->environment('production');
        $debug = (bool) config('app.debug');

        if ($isProduction && $debug) {
            $this->warnCheck('APP_DEBUG', 'APP_DEBUG is true while APP_ENV is production.');

            return;
        }

        $this->ok(
            'APP_DEBUG',
            $isProduction
                ? 'APP_DEBUG is false in production.'
                : 'APP_DEBUG check skipped outside production (current: '.app()->environment().').',
        );
    }

    private function checkStorageLink(): void
    {
        $link = public_path('storage');

        if (! File::exists($link) && ! is_link($link)) {
            $this->warnCheck('STORAGE_LINK', 'public/storage link is missing (run php artisan storage:link).');

            return;
        }

        $this->ok('STORAGE_LINK', 'public/storage link exists.');
    }

    private function checkQueueConnection(): void
    {
        $connection = (string) config('queue.default');

        if ($connection === '') {
            $this->failCheck('QUEUE', 'Queue default connection is not configured.');

            return;
        }

        if ($connection === 'sync' && app()->environment('production')) {
            $this->warnCheck('QUEUE', 'Queue connection is "sync" in production — background jobs will block requests.');

            return;
        }

        $this->ok('QUEUE', "Queue connection is \"{$connection}\".");
    }

    private function checkMailer(): void
    {
        $mailer = (string) config('mail.default');

        if ($mailer === '') {
            $this->failCheck('MAIL', 'Default mailer is not configured.');

            return;
        }

        if (in_array($mailer, ['array', 'log'], true) && app()->environment('production')) {
            $this->warnCheck('MAIL', "Mailer is \"{$mailer}\" in production — outbound mail will not be delivered.");

            return;
        }

        $this->ok('MAIL', "Default mailer is \"{$mailer}\".");
    }

    private function checkPaymentProofDisk(): void
    {
        $disk = (string) config('pebbles.payment_proof_disk', 'local');
        $visibility = config("filesystems.disks.{$disk}.visibility");
        $root = (string) config("filesystems.disks.{$disk}.root", '');

        if (! config()->has("filesystems.disks.{$disk}")) {
            $this->failCheck('PAYMENT_PROOF_DISK', "Configured disk \"{$disk}\" does not exist.");

            return;
        }

        if ($disk === 'public' || $visibility === 'public') {
            $this->failCheck(
                'PAYMENT_PROOF_DISK',
                "Payment proof disk \"{$disk}\" is public — proofs must use a private disk.",
            );

            return;
        }

        if ($root !== '' && str_contains(str_replace('\\', '/', $root), '/app/public')) {
            $this->failCheck(
                'PAYMENT_PROOF_DISK',
                "Payment proof disk \"{$disk}\" roots under storage/app/public.",
            );

            return;
        }

        $this->ok('PAYMENT_PROOF_DISK', "Payment proof disk \"{$disk}\" is not public.");
    }

    private function ok(string $check, string $message): void
    {
        $this->line("<fg=green>OK</>    [{$check}] {$message}");
    }

    private function warnCheck(string $check, string $message): void
    {
        $this->line("<fg=yellow>WARN</>  [{$check}] {$message}");
    }

    private function failCheck(string $check, string $message): void
    {
        $this->failures++;
        $this->line("<fg=red>FAIL</>  [{$check}] {$message}");
    }
}
