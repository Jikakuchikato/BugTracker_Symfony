<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use DateTime;

use App\Entity\Projet;
use App\Entity\Article;
use App\Entity\Categorie;

use App\Form\CreateCategorieFormType;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints\Existence;
use Symfony\Component\Validator\Constraints\NotNull;

class CategorieController extends AbstractController
{
    #[Route('/categorie', name: 'app_categorie')]
    public function categorie(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $categorie = $entityManager->getRepository(Categorie::class)->findBy(array('id' => $_GET['catId']));
        $articles = $entityManager->getRepository(Article::class)->findBy(array(
            'categorie'=>$_GET['catId'],
            'datecharge'=>NULL,
            'dateresolu'=>NULL,
        ));
        $articlesCharge = $entityManager->getRepository(Article::class)->findBy(array(
            'categorie'=>$_GET['catId'],
            'dateresolu' => NULL,
            'datecharge' => NULL,
        ));
        $articlesResolu = $entityManager->getRepository(Article::class)->findBy(array(
            'categorie'=>$_GET['catId'],
        ));


        return $this->render('categorie/articles.html.twig', [
            'categorie' => $categorie[0],
            'articles' => $articles,
            'articlesCharge' => $articlesCharge,
            'articlesResolu' => $articlesResolu,
        ]);
    }

    #[Route('/categorie/create', name: 'app_create_categorie')]
    public function categorieCreate(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $projetId = $_GET['catId'];
        $projet = $entityManager->getRepository(Projet::class)->findBy(array("id" => $projetId));
        $c = new Categorie();

        $form = $this->createForm(CreateCategorieFormType::class, $c);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $c->setTitre($form->get('titre')->getData());
            $c->setDescription($form->get('description')->getData());
            $c->setProjetId($projetId);
            $c->setDateCrea(new DateTime);
            $c->setAuteur($this->getUser()->getUserIdentifier());

            $entityManager->persist($c);
            $entityManager->flush();

            $categories = $entityManager->getRepository(Categorie::class)->findBy(array("projetId"=>$_GET['catId']));
            if ($categories != null) {
                $categorie = $categories[0];
            }

            return $this->render('projets/categories.html.twig', [
                'categories' => $categories,
                'projet' => $projet[0],
            ]);

        }

        return $this->render('categorie/createCategorie.html.twig', [
            'createCategorieForm' => $form->createView(),
        ]);
    }
    #[Route('/categorie/modif', name: 'app_modif_categorie')]
    public function categorieModif(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $categorie = $entityManager->getRepository(Categorie::class)->findOneBy(array("id" => $_GET['catId']));

        $form = $this->createForm(CreateCategorieFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $categorie->setTitre($form->get('titre')->getData());
            $categorie->setDescription($form->get('description')->getData());

            $entityManager->flush();

            $projet = $entityManager->getRepository(Projet::class)->findOneBy(array('id'=>$_GET['projetId']));
            $categories = $entityManager->getRepository(Categorie::class)->findBy(array('projetId' => $_GET['projetId']));

            return $this->render('projets/categories.html.twig', [
                'infos' => 'Cat??gorie modifi?? avec succ??s.',
                'categories' => $categories,
                'projet' => $projet,
            ]);
        }

        return $this->render('categorie/modifCategorie.html.twig', [
            'categorie' => $categorie,
            'createCategorieForm' => $form->createView(),
        ]);
        
    }

    #[Route('/categorie/delete', name: 'app_categorie_delete')]
    public function categorieDelete(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $categorieId = $_GET['catId'];

        $categories = $entityManager->getRepository(Categorie::class)->findBy(array('id' => $categorieId));
        if ($categories != null)
        {
            $entityManager->remove($categories[0]);
            $articles = $entityManager->getRepository(Article::class)->findBy(array("categorie" => $categories[0]->getId()));
            if ($articles != null)
            {
                foreach ($articles as $article)
                {
                    $entityManager->remove($article);
                }
            }
        }

        $entityManager->flush();

        $categoriesUpdate = $entityManager->getRepository(Categorie::class)->findBy(array('projetId'=> $_GET['projetId']));
        $projet = $entityManager->getRepository(Projet::class)->findOneBy(array("id" => $_GET['projetId']));  

        
        return $this->render('projets/categories.html.twig', [
            'infos' => 'Cat??gorie supprim??e avec succ??s.',
            'categories' => $categoriesUpdate,
            'projet' => $projet,
        ]);
    }
}
