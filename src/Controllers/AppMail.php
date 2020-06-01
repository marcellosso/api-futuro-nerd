<?php
use Anddye\Mailer\Mailable;

namespace Controllers;

class AppMail extends \Anddye\Mailer\Mailable
{
    
    protected $user;
    
    public function __construct($user)
    {
        $this->user = $user;
    }
    
    public function build()
    {
        $this->setSubject('Welcome to the Team!');
        $this->setView('emails/welcome.html.twig', [
            'user' => $this->user
        ]);
        
        return $this;
    }
    
}