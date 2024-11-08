<?php

namespace App\Controller;

use App\Entity\AladinError;
use App\Entity\Institution;
use App\Entity\InstitutionService;
use App\Entity\Service;
use App\Form\Type\AuthnTestType;
use App\Form\Type\AuthzTestType;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use SimpleSAML\Auth\Simple;
use SimpleSAML\Session;
use SimpleSAML\Utils\Auth;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class TestsController
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TestsController extends AbstractController
{
    private LoggerInterface $aladinErrorLogger;

    private string $svcProvider;

    private string $authzUrl;

    private string $memcachedHost;

    private string $memcachedPort;

    /**
     * TestsController constructor.
     *
     * @param LoggerInterface $aladinErrorLogger
     * @param string $svcProvider
     * @param string $authzUrl
     * @param string $memcachedHost
     * @param string $memcachedPort
     */
    public function __construct(
        LoggerInterface $aladinErrorLogger,
        string $svcProvider,
        string $authzUrl,
        string $memcachedHost,
        string $memcachedPort
    ) {
        $this->aladinErrorLogger = $aladinErrorLogger;
        $this->svcProvider = $svcProvider;
        $this->authzUrl = $authzUrl;
        $this->memcachedHost = $memcachedHost;
        $this->memcachedPort = $memcachedPort;
    }

    /**
     * Authentication test
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     *
     * @return Response
     *
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    #[Route('/authN', name: 'auth_n_test')]
    public function authN(EntityManagerInterface $entityManager, Request $request): Response
    {
        $errorIntro = 'Authentication Test Error';  // Set the error intro text
        $error = new AladinError('authentication', $errorIntro);  // Create a new AladinError
        $errorController = new AladinErrorController();  // Create a new ErrorController

        $auth = new Auth();  // Create a new Auth object
        $isAuth = $auth->isAdmin();  // Check if the user is an admin

        $session = Session::getSessionFromRequest();  // Get the session
        $entityId = $session->getAuthData('default-sp', 'saml:sp:IdP');  // Get the entity ID

        // INDEX
        $index = $request->get('institution');  // Get the institution index

        if (!$index) {  // If the institution is not provided..
            if ($isAuth) {  // If the user is an admin
                $form = $this->createForm(AuthnTestType::class, null, [
                    'institutions' => $entityManager->getRepository(Institution::class)->findAll(),
                ]);  // Create the authentication form
                $form->handleRequest($request);  // Handle the request

                if ($form->isSubmitted() && $form->isValid()) {  // If the form is submitted and valid
                    $institution = $form->get('institution')->getData();  // Get the institution
                    $index = $institution->getInstIndex();  // Get the institution index
                    return $this->redirectToRoute('auth_n_test', ['institution' => $index]);  // Redirect to the authentication test page
                }

                return $this->render('tests/authNForm.html.twig', [  // Render the authentication form
                    'form' => $form->createView(),  // Set the form
                ]);
            }
            // If the user is not an admin
            $error->setErrors(['No institution provided.']);  // Set the error
            return $this->render('error.html.twig', $errorController->renderError($error));  // Return the error page
        }

        // INSTITUTION
        $institution = $entityManager->getRepository(Institution::class)->findOneBy(['instIndex' => $index]);  // Find the institution by index
        if (!$institution) {  // If the institution is not found
            $error->setErrors(['Institution "' . $index . '" not found.']);  // Set the error
            return $this->render('error.html.twig', $errorController->renderError($error));  // Return the error page
        }

        if ($entityId) {  // If the session data contains 'default-sp'
            $instController = new InstitutionController($this->memcachedHost, $this->memcachedPort);  // Create a new InstitutionController
            $idp = $instController->getIdpDetails($entityId);  // Get the IDP details
            return $this->render('tests/authN.html.twig', [  // Render the authentication page
                'attributes' => $session->getAuthData('default-sp', 'Attributes'),  // Set the attributes
                'idp' => $idp,  // Set the IdP
                'institution' => $institution,  // Set the institution
            ]);
        }

        // AUTHENTICATION
        $authnController = new AuthnController($this->svcProvider);  // Create a new LoginController
        $attributes = $authnController->authnUser($institution);  // Authenticate the user and get the attributes
        if (is_subclass_of($attributes, Exception::class)) {  // If the attributes are an Exception
            $error->setErrors([$attributes->getMessage()]);  // Set the error
            $this->aladinErrorLogger->error('[' . $error->getType() . '] ' . $error->getIntro() . ': ' . $attributes->getMessage());
            return $this->render('error.html.twig', $errorController->renderError($error));  // Return the error page
        }

        // RENDER ATTRIBUTES
        return $this->render('tests/authN.html.twig', [  // Render the authentication page
            'attributes' => $attributes,  // Set the attributes
            'institution' => $institution,  // Set the institution
        ]);
    }

    /**
     * Clear the authentication session
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     *
     * @return Response|null
     */
    #[Route('/authN/clear', name: 'auth_n_test_clear')]
    public function authnClear(EntityManagerInterface $entityManager, Request $request): Response|null
    {
        $index = $request->get('institution');  // Get the institution index

        if (!$index) {  // If the institution is not provided
            $this->addFlash('error', 'No institution specified');  // Add a flash message
            return $this->redirectToRoute('auth_n_test');  // Redirect to the authentication test
        }

        // INSTITUTION
        $institution = $entityManager->getRepository(Institution::class)->findOneBy(['instIndex' => $index]);

        if (!$institution) {  // If the institution is not found
            $this->addFlash('error', 'Institution not found');  // Add a flash message
            return $this->redirectToRoute('auth_n_test');  // Redirect to the authentication test
        }

        $auth = new Simple('default-sp');  # Create a new Auth object
        $auth->logout('/authN?institution=' . $index);  # Logout the user
        return null;
    }

    /**
     * Authorization test
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     *
     * @return Response
     *
     * @throws TransportExceptionInterface
     */
    #[Route('/authZ', name: 'auth_z_test')]
    public function authz(EntityManagerInterface $entityManager, Request $request): Response
    {
        $errorIntro = 'Authorization Test Error';  // Set the error intro text
        $error = new AladinError('authorization', $errorIntro);  // Create a new AladinError
        $errorController = new AladinErrorController();  // Create a new ErrorController

        $auth = new Auth();  // Create a new Auth object
        $isAuth = $auth->isAdmin();  // Check if the user is an admin

        // INSTIUTION
        $index = $request->get('institution');  // Get the institution index
        if (!$index) {  // If the institution is not provided
            $error->setErrors(['No institution provided.']);  // Set the error
            return $this->render('error.html.twig', $errorController->renderError($error));  // Return the error page
        }

        // SERVICE
        $slug = $request->get('service');  // Get the service slug
        if (!$slug) {  // If the service is not provided
            $error->setErrors(['No service provided.']);  // Set the error
            return $this->render('error.html.twig', $errorController->renderError($error));  // Return the error page
        }

        // INSTITUTION SERVICE
        $institution = $entityManager->getRepository(Institution::class)->findOneBy(['instIndex' => $index]);
        $service = $entityManager->getRepository(Service::class)->findOneBy(['slug' => $slug]);
        $institutionService = $entityManager->getRepository(InstitutionService::class)->findOneBy(['institution' => $institution->getId(), 'service' => $service->getId()]);
        if (!$institutionService) {  // If the service is not found
            $error->setErrors([$slug . 'is not a valid service for ' . $index]);  // Set the error
            return $this->render('error.html.twig', $errorController->renderError($error));  // Return the error page
        }

        // USER
        $user = $request->get('user');  // Get the user
        if (!$user) {  // If the user is not provided
            if ($isAuth) {
                return $this->authzForm($institutionService, $request);  // Return the authorization form
            }
            $error->setErrors(['No user provided.']);  // Set the error
            return $this->render('error.html.twig', $errorController->renderError($error));  // Return the error page
        }

        // AUTHORIZATION
        $authzController = new AuthzController($this->authzUrl);  // Create a new AuthzController
        $result = $authzController->authz($institutionService, $user);  // Authorize the user

        if ($result['errors']) {  // If there's an error in the result
            if ($result['match'][0] == 'Alma user not found') {
                $error->setErrors(['User "' . $user . '" not found for ' . $institution->getName()]);  // Set the error
                $this->aladinErrorLogger->error('[' . $error->getType() . '] ' . $error->getIntro() . ': ' . 'User "' . $user . '" not found for ' . $institution->getName());
            }
            $error->setErrors($result['match']);
            $this->aladinErrorLogger->error('[' . $error->getType() . '] ' . $error->getIntro() . ': ' . $result['match'][0]);
            return $this->render('error.html.twig', $errorController->renderError($error));  // Return the error page
        }

        // RENDER RESULT
        return $this->render('tests/authZ.html.twig', [
            'result' => $result,  // Set the result
            'institution' => $institutionService->getInstitution(),  // Set the institution
            'service' => $institutionService->getService(),  // Set the service
            'user' => $user,  // Set the user
        ]);
    }

    /**
     * Authorization form
     *
     * @param InstitutionService $institutionService
     * @param Request $request
     *
     * @return Response
     */
    public function authzForm(InstitutionService $institutionService, Request $request): Response
    {
        $form = $this->createForm(AuthzTestType::class, null, [
            'institution' => $institutionService->getInstitution(),
            'service' => $institutionService->getService(),
        ]);  // Create the authorization form
        $form->handleRequest($request);  // Handle the request

        if ($form->isSubmitted() && $form->isValid()) {  // If the form is submitted and valid
            $user = $form->get('user')->getData();  // Get the user
            return $this->redirectToRoute(
                'auth_z_test',
                [
                    'institution' => $institutionService->getInstitution()->getIndex(),
                    'service' => $institutionService->getService()->getSlug(),
                    'user' => $user
                ]
            );  // Redirect to the authorization test page
        }

        return $this->render('tests/authZForm.html.twig', [
            'form' => $form,
            'institution' => $institutionService->getInstitution(),
            'service' => $institutionService->getService(),
        ]);
    }
}
