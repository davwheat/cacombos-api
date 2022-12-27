<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Psr\Http\Message\UploadedFileInterface;

class SubmitCombos extends Mailable
{
    use Queueable, SerializesModels;

    protected ?string $fromUser;
    protected string $deviceName;
    protected string $deviceModel;
    protected string $deviceFirmware;
    protected string $comment;
    protected ?UploadedFileInterface $log;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(?string $fromUser, string $deviceName, string $deviceModel, string $deviceFirmware, string $comment, ?UploadedFileInterface $log)
    {
        $this->fromUser = $fromUser;
        $this->deviceName = $deviceName;
        $this->deviceModel = $deviceModel;
        $this->deviceFirmware = $deviceFirmware;
        $this->comment = $comment;
        $this->log = $log;

        if ($fromUser !== null) $this->replyTo($fromUser);
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: 'Combos submitted',
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            markdown: 'emails.submitcombos',
            with: [
                'fromUser' => $this->fromUser,
                'deviceName' => $this->deviceName,
                'deviceModel' => $this->deviceModel,
                'deviceFirmware' => $this->deviceFirmware,
                'comment' => $this->comment,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        if ($this->log === null) {
            return [];
        }

        return [
            Attachment::fromData(
                fn () => $this->log->getStream()->getContents(),
                name: $this->log->getClientFilename(),
            )
        ];
    }
}
