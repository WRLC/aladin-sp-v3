<?php

namespace App\Controller;

use App\Entity\AladinError;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AladinErrorController extends AbstractController
{
    /**
     * Render an error
     *
     * @param AladinError $error
     *
     * @return array
     */
    public function renderError(AladinError $error): array
    {
        return [
            'type' => $error->getType(),
            'intro' => $error->getIntro(),
            'errors' => $error->getErrors(),
        ];
    }

}