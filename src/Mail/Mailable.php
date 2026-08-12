<?php

namespace Clicalmani\Foundation\Mail;

/**
 * Class Mailable
 * 
 * Abstract base class providing a fluent configuration interface for defining 
 * transaction-driven application outbound emails, structural templates, attachments, 
 * and routing recipient envelopes.
 * 
 * @package Clicalmani\Foundation\Mail
 * @author @clicalmani
 */
abstract class Mailable implements MailableInterface
{
    /**
     * The template view file identifier used to render the electronic body content.
     * 
     * @var string
     */
    public string $template = '';

    /**
     * Associative key-value data container passed down into the rendering engine view scope.
     * 
     * @var array<string, mixed>
     */
    public array $data = [];

    /**
     * The subject header line text summarizing the message contents.
     * 
     * @var string
     */
    public string $subject = '(No Subject)';

    /**
     * The targeted recipient collection detailing the route endpoint address and optional descriptive name.
     * 
     * @var array{0: string, 1: string|null}|array<empty>
     */
    public array $to = [];

    /**
     * Collection containing structural metadata parameters for absolute filesystem attachments.
     * 
     * @var array<int, array{0: string, 1: string|null, 2: string|null}>
     */
    public array $pathAttachments = [];

    /**
     * Binds a dedicated template layout identity path alongside an associated rendering payload to the instance.
     * 
     * @param string $template The relative file path key or reference identifying the template view resource.
     * @param array<string, mixed> $data Dynamic payload context variables mapped to the template scope.
     * @return self The current configured mailable instance to sustain fluent call chains.
     */
    public function view(string $template, array $data = []): self
    {
        $this->template = $template;
        $this->data     = $data;
        return $this;
    }

    /**
     * Configures a custom descriptive subject string layout for the outbound message metadata.
     * 
     * @param string $subject The clear text description identifying the incoming message intent.
     * @return self The current configured mailable instance to sustain fluent call chains.
     */
    public function subject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Configures the delivery endpoint criteria routing the outbound message to a specified recipient.
     * 
     * @param string $email The targeted destination internet mailbox email address string.
     * @param string|null $name The human-readable contact description name representing the recipient.
     * @return self The current configured mailable instance to sustain fluent call chains.
     */
    public function to(string $email, ?string $name = null): self
    {
        $this->to = [$email, $name];
        return $this;
    }

    /**
     * Appends an active local file resource to the outbound email delivery envelope using an absolute filesystem address.
     * 
     * @param string $path The absolute storage path locator directing toward the actual file asset.
     * @param string|null $name An optional alternative descriptive file name display parameter exposed to recipients.
     * @param string|null $contentType The exact semantic internet media type (MIME type) descriptor matching the asset.
     * @return self The current configured mailable instance to sustain fluent call chains.
     */
    public function attachFromPath(string $path, ?string $name = null, ?string $contentType = null): self
    {
        $this->pathAttachments[] = [$path, $name, $contentType];
        return $this;
    }
}