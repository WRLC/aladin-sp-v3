<?php

namespace App\Controller;

use App\Entity\AladinError;
use App\Entity\InstitutionService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class LoginAuthnController
 */
class LoginAuthnController extends AbstractController
{
    private LoggerInterface $aladinLogger;
    private LoggerInterface $aladinErrorLogger;
    private string $svcProvider;

    /**
     * @param LoggerInterface $aladinLogger
     * @param LoggerInterface $aladinErrorLogger
     * @param string $svcProvider
     */
    public function __construct(
        LoggerInterface $aladinLogger,
        LoggerInterface $aladinErrorLogger,
        string $svcProvider
    ) {
        $this->aladinLogger = $aladinLogger;
        $this->aladinErrorLogger = $aladinErrorLogger;
        $this->svcProvider = $svcProvider;
    }

    /**
     * Authenticate the user
     *
     * @param InstitutionService $institutionService
     *
     * @return AladinError|array<string, mixed>
     *
     * @throws Exception
     */
    public function doLoginAuth(InstitutionService $institutionService): AladinError|array
    {
        $authnController = new AuthnController($this->svcProvider);  // Create a new AuthnController
        return $this->getUserAttributes($authnController, $institutionService);  // Get the user attributes
    }

    /**
     * Get the user attributes
     *
     * @param AuthnController $authnController
     * @param InstitutionService $institutionService
     *
     * @return array<string, mixed>|AladinError
     *
     * @throws Exception
     */
    private function getUserAttributes(AuthnController $authnController, InstitutionService $institutionService): array|AladinError
    {
        $error = new AladinError('authentication', 'Login Error:');
        $userAttributes = $authnController->authnUser($institutionService->getInstitution());  // Authenticate the user

        // If authentication fails, return an error page
        if ($userAttributes instanceof Exception) {
            $error->setIntro('Authentication failed');
            $error->setErrors([$userAttributes->getMessage()]);
            $this->aladinErrorLogger->error('[' . $error->getType() . '] ' . $error->getIntro() . ': ' . $userAttributes->getMessage());
            return $error;
        }
        return $userAttributes;
    }

    /**
     * Get the institution user ID
     *
     * @param InstitutionService $institutionService
     * @param array<string, mixed> $userAttributes
     *
     * @return string|AladinError
     */
    public function getInstUid(
        InstitutionService $institutionService,
        array $userAttributes,
    ): string | AladinError {

        // Get the user ID from the user attributes
        $userId = $userAttributes[$institutionService->getInstitution()->getIdAttribute()][0];
        if (empty($userId)) {
            $error = new AladinError('authentication', 'Login Error:');
            $error->setIntro('Authentication failed');
            $error->setErrors(['User ID not found']);
            $this->aladinErrorLogger->error('[' . $error->getType() . '] ' . $error->getIntro() . ': ' . $error->getErrors()[0]);
            return $error;
        }

        // Get the special transform toggler value
        $transform = $institutionService->getInstitution()->isSpecialTransform();

        // If the inst requires a special transform...
        if ($transform) {
            $splitEmail = explode('@', $userId);  // split the user id at the '@'
            $userId = $splitEmail[0];  // set the user id to the first part
        }

        // Log the authentication result
        $this->aladinLogger->debug('Authenticated User: ' . $userId . ' for ' . $institutionService->getInstitution()->getName());

        return $userId;
    }
}
