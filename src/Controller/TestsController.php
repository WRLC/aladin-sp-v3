<?php

namespace App\Controller;

use App\Entity\AladinError;
use App\Entity\Institution;
use App\Entity\InstitutionService;
use App\Entity\Service;
use App\Form\Type\AuthnTestType;
use App\Form\Type\authzTestType;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use SimpleSAML\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class TestsController extends AbstractController
{

    /**
     * Authentication test
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     *
     * @return Response
     *
     * @throws Exception
     */
    #[Route('/authN', name: 'auth_n_test')]
    public function authN(EntityManagerInterface $entityManager, Request $request): Response
    {
        $error_intro = 'Authentication Test Error';  // Set the error intro text
        $error = new AladinError('authentication', $error_intro);  // Create a new AladinError
        $errorController = new AladinErrorController();  // Create a new ErrorController

        $session = Session::getSessionFromRequest();  // Get the session
        $entityId = $session->getAuthData('default-sp', 'saml:sp:IdP');  // Get the entity ID

        if ($entityId) {  // If the session data contains 'default-sp'
            $institutionController = new InstitutionController();  // Create a new InstitutionController
            $idp = $institutionController->getIdpDetails($entityId);  // Get the IDP details
            return $this->render('tests/authN.html.twig', [  // Render the authentication page
                'attributes' => $session->getAuthData('default-sp', 'Attributes'),  // Set the attributes
                'idp' => $idp,  // Set the institution
            ]);
        }

        // INDEX
        $index = $request->get('institution');  // Get the institution index

        if (!$index) {  // If the institution is not provided..
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

        // INSTITUTION
        $institution = $entityManager->getRepository(Institution::class)->findOneBy(['inst_index' => $index]);  // Find the institution by index
        if (!$institution) {  // If the institution is not found
            $error->setErrors(['Institution "' . $index . '" not found.']);  // Set the error
            return $this->render('error.html.twig', $errorController->renderError($error));  // Return the error page
        }

        // AUTHENTICATION
        $authnController = new AuthnController();  // Create a new LoginController
        $attributes = $authnController->authn_user($institution);  // Authenticate the user and get the attributes
        if (is_subclass_of($attributes, Exception::class)) {  // If the attributes are an Exception
            $error->setErrors([$attributes->getMessage()]);  // Set the error
            return $this->render('error.html.twig', $errorController->renderError($error));  // Return the error page
        }

        // RENDER ATTRIBUTES
        return $this->render('tests/authN.html.twig', [  // Render the authentication page
            'attributes' => $attributes,  // Set the attributes
            'institution' => $institution,  // Set the institution
        ]);
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
        $error_intro = 'Authorization Test Error';  // Set the error intro text
        $error = new AladinError('authorization', $error_intro);  // Create a new AladinError
        $errorController = new AladinErrorController();  // Create a new ErrorController

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
        $institution = $entityManager->getRepository(Institution::class)->findOneBy(['inst_index' => $index]);
        $service = $entityManager->getRepository(Service::class)->findOneBy(['slug' => $slug]);
        $institutionService = $entityManager->getRepository(InstitutionService::class)->findOneBy(['Institution' => $institution->getId(), 'Service' => $service->getId()]);
        if (!$institutionService) {  // If the service is not found
            $error->setErrors([$slug . 'is not a valid service for ' . $index]);  // Set the error
            return $this->render('error.html.twig', $errorController->renderError($error));  // Return the error page
        }

        // USER
        $user = $request->get('user');  // Get the user
        if (!$user) {  // If the user is not provided
            $form = $this->createForm(AuthzTestType::class, null, [
                'institution' => $institutionService->getInstitution(),
                'service' => $institutionService->getService(),
            ]);  // Create the authorization form
            $form->handleRequest($request);  // Handle the request

            if ($form->isSubmitted() && $form->isValid()) {  // If the form is submitted and valid
                $user = $form->get('user')->getData();  // Get the user
                return $this->redirectToRoute('auth_z_test', ['institution' => $index, 'service' => $slug, 'user' => $user]);  // Redirect to the authorization test page
            }

            return $this->render('tests/authZForm.html.twig', [
                'form' => $form,
                'institution' => $institutionService->getInstitution(),
                'service' => $institutionService->getService(),
            ]);
        }

        // AUTHORIZATION
        $authzController = new AuthzController();  // Create a new AuthzController
        $result = $authzController->authz($institutionService, $user);  // Authorize the user

        if ($result['errors']) {  // If there's an error in the result
            if (str_starts_with($result['match'][0], 'HTTP/1.1 404 Not Found returned for')){
                $error->setErrors(['User "' . $user . '" not found for ' . $institution->getName()]);  // Set the error
            }
            else {
                $error->setErrors($result['match']);
            }
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
}