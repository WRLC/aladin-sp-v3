<?php

namespace App\Controller;

use App\Entity\Authz;
use App\Entity\InstitutionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class AuthzController
 *
 * Handles the authorization of users for specific institutional services
 *
 */
class AuthzController extends AbstractController
{
    private string $authzUrl;

    /**
     * AuthzController constructor
     *
     * @param string $authzUrl
     */
    public function __construct(string $authzUrl)
    {
        $this->authzUrl = $authzUrl;
    }
    /**
     * Authorize the user
     *
     * @param InstitutionService $institutionService
     * @param string $user
     *
     * @return array<string, mixed>
     *
     * @throws TransportExceptionInterface
     */
    public function authz(InstitutionService $institutionService, string $user): array
    {
        $authz = new Authz($institutionService);  // Create a new Authz object defaulting to unauthorized

        return $this->authzStatus($institutionService, $user, $authz);  // Get the authorization status
    }

    /**
     * Get the authorization status
     *
     * @param InstitutionService $institutionService
     * @param string $user
     * @param Authz $authz
     *
     * @return array<string, mixed>
     *
     * @throws TransportExceptionInterface
     */
    private function authzStatus(InstitutionService $institutionService, string $user, Authz $authz): array
    {
        return match ($institutionService->getAuthzType()) {
            'none' => $this->authzNone($authz),
            'user_id' => $this->authzUserId($institutionService, $user, $authz),
            'any_alma' => $this->authzAnyAlma($institutionService, $user, $authz),
            'user_role' => $this->authzUserRole($institutionService, $user, $authz),
            'user_group' => $this->authzUserGroup($institutionService, $user, $authz),
            default => $this->authzError($institutionService, $user, $authz)
        };
    }

    /**
     * Handle the 'none' authorization type
     *
     * @param Authz $authz
     *
     * @return array<string, mixed>
     */
    private function authzNone(Authz $authz): array
    {
        $authz->setAuthorized();  // ...grant access
        return $this->returnAuthz($authz);
    }

    /**
     * Handle the 'user_id' authorization type
     *
     * @param InstitutionService $institutionService
     * @param string $user
     * @param Authz $authz
     *
     * @return array<string, mixed>
     */
    private function authzUserId(InstitutionService $institutionService, string $user, Authz $authz): array
    {
        // If the user is not in the authorized members list...
        if (!in_array($user, $this->getAuthzMembers($institutionService))) {
            $authz->setErrors();  // ...set the error flag
            $authz->setMatch(  // ...set the matching user
                ['User ' . $user . ' not authorized to use ' . $institutionService->getService()->getName()]
            );
            return $this->returnAuthz($authz);  // ...return an error
        }
        $authz->setAuthorized();  // ...grant access
        return $this->returnAuthz($authz);
    }

    /**
     * Handle the 'any_alma' authorization type
     *
     * @param InstitutionService $institutionService
     * @param string $user
     * @param Authz $authz
     *
     * @return array<string, mixed>
     *
     * @throws TransportExceptionInterface
     */
    private function authzAnyAlma(InstitutionService $institutionService, string $user, Authz $authz): array
    {
        $attributes = $this->getAlmaAttributes($user, $institutionService);  // Get the user attributes

        // If the attributes are are an error...
        if (key_exists('error', $attributes)) {
            $authz->setErrors();  // ...set the error flag
            $authz->setMatch(  // ...set the matching user
                [
                    'User ' . $user . ' not authorized to use ' . $institutionService->getService()->getName(),
                    $attributes['error']
                ]
            );
            return $this->returnAuthz($authz);  // ...return an error
        }
        // Otherwise...
        $authz->setAuthorized();  // ...grant access
        return $this->returnAuthz($authz);  // ...return the authorization
    }

    /**
     * Handle the 'user_role' authorization type
     *
     * @param InstitutionService $institutionService
     * @param string $user
     * @param Authz $authz
     *
     * @return array<string, mixed>
     *
     * @throws TransportExceptionInterface
     */
    private function authzUserRole(InstitutionService $institutionService, string $user, Authz $authz): array
    {
        // If there are no approved roles...
        if (count($this->getAuthzMembers($institutionService)) == 0) {
            return $this->returnAuthz($authz);  // ...deny access
        }

        $attributes = $this->getAlmaAttributes($user, $institutionService);  // Get the user attributes

        // If the attributes are are an error...
        if (key_exists('error', $attributes)) {
            $authz->setErrors();  // ...set the error flag
            $authz->setMatch(  // ...set the matching user
                [
                    'User ' . $user . ' not authorized to use ' . $institutionService->getService()->getName(),
                    $attributes['error'],
                ]
            );
            return $this->returnAuthz($authz);  // ...return an error
        }

        $matchingRoles = [];  // Initialize a matching roles list
        // Iterate through the user roles to check for a match
        foreach ($attributes['user_role'] as $userRole) {  // For each user role...
            if ($userRole['status']['value'] == 'ACTIVE') {  // Only look at active roles
                // If the user role is in the authorized members list...
                if (in_array($userRole['role_type']['value'], $this->getAuthzMembers($institutionService))) {
                    // ...add the role to the matching roles list
                    $matchingRoles[] = $userRole['role_type']['value'];
                }
            }
        }

        // Now check the matching role list count
        if (count($matchingRoles) == 0) {  // If there are no matching roles...
            return $this->returnAuthz($authz);  // ...deny access
        }

        // If we're still here (phew!), we had at least one matching role...
        $authz->setAuthorized();  // ...so grant access
        $authz->setMatch($matchingRoles);  // ...set the matching roles
        return $this->returnAuthz($authz);  // ...and return the authorization
    }

    /**
     * Handle the 'user_group' authorization type
     *
     * @param InstitutionService $institutionService
     * @param string $user
     * @param Authz $authz
     *
     * @return array<string, mixed>
     *
     * @throws TransportExceptionInterface
     */
    private function authzUserGroup(InstitutionService $institutionService, string $user, Authz $authz): array
    {
        $attributes = $this->getAlmaAttributes($user, $institutionService);  // Get the user attributes

        // If the attributes are are an error...
        if (key_exists('error', $attributes)) {
            $authz->setErrors();  // ...set the error flag
            $authz->setMatch(  // ...set the matching user
                [
                    'User ' . $user . ' not authorized to use ' . $institutionService->getService()->getName(),
                    $attributes['error'],
                ]
            );
            return $this->returnAuthz($authz);  // ...return an error
        }

        if (in_array($attributes['user_group']['value'], $this->getAuthzMembers($institutionService))) {
            $authz->setAuthorized();
            $authz->setMatch([$attributes['user_group']['value']]);
            return $this->returnAuthz($authz);
        }
        // If we're still here, the user group isn't authorized
        return $this->returnAuthz($authz);  // Deny access
    }

    /**
     * Handle the 'error' authorization type
     *
     * @param InstitutionService $institutionService
     * @param string $user
     * @param Authz $authz
     *
     * @return array<string, mixed>
     */
    private function authzError(InstitutionService $institutionService, string $user, Authz $authz): array
    {
        $authz->setErrors();  // Set the error flag
        $authz->setMatch(  // Set the matching user
            ['User ' . $user . ' not authorized to use ' . $institutionService->getService()->getName()]
        );
        return $this->returnAuthz($authz);  // Return an error
    }

    /**
     * Get the Alma user attributes
     *
     * @param string $user
     * @param InstitutionService $institutionService
     * @return array<string, mixed>
     *
     * @throws TransportExceptionInterface
     */
    private function getAlmaAttributes(string $user, InstitutionService $institutionService): array
    {
        // Make the API call
        $attributes = $this->sessionApiCall($this
                ->authzUrl . '?uid=' . $user . '&inst=' . $institutionService
                ->getInstitution()
                ->getAlmaLocationCode());

        if (key_exists('error', $attributes)) {  // If there's an error in the Alma attributes...
            $attributes = $this->sessionApiCall(  // ...try again with the user ID in lowercase
                $this
                    ->authzUrl . '?uid=' . strtolower($user) . '&inst=' . $institutionService->getInstitution()
                    ->getAlmaLocationCode()
            );
        }
        return $attributes;
    }

    /**
     * Make the API call
     *
     * @param string $endpoint
     *
     * @return array<string, mixed>
     *
     * @throws TransportExceptionInterface
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function sessionApiCall(string $endpoint): array
    {
        $client = HttpClient::create();  // Create a new HTTP client
        $response = $client->request('GET', $endpoint);  // Make the API call

        try {
            return $response->toArray();  // Return the response as an array
        } catch (
            ClientExceptionInterface |
            DecodingExceptionInterface |
            RedirectionExceptionInterface |
            ServerExceptionInterface |
            TransportExceptionInterface $e
        ) {
            return ['error' => $e->getMessage()];
        }  // Return the error message
    }

    /**
     * Get the authorized members
     *
     * @param InstitutionService $institutionService
     *
     * @return array<string>
     */
    private function getAuthzMembers(InstitutionService $institutionService): array
    {
        $authzMembers = $institutionService->getAuthzMembers();
        $authzList = [];
        foreach ($authzMembers as $authzMember) {
            $authzList[] = $authzMember->getMember();
        }
        return $authzList;
    }

    /**
     * Return the authorization results
     *
     * @param Authz $authz
     *
     * @return array<string, mixed>
     */
    private function returnAuthz(Authz $authz): array
    {
        return [
            'authorized' => $authz->isAuthorized(),
            'authzType' => $authz->getInstitutionService()->getAuthzType(),
            'authzMembers' => $authz->getInstitutionService()->getAuthzMembers(),
            'match' => $authz->getMatch(),
            'errors' => $authz->isErrors(),
        ];
    }
}
