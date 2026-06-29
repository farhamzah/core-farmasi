<?php

namespace App\Mail;

use App\Models\AccountRequest;
use App\Models\User;
use App\Models\UserAppAccess;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountRequestApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly AccountRequest $accountRequest,
        public readonly User $user,
        public readonly ?UserAppAccess $appAccess = null,
        public readonly ?string $passwordSetupUrl = null,
        public readonly int $passwordSetupExpiresInMinutes = 60,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Permohonan Akun Core Farmasi Disetujui',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-request-approved',
        );
    }
}
