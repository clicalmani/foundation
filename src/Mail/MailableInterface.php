<?php
namespace Clicalmani\Foundation\Mail;

interface MailableInterface
{
    /**
     * Set the view template and data for the email.
     *
     * @param string $template The view template name.
     * @param array $data The data to be passed to the view.
     * @return self
     */
    public function view(string $template, array $data = []): self;

    /**
     * Set the subject of the email.
     *
     * @param string $subject The subject of the email.
     * @return self
     */
    public function subject(string $subject): self;

    /**
     * Set the recipient of the email.
     *
     * @param string $email The recipient's email address.
     * @param string|null $name The recipient's name (optional).
     * @return self
     */
    public function to(string $email, ?string $name = null): self;

    /**
     * Attach a file to the email from a given path.
     *
     * @param string $path The file path to attach.
     * @param string|null $name The name of the attachment (optional).
     * @param string|null $contentType The content type of the attachment (optional).
     * @return self
     */
    public function attachFromPath(string $path, ?string $name = null, ?string $contentType = null);
}