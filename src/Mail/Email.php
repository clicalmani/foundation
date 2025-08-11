<?php
namespace Clicalmani\Foundation\Mail;

use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mailer\Header\TagHeader;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Symfony\Component\Mime\Header\DateHeader;
use Symfony\Component\Mime\Header\UnstructuredHeader;

class Email extends SymfonyEmail
{
    /**
     * Create a new email instance.
     *
     * @param string $subject
     * @param string $html
     */
    public function __construct(string $subject, string $html)
    {
        parent::__construct();
        $this->subject($subject)
             ->html($html);
        
        if ($from = config('mail.from')) {
            $this->from(new Address($from['address'], $from['name'] ?? null));
        }

        if ($to = config('mail.to')) {
            $this->to(new Address($to['address'], $to['name'] ?? null));
        }

        if ($reply_to = config('mail.reply_to')) {
            $this->replyTo(new Address($reply_to['address'], $reply_to['name'] ?? null));
        }

        if ($cc = config('mail.cc')) {
            $this->cc(new Address($cc['address'], $cc['name'] ?? null));
        }

        if ($bcc = config('mail.bcc')) {
            $this->bcc(new Address($bcc['address'], $bcc['name'] ?? null));
        }

        if ($tag_headers = config('mail.headers.tags', [])) {
            foreach ($tag_headers as $tag) {
                $this->getHeaders()->add(new TagHeader($tag));
            }
        }

        if ($metadata_headers = config('mail.headers.metadata', [])) {
            foreach ($metadata_headers as $key => $value) {
                $this->getHeaders()->add(new MetadataHeader($key, $value));
            }
        }

        if ($headers = config('mail.headers.mailbox', [])) {
            foreach ($headers as $key => $value) {
                if (is_array($value)) {
                    $this->getHeaders()->addMailboxListHeader($key, $value);
                } else {
                    $this->getHeaders()->addMailboxHeader($key, $value);
                }
            }
        }

        if ($dates = config('mail.headers.dates', [])) {
            foreach ($dates as $key => $date) {
                $this->getHeaders()->add(new DateHeader($key, $date));
            }
        }

        if ($unstructured_headers = config('mail.headers.unstructured', [])) {
            foreach ($unstructured_headers as $key => $value) {
                $this->getHeaders()->add(new UnstructuredHeader($key, $value));
            }
        }

        if ($paths = config('mail.headers.paths', [])) {
            foreach ($paths as $key => $path) {
                $this->getHeaders()->addPathHeader($key, $path);
            }
        }
    }
}