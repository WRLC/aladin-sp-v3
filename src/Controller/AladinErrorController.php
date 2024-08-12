<?php

namespace App\Controller;

use App\Entity\AladinError;
use Monolog\Logger;
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
        if ($error->getLog()) {
            $this->logError($error);
        }
        return [
            'type' => $error->getType(),
            'intro' => $error->getIntro(),
            'errors' => $error->getErrors(),
        ];
    }

    /**
     * Log an error
     *
     * @param AladinError $error
     *
     * @return void
     */
    private function logError(AladinError $error): void
    {
        // Log the error
        $logger = new Logger('aladin');
        $logger->error('[' . $error->getType() . '] ' . $error->getIntro() . ':', $error->getErrors());
    }

}