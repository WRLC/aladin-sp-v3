<?php

namespace App\Controller;

use App\Entity\Institution;
use App\Entity\Service;
use App\Form\Type\EntityDeleteType;
use App\Form\Type\InstitutionServiceSelectType;
use App\Form\Type\InstitutionType;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Utils\Auth;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This class defines a controller for Institution entity
 */

class InstitutionController extends AbstractController
{
    /**
     * Redirect / to the Institution list page
     */
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->redirectToRoute('list_institutions');  // Redirect to the Institution list page
    }

    /**
     * Lists all Institution entities
     *
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    #[Route('/institution', name: 'list_institutions')]
    public function listInstitutions(EntityManagerInterface $entityManager): Response
    {
        $auth = new Auth();
        $auth->requireAdmin();  // Require admin access

        $institutions = $entityManager->getRepository(Institution::class)->findAll();  // Get all Institution entities
        $institutions = $this->sort_institutions_position($institutions);  // Sort the Institution entities by index

        return $this->render('institution/index.html.twig', [  // Render the Institution list page
            'institutions' => $institutions,  // Pass the Institution entities to the template
            'title' => 'Institutions',  // Pass the title to the template
        ]);
    }

    /**
     * Creates a new Institution entity
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     *
     * @return Response
     *
     * @throws \SimpleSAML\Error\Exception
     */
    #[Route('/institution/new', name: 'create_institution')]
    public function createInstitution(EntityManagerInterface $entityManager, Request $request): Response
    {
        $auth = new Auth();
        $auth->requireAdmin();  // Require admin access

        $institution = new Institution();  // Create a new Institution entity

        $form = $this->createForm(InstitutionType::class, $institution);  // Create a form for the Institution entity
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {  // If the form is submitted and valid
            $institution = $form->getData();  // Get the form data
            $all_inst = $entityManager->getRepository(Institution::class)->findAll();  // Get all Institution entities
            $max_position = $this->get_highest_position($all_inst);  // Get the highest position
            $institution->setPosition($max_position + 1);  // Set the Institution position to the highest position + 1
            $entityManager->persist($institution);  // Persist the Institution entity
            $entityManager->flush();  // Flush the entity manager

            $this->addFlash('success', $institution->getName() . ' created');  // Add a success flash message

            return $this->redirectToRoute('list_institutions');  // Redirect to the new Institution page
        }

        return $this->render('institution/form.html.twig', [  // Render the new Institution page
            'form' => $form,  // Pass the form to the template
            'title' => 'Add Institution',  // Pass the title to the template
        ]);
    }

    /**
     * Shows a single Institution entity
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param string $index
     *
     * @return Response
     *
     * @throws \SimpleSAML\Error\Exception
     * @throws Exception
     */
    #[Route('/institution/{index}', name: 'show_institution')]
    public function showInstitution(EntityManagerInterface $entityManager, Request $request, string $index): Response
    {
        $auth = new Auth();
        $auth->requireAdmin();  // Require admin access

        $institution = $entityManager->getRepository(Institution::class)->findOneBy(['inst_index' => $index]);  // Find an Institution entity by ID
        if (!$institution) {  // If the Institution entity is not found
            throw $this->createNotFoundException('Institution not found');  // Throw a 404 exception
        }


        $idpDetails = $this->getIdpDetails($institution->getEntityId());  // Get IdP details

        // Get services not yet associated with the institution
        $services = $entityManager->getRepository(Service::class)->findAll();  // Get all Service entities
        $institutionServices = $institution->getInstitutionServices();  // Get the Institution services

        $serviceSlugs = [];
        foreach ($institutionServices as $institutionService) {  // For each Institution service
            $serviceSlugs[] = $institutionService->getService()->getSlug();  // Add the service slug to the service slugs array
        }
        $services = array_filter($services, function ($service) use ($serviceSlugs) {  // Filter the services
            return !in_array($service->getSlug(), $serviceSlugs);  // Return the services not in the service slugs array
        });


        $form = $this->createForm(InstitutionServiceSelectType::class, null, ['services' => $services]);  // Create a form for the Institution entity
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {  // If the form is submitted and valid
            $data = $form->getData();  // Get the form data
            return $this->redirectToRoute('add_institution_service', ['index' => $index, 'slug' => $data['service']->getSlug()]);  // Redirect to the create Institution service page
        }

        return $this->render('institution/show.html.twig', [  // Render the Institution show page
            'institution' => $institution,  // Pass the Institution entity to the template
            'idpDetails' => $idpDetails,  // Pass the IdP details to the template
            'services' => $services,  // Pass the services to the template
            'form' => $form,  // Pass the form to the template
            'title' => $institution->getName(),  // Pass the title to the template
        ]);
    }

    /**
     * Edit a single Institution entity
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param string $index
     *
     * @return Response
     *
     * @throws \SimpleSAML\Error\Exception
     */
    #[Route('/institution/{index}/edit', name: 'edit_institution')]
    public function editInstitution(EntityManagerInterface $entityManager, Request $request, string $index): Response
    {
        $auth = new Auth();
        $auth->requireAdmin();  // Require admin access

        $institution = $entityManager->getRepository(Institution::class)->findOneBy(['inst_index' => $index]);  // Find an Institution entity by ID

        if (!$institution) {  // If the Institution entity is not found
            throw $this->createNotFoundException('Institution not found');  // Throw a 404 exception
        }

        $form = $this->createForm(InstitutionType::class, $institution);  // Create a form for the Institution entity
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {  // If the form is submitted and valid
            $institution = $form->getData();  // Get the form data
            $entityManager->persist($institution);  // Persist the Institution entity
            $entityManager->flush();  // Flush the entity manager

            $this->addFlash('success', $institution->getName() . ' updated');  // Add a success flash message

            return $this->redirectToRoute('show_institution', ['index' => $index]);  // Redirect to the edit Institution page
        }


        return $this->render('institution/form.html.twig', [  // Render the edit Institution page
            'form' => $form,  // Pass the form to the template
            'title' => 'Edit ' . $institution->getName(),  // Pass the title to the template
        ]);
    }

    /**
     * Delete a single Institution entity
     *
     * @throws \SimpleSAML\Error\Exception
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param string $index
     *
     * @return Response
     */
    #[Route('/institution/{index}/delete', name: 'delete_institution')]
    public function deleteInstitution(EntityManagerInterface $entityManager, Request $request, string $index): Response
    {
        $auth = new Auth();
        $auth->requireAdmin();  // Require admin access

        $institution = $entityManager->getRepository(Institution::class)->findOneBy(['inst_index' => $index]);  // Find an Institution entity by ID

        if (!$institution) {  // If the Institution entity is not found
            throw $this->createNotFoundException('Institution not found');  // Throw a 404 exception
        }

        $form = $this->createForm(EntityDeleteType::class);  // Create a form for the Institution entity
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {  // If the form is submitted and valid
            $entityManager->remove($institution);  // Remove the Institution entity
            $entityManager->flush();  // Flush the entity manager

            $this->addFlash('success', 'Institution "' . $institution->getName() . '" deleted');  // Add a success flash message

            return $this->redirectToRoute('list_institutions');  // Redirect to the Institution list page
        }

        return $this->render('institution/delete.html.twig', [  // Render the Institution delete page
            'form' => $form,  // Pass the form to the template
            'institution' => $institution,  // Pass the Institution entity to the template
            'title' => 'Delete ' . $institution->getName(),  // Pass the title to the template
        ]);
    }

    /**
     * Sort the Institution entities by position
     * @throws \SimpleSAML\Error\Exception
     */
    #[Route('/institution/sort/{id}/{thing}', name: 'sort_institutions')]
    public function sortInstitutions(EntityManagerInterface $entityManager, int $id, int $thing): Response
    {
        $auth = new Auth();
        $auth->requireAdmin();  // Require admin access

        $institution = $entityManager->getRepository(Institution::class)->find($id);  // Find an Institution entity by ID
        $institution->setPosition($thing);  // Set the Institution position
        $entityManager->persist($institution);  // Persist the Institution entity
        $entityManager->flush();  // Flush the entity manager
        dump($thing);  // Dump the Institution entity

        return $this->redirectToRoute('list_institutions');  // Return a JSON response
    }

    /**
     * @throws Exception
     */
    public function getIdpDetails($entityid): array
    {
        $metadata = $this->getIdps();  // Get IdPs from the IdP controller
        $details = [];  // Initialize the details array
        foreach ($metadata as $idp) {  // For each IdP
            if (is_array($idp) && $idp['entityid'] === $entityid) {  // If the IdP is an array and the entity ID matches
                $details = $idp;  // Set the details to the IdP
                dump($details);  // Dump the details
                break;
            }
        }

        return $details;
    }

    /**
     * @throws Exception
     */
    public function getIdps(): array
    {
        $federationControllor = MetaDataStorageHandler::getMetadataHandler();  // Get the IdP metadata handler

        // Get the IdPs from the metadata handler
        return $federationControllor->getList('saml20-idp-remote', true);
    }

    public function sort_institutions_position(array $institutions): array
    {
        $ordered_institutions = [];
        foreach ($institutions as $institution) {
            $ordered_institutions[$institution->getPosition()] = $institution;
        }
        ksort($ordered_institutions);
        return $ordered_institutions;
    }

    public function get_highest_position(array $institutions): int
    {
        if (empty($institutions)) {  // If the Institution array is empty
            return 0;  // Return 0
        }

        $positions = [];  // Initialize the positions array
        foreach ($institutions as $institution) {  // For each Institution
            $positions[] = $institution->getPosition();  // Add the position to the positions array
        }
        return max($positions);  // Return the maximum position
    }
}