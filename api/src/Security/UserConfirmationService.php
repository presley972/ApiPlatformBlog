<?php


namespace App\Security;


use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserConfirmationService
{
    /**
     * @var UtilisateurRepository
     */
    private $utilisateurRepository;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(UtilisateurRepository $utilisateurRepository, EntityManagerInterface $entityManager)
    {

        $this->utilisateurRepository = $utilisateurRepository;
        $this->entityManager = $entityManager;
    }

    public function confirmUser(string $confirmationToken)
    {
        $user = $this->utilisateurRepository->findOneBy(['confirmationToken' => $confirmationToken]);

        // User was not found by confirmation token

        if (!$user){
            throw new NotFoundHttpException();
        }
        $user->setEnabled(true);
        $user->setConfirmationToken(null);
        $this->entityManager->flush();
    }

}
