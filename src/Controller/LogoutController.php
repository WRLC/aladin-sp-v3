<?php

namespace App\Controller;

use App\Entity\AladinError;
use App\Entity\Config;
use App\Entity\Service;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Memcached;
use SimpleSAML\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LogoutController extends AbstractController
{
    #[Route('/logout', name: 'logout')]
    public function logout(EntityManagerInterface $entityManager, Request $request): Response
    {
        // Error handling
        $error_intro = 'Login Error:';
        $errorController = new AladinErrorController();
        $error = new AladinError('authorization', $error_intro);

        // Service slug is a required parameter
        $slug = $request->query->get('service');

        // If no service is provided, show an error
        if (!$slug) {
            $error->setIntro('Missing service parameter');
            return $this->render('error.html.twig', $errorController->renderError($error));
        }

        // Get the service
        $service = $entityManager->getRepository(Service::class)->findOneBy(['slug' => $slug]);

        if (!$service) {
            $error->setIntro('Service not found');
            return $this->render('error.html.twig', $errorController->renderError($error));
        }

        $serviceUrl = $service->getUrl();

        $cookiePrefix = $entityManager->getRepository(Config::class)->findOneBy(['name' => 'cookie_prefix'])->getValue();
        $cookieName = $cookiePrefix . $service->getSlug();
        $cookieDomain = $entityManager->getRepository(Config::class)->findOneBy(['name' => 'cookie_domain'])->getValue();

        if ($cookieValue = $_COOKIE[$cookieName]) {  # Get the cookie value from the cookie
            # Delete the cookie value from memcache
            $m = new Memcached();  # create memcache object
            $mServer = $entityManager->getRepository(Config::class)->findOneBy(['name' => 'memcached_host'])->getValue();  # Get the memcache server
            $mServerPort = $entityManager->getRepository(Config::class)->findOneBy(['name' => 'memcached_port'])->getValue();  # Get the memcache server port
            $m->addServer($mServer, $mServerPort);  # Add the memcache server
            $m->delete($cookieValue);  # Delete the session from memcache
            setcookie($cookieName, '', time() - 3600, '/', $cookieDomain); # Expire the cookie
        }

        try {
            $session = Session::getSessionFromRequest();
        } catch (Exception $e) {  # If there's an exception, there's no session to destroy...
            return $this->redirect($serviceUrl);  # ...so just redirect to the service URL
        }
        $session->cleanup();  # Cleanup the session
        $session->__destruct();  # Destroy the session

        return $this->redirect($serviceUrl);  # Redirect to the service URL
    }
}