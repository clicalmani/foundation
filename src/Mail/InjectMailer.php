<?php
namespace Clicalmani\Foundation\Mail;

use Clicalmani\Foundation\Http\Controllers\InjectionLocator;

class InjectMailer extends InjectionLocator
{
    /**
     * @var bool
     */
    protected const INIT_INSTANCE = false;

    public function handle(): ?object
    {
        if (is_subclass_of($this->class, Mailer::class) || $this->class === MailerInterface::class) {

            $mailers = app()->config('mail.mailers', []);

            foreach ($mailers as $name => $mailer) {
                if ($this->container->has("$name.mailer")) {

                    $instance = $this->container->get("$name.mailer");

                    if ($instance instanceof $this->class) {
                        return $instance;
                    }

                    break;
                }
            }
        }

        return null;
    }

    public function setType(string $class) : void
    {
        $this->class = $class;
    }
}