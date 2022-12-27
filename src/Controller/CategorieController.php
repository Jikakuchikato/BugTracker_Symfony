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


use Doctrine\ORM\EntityManagerInterface;

class CategorieController extends AbstractController
{
    #[Route('/categorie', name: 'app_categorie')]
    public function categorie(): Response
    {
        return $this->render('categorie/index.html.twig', [
            'controller_name' => 'CategorieController',
        ]);
    }

    #[Route('/categorie/create', name: 'app_categorie')]
    public function categorieCreate(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $projetId = $_GET['id'];
        $projet = $entityManager->getRepository(Projet::class)->findBy(array("id" => $projetId));
        $c = new Categorie();

        $form = $this->createForm(CreateCategorieFormType::class, $c);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $c->setTitre($form->get('titre')->getData());
            $c->setDescription($form->get('description')->getData());
            $c->setProjetId($projetId);
            $c->setDateCrea(new DateTime);
            $c->setAuteur($this->getUser()->getUsername());

            $entityManager->persist($c);
            $entityManager->flush();

            $categories = $entityManager->getRepository(Categorie::class)->findAll();
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

    #[Route('/categorie/delete', name: 'app_categorie_delete')]
    public function categorieDelete(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $categorieId = $_GET['id'];

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

        $projet = $entityManager->getRepository(Categorie::class)->findBy(array("projetId" => $categories[0]->getProjetId()));
        //$categories = $entityManager->getRepository(Categorie::class)->findBy(array("projet_id" => $projetId));

        $categoriesUpdate = $entityManager->getRepository(Categorie::class)->findAll();
        
        return $this->render('projets/categories.html.twig', [
            'controller_name' => 'BlogController',
            'infos' => 'Catégorie supprimée avec succès.',
            'categories' => $categoriesUpdate,
            'projet' => $projet[0],
        ]);
    }
}
