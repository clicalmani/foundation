<?php
namespace Clicalmani\Core\Mail;

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

    public function getLayout() : ?string;

    public function layoutDisabled() : bool;
}