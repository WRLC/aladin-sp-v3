<?php

namespace App\Controller;

use App\Entity\AuthzType;
use App\Form\Type\AuthzTypeType;
use App\Form\Type\EntityDeleteType;
use Doctrine\ORM\EntityManagerInterface;
use SimpleSAML\Error\Exception;
use SimpleSAML\Utils\Auth;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AuthzTypeController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route('/authtype', name: 'list_authztypes')]
    public function listAuthzTypes(EntityManagerInterface $entityManager): Response
    {
        $auth = new Auth();
        $auth->requireAdmin();  // Require admin access

        $authztypes = $entityManager->getRepository(AuthzType::class)->findAll();  // Get all AuthzType entities
        $authztypes = $this->alpha_authztypes_slug($authztypes);  // Sort the AuthzType entities by slug

        return $this->render('authz_type/index.html.twig', [
            'authztypes' => $authztypes,  // Pass the AuthzType entities
            'title' => 'Authorization Types',  // Pass the title
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route('/authtype/new', name: 'create_authztype')]
    public function createAuthzType(EntityManagerInterface $entityManager, Request $request): Response
    {
        $auth = new Auth();
        $auth->requireAdmin();  // Require admin access

        $authztype = new AuthzType();  // Create a new AuthzType entity
        $form = $this->createForm(AuthzTypeType::class, $authztype);  // Create a form for the AuthzType entity
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {  // If the form is submitted and valid
            $authztype = $form->getData();  // Get the form data
            $entityManager->persist($authztype);  // Persist the AuthzType entity
            $entityManager->flush();  // Flush the entity manager

            $this->addFlash('success', 'Authorization type "' . $authztype->getType() . '" created');  // Add a success flash message

            return $this->redirectToRoute('list_authztypes');  // Redirect to the new AuthzType page
        }

        return $this->render('authz_type/form.html.twig', [  // Render the new AuthzType page
            'form' => $form,  // Pass the form to the template
            'title' => 'Add AuthzType',  // Pass the title to the template
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route('/authtype/edit/{type}', name: 'edit_authztype')]
    public function editAuthzType(EntityManagerInterface $entityManager, Request $request, string $type): Response
    {
        $auth = new Auth();
        $auth->requireAdmin();  // Require admin access

        $authztype = $entityManager->getRepository(AuthzType::class)->findOneBy(['type' => $type]);  // Get the AuthzType entity

        if (!$authztype) {
            throw $this->createNotFoundException('The Authorization Type "' . $type . '" does not exist');
        }

        $form = $this->createForm(AuthzTypeType::class, $authztype);  // Create a form for the AuthzType entity
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {  // If the form is submitted and valid
            $authztype = $form->getData();  // Get the form data
            $entityManager->persist($authztype);  // Persist the AuthzType entity
            $entityManager->flush();  // Flush the entity manager

            $this->addFlash('success', 'Authorization type "' . $authztype->getType() . '" updated');  // Add a success flash message

            return $this->redirectToRoute('list_authztypes');  // Redirect to the new AuthzType page
        }

        return $this->render('authz_type/form.html.twig', [  // Render the new AuthzType page
            'form' => $form,  // Pass the form to the template
            'title' => 'Edit AuthzType',  // Pass the title to the template
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route('/authtype/delete/{type}', name: 'delete_authztype')]
    public function deleteAuthzType(EntityManagerInterface $entityManager, Request $request, string $type): Response
    {
        $auth = new Auth();
        $auth->requireAdmin();  // Require admin access

        $authztype = $entityManager->getRepository(AuthzType::class)->findOneBy(['type' => $type]);  // Get the AuthzType entity

        if (!$authztype) {
            throw $this->createNotFoundException('The Authorization Type "' . $type . '" does not exist');
        }

        $form = $this->createForm(EntityDeleteType::class);  // Create a form for the AuthzType entity
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {  // If the form is submitted and valid
            $entityManager->remove($authztype);  // Remove the AuthzType entity
            $entityManager->flush();  // Flush the entity manager

            $this->addFlash('success',  'Authorization type "' . $authztype->getType() . '" deleted');  // Add a success flash message

            return $this->redirectToRoute('list_authztypes');  // Redirect to the new AuthzType page
        }

        return $this->render('authz_type/delete.html.twig', [  // Render the delete AuthzType page
            'form' => $form,  // Pass the form to the template
            'authztype' => $authztype,  // Pass the AuthzType entity to the template
            'title' => 'Delete AuthzType',  // Pass the title to the template
        ]);
    }

    public function alpha_authztypes_slug($authztypes): array
    {
        $alpha_authztypes = [];
        foreach ($authztypes as $authztype) {
            $alpha_authztypes[$authztype->getType()] = $authztype;
        }
        ksort($alpha_authztypes);
        return $alpha_authztypes;
    }

}