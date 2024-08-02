<?php

namespace App\Controller;

use App\Entity\AladinError;
use App\Entity\Institution;
use App\Entity\InstitutionService;
use App\Entity\Service;
use App\Form\Type\WayfType;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class LoginController extends AbstractController
{
    /**
     * SAML SP Login script
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     *
     * @return Response|RedirectResponse
     *
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    #[Route('/login', name: 'login')]
    public function login(EntityManagerInterface $entityManager, Request $request): Response | RedirectResponse
    {
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

        // If the service is not found, show an error
        if (!$service) {
            $error->setIntro('Invalid service parameter: '. $slug);
            return $this->render('error.html.twig', $errorController->renderError($error));
        }

        // Institution index is a required parameter
        $index = $request->query->get('institution');

        // If no institution is provided, show the WAYF
        if (!$index) {
            // Get all institutional services for the service
            $institutionServices = $entityManager->getRepository(InstitutionService::class)->findBy(['Service' => $service->getId()]);

            // If no institutional services found, show an error
            if (count($institutionServices) == 0) {
                $error->setIntro($service->getName() .' authorization is not available at this time.');
                return $this->render('error.html.twig', $errorController->renderError($error));
            }

            // Get the institutions
            $institutions = [];  // Initialize the institutions array
            foreach ($institutionServices as $inst_service) {  // For each institutional service...
                $institutions[] = $inst_service->getInstitution();  // ...add the institution to the institutions array
            }

            // Sort institutions
            $institutionController = new InstitutionController();
            $sorted_institutions = $institutionController->sort_institutions_position($institutions);

            // Create the WAYF form w/ institutions
            $form = $this->createForm(WayfType::class, null, ['institutions' => $sorted_institutions]);
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
            $error->setErrors(['Invalid institution parameter: '. $index]);
            return $this->render('error.html.twig', $errorController->renderError($error));
        }

        // Get the institutional service
        $institutionService = $entityManager->getRepository(InstitutionService::class)->findOneBy(['Institution' => $institution, 'Service' => $service]);

        // If the institutional service is not found, return an error page
        if (!$institutionService) {
            $error->setIntro($institution->getName() . ' is not authorized for ' . $service->getName());
            return $this->render('error.html.twig', $errorController->renderError($error));
        }

        // Authentication
        $authnController = new AuthnController();  // Create a new AuthnController
        $user_attributes = $authnController->authn_user($institution);  // Authenticate the user

        // If authentication fails, return an error page
        if ($user_attributes instanceof Exception) {
            $error->setIntro('Authentication failed');
            $error->setErrors([$user_attributes->getMessage()]);
            $error->setLog(true);
            return $this->render('error.html.twig', $errorController->renderError($error));
        }

        $user_id = $this->get_institution_user_id($institutionService, $user_attributes);  // Get the user ID attribute

        // If the ID attribute is not found, show an error
        if ($user_id instanceof Exception) {
            $error->setIntro('Invalid User ID attribute <pre>'. $institutionService->getIdAttribute() . '</pre> for '. $institution->getName());
            $error->setErrors([$user_id->getMessage()]);
            $error->setLog(true);
            return $this->render('error.html.twig', $errorController->renderError($error));
        }

        // Authorization
        $authzController = new AuthzController();  // Create a new AuthzController
        $result = $authzController->authz($institutionService, $user_id);  // Authorize the user

        // If the user is not authorized, show an error
        if (!$result['authorized']) {
            $error->setIntro($institution->getName() . ' user '. $user_id .' not authorized for '. $service->getName());
            $error->setErrors($result['match']);
            $error->setLog(true);
            return $this->render('error.html.twig', $errorController->renderError($error));
        }

        $data = $this->set_data_string($institutionService, $user_attributes);

        return $this->render('login/index.html.twig', [
            'data' => $data
        ]);
    }

    /**
     * Get the institution user ID
     *
     * @param InstitutionService $institutionService
     * @param array $user_attributes
     *
     * @return string | Exception
     */
    private function get_institution_user_id(InstitutionService $institutionService, array $user_attributes): string | Exception
    {
        $institution = $institutionService->getInstitution();  // Get the institution
        $id_attribute = $institution->getIdAttribute();  // Get the ID attribute

        try {
            $user_id = $user_attributes[$id_attribute][0];  // Get the user ID attribute
        }
        catch (Exception $e) {
            return $e;  // Errors
        }
        return $user_id;
    }

    /**
     * Create data string with session information
     *
     * @param InstitutionService $institutionService
     * @param array $user_attributes
     *
     * @return string
     */
    private function set_data_string(InstitutionService $institutionService, array $user_attributes): string
    {
        $institution = $institutionService->getInstitution();  // Get the institution
        $instSvcIdAttr = $institutionService->getIdAttribute();  // Get the institution service ID attribute
        $method = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $instSvcIdAttr)));  // Get the method to call
        $uid = $user_attributes[$institution->$method()][0];  // Get the user ID used by service
        $normal_uid = strtolower($uid);  // Normalize the user ID
        $normal_email = strtolower($user_attributes[$institution->getMailAttribute()][0]);  // Normalize the email
        $last_name = $user_attributes[$institution->getNameAttribute()][0] ?? '';  // Get the last name
        $first_name = $user_attributes[$institution->getFirstNameAttribute()][0] ?? '';  // Get the first name


        $data = '';  # initialize data string

        $data .= 'UserName=' . $normal_uid . "\r\n";
        $data .= 'University=' . $institution->getName() . "\r\n";
        $data .= 'RemoteIP=' . $_SERVER['REMOTE_ADDR'] . "\r\n";
        $data .= 'Expiration=' . time()+(86400*14) . "\r\n";
        $data .= 'Email=' . $normal_email . "\r\n";
        $data .= 'Name=' . $last_name . "\r\n";
        $data .= 'GivenName=' . $first_name . "\r\n";

        return $data;
    }
}