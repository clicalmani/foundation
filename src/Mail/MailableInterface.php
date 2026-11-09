<?php
namespace Clicalmani\Core\Mail;

interface MailableInterface
{
    /**
     * Set the view template and data for the email.
     *
     * @param string $template The view template name.
     * @return self
     */
    public function view(string $template): self;

    /**
     * Set the plain text body of the email.
     *
     * @param string $body The plain text body.
     * @param string $charset The character set (default is 'utf-8').
     * @return self
     */
    public function text(string $body, string $charset = 'utf-8'): self;

    /**
     * Set the subject of the email.
     *
     * @param string $subject The subject of the email.
     * @return self
     */
    public function subject(string $subject): self;

    public function getLayout() : ?string;

    public function layoutDisabled() : bool;

    /**
     * Set the layout for the email.
     *
     * @param string $layout The layout name.
     * @return self
     */
    public function with(array $data = []): self;
}