<?php

namespace App\Controller;

use App\Entity\AladinError;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AladinErrorController extends AbstractController
{
    private LoggerInterface $aladinErrorLogger;

    public function __construct(LoggerInterface $aladinErrorLogger)
    {
        $this->aladinErrorLogger = $aladinErrorLogger;
    }

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
        $this->aladinErrorLogger->error('[' . $error->getType() . '] ' . $error->getIntro() . ':', $error->getErrors());
    }

}