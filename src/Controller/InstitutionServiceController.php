<?php

namespace App\Controller;

use App\Entity\Institution;
use App\Entity\InstitutionService;
use App\Entity\Service;
use App\Form\Type\EntityDeleteType;
use App\Form\Type\InstitutionServiceType;
use Doctrine\ORM\EntityManagerInterface;
use SimpleSAML\Error\Exception;
use SimpleSAML\Utils\Auth;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class InstitutionServiceController extends AbstractController
{
    /**
     * Show an InstitutionService.
     *
     * @param string $index
     *
     * @return Response
     * @noinspection PhpUnused
     */
    #[Route('/institution/{index}/{slug}', name: 'show_institution_service')]
    public function showInstitutionService(string $index): Response
    {
        // Don't want to show the institution service, just redirect to the institution
        return $this->redirectToRoute('show_institution', ['index' => $index]);
    }

    /**
     * Add a new InstitutionService for an institution.
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param string $index
     * @param string $slug
     *
     * @return Response
     *
     * @throws Exception
     */
    #[Route('/institution/{index}/{slug}/add', name: 'add_institution_service')]
    public function addInstitutionService(EntityManagerInterface $entityManager, Request $request, string $index, string $slug): Response
    {
        $auth = new Auth();
        $auth->requireAdmin();

        $institution = $entityManager->getRepository(Institution::class)->findOneBy(['inst_index' => $index]);
        $service = $entityManager->getRepository(Service::class)->findOneBy(['slug' => $slug]);

        if (!$institution) {
            throw $this->createNotFoundException('No institution found for index ' . $index);
        }

        $institutionService = new InstitutionService();

        $form = $this->createForm(InstitutionServiceType::class, $institutionService, ['institution' => $institution, 'service' => $service, 'type' => 'add']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $institutionService = $form->getData();
            $entityManager->persist($institutionService);
            $entityManager->flush();

            $this->addFlash('success', 'Institution Service ' . $institutionService->getInstitution()->getName() . ': ' . $institutionService->getService()->getName() . ' added successfully.');

            return $this->redirectToRoute('show_institution', ['index' => $index]);
        }

        return $this->render('institution_service/form.html.twig', [
            'form' => $form,
            'institution' => $institution,
            'service' => $service, // This is the service slug passed in the URL, e.g. 'ldap
            'title' => 'Add  ' . $institution->getName() . ': ' . $service->getName(),
            'type' => 'add',
        ]);
    }

    /**
     * Edit an InstitutionService.
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param string $index
     * @param string $slug
     *
     * @return Response
     *
     * @throws Exception
     */
    #[Route('/institution/{index}/{slug}/edit', name: 'edit_institution_service')]
    #[Route('/service/{slug}/{index}/edit', name: 'edit_service_institution')]
    public function editInstitutionService(EntityManagerInterface $entityManager, Request $request, string $index, string $slug): Response
    {
        $auth = new Auth();
        $auth->requireAdmin();

        $return = $this->setReturn($request);

        $inst_id = $entityManager->getRepository(Institution::class)->findOneBy(['inst_index' => $index])->getId();
        $serv_id = $entityManager->getRepository(Service::class)->findOneBy(['slug' => $slug])->getId();
        $institutionService = $entityManager->getRepository(InstitutionService::class)->findOneBy(['Institution' => $inst_id, 'Service' => $serv_id]);

        if (!$institutionService) {
            throw $this->createNotFoundException('No service "' . $slug . '" found for institution "' . $index . '"');
        }

        $form = $this->createForm(InstitutionServiceType::class, $institutionService, ['institution' => $institutionService->getInstitution(), 'service' => $institutionService->getService(), 'type' => 'edit']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $institutionService = $form->getData();
            $entityManager->persist($institutionService);
            $entityManager->flush();

            $this->addFlash('success', 'Institutional Service ' . $institutionService->getInstitution()->getName() . ': ' . $institutionService->getService()->getName() . ' updated successfully.');

            if ($return == 'service') {
                return $this->redirectToRoute('show_service', ['slug' => $slug]);
            }

            return $this->redirectToRoute('show_institution', ['index' => $index]);
        }

        return $this->render('institution_service/form.html.twig', [
            'form' => $form,
            'institution' => $institutionService->getInstitution(),
            'service' => $institutionService->getService(),
            'type' => 'edit',
            'return' => $return,
        ]);
    }

    /**
     * Delete an InstitutionService.
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param string $index
     * @param string $slug
     *
     * @return Response
     *
     * @throws Exception
     */
    #[Route('/institution/{index}/{slug}/delete', name: 'delete_institution_service')]
    #[Route('/service/{slug}/{index}/delete', name: 'delete_service_institution')]
    public function deleteInstitutionService(EntityManagerInterface $entityManager, Request $request, string $index, string $slug): Response
    {
        $auth = new Auth();
        $auth->requireAdmin();

        $return = $this->setReturn($request);

        $inst_id = $entityManager->getRepository(Institution::class)->findOneBy(['inst_index' => $index])->getId();
        $serv_id = $entityManager->getRepository(Service::class)->findOneBy(['slug' => $slug])->getId();
        $institutionService = $entityManager->getRepository(InstitutionService::class)->findOneBy(['Institution' => $inst_id, 'Service' => $serv_id]);

        if (!$institutionService) {
            throw $this->createNotFoundException('No service "' . $slug . '" found for institution "' . $index . '"');
        }

        $form = $this->createForm(EntityDeleteType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->remove($institutionService);
            $entityManager->flush();

            $this->addFlash('success', 'Institution Service ' . $institutionService->getInstitution()->getName() . ': ' . $institutionService->getService()->getName() . ' deleted successfully.');

            if ($return == 'service') {
                return $this->redirectToRoute('list_services');
            }

            return $this->redirectToRoute('list_institutions');
        }

        return $this->render('institution_service/delete.html.twig', [
            'form' => $form,
            'institution' => $institutionService->getInstitution(),
            'service' => $institutionService->getService(),
            'title' => 'Delete ' . $institutionService->getInstitution()->getName() . ': ' . $institutionService->getService()->getName(),
            'return' => $return,
        ]);
    }

    /**
     * Set the return route.
     *
     * @param Request $request
     *
     * @return string
     */
    private function setReturn(Request $request): string
    {
        $return = 'institution';

        if ($request->get('_route') == 'edit_service_institution' || $request->get('_route') == 'delete_service_institution') {
            $return = 'service';
        }

        return $return;
    }

}