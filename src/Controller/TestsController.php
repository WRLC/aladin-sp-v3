<?php

namespace App\Controller;

use App\Entity\Institution;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
        // INDEX
        $index = $request->get('institution');  // Get the institution index
        if (!$index) {  // If the institution is not provided
            return $this->render('error.html.twig', [  // Render the error page
                'intro' => 'Authentication Test Error',  // Set the intro text
                'errors' => [0 => 'No institution provided.',],  // Set the errors
            ]);
        }

        // INSTITUTION
        $institution = $entityManager->getRepository(Institution::class)->findOneBy(['inst_index' => $index]);  // Find the institution by index
        if (!$institution) {  // If the institution is not found
            return $this->render('error.html.twig', [  // Render the error page
                'intro' => 'Authentication Test Error',  // Set the intro text
                'errors' => [0 => $request->get('institution') . ' is not a valid institution.',],  // Set the errors
            ]);
        }

        // AUTHENTICATION
        $authnController = new AuthnController();  // Create a new LoginController
        $attributes = $authnController->authn_user($institution);  // Authenticate the user and get the attributes
        if (is_subclass_of($attributes, Exception::class)) {  // If the attributes are an Exception
            return $this->render('error.html.twig', [  // Render the error page
                'intro' => 'Authentication Test Error',  // Set the intro text
                'errors' => [0 => $attributes->getMessage()],  // Set the errors
            ]);
        }

        // RENDER ATTRIBUTES
        return $this->render('institution/authN.html.twig', [  // Render the authentication page
            'attributes' => $attributes,  // Set the attributes
        ]);
    }

    /**
     * Authorization test
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/authZ', name: 'auth_z_test')]
    public function authz(EntityManagerInterface $entityManager, Request $request): Response
    {
        // TODO: Authorization

        return $this->render('tests/authZ.html.twig', [
            'controller_name' => 'TestsController',
        ]);
    }
}