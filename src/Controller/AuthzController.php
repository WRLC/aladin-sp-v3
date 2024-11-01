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

        // Get the institutional Service's authorization type
        $authzType = $institutionService->getAuthzType();

        // NONE $authzType (Just make sure they're authenticated, which they already are)
        if ($authzType == 'none') {  // If the authorization field type is 'none'...
            $authz->setAuthorized();  // ...grant access
            return $this->returnAuthz($authz);
        }

        // Get authz members
        $authzMembers = $this->getAuthzMembers($institutionService);

        if ($authzType != 'any_alma') {  // If the authorization field type is not 'any_alma'...
            if (count($authzMembers) == 0) {  // ...and institutional service has no authorized members...
                return $this->returnAuthz($authz);  // ...deny access
            }
        }

        // IDP USER ID $authzType (only users with matching User ID)
        if ($authzType == 'user_id') {
            if (in_array($user, $authzMembers)) {  // If the user is in the authorized members list...
                $authz->setAuthorized();  // ...grant access
            }
            return $this->returnAuthz($authz);
        }

        // Get the alma user attributes, because everything else depends on them
        $attributes = $this->getAlmaAttributes($user, $institutionService->getInstitution()->getAlmaLocationCode());

        // If there's an error in the Alma attributes...
        if (key_exists('error', $attributes)) {
            // Go ahead and set the error message based on user ID sent by SSO
            $authz->setMatch(['Alma user not found', $attributes['error']]);

            // But first retry the call with all lowercase user ID
            $attributes = $this->getAlmaAttributes(
                strtolower($user),
                $institutionService->getInstitution()->getAlmaLocationCode()
            );
        }

        // If there's still an error, then return the error
        if (key_exists('error', $attributes)) {
            $authz->setErrors();  // Set the error flag
            return $this->returnAuthz($authz);  // Return an error
        }

        // ANY ALMA $authz_type (just make sure there's a matching Alma user)
        if ($authzType == 'any_alma') {  // If the authorization field type is 'none'...
            $authz->setAuthorized();
            return $this->returnAuthz($authz);
        }

        // ALMA ROLE $authz_type (only users with matching user_role)
        if ($authzType == 'user_role') {  // If the authorization field type is 'user_role'...
            $userRoles = $attributes['user_role'];  // Get the user roles
            $matchingRoles = [];  // Initialize a matching roles list

            // Iterate through the user roles to check for a match
            foreach ($userRoles as $userRole) {  // For each user role...
                if ($userRole['status']['value'] == 'ACTIVE') {  // Only look at active roles
                    // If the user role is in the authorized members list...
                    if (in_array($userRole['role_type']['value'], $authzMembers)) {
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

        // ALMA GROUP $authz_type (only users with matching user_group)
        if ($authzType == 'user_group') {
            if (in_array($attributes['user_group']['value'], $authzMembers)) {
                $authz->setAuthorized();
                $authz->setMatch([$attributes['user_group']['value']]);
                return $this->returnAuthz($authz);
            }
            // If we're still here, the user group isn't authorized
            return $this->returnAuthz($authz);  // Deny access
        }

        // And if we got this far, something ain't right...
        $authz->setMatch(['Unknown authorization type: ' . $authzType,]);
        $authz->setErrors();

        return $this->returnAuthz($authz);
    }

    /**
     * Get the Alma user attributes
     *
     * @param string $user
     * @param string $almaCode
     *
     * @return array<string, mixed>
     *
     * @throws TransportExceptionInterface
     */
    private function getAlmaAttributes(string $user, string $almaCode): array
    {
        $userApiCall = $this->authzUrl . '?uid=' . $user . '&inst=' . $almaCode;  // Set the Alma API call

        return $this->sessionApiCall($userApiCall);  // Return the response
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
