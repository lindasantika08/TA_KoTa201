<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CredentialMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $userEmail;
    protected $userPassword;
    protected $userName;

    public function __construct($email, $password, $nama)
    {
        $this->userEmail = $email;
        $this->userPassword = $password;
        $this->userName = $nama;
    }

    public function build()
    {
        return $this->markdown('emails.credentials')
                    ->subject('Account Information Assessment Apps')
                    ->with([
                        'email' => $this->userEmail,
                        'password' => $this->userPassword,
                        'nama' => $this->userName
                    ]);
    }
}