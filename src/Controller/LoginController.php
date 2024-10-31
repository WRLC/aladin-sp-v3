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
 */
class LoginController extends AbstractController
{
    private LoggerInterface $aladinLogger;
    private LoggerInterface $aladinErrorLogger;

    /**
     * LoginController constructor.
     *
     * @param LoggerInterface $aladinLogger
     * @param LoggerInterface $aladinErrorLogger
     */
    public function __construct(LoggerInterface $aladinLogger, LoggerInterface $aladinErrorLogger)
    {
        $this->aladinLogger = $aladinLogger;
        $this->aladinErrorLogger = $aladinErrorLogger;
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
        // Error handling
        $error_intro = 'Login Error:';
        $errorController = new AladinErrorController();
        $error = new AladinError('authorization', $error_intro);


        // Service slug is a required parameter
        $slug = $request->query->get('service');

        // Institution index is a required parameter
        $index = $request->query->get('institution');

        // If no service is provided...
        if (!$slug) {
            if (!$index) {  // ...and no institution is provided, show an error
                $error->setIntro('Missing service parameter');
                return $this->render('error.html.twig', $errorController->renderError($error));
            } else {  // ...but an institution is provided
                // Get the institution
                $institution = $entityManager->getRepository(Institution::class)->findOneBy(['inst_index' => $index]);

                // If the institution is not found, return an error page
                if (!$institution) {
                    $error->setErrors(['Invalid institution parameter: ' . $index]);
                    return $this->render('error.html.twig', $errorController->renderError($error));
                }

                // Get all institutional services for the institution
                $institutionServices = $entityManager->getRepository(InstitutionService::class)
                    ->findBy(['Institution' => $institution->getId()]);

                // If no institutional services found, show an error
                if (count($institutionServices) == 0) {
                    $error->setIntro($institution->getName() . ' authorization is not available at this time.');
                    return $this->render('error.html.twig', $errorController->renderError($error));
                }

                $form = $this->generateWayg($institutionServices);  // Generate the WAYG form
                $form->handleRequest($request);  // Handle the form request

                // Render the WAYG form
                return $this->render('login/wayg.html.twig', [
                    'institution' => $institution,
                    'form' => $form,
                ]);
            }
        }

        // Get the service
        $service = $entityManager->getRepository(Service::class)->findOneBy(['slug' => $slug]);

        // If the service is not found, show an error
        if (!$service) {
            $error->setIntro('Invalid service parameter: ' . $slug);
            return $this->render('error.html.twig', $errorController->renderError($error));
        }

        // If no institution is provided, show the WAYF
        if (!$index) {
            // Get all institutional services for the service
            $institutionServices = $entityManager->getRepository(InstitutionService::class)
                ->findBy(['Service' => $service->getId()]);

            // If no institutional services found, show an error
            if (count($institutionServices) == 0) {
                $error->setIntro($service->getName() . ' authorization is not available at this time.');
                return $this->render('error.html.twig', $errorController->renderError($error));
            }

            $form = $this->generateWayf($institutionServices);  // Generate the WAYF form
            $form->handleRequest($request);  // Handle the form request

            // Render the WAYF form
            return $this->render('login/wayf.html.twig', [
                'service' => $service,
                'form' => $form,
            ]);
        }

        // Get the institution
        $institution = $entityManager->getRepository(Institution::class)->findOneBy(['inst_index' => $index]);

        // If the institution is not found, return an error page
        if (!$institution) {
            $error->setErrors(['Invalid institution parameter: ' . $index]);
            return $this->render('error.html.twig', $errorController->renderError($error));
        }

        // Get the institutional service
        $institutionService = $entityManager->getRepository(InstitutionService::class)
            ->findOneBy(['Institution' => $institution, 'Service' => $service]);

        // If the institutional service is not found, return an error page
        if (!$institutionService) {
            $error->setIntro($institution->getName() . ' is not authorized for ' . $service->getName());
            return $this->render('error.html.twig', $errorController->renderError($error));
        }

        // Authentication
        $authnController = new AuthnController();  // Create a new AuthnController
        $user_attributes = $authnController->authnUser($institution);  // Authenticate the user

        // If authentication fails, return an error page
        if ($user_attributes instanceof Exception) {
            $error->setIntro('Authentication failed');
            $error->setErrors([$user_attributes->getMessage()]);
            $this->aladinErrorLogger->error('[' . $error->getType() . '] ' . $error->getIntro() . ': ' . $user_attributes->getMessage());
            return $this->render('error.html.twig', $errorController->renderError($error));
        }

        // Get the user ID attribute
        $user_id = $this->getInstUid($institutionService, $user_attributes);

        // If user id is null, there's a problem with attribute names
        if ($user_id == null) {
            $error->setIntro('No user ID attribute found');
            $error->setErrors(['The user was authenticated by their institution, but WRLC Aladin-SP didn\'t recognize a user ID attribute.']);
            $this->aladinErrorLogger->error('[' . $error->getType() . '] ' . $error->getIntro() . ': The user was authenticated by their institution, but WRLC Aladin-SP didn\'t recognize a user ID attribute.');
            return $this->render('error.html.twig', $errorController->renderError($error));
        }

        // Get the special transform toggler value
        $transform = $institution->getSpecialTransform();

        // If the inst requires a special transform...
        if ($transform) {
            $split_email = explode('@', $user_id);  // split the user id at the '@'
            $user_id = $split_email[0];  // set the user id to the first part
        }

        // Log the authentication result
        $this->aladinLogger->debug('Authenticated User: ' . $user_id . ' for ' . $institution->getName());

        // If the ID attribute is not found, show an error
        if ($user_id instanceof Exception) {
            $error->setIntro(
                'Invalid User ID attribute <pre>' . $institutionService->getIdAttribute() . '</pre> for ' .
                $institution->getName()
            );
            $error->setErrors([$user_id->getMessage()]);
            $this->aladinErrorLogger->error('[' . $error->getType() . '] ' . $error->getIntro() . ': ' . $user_id->getMessage());
            return $this->render('error.html.twig', $errorController->renderError($error));
        }

        // Authorization
        $authzController = new AuthzController();  // Create a new AuthzController
        $result = $authzController->authz($institutionService, $user_id);  // Authorize the user

        // If the user is not authorized, show an error
        if (!$result['authorized']) {
            $error->setIntro(
                $institution->getName() . ' user ' . $user_id . ' not authorized for ' . $service->getName()
            );
            $error->setErrors($result['match']);
            $this->aladinErrorLogger->error('[' . $error->getType() . '] ' . $error->getIntro() . ': ' . $result['match'][0]);
            return $this->render('error.html.twig', $errorController->renderError($error));
        }

        $this->aladinLogger->info('Authorized User: ' . $user_id . '@' . $index . ' for ' . $service->getName());

        # generate random session id for memcached key
        $randomUtils = new Random();
        $sessionID = $randomUtils->generateID();

        $cookie_prefix = $_ENV['COOKIE_PREFIX'];  // Get the cookie prefix

        $cookie_name = $cookie_prefix . $service->getSlug();  // Create the cookie name

        // Set the cookie
        if (
            setcookie($cookie_name, $sessionID, [
            'expires' => time() + (86400 * 14),
            'path' => '/',
            'domain' => $_ENV['COOKIE_DOMAIN'],
            ])
        ) {
            // Set the session data
            $m = new Memcached();  // Create a new Memcached object
            $mServer = $_ENV['MEMCACHED_HOST'];  // Get the Memcached host
            $mPort = $_ENV['MEMCACHED_PORT'];  // Get the Memcached port
            $m->addServer($mServer, intval($mPort));  // Add the server
            $data = $this->setDataString($institutionService, $user_attributes);  // Create the data string

            // If the data string is an error, show an error page
            if ($data instanceof Exception) {
                $error->setIntro('Failed to set user session');
                $error->setErrors([$data->getMessage()]);
                $this->aladinErrorLogger->error('[' . $error->getType() . '] ' . $error->getIntro() . ': ' . $data->getMessage());
                return $this->render('error.html.twig', $errorController->renderError($error));
            }

            $m->set($sessionID, $data, time() + 86400 * 14);  // Set the session data

            $mdata = $m->get($sessionID);  // Get the session data
            $mdata = explode("\r\n", $mdata);  // Explode the session data
            $fmdata = [];
            foreach ($mdata as $md) {
                $tmp = explode('=', $md);
                if (count($tmp) == 2) {
                    $fmdata[$tmp[0]] = $tmp[1];
                }
            }
            $jdata = preg_replace('/\s+/mu', ' ', json_encode($fmdata, JSON_PRETTY_PRINT));  // Encode the session data

            $this->aladinLogger->debug('Memcached Session Data: ' . $jdata);

            $this->aladinLogger->debug('Set cookie ' . $cookie_name . ': ' . $sessionID . ' for ' . $user_id . '@' . $index);

            // Redirect to the service
            $this->aladinLogger->info('Redirecting ' . $user_id . '@' . $index . ' to ' . $service->getUrl() . $service->getCallbackPath());
            return $this->redirect($service->getUrl() . $service->getCallbackPath());
        }

        // If the cookie is not set, show an error page
        $error->setIntro('Failed to set cookie');
        $error->setErrors(['Cookie name: ' . $cookie_name]);
        $this->aladinErrorLogger->error('[' . $error->getType() . '] ' . $error->getIntro() . ': Cookie name: ' . $cookie_name);
        return $this->render('error.html.twig', $errorController->renderError($error));
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
        foreach ($institutionServices as $inst_service) {  // For each institutional service...
            $services[] = $inst_service->getService();  // ...add the service to the services array
        }

        // Sort services
        $alpha_services = [];
        foreach ($services as $service) {
            $alpha_services[$service->getName()] = $service;
        }
        ksort($alpha_services);

        return $this->createForm(WaygType::class, null, ['services' => $alpha_services, 'institution' => $institutionServices[0]->getInstitution()->getName()]);
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
        foreach ($institutionServices as $inst_service) {  // For each institutional service...
            $institutions[] = $inst_service->getInstitution();  // ...add the institution to the institutions array
        }

        // Sort institutions
        $institutionController = new InstitutionController();
        $sorted_institutions = $institutionController->sortInstPosition($institutions);

        // Create the WAYF form w/ institutions
        return $this->createForm(WayfType::class, null, ['institutions' => $sorted_institutions, 'service' => $institutionServices[0]->getService()->getName()]);
    }

    /**
     * Get the institution user ID
     *
     * @param InstitutionService $institutionService
     * @param array<string, mixed> $user_attributes
     *
     * @return string|Exception|null
     */
    private function getInstUid(InstitutionService $institutionService, array $user_attributes): string | Exception | null
    {
        try {
            // Get the user ID attribute
            $user_id = $user_attributes[$institutionService->getInstitution()->getIdAttribute()][0];
        } catch (Exception $e) {
            return $e;  // Errors
        }
        return $user_id;
    }

    /**
     * Create data string with session information
     *
     * @param InstitutionService $institutionService
     * @param array<string, mixed> $user_attributes
     *
     * @return string|Exception
     */
    private function setDataString(InstitutionService $institutionService, array $user_attributes): string | Exception
    {
        $instSvcIdAttr = $institutionService->getIdAttribute();  // Get the institution service ID attribute

        // Get the method to call
        $method = 'get' . str_replace(' ', '', ucwords(str_replace(
            '_',
            ' ',
            $instSvcIdAttr
        )));

        $uid = $user_attributes[$institutionService->getInstitution()->$method()][0] ?? '';

        if ($uid == '') {
            return new Exception('User ID for ' . $institutionService->getService()->getName() . ' not found');
        }

        $email = $user_attributes[$institutionService->getInstitution()->getMailAttribute()][0] ?? '';

        if ($email == '') {
            return new Exception('Email address for ' . $institutionService->getService()->getName() . ' not found');
        }

        // Set optional name attributes
        $last_name = $user_attributes[$institutionService->getInstitution()->getNameAttribute()][0] ?? '';
        $first_name = $user_attributes[$institutionService->getInstitution()->getFirstNameAttribute()][0] ?? '';

        $expTime = (string) (time() + (86400 * 14));  // Set the expiration time

        $data = 'UserName=' . strtolower($uid) . "\r\n";
        $data .= 'Service=' . $institutionService->getService()->getSlug() . "\r\n";
        $data .= 'University=' . $institutionService->getInstitution()->getIndex() . "\r\n";
        $data .= 'Email=' . strtolower($email) . "\r\n";
        $data .= 'GivenName=' . $first_name . "\r\n";
        $data .= 'Name=' . $last_name . "\r\n";
        $data .= 'RemoteIP=' . $_SERVER['REMOTE_ADDR'] . "\r\n";
        $data .= 'Expiration=' . $expTime . "\r\n";

        return $data;
    }
}
