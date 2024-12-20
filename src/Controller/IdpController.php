<?php

/** @noinspection PhpUnused */

/** @noinspection PhpUnusedParameterInspection */

namespace App\Controller;

use App\Entity\AladinError;
use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Metadata\MetaDataStorageHandlerPdo;
use SimpleSAML\Module\metarefresh\Controller\MetaRefresh;
use SimpleSAML\Utils\Auth;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class IdpController
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IdpController extends AbstractController
{
    private string $memcachedHost;

    private string $memcachedPort;

    /**
     * IdpController constructor
     *
     * @param string $memcachedHost
     * @param string $memcachedPort
     */
    public function __construct(string $memcachedHost, string $memcachedPort)
    {
        $this->memcachedHost = $memcachedHost;
        $this->memcachedPort = $memcachedPort;
    }
    /**
     * Show the current IDP metadata.
     *
     * @return Response
     *
     * @throws Exception
     * @throws \Exception
     */
    #[Route('/idps', name: 'current_metadata')]
    public function currentMetadata(): Response
    {
        $auth = new Auth();  // Create a new Auth object
        $auth->requireAdmin();  // Require authentication

        $idpController = new InstitutionController($this->memcachedHost, $this->memcachedPort);  // Create a new InstitutionController object
        $metadata = $idpController->getIdps();  // Get the IDPs
        ksort($metadata);  // Sort the IDPs

        return $this->render('idps/current_metadata.html.twig', [
            'metadata' => $metadata,
        ]);
    }

    /**
     * Delete an IDP.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws Exception
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    #[Route('/idps/delete', name: 'delete_idp')]
    public function deleteIdp(Request $request): Response
    {
        $auth = new Auth();  // Create a new Auth object
        $auth->requireAdmin();  // Require authentication

        $entityid = urldecode($request->get('entityid'));  // Get the entity ID

        if (!$entityid) {  // If the entity ID is not set
            $error = new AladinError('Idp Error', 'Entity ID is missing');  // Throw an exception
            return $this->render('error.html.twig', [
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

    /**
     * Generate PDO tables.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws Exception
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    #[Route('/idps/pdo', name: 'generate_pdo_tables')]
    public function generatePdoTables(Request $request): Response
    {
        $auth = new Auth();  // Create a new Auth object
        $auth->requireAdmin();  // Require authentication

        $form = $this->createFormBuilder()
            ->add('generate', SubmitType::class, ['label' => 'Create SSP PDO Tables', 'attr' => ['class' => 'btn btn-primary']])
            ->getForm();  // Create the config form
        $form->handleRequest($request);  // Handle the request

        if ($form->isSubmitted() && $form->isValid()) {  // If the form is submitted and valid
            $sspConfig = Configuration::getConfig();  // Get the SimpleSAML configuration
            $sources = $sspConfig->getConfigItem('metadata.sources')->toArray();  // Dump the SimpleSAML configuration
            # Iterate through configured metadata sources and ensure
            # that a PDO source exists.
            foreach ($sources as $source) {
                # If pdo is configured, create the new handler and initialize the DB.
                if ($source['type'] === "pdo") {
                    $metadataStorage = new MetaDataStorageHandlerPdo($source);
                    $result = $metadataStorage->initDatabase();

                    if ($result === false) {
                        $this->addFlash('error', 'Failed to initialize metadata database.');  // Add an error flash message
                    }
                    if ($result !== false) {
                        $this->addFlash('success', 'Successfully initialized metadata database.');  // Add a success flash message
                    }
                }
            }
            return $this->redirectToRoute('current_metadata');  // Redirect to the config page
        }

        return $this->render('idps/generate_pdo.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Convert flatfile to PDO.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws Exception
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    #[Route('/idps/flatfile', name: 'convert_flatfile')]
    public function convertFlatfile(Request $request): Response
    {
        $auth = new Auth();  // Create a new Auth object
        $auth->requireAdmin();  // Require authentication

        $form = $this->createFormBuilder()
            ->add('convert', SubmitType::class, [
                'label' => 'Convert Flatfile to PDO',
                'attr' => ['class' => 'btn btn-primary']
            ])
            ->getForm();  // Create the config form
        $form->handleRequest($request);  // Handle the request

        if ($form->isSubmitted() && $form->isValid()) {
            $sspConfig = Configuration::getConfig();  // Get the SimpleSAML configuration
            $metadataDir = $sspConfig->getValue('metadatadir');  // Get the SimpleSAML configuration directory
            $sources = $sspConfig->getConfigItem('metadata.sources')->toArray();  // Dump the SimpleSAML configuration
            # Iterate through configured metadata sources and ensure
            # that a PDO source exists.
            foreach ($sources as $source) {
                # If pdo is configured, create the new handler and initialize the DB.
                if ($source['type'] === "pdo") {
                    $metadataStorage = new MetaDataStorageHandlerPdo($source);
                    $metadataStorage->initDatabase();

                    $filename = $metadataDir . '/saml20-idp-remote.php';  // Get the metadata file
                    $metadata = [];
                    require_once $filename;  // Require the saml20-idp-remote.php file
                    $set = basename($filename, ".php");  // Get the set name
                    foreach ($metadata as $key => $value) {  /** @phpstan-ignore foreach.emptyArray */
                        $metadataStorage->addEntry($key, $set, $value);  // Add the metadata entry
                    }
                }
            }

            $this->addFlash('success', 'Successfully converted flatfile to PDO.');  // Add a success flash message

            return $this->redirectToRoute('current_metadata');  // Redirect to the config page
        }

        return $this->render('idps/flatfile.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Refresh metadata.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws Exception
     */
    #[Route('/idps/metarefresh', name: 'metarefresh')]
    public function metarefresh(Request $request): Response
    {
        $auth = new Auth();  // Create a new Auth object
        $auth->requireAdmin();  // Require authentication

        $form = $this->createFormBuilder()
            ->add('refresh', SubmitType::class, [
                'label' => 'Refresh Metadata',
                'attr' => ['class' => 'btn btn-primary']
            ])
            ->getForm();  // Create the config form
        $form->handleRequest($request);  // Handle the request

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute('metarefresh_results');
        }
        return $this->render('idps/metarefresh.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Show the results of the metadata refresh.
     *
     * @return Response
     *
     * @throws Exception
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    #[Route('/idps/metarefresh/results', name: 'metarefresh_results')]
    public function metarefreshResults(): Response
    {
        $auth = new Auth();  // Create a new Auth object
        $auth->requireAdmin();  // Require authentication

        $metarefreshConfig = Configuration::getConfig('module_metarefresh.php');  // Get the metarefresh configuration
        $metarefresh = new MetaRefresh($metarefreshConfig);  // Create a new MetaRefresh object

        $logger = new (Logger::class);  // Create a new Logger object
        $logger->setCaptureLog();  // Set the log capture

        $metarefresh->main();  // Run the main function

        $logentries = $logger->getCapturedLog();  // Get the captured log

        return $this->render('idps/metarefresh_results.html.twig', [  // Render the results
            'logentries' => $logentries,
        ]);
    }
}
