<?php

namespace App\Controller;

use App\Entity\Config;
use App\Form\Type\ConfigType;
use Doctrine\ORM\EntityManagerInterface;
use SimpleSAML\Error\Exception;
use SimpleSAML\Utils\Auth;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

}