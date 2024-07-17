<?php

namespace App\Controller;

use SimpleSAML\Error\Exception;
use SimpleSAML\Utils\Auth;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        $auth = new Auth();
        $auth->requireAdmin();  // Require admin access

        return $this->render('home/index.html.twig');
    }
}