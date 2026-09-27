<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\TeamInvitation as TeamInvitationModel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Invites an email address to join a team, with a signed link to accept.
 *
 * Replaces Jetstream's mailable, which only accepts Jetstream's own invitation model.
 */
class TeamInvitation extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public TeamInvitationModel $invitation) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Team Invitation'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.team-invitation',
            with: ['acceptUrl' => URL::signedRoute('team-invitations.accept', ['invitation' => $this->invitation])],
        );
    }
}
