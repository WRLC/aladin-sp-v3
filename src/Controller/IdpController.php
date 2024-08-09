<?php

namespace App\Controller;

use App\Entity\AladinError;
use SimpleSAML\Error\Exception;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Metadata\MetaDataStorageHandlerPdo;
use SimpleSAML\Utils\Auth;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IdpController extends AbstractController
{
    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Route('/idps', name: 'current_metadata')]
    public function currentMetadata(Request $request): Response
    {
        $auth = new Auth();  // Create a new Auth object
        $auth->requireAdmin();  // Require authentication

        $idpController = new InstitutionController();
        $metadata = $idpController->getIdps();  // Get the IDPs
        ksort($metadata);  // Sort the IDPs

        return $this->render('idps/current_metadata.html.twig', [
            'metadata' => $metadata,
        ]);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Route('/idps/delete', name: 'delete_idp')]
    public function deleteIdp(Request $request): Response
    {
        $auth = new Auth();  // Create a new Auth object
        $auth->requireAdmin();  // Require authentication

        $entityid = urldecode($request->get('entityid'));  // Get the entity ID

        if (!$entityid) {  // If the entity ID is not set
            $error = new AladinError('Idp Error', 'Entity ID is missing');  // Throw an exception
            return $this->render('error/error.html.twig', [
                'error' => $error,
            ]);
        }

        $form = $this->createFormBuilder()  // Create a form builder
            ->add('delete', SubmitType::class, [
                'label' => 'Delete IdP',
                'attr' => ['class' => 'btn btn-danger']
            ])  // Add a delete button
            ->getForm();  // Get the form
        $form->handleRequest($request);  // Handle the request

        if ($form->isSubmitted() && $form->isValid()) {  // If the form is submitted and valid
            $federationControllor = MetaDataStorageHandler::getMetadataHandler();  // Get the IdP metadata handler
            $idp = $federationControllor->getMetaDataConfig($entityid, 'saml20-idp-remote')->toArray();  // Get the metadata config
            $idppdo = new MetaDataStorageHandlerPdo($idp);  // Create a new PDO metadata storage handler
            $idppdo->removeEntry($entityid, 'saml20-idp-remote');  // Delete the entry
            $this->addFlash('success', '"' . $entityid . '" deleted successfully!');  // Add a success flash message
            return $this->redirectToRoute('current_metadata');  // Redirect to the current metadata
        }

        return $this->render('idps/delete_idp.html.twig', [
            'form' => $form->createView(),
            'entityid' => $entityid,
        ]);
    }

}