<?php
namespace Clicalmani\Core\Http;

interface Redirectable
{
    /**
     * Triggers a specialized HTTP routing redirection execution sweep.
     * 
     * @return void
     */
    public function redirect(): void;
}