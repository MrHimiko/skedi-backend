<?php

namespace App\Service;

use App\Exception\SlackException;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SlackService
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function message(string $message, string $username = 'Rentsera'): void
    {
        return;

        try 
        {
            $response = $this->httpClient->request('POST', 'https://hooks.slack.com/services/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', [
                'json' => [
                    'text' => $message,
                    'username' => $username
                ],
            ]);

            if($response->getStatusCode() !== 200)
            {
                throw new SlackException('Failed to send message to Slack.');
            }
        } 
        catch (\Exception $e) 
        {
            throw new SlackException($e->getMessage());
        }
    }
}
