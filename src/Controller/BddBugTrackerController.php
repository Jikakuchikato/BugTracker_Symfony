<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\Article;
use App\Entity\Projet;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;

class BddBugTrackerController extends AbstractController
{
    #[Route('/bdd/create', name: 'app_bdd_bug_tracker')]
    public function createArticleTest(ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();

        $article = new Article();
        $article->setTitre("Titre2")
                ->setPriorite(0)
                ->setCategorie(0)
                ->setDescription("Ceci est une autre description")
                ->setDatecreation(new DateTime());

        $entityManager->persist($article);
        $entityManager->flush();

        return new Response ($article->getDescription());

    }

    #[Route('/projet/createtest', name: 'app_create_projettest')]
    public function createProjetTest(ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();

        $projet = new Projet();
        $projet->setTitre("Titre Projet 2")
                ->setDescription("Description Projet 2")
                ->setDateCrea(new DateTime())
                ->setAuteur(array("Jika"));

        $entityManager->persist($projet);
        $entityManager->flush();

        return new Response ();

    }

    #[Route('/blog', name: 'app_bdd_show')]
    public function afficherArticle(ManagerRegistry $doctrine): Response
    {
        $articles = $doctrine->getRepository(Article::class)->findAll();
        
        if (!$articles)
        {
            throw $this->createNotFoundException("Aucun item trouvé");
        }
        
        return $this->render('blog/blog.html.twig', [
            'articles' => $articles,
        ]);
    }


}
