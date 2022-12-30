<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Projet;

class BugTrackerController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(): Response
    {
        return $this->render('blog/index.html.twig', [
            'controller_name' => 'BlogController',
        ]);
    }

    #[Route('/accueil', name: 'app_accueil')]
    public function accueil(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $query = $entityManager->getRepository(Projet::class)->findAll();

        $liste = [];
        foreach ($query as $sql)
        {

            if (in_array($this->getUser()->getUsername(),$sql->getAuteur()))
            {
                array_push($liste, $sql);
            }
        }

        return $this->render('blog/accueil.html.twig', [
            'controller_name' => 'BlogController',
            'projets' => $liste,
        ]);
    }
}
