<?php

namespace App\Services;

use OpenAI;
use OpenAI\client;
use Exception;

class OpenAIService
{
    protected $client;

    public function __construct()
    {
        try {
            $this->client = OpenAI::client(config('services.openai.key'));
        } catch (Exception $e) {
            throw new Exception('Failed to initialize OpenAI client: ' . $e->getMessage());
        }
    }

    public function getResponse($prompt)
    {
        try {
            $response = $this->client->chat()->create([
                'model' => 'gpt-3.5-turbo', // atau 'gpt-4' jika Anda punya akses
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.7,
            ]);

            return $response->choices[0]->message->content;
        } catch (Exception $e) {
            throw new Exception('Failed to get OpenAI response: ' . $e->getMessage());
        }
    }
}
