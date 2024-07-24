<?php

namespace App\Controller;

use App\Entity\Institution;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use SimpleSAML\Auth\Simple;
use SimpleSAML\Configuration;
use SimpleSAML\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LoginController extends AbstractController
{
    /**
     * SAML SP Login script
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     *
     * @return Response
     *
     */
    #[Route('/login', name: 'login')]
    public function login(EntityManagerInterface $entityManager, Request $request): Response
    {
        // TODO: Authentication

        // TODO: Authorization

        return $this->render('login/index.html.twig', [
            'controller_name' => 'LoginController'
        ]);
    }

    /**
     * WAYF menu
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/login/wayf', name: 'wayf')]
    public function wayf(EntityManagerInterface $entityManager, Request $request): Response
    {
        return $this->render('login/wayf.html.twig', [
            'controller_name' => 'LoginController',
        ]);
    }
}