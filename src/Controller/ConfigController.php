<?php

namespace App\Controller;

use App\Entity\Config;
use App\Form\Type\ConfigType;
use Doctrine\ORM\EntityManagerInterface;
use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\Metadata\MetaDataStorageHandlerPdo;
use SimpleSAML\Utils\Auth;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConfigController extends AbstractController
{
    /**
     * Show the configuration form.
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     *
     * @return Response
     *
     * @throws Exception
     */
    #[Route('/config', name: 'config')]
    public function config(EntityManagerInterface $entityManager, Request $request): Response
    {
        $auth = new Auth();  // Create a new Auth object
        $auth->requireAdmin();  // Require authentication

        $configs = $entityManager->getRepository(Config::class)->findAll();  // Get all the configs

        $form = $this->createForm(ConfigType::class, $configs, ['configs' => $configs]);  // Create the config form
        $form->handleRequest($request);  // Handle the request

        if ($form->isSubmitted() && $form->isValid()) {  // If the form is submitted and valid
            foreach ($configs as $config) {
                $config->setValue($form->get($config->getName())->getData());  // Set the config value
                $entityManager->persist($config);  // Persist the config
            }
            $entityManager->flush();  // Flush the entity manager
            $this->addFlash('success', 'Configurations updated successfully!');  // Add a success flash message
        }

        return $this->render('config/config.html.twig', [
            'form' => $form,
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
     */
    #[Route('/config/pdo', name: 'generate_pdo_tables')]
    public function generatePdoTables(Request $request): Response
    {
        $auth = new Auth();  // Create a new Auth object
        $auth->requireAdmin();  // Require authentication

        $form = $this->createFormBuilder()
            ->add('generate', SubmitType::class, ['label' => 'Generate PDO Tables'])
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
                    $metadataStorageHandler = new MetaDataStorageHandlerPdo($source);
                    $result = $metadataStorageHandler->initDatabase();

                    if ($result === false) {
                        $this->addFlash('error', 'Failed to initialize metadata database.');  // Add an error flash message
                    } else {
                        $this->addFlash('success', 'Successfully initialized metadata database.');  // Add a success flash message
                    }
                }
            }
            return $this->redirectToRoute('config');  // Redirect to the config page
        }

        return $this->render('config/generate_pdo.html.twig', [
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
     */
    #[Route('/config/flatfile', name: 'convert_flatfile')]
    public function convertFlatfile(Request $request): Response
    {
        $auth = new Auth();  // Create a new Auth object
        $auth->requireAdmin();  // Require authentication

        $form = $this->createFormBuilder()
            ->add('convert', SubmitType::class, ['label' => 'Convert Flatfile to PDO'])
            ->getForm();  // Create the config form
        $form->handleRequest($request);  // Handle the request

        if ($form->isSubmitted() && $form->isValid()) {
            $sspConfig = Configuration::getConfig();  // Get the SimpleSAML configuration
            $sources = $sspConfig->getConfigItem('metadata.sources')->toArray();  // Dump the SimpleSAML configuration
            # Iterate through configured metadata sources and ensure
            # that a PDO source exists.
            foreach ($sources as $source) {
                # If pdo is configured, create the new handler and initialize the DB.
                if ($source['type'] === "pdo") {
                    $metadataStorageHandler = new MetaDataStorageHandlerPdo($source);
                    $metadataStorageHandler->initDatabase();

                    $filename = '/app/aladin-config/simplesamlphp/saml20-idp-remote.php';  // Get the metadata file
                    $metadata = [];
                    require_once $filename;  // Require the saml20-idp-remote.php file
                    $set = basename($filename, ".php");  // Get the set name
                    foreach ($metadata as $key => $value) {
                        $metadataStorageHandler->addEntry($key, $set, $value);  // Add the metadata entry
                    }
                }
            }

            $this->addFlash('success', 'Successfully converted flatfile to PDO.');  // Add a success flash message

            return $this->redirectToRoute('config');  // Redirect to the config page
        }

        return $this->render('config/flatfile.html.twig', [
            'form' => $form,
        ]);
    }
}