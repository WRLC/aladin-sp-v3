<?php

namespace App\Controller;

use App\Entity\Service;
use App\Form\Type\EntityDeleteType;
use App\Form\Type\ServiceType;
use Doctrine\ORM\EntityManagerInterface;
use SimpleSAML\Error\Exception;
use SimpleSAML\Utils\Auth;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ServiceController extends AbstractController
{
    /**
     * List all Service entities
     *
     * @param EntityManagerInterface $entityManager
     *
     * @return Response
     *
     * @throws Exception
     */
    #[Route('/service', name: 'list_services')]
    public function listServices(EntityManagerInterface $entityManager): Response
    {
        $auth = new Auth();
        $auth->requireAdmin();  // Require admin access

        $services = $entityManager->getRepository(Service::class)->findAll();  // Get all Service entities
        $services = $this->alpha_services_slug($services);  // Sort the Service entities by slug

        return $this->render('service/index.html.twig', [
            'services' => $services,  // Pass the Service
        ]);
    }

    /**
     * Create a new Service entity
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     *
     * @return Response
     *
     * @throws Exception
     */
    #[Route('/service/new', name: 'create_service')]
    public function createService(EntityManagerInterface $entityManager, Request $request): Response
    {
        $auth = new Auth();
        $auth->requireAdmin();  // Require admin access

        $service = new Service();  // Create a new Institution entity
        $form = $this->createForm(ServiceType::class, $service);  // Create a form for the Institution entity
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {  // If the form is submitted and valid
            $service = $form->getData();  // Get the form data
            $entityManager->persist($service);  // Persist the Institution entity
            $entityManager->flush();  // Flush the entity manager

            $this->addFlash('success', $service->getName() . ' created');  // Add a success flash message

            return $this->redirectToRoute('list_services');  // Redirect to the new Institution page
        }

        return $this->render('service/form.html.twig', [  // Render the new Institution page
            'form' => $form,  // Pass the form to the template
        ]);
    }

    /**
     * Show a Service entity
     *
     * @param EntityManagerInterface $entityManager
     * @param string $slug
     *
     * @return Response
     *
     * @throws Exception
     */
    #[Route('/service/{slug}', name: 'show_service')]
    public function showService(EntityManagerInterface $entityManager, string $slug): Response
    {
        $auth = new Auth();
        $auth->requireAdmin();  // Require admin access

        $service = $entityManager->getRepository(Service::class)->findOneBy(['slug' => $slug]);  // Find an Institution entity by ID

        if (!$service) {  // If the Service entity is not found
            throw $this->createNotFoundException('Service not found');  // Throw a 404 exception
        }

        $institutionServices = $service->getServiceInstitutions();  // Get the InstitutionServices for the Service
        return $this->render('service/show.html.twig', [  // Render the Institution show page
            'service' => $service,  // Pass the Institution entity to the template
            'institutions' => $institutionServices,  // Pass the title to the template
        ]);
    }

    /**
     * Edit a Service entity
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param string $slug
     *
     * @return Response
     *
     * @throws Exception
     */
    #[Route('/service/{slug}/edit', name: 'edit_service')]
    public function editService(EntityManagerInterface $entityManager, Request $request, string $slug): Response
    {
        $auth = new Auth();
        $auth->requireAdmin();  // Require admin access

        $service = $entityManager->getRepository(Service::class)->findOneBy(['slug' => $slug]);  // Find an Institution entity by ID

        if (!$service) {  // If the Institution entity is not found
            throw $this->createNotFoundException('Service not found');  // Throw a 404 exception
        }

        $form = $this->createForm(ServiceType::class, $service);  // Create a form for the Institution entity
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {  // If the form is submitted and valid
            $service = $form->getData();  // Get the form data
            $entityManager->persist($service);  // Persist the Institution entity
            $entityManager->flush();  // Flush the entity manager

            $this->addFlash('success', $service->getName() . ' updated');  // Add a success flash message

            return $this->redirectToRoute('show_service', ['slug' => $slug]);  // Redirect to the edit Institution page
        }

        return $this->render('service/form.html.twig', [  // Render the edit Institution page
            'form' => $form,  // Pass the form to the template
        ]);
    }

    /**
     * Delete a Service entity
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param string $slug
     *
     * @return Response
     *
     * @throws Exception
     */
    #[Route('/service/{slug}/delete', name: 'delete_service')]
    public function deleteService(EntityManagerInterface $entityManager, Request $request, string $slug): Response
    {
        $auth = new Auth();
        $auth->requireAdmin();  // Require admin access

        $service = $entityManager->getRepository(Service::class)->findOneBy(['slug' => $slug]);  // Find a Service entity by slug

        if (!$service) {  // If the Institution entity is not found
            throw $this->createNotFoundException('Service not found');  // Throw a 404 exception
        }

        $form = $this->createForm(EntityDeleteType::class);  // Create a form for the AuthzType entity
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {  // If the form is submitted and valid
            $entityManager->remove($service);  // Remove the Service entity
            $entityManager->flush();  // Flush the entity manager

            $this->addFlash('success', 'Service "' . $service->getName() . '" deleted');  // Add a success flash message

            return $this->redirectToRoute('list_services');  // Redirect to the Institution list page
        }
        return $this->render('service/delete.html.twig', [  // Render the delete Institution page
            'service' => $service,  // Pass the Institution entity to the template
            'title' => 'Delete ' . $service->getName()
        ]);  // Pass the title to the template
    }

    /**
     * Sort Service entities by slug
     *
     * @param array $services
     *
     * @return array
     */
    public function alpha_services_slug(array $services): array
    {
        $alpha_services = [];
        foreach ($services as $service) {
            $alpha_services[$service->getSlug()] = $service;
        }
        ksort($alpha_services);
        return $alpha_services;
    }

    /**
     * Sort InstitutionServices by institution sort order
     *
     * @param array $institutionServices
     *
     * @return array
     */
    public function alpha_institution_services(array $institutionServices): array
    {
        $alpha_institution_services = [];
        foreach ($institutionServices as $institutionService) {
            $alpha_institution_services[$institutionService->getInstitution()->getPosition()] = $institutionService;
        }
        ksort($alpha_institution_services);
        return $alpha_institution_services;
    }
}