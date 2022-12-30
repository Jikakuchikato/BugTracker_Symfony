<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\BugAuthAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\ProfilFormType;

use App\Entity\User;

class ProfilController extends AbstractController
{
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
                $request,
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
        
}
