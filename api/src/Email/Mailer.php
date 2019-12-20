<?php


namespace App\Email;



use App\Entity\Utilisateur;
use Twig\Environment;

class Mailer
{
    /**
     * @var \Swift_Mailer
     */
    private $mailer;
    /**
     * @var Environment
     */
    private $twigEnvironment;

    public function __construct(\Swift_Mailer $mailer, Environment $twigEnvironment)
    {

        $this->mailer = $mailer;
        $this->twigEnvironment = $twigEnvironment;
    }

    public function sendConfirmationEmail(Utilisateur $user)
    {
        $body = $this->twigEnvironment->render('email/confirmation.html.twig',
            [
                'user' => $user
            ]);

        $message = (new \Swift_Message('Please confirm your account!'))
            ->setFrom('Impulse@gmail.com')
            ->setTo($user->getEmail())
            ->setBody($body, 'text/html');
        $this->mailer->send($message);

    }

}
