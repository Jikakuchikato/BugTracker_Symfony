<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Security\BugAuthAuthenticator;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use DateTime;
use SYmfony\Component\Security\Core\Security;

use App\Entity\Article;
use App\Entity\User;
use App\Entity\Projet;
use App\Entity\Categorie;
use Doctrine\Persistence\ManagerRegistry;

use App\Form\ProfilFormType;
use App\Form\MotDePasseFormType;
use App\Form\CreateProjetFormType;
use Doctrine\ORM\Mapping\Entity;

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
        $this->denyAccessUnlessGranted('ROLE_USER');
        return $this->render('blog/blog.html.twig', [
            'controller_name' => 'BlogController',
        ]);
    }

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

    #[Route('/accueil', name: 'app_accueil')]
    public function accueil(EntityManagerInterface $entityManager, ManagerRegistry $doctrine): Response
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

    #[Route('/projet/delete', name: 'app_projet_delete')]
    public function projetDelete(EntityManagerInterface $entityManager, ManagerRegistry $doctrine): Response
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

    #[Route('/admin', name: 'app_admin')]
    public function admin(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('blog/admin.html.twig', [
            'controller_name' => 'BlogController',
        ]);
    }

    #[Route('/profil', name: 'app_profil')]
    public function editProfil(ManagerRegistry $doctrine, Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, BugAuthAuthenticator $authenticator, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $entityManager->getRepository(User::class)->find($this->getUser());
        $userId = $this->getUser();
        $form = $this->createForm(ProfilFormType::class, $user);
        $form->handleRequest($request);

        $entityManager = $doctrine->getManager();

        $infos = $entityManager->getRepository(User::class)->findBy(array("id"=>$userId));
        if ($infos != null)
        {
            $info = $infos[0];
        }

        //print_r("user:".$user->getId());
        //print_r($info->getDateNaissance()->format('d-M-y'));
        //print_r($infos[0]->getUsername());

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            

            //$user->setUsername($form->get('username')->getData());
            $user->setPrenom($form->get('prenom')->getData());
            $user->setNom($form->get('nom')->getData());
            $user->setdateNaissance($form->get('dateNaissance')->getData());

            $entityManager->flush();


            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('blog/profil.html.twig', [
            'profilForm' => $form->createView(),
            'nomUtilisateur' => $info->getUsername(),
            'prenom' => $info->getPrenom(),
            'nom' => $info->getNom(),
            'dateNaissance' => $info->getDateNaissance()->format('d-M-y'),
        ]);
        }

        #[Route('/profil/mdp', name: 'app_profil_mdp')]
        public function afficherArticle(ManagerRegistry $doctrine, Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, BugAuthAuthenticator $authenticator, EntityManagerInterface $entityManager): Response
        {
            $this->denyAccessUnlessGranted('ROLE_USER');

            $userId = $this->getUser();
            $infos = $entityManager->getRepository(User::class)->findBy(array("id"=>$userId));
            if ($infos != null)
            {
                $info = $infos[0];
            }

            $user = $entityManager->getRepository(User::class)->find($this->getUser());
            $form = $this->createForm(MotDePasseFormType::class, $user);
            $form->handleRequest($request);


            if ($form->isSubmitted() && $form->isValid())
            {
                if ($form->get('mdpNouveau')->getData() == $form->get('mdpNouveauVerif')->getData()) 
                {
                    if (password_verify($form->get('mdpActuel')->getData(),$info->getPassword()))
                    {
                        $user->setPassword(
                            $userPasswordHasher->hashPassword(
                                $user,
                                $form->get('mdpNouveau')->getData()                             
                            )
                        );
                        $entityManager->flush();
        
                        return $this->render('blog/accueil.html.twig', [
                            'mdpChangement' => 'Mot de passe modifié avec succès',
                        ]);
                    }
                    else 
                    {    
                        return $this->render('blog/profil_mdp.html.twig', [
                            'mdpForm' => $form->createView(),
                            'mdpChangement' => 'Mauvais mot de passe.',
                        ]);
                    }
                }
                else 
                {
                    return $this->render('blog/profil_mdp.html.twig', [
                        'mdpForm' => $form->createView(),
                        'mdpChangement' => 'Les nouveaux mots de passe ne correspondent pas.',
                    ]);
                }
            }
                
            return $this->render('blog/profil_mdp.html.twig', [
                'mdpForm' => $form->createView(),
            ]);
            
        }

        #[Route('/projet/create', name: 'app_create_projet')]
        public function ajouterProjet(ManagerRegistry $doctrine, Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, BugAuthAuthenticator $authenticator, EntityManagerInterface $entityManager): Response
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
        public function modifProjet(ManagerRegistry $doctrine, Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, BugAuthAuthenticator $authenticator, EntityManagerInterface $entityManager): Response
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
                
            return $this->render('projets/createProjet.html.twig', [
                'createProjetForm' => $form->createView(),
            ]);
            
        }

        
    }

            
            
        



    


