<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Vonage\Client;
use Vonage\Client\Credentials\Basic;
use Vonage\Messages\Channel\SMS\SMSText;

class verification_code implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $user;
    /**
     * Create a new job instance.
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $credentials = new Basic(env('VONAGE_API_KEY'), env('VONAGE_API_SECRET'));
        $phoneNumber = preg_replace('/[^0-9]/', '', $this->user->phone_number);
        $phoneNumber = '+52' . $phoneNumber;
        $client = new Client($credentials);;
        $message = new SMSText(
        $phoneNumber,
        'LOGIN PRACTICA',
        ('HOLA, ESTE ES TU CODIGO PARA AUTENTICACION DE 2 PASOS: ' ."\n". StrVal($this->user->verification_code) . "\n"));

        $client->messages()->send($message);

    }
}
