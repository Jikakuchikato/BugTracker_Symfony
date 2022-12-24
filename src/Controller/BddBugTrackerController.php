<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\Article;
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

    #[Route('/blog', name: 'app_bdd_show')]
    public function afficherArticle(ManagerRegistry $doctrine): Response
    {
        $articles = $doctrine->getRepository(Article::class)->findAll();
        
        if (!$articles)
        {
            throw $this->createNotFoundException("Aucun item trouvé");
        }

        foreach ($articles as $art)
        {
            //echo $art->getTitre();
        }
        
        return $this->render('blog/blog.html.twig', [
            'articles' => $articles,
        ]);
    }


}
