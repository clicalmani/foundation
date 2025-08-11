<?php
namespace Clicalmani\Foundation\Mail;

interface TransportInterface
{
    /**
     * Creates a mailer transport instance.
     *
     * @return \Symfony\Component\Mailer\Transport\TransportInterface
     */
    public function create() : \Symfony\Component\Mailer\Transport\TransportInterface;
}