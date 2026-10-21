<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenericMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subjectLine;
    public $htmlContent;
    public $attachments; // <-- New property
    //public $bcc;
    /**
     * @param string $subjectLine
     * @param string $htmlContent
     * @param array $attachments // Each item: ['path' => '/full/path/to/file', 'as' => 'filename.pdf', 'mime' => 'application/pdf']
     */
    public function __construct($subjectLine, $htmlContent, $attachments = [])
    {
        Log::info(" GenericMail constructor called", compact('subjectLine', 'htmlContent', 'attachments'));

        $this->subjectLine = $subjectLine;
        $this->htmlContent = $htmlContent;
        $this->attachments = $attachments;
        //$this->bcc = $bcc;
    }

    public function build()
    {
        Log::info('GenericMail email...', ['subject' => $this->subjectLine,'body' => $this->htmlContent,'attachments' => $this->attachments]);

        $email = $this->subject($this->subjectLine)
                      ->html($this->htmlContent);
        // if (!empty($this->bcc)) {
        //     $email->bcc($this->bcc);
        // }

        foreach ($this->attachments as $file) {
            $email->attach($file['file'], $file['options'] ?? []);
        }
        return $email;
    }
}
