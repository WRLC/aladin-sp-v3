<?php

namespace App\Controller;

use App\Entity\AladinError;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class AladinErrorController
 */
class AladinErrorController extends AbstractController
{
    /**
     * Render an error
     *
     * @param AladinError $error
     *
     * @return array<string, mixed>
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