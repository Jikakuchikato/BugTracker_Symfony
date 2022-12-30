<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route
;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Amp\Http\Client\Request;

use App\Entity\Projet;
use App\Entity\Categorie;
use App\Entity\Article;

class ProjetController extends AbstractController
{
    #[Route('/projet', name: 'app_blog')]
    public function projets(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $id = $_GET['projetId'];

        $projets= $entityManager->getRepository(Projet::class)->findBy(array("id" => $id));

        if ($projets != null)
        {
            $projet = $projets[0];
        }
        $categories = $entityManager->getRepository(Categorie::class)->findBy(array("projetId" => $id));

        if (in_array($this->getUser()->getUsername(),$projet->getAuteur()))
        {
            return $this->render('projets/categories.html.twig', [
                'controller_name' => 'BlogController',
                'auteur' => 'access',
                'projet' => $projet,
                'categories' => $categories,
            ]);
        }

        return $this->render('blog/accueil.html.twig', [
            'erreur' => "Vous n'avez pas les droits sur ce projet.",
        ]);
        
    }

    #[Route('/projet/delete', name: 'app_projet_delete')]
    public function projetDelete(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $projetId = $_GET['projetId'];

        $projets = $entityManager->getRepository(Projet::class)->findBy(array("id"=>$projetId));
        if ($projets != null)
        {
            $projet = $projets[0];
        }
        $categories = $entityManager->getRepository(Categorie::class)->findBy(array("projetId" => $projetId));

        if ($categories != null)
        {
            foreach ($categories as $categorie)
            {
                $entityManager->remove($categorie);
                $articles = $entityManager->getRepository(Article::class)->findBy(array("categorie"=>$projetId));
                if ($articles != null)
                {
                    foreach ($articles as $article)
                    {
                        $entityManager->remove($article);
                    }
                }
            }
        }

        $entityManager->remove($projet);
        $entityManager->flush();

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
            'infos' => 'Projet supprimé avec succès.',
        ]);
    }

    #[Route('/projet/create', name: 'app_create_projet')]
    public function ajouterProjet(ManagerRegistry $doctrine, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $projet = $entityManager->getRepository(Projet::class)->find($this->getUser());
        $form = $this->createForm(CreateProjetFormType::class, $projet);
        $form->handleRequest($request);

        $p = new Projet();


        if ($form->isSubmitted() && $form->isValid())
        {
            $p->setTitre($form->get('titre')->getData());
            $p->setDescription($form->get('description')->getData());
            $p->setDateCrea(new DateTime);
            $p->setAuteur(array($this->getUser()->getUsername()));

            $entityManager->persist($p);
            $entityManager->flush();

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
                'info' => 'Projet crée avec succès.',
                'projets' => $liste,
            ]);
        }
            
        return $this->render('projets/createProjet.html.twig', [
            'createProjetForm' => $form->createView(),
        ]);
        
    }

    #[Route('/projet/modif', name: 'app_modif_projet')]
    public function modifProjet(ManagerRegistry $doctrine, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $projet = $entityManager->getRepository(Projet::class)->findOneBy(array("id"=>$_GET['projetId']));
        $form = $this->createForm(CreateProjetFormType::class, $projet);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid())
        {
            $projet->setTitre($form->get('titre')->getData());
            $projet->setDescription($form->get('description')->getData());

            $entityManager->flush();

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
                'info' => 'Projet crée avec succès.',
                'projets' => $liste,
            ]);
        }
            
        return $this->render('projets/modifProjet.html.twig', [
            'createProjetForm' => $form->createView(),
        ]);
        
    }

    #[Route('/projets/modifAuteurs', name: 'app_modif_auteur')]
    public function modifAuteurs(EntityManagerInterface $entityManager, ManagerRegistry $doctrine): Response
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

        return $this->render('/projets/modifAuteurs.html.twig', [
            'controller_name' => 'BlogController',
            'projets' => $liste,
        ]);
    }
}
