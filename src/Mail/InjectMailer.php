<?php
namespace Clicalmani\Core\Mail;

use Clicalmani\Core\Http\Controllers\InjectionLocator;

class InjectMailer extends InjectionLocator
{
    /**
     * @var bool
     */
    protected const INIT_INSTANCE = false;

    public function handle(): ?object
    {
        if (!is_subclass_of($this->class, Mailer::class) 
            && $this->class !== MailerInterface::class) {
            return null;
        }
        
        return $this->container->get('mailer');
    }

    public function setType(string $class) : static
    {
        $this->class = $class;
        return $this;
    }
}