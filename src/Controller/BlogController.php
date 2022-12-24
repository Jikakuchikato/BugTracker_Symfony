<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class BlogController extends AbstractController
{

    #[Route('/', name: 'app_index')]
    public function index(): Response
    {
        return $this->render('blog/index.html.twig', [
            'controller_name' => 'BlogController',
        ]);
    }

    #[Route('/blog7777', name: 'app_blog')]
    public function blog(): Response
    {
        return $this->render('blog/blog.html.twig', [
            'controller_name' => 'BlogController',
        ]);
    }

    #[Route('/a', name: 'app_accueil')]
    public function accueil(): Response
    {
        return $this->render('blog/home.html.twig', [
            'controller_name' => 'BlogController',
            'prenom' => 'test',
        ]);
    }


}
