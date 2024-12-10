<?php

namespace App\Controller;

use App\Entity\AladinError;
use App\Entity\Institution;
use App\Entity\InstitutionService;
use App\Entity\Service;
use App\Form\Type\WayfType;
use App\Form\Type\WaygType;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Memcached;
use Psr\Log\LoggerInterface;
use SimpleSAML\Utils\Random;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class LoginController
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LoginController extends AbstractController
{
    private LoggerInterface $aladinLogger;
    private LoggerInterface $aladinErrorLogger;
    private string $svcProvider;
    private string $authzUrl;
    private string $cookiePrefix;
    private string $cookieDomain;
    private string $memcachedHost;
    private string $memcachedPort;

    /**
     * LoginController constructor.
     *
     * @param LoggerInterface $aladinLogger
     * @param LoggerInterface $aladinErrorLogger
     * @param string $svcProvider
     * @param string $authzUrl
     * @param string $cookiePrefix
     * @param string $cookieDomain
     * @param string $memcachedHost
     * @param string $memcachedPort
     */
    public function __construct(
        LoggerInterface $aladinLogger,
        LoggerInterface $aladinErrorLogger,
        string $svcProvider,
        string $authzUrl,
        string $cookiePrefix,
        string $cookieDomain,
        string $memcachedHost,
        string $memcachedPort
    ) {
        $this->aladinLogger = $aladinLogger;
        $this->aladinErrorLogger = $aladinErrorLogger;
        $this->svcProvider = $svcProvider;
        $this->authzUrl = $authzUrl;
        $this->cookiePrefix = $cookiePrefix;
        $this->cookieDomain = $cookieDomain;
        $this->memcachedHost = $memcachedHost;
        $this->memcachedPort = $memcachedPort;
    }

    /**
     * SAML SP Login script
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     *
     * @return Response
     *
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    #[Route('/login', name: 'login')]
    public function login(EntityManagerInterface $entityManager, Request $request): Response
    {
        $errorController = new AladinErrorController();  // Create a new AladinErrorController

        $service = $this->getService($entityManager, $request);  // Get the service
        $institution = $this->getInstitution($entityManager, $request);  // Get the institution

        $institutionService = $this->getInstSvc(  // Get the institutional service
            $entityManager,
            $request,
            $service,
            $institution
        );

        if ($service instanceof AladinError) {  // If the service is an error, return an error page
            return $this->render('error.html.twig', $errorController->renderError($service));
        }
        if ($institutionService instanceof Response) {  // If the institution service is a response...
            return $institutionService;  // ...return the response
        }

        $loginAuthCtrlr = new LoginAuthnController(  // Create a new LoginAuthnController
            $this->aladinLogger,
            $this->aladinErrorLogger,
            $this->svcProvider
        );

        // Authentication
        $userAttributes = $loginAuthCtrlr->doLoginAuth($institutionService);  // Authenticate the user
        if ($userAttributes instanceof AladinError) {  // If the user attributes are an error, return an error page
            return $this->render('error.html.twig', $errorController->renderError($userAttributes));
        }

        // Get the user ID
        $userId = $loginAuthCtrlr->getInstUid($institutionService, $userAttributes);  // Get the institution user ID
        if ($userId instanceof AladinError) {  // If the user ID is an error, return an error page
            return $this->render('error.html.twig', $errorController->renderError($userId));
        }

        // Authorization
        $authzController = new AuthzController($this->authzUrl);  // Create a new AuthzController
        $result = $this->authzUser($authzController, $institutionService, $userId);  // Authorize the user
        if ($result instanceof AladinError) {  // If the user is not authorized, return an error page
            return $this->render('error.html.twig', $errorController->renderError($result));
        }

        // Set the cookie
        $session = $this->setSessionCookie($service);
        if ($session instanceof AladinError) {  // If the cookie is an error...
            return $this->render('error.html.twig', $errorController->renderError($session));  // ...return error
        }
        // Set the session data
        $memcachedSession = $this->setMemcachedSession($institutionService, $userAttributes, $request, $session);
        if ($memcachedSession instanceof AladinError) {  // If the session data is an error...
            return $this->render(
                'error.html.twig',
                $errorController->renderError($memcachedSession)
            );  // ...return the error page
        }

        // Redirect to the service
        $this->aladinLogger
            ->info(
                'Redirecting ' . $userId . '@' . $institution->getIndex() . ' to ' . $service->getUrl() .
                $service->getCallbackPath()
            );
        return $this->redirect($service->getUrl() . $service->getCallbackPath());
    }

    /**
     * Get the Institutional Service
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param Service|AladinError $service
     * @param Institution|AladinError $institution
     *
     * @return InstitutionService|Response|AladinError
     */
    private function getInstSvc(
        EntityManagerInterface $entityManager,
        Request $request,
        Service|AladinError $service,
        Institution|AladinError $institution
    ): InstitutionService|Response|AladinError {
        if ($service instanceof AladinError) {  // If the service is an error...
            if ($institution instanceof AladinError) {  // ...and the institution is an error...
                $error = new AladinError('authorization', 'Login Error:');
                $error->setIntro($service->getIntro() . ' ' . $institution->getIntro());
                $error->setErrors(array_merge($service->getErrors(), $institution->getErrors()));
                return $error;  // Return an error
            }

            // Gererate the WAYG form
            return $this->returnWayg($this->getSvcs($entityManager, $institution), $institution, $request);
        }

        // If a valid service is provided...
        if ($institution instanceof AladinError) {  // ...but the institution is an error...
            // Generate the WAYF form
            return $this->returnWayf($this->getInsts($entityManager, $service), $service, $request);
        }

        // Get the institution service
        $institutionService = $entityManager
            ->getRepository(InstitutionService::class)
            ->findOneBy(['institution' => $institution->getId(), 'service' => $service->getId()]);

        if (!$institutionService) {  // If the institution service is not found...
            $error = new AladinError('authorization', 'Login Error:');
            $error->setIntro('Invalid institution service');
            return $error;  // ...return an error
        }

        return $institutionService;
    }

    /**
     * Get the service
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     *
     * @return AladinError|Service
     */
    private function getService(EntityManagerInterface $entityManager, Request $request): AladinError|Service
    {
        $slug = $this->getServiceSlug($request);  // Service slug is a required parameter

        if ($slug instanceof AladinError) {  // If the service slug is an error...
            return $slug;  // ...return the error
        }

        $service = $entityManager
            ->getRepository(Service::class)
            ->findOneBy(['slug' => $request->query->get('service')]);  // Get the service

        if (!$service) {  // If the service is not found...
            $error = new AladinError('authorization', 'Login Error:');
            $error->setIntro('Invalid service parameter: ' . $request->query->get('service'));
            return $error;  // ...return an error
        }

        return $service;
    }

    /**
     * Get the service slug
     *
     * @param Request $request
     *
     * @return string|AladinError
     */
    private function getServiceSlug(Request $request): string|AladinError
    {
        $slug = $request->query->get('service');  // Service slug is a required parameter

        if (!$slug) {  // If no service is provided...
            $error = new AladinError('authorization', 'Login Error:');
            $error->setIntro('Missing service parameter');
            return $error;  // ...return an error
        }
        return $slug;
    }

    /**
     * Get the institution
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     *
     * @return Institution|AladinError
     */
    private function getInstitution(EntityManagerInterface $entityManager, Request $request): Institution|AladinError
    {
        $index = $this->getInstIndex($request);  // Get the institution index
        if ($index instanceof AladinError) {  // If no institution is provided...
            return $index;  // ...return null
        }

        $institution = $entityManager
            ->getRepository(Institution::class)
            ->findOneBy(['instIndex' => $index]);  // Get the institution

        // If the institution is not found, return an error page
        if (!$institution) {
            $error = new AladinError('authorization', 'Login Error:');
            $error->setErrors(['Invalid institution parameter: ' . $index]);
            return $error;
        }

        return $institution;
    }

    /**
     * Get the institution index
     *
     * @param Request $request
     *
     * @return string|AladinError
     */
    private function getInstIndex(Request $request): string|AladinError
    {
        $index = $request->query->get('institution');  // Get the institution index

        if (!$index) {  // If no institution is provided...
            $error = new AladinError('authorization', 'Login Error:');
            $error->setIntro('Missing institution parameter');
            return $error;  // ...return an error
        }
        return $index;
    }

    /**
     * Get all institutions for a service
     *
     * @param EntityManagerInterface $entityManager
     * @param Service $service
     *
     * @return array<InstitutionService>|AladinError
     */
    private function getInsts(EntityManagerInterface $entityManager, Service $service): array|AladinError
    {
        // Get all institutional services for the institution
        $institutionServices = $entityManager->getRepository(InstitutionService::class)
            ->findBy(['service' => $service->getId()]);

        // If no institutional services found, return an error
        if (count($institutionServices) == 0) {
            $error = new AladinError('authorization', 'Login Error:');
            $error->setIntro($service->getName() . ' authorization is not available at this time.');
            $institutionServices = $error;
        }
        return $institutionServices;
    }

    /**
     * Get all services for an institution
     *
     * @param EntityManagerInterface $entityManager
     * @param Institution $institution
     *
     * @return array<InstitutionService>|AladinError
     */
    private function getSvcs(EntityManagerInterface $entityManager, Institution $institution): array|AladinError
    {
        // Get all institutional services for the institution
        $institutionServices = $entityManager->getRepository(InstitutionService::class)
            ->findBy(['institution' => $institution->getId()]);

        // If no institutional services found, return an error
        if (count($institutionServices) == 0) {
            $error = new AladinError('authorization', 'Login Error:');
            $error->setIntro($institution->getName() . ' authorization is not available at this time.');
            $institutionServices = $error;
        }
        return $institutionServices;
    }

    /**
     * Return the WAYG form
     *
     * @param array<InstitutionService> $services
     * @param Institution $institution
     * @param Request $request
     *
     * @return Response
     */
    private function returnWayg(array $services, Institution $institution, Request $request): Response
    {
        $form = $this->generateWayg($services);
        $form->handleRequest($request);
        return $this->render('login/wayg.html.twig', [
            'institution' => $institution,
            'form' => $form
        ]);
    }

    /**
     * Generate the WAYG form
     *
     * @param array<InstitutionService> $institutionServices
     *
     * @return FormInterface
     */
    private function generateWayg(array $institutionServices): FormInterface
    {
        // Get the services
        $services = [];  // Initialize the services array
        foreach ($institutionServices as $instSvc) {  // For each institutional service...
            $services[] = $instSvc->getService();  // ...add the service to the services array
        }

        // Sort services
        $alphaSvcs = [];
        foreach ($services as $service) {
            $alphaSvcs[$service->getName()] = $service;
        }
        ksort($alphaSvcs);

        return $this->createForm(
            WaygType::class,
            null,
            ['services' => $alphaSvcs, 'institution' => $institutionServices[0]->getInstitution()->getName()]
        );
    }

    /**
     * Return the WAYF form
     * @param array<InstitutionService> $institutions
     * @param Service $service
     * @param Request $request
     *
     * @return Response
     */
    private function returnWayf(array $institutions, Service $service, Request $request): Response
    {
        $form = $this->generateWayf($institutions);
        $form->handleRequest($request);
        return $this->render('login/wayf.html.twig', [
            'service' => $service,
            'form' => $form
        ]);
    }

    /**
     * Generate the WAYF form
     *
     * @param array<InstitutionService> $institutionServices
     *
     * @return FormInterface
     */
    private function generateWayf(array $institutionServices): FormInterface
    {
        // Get the institutions
        $institutions = [];  // Initialize the institutions array
        foreach ($institutionServices as $instSvc) {  // For each institutional service...
            $institutions[] = $instSvc->getInstitution();  // ...add the institution to the institutions array
        }

        // Sort institutions
        $instController = new InstitutionController(
            $this->memcachedHost,
            $this->memcachedPort
        )
        ;  // Create a new InstitutionController
        $sortedInsts = $instController->sortInstPosition($institutions);

        // Create the WAYF form w/ institutions
        return $this->createForm(
            WayfType::class,
            null,
            ['institutions' => $sortedInsts, 'service' => $institutionServices[0]->getService()->getName()]
        );
    }

    /**
     * Authorize the user
     *
     * @param AuthzController $authzController
     * @param InstitutionService $institutionService
     * @param string $userId
     *
     * @return bool|AladinError
     *
     * @throws TransportExceptionInterface
     */
    private function authzUser(
        AuthzController $authzController,
        InstitutionService $institutionService,
        string $userId
    ): bool | AladinError {
        $result = $authzController->authz($institutionService, $userId);  // Authorize the user

        // If the user is not authorized, show an error
        if (!$result['authorized']) {
            $error = new AladinError('authorization', 'Login Error:');
            $error->setIntro(
                $institutionService->getInstitution()->getName() . ' user ' . $userId . ' not authorized for ' .
                $institutionService->getService()->getName()
            );
            $error->setErrors($result['match']);
            $message = '';
            if (count($result['match']) > 0) {
                $message = $result['match'][0];
            }
            $this->aladinErrorLogger->error('[' . $error->getType() . '] ' . $error->getIntro() . ': ' .
                $message);
            return $error;
        }

        $this->aladinLogger->info(
            'Authorized User: ' . $userId . '@' . $institutionService->getInstitution()->getIndex() . ' for ' .
            $institutionService->getService()->getName()
        );
        return true;
    }

    /**
     * Set the session cookie
     *
     * @param Service $service
     *
     * @return string|AladinError
     */
    private function setSessionCookie(Service $service): string|AladinError
    {
        $randomUtils = new Random();
        $sessionID = $randomUtils->generateID();

        $cookieName = $this->cookiePrefix . $service->getSlug();  // Create the cookie name

        $cookie =  setcookie($cookieName, $sessionID, [
            'expires' => time() + (86400 * 14),
            'path' => '/',
            'domain' => $this->cookieDomain,
        ]);

        if (!$cookie) {
            $error = new AladinError('authorization', 'Login Error:');
            $error->setIntro('Failed to set cookie');
            $error->setErrors(['Cookie name: ' . $cookieName]);
            $this->aladinErrorLogger->error(
                '[' . $error->getType() . '] ' . $error->getIntro() . ': Cookie name: ' . $cookieName
            );
            return $error;
        }

        return $sessionID;
    }

    /**
     * Set the memcached session
     *
     * @param InstitutionService $institutionService
     * @param array<string, mixed> $userAttributes
     * @param Request $request
     * @param string $session
     *
     * @return null|AladinError
     */
    private function setMemcachedSession(
        InstitutionService $institutionService,
        array $userAttributes,
        Request $request,
        string $session
    ): null|AladinError {
        $memcached = new Memcached();  // Create a new Memcached object
        $memcached->addServer($this->memcachedHost, intval($this->memcachedPort));  // Add the server
        $data = $this->setDataString($institutionService, $userAttributes, $request);  // Create the data string

        // If the data string is an error, show an error page
        if ($data instanceof Exception) {
            $error = new AladinError('authorization', 'Login Error:');
            $error->setIntro('Failed to set user session');
            $error->setErrors([$data->getMessage()]);
            $this->aladinErrorLogger->error(
                '[' . $error->getType() . '] ' . $error->getIntro() . ': ' . $data->getMessage()
            );
            return $error;
        }

        $memcached->set($session, $data, time() + 86400 * 14);  // Set the session data

        $mdata = $memcached->get($session);  // Get the session data
        $mdata = explode("\r\n", $mdata);  // Explode the session data
        $fmdata = [];
        foreach ($mdata as $md) {
            $tmp = explode('=', $md);
            if (count($tmp) == 2) {
                $fmdata[$tmp[0]] = $tmp[1];
            }
        }
        $jdata = preg_replace(
            '/\s+/mu',
            ' ',
            json_encode($fmdata, JSON_PRETTY_PRINT)
        );  // Encode the session data

        $this->aladinLogger->debug('Memcached Session Data: ' . $jdata);

        return null;
    }

    /**
     * Create data string with session information
     *
     * @param InstitutionService $institutionService
     * @param array<string, mixed> $userAttributes
     * @param Request $request
     *
     * @return string|Exception
     */
    private function setDataString(
        InstitutionService $institutionService,
        array $userAttributes,
        Request $request
    ): string | Exception {
        $instSvcIdAttr = $institutionService->getIdAttribute();  // Get the institution service ID attribute

        // Get the method to call
        $method = 'get' . str_replace(' ', '', ucwords(str_replace(
            '_',
            ' ',
            $instSvcIdAttr
        )));

        $uid = $userAttributes[$institutionService->getInstitution()->$method()][0] ?? '';

        if ($uid == '') {
            return new Exception('User ID for ' . $institutionService->getService()->getName() . ' not found');
        }

        $email = $userAttributes[$institutionService->getInstitution()->getMailAttribute()][0] ?? '';

        if ($email == '') {
            return new Exception('Email address for ' . $institutionService->getService()->getName() . ' not found');
        }

        // Set optional name attributes
        $lastName = $userAttributes[$institutionService->getInstitution()->getNameAttribute()][0] ?? '';
        $firstName = $userAttributes[$institutionService->getInstitution()->getFirstNameAttribute()][0] ?? '';

        $expTime = (string) (time() + (86400 * 14));  // Set the expiration time

        $data = 'UserName=' . strtolower($uid) . "\r\n";
        $data .= 'Service=' . $institutionService->getService()->getSlug() . "\r\n";
        $data .= 'University=' . $institutionService->getInstitution()->getIndex() . "\r\n";
        $data .= 'Email=' . strtolower($email) . "\r\n";
        $data .= 'GivenName=' . $firstName . "\r\n";
        $data .= 'Name=' . $lastName . "\r\n";
        $data .= 'RemoteIP=' . $request->getClientIp() . "\r\n";
        $data .= 'Expiration=' . $expTime . "\r\n";

        return $data;
    }
}
