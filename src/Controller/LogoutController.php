<?php

namespace App\Controller;

use App\Entity\AladinError;
use App\Entity\Service;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Memcached;
use SimpleSAML\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class LogoutController
 */
class LogoutController extends AbstractController
{
    private string $cookiePrefix;

    private string $cookieDomain;

    private string $memcachedHost;

    private string $memcachedPort;

    /**
     * LogoutController constructor.
     *
     * @param string $cookiePrefix
     * @param string $cookieDomain
     * @param string $memcachedHost
     * @param string $memcachedPort
     */
    public function __construct(
        string $cookiePrefix,
        string $cookieDomain,
        string $memcachedHost,
        string $memcachedPort
    ) {
        $this->cookiePrefix = $cookiePrefix;
        $this->cookieDomain = $cookieDomain;
        $this->memcachedHost = $memcachedHost;
        $this->memcachedPort = $memcachedPort;
    }
    /**
     * Logout
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     *
     * @return Response
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    #[Route('/logout', name: 'logout')]
    public function logout(
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        // Error handling
        $errorIntro = 'Login Error:';
        $errorController = new AladinErrorController();
        $error = new AladinError('authorization', $errorIntro);

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

        if ($request->cookies->get($this->cookiePrefix . $service->getSlug()) !== null) {  # If the cookie is set...
            # Delete the cookie value from memcache
            $m = new Memcached();  # create memcache object
            $m->addServer($this->memcachedHost, intval($this->memcachedPort));  # Add the memcache server
            $m->delete($request->cookies->get($this->cookiePrefix . $service->getSlug()));  # Delete the session from memcache
            setcookie($this->cookiePrefix . $service->getSlug(), '', time() - 3600, '/', $this->cookieDomain); # Expire the cookie
        }

        try {
            $session = Session::getSessionFromRequest();
        } catch (Exception) {  # If there's an exception, there's no session to destroy...
            return $this->redirect($serviceUrl);  # ...so just redirect to the service URL
        }
        $session->cleanup();  # Cleanup the session
        $session->__destruct();  # Destroy the session

        return $this->redirect($serviceUrl);  # Redirect to the service URL
    }
}
