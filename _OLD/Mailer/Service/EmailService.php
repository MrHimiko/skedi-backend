<?php

namespace App\Plugins\Mailer\Service;

use Aws\Ses\SesClient;
use Twig\Environment;

use App\Plugins\Mailer\Exception\MailerException;
use App\Exception\CrudException;

use App\Plugins\Account\Entity\OrganizationEntity;
use App\Plugins\Account\Entity\UserEntity;

use App\Plugins\Mailer\Service\LogService;

class EmailService
{
    private SesClient $sesClient;
    private Environment $twig;
    private LogService $logService;

    public function __construct(
        Environment $twig, 
        LogService $logService,
        string $awsAccessKeyId, 
        string $awsSecretAccessKey, 
        string $awsRegion
    )
    {
        $this->twig = $twig;
        $this->logService = $logService;

        $this->sesClient = new SesClient([
            'version' => '2010-12-01',
            'region' => $awsRegion,
            'credentials' => [
                'key' => $awsAccessKeyId,
                'secret' => $awsSecretAccessKey,
            ],
        ]);
    }

    public function send(OrganizationEntity $organization, ?UserEntity $user, string $to, string $template, array $variables = []): void
    {
        switch($template)
        {
            case 'recovery.request':
                $subject = 'Password Recovery Request';
                break;
            case 'recovery.recover':
                $subject = 'Password Reset Confirmation';
                break;
            case 'welcome':
                $subject = 'Welcome to Rentsera';
                break;  
        }

        try 
        {
            $html = $this->twig->render('emails/' . $template . '.html.twig', $variables);

            $this->sesClient->sendEmail([
                'Destination' => [
                    'ToAddresses' => [$to],
                ],
                'Message' => [
                    'Body' => [
                        'Html' => [
                            'Charset' => 'UTF-8',
                            'Data' => $html,
                        ],
                        'Text' => [
                            'Charset' => 'UTF-8',
                            'Data' => strip_tags($html),
                        ],
                    ],
                    'Subject' => [
                        'Charset' => 'UTF-8',
                        'Data' => $subject,
                    ],
                ],
                'Source' => 'no-reply@rentsera.com', 
            ]);

            $this->logService->create($organization, $user, [
                'email'    => $to,
                'template' => $template,
                'data'     => (object) $variables,
            ]);
        } 
        catch(CrudException $e)
        {
            throw new MailerException($e->getMessage());
        }
        catch (\Exception $e)
        {
            throw $e;
        }
    }
}
