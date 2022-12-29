<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;

use App\Entity\Article;
use App\Entity\Categorie;
use App\Form\CreateArticleFormType;

class ArticleController extends AbstractController
{
    #[Route('/article/create', name: 'app_create_article')]
    public function articleCreate(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $a = new Article();

        $form = $this->createForm(CreateArticleFormType::class, $a);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $a->setTitre($form->get('titre')->getData());
            $a->setPriorite($form->get('priorite')->getData());
            $a->setDescription($form->get('description')->getData());
            $a->setDatecreation(new DateTime());
            $a->setCategorie($_GET['catId']);

            $entityManager->persist($a);
            $entityManager->flush();

            $articles = $entityManager->getRepository(Article::class)->findBy(array('categorie'=>$_GET['catId']));
            $categories = $entityManager->getRepository(Categorie::class)->findOneBy(array('id'=>$_GET['catId']));

            return $this->render('categorie/articles.html.twig', [
                'categorie' => $categories,
                'articles' => $articles,
            ]);
        }

        return $this->render('article/createArticle.html.twig', [
            'createArticleForm' => $form->createView(),
        ]);
    }

    #[Route('/article/delete', name: 'app_delete_article')]
    public function articleDelete(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $article = $entityManager->getRepository(Article::class)->findOneBy(array('id' => $_GET['itemId']));
        if ($article != null)
        {
            $entityManager->remove($article);
        }

        $entityManager->flush();

        $articles = $entityManager->getRepository(Article::class)->findBy(array('categorie'=>$_GET['catId']));
        $categories = $entityManager->getRepository(Categorie::class)->findOneBy(array('id'=>$_GET['catId']));
        
        return $this->render('categorie/articles.html.twig', [
            'infos' => 'Item supprimé avec succès.',
            'categorie' => $categories,
            'articles' => $articles,
        ]);
    }

    #[Route('/article/modif', name: 'app_modif_article')]
    public function articleModif(EntityManagerInterface $entityManager, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $article = $entityManager->getRepository(Article::class)->findOneBy(array("id" => $_GET['itemId']));

        $form = $this->createForm(CreateArticleFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $article->setTitre($form->get('titre')->getData());
            $article->setDescription($form->get('description')->getData());
            $article->setPriorite($form->get('priorite')->getData());

            $entityManager->flush();

            $articles = $entityManager->getRepository(Article::class)->findBy(array('categorie'=>$_GET['catId']));
            $categories = $entityManager->getRepository(Categorie::class)->findOneBy(array('id'=>$_GET['catId']));

            return $this->render('categorie/articles.html.twig', [
                'infos' => 'Item modifié avec succès.',
                'categorie' => $categories,
                'articles' => $articles,
            ]);
        }

        return $this->render('article/modif_article.html.twig', [
            'item' => $article,
            'itemForm' => $form->createView(),
        ]);
        
        
    }

    
}
