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

class AuthzController extends AbstractController
{
    /**
     * Authorize the user
     *
     * @param InstitutionService $institutionService
     * @param string $user
     *
     * @return array
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
            $authz->setAuthorized(true);  // ...grant access
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
                $authz->setAuthorized(true);  // ...grant access
            }
            return $this->returnAuthz($authz);
        }

        // Get the alma user attributes, because everything else depends on them
        $attributes = $this->get_alma_attributes($user, $institutionService->getInstitution()->getAlmaLocationCode());  // Alma user attributes

        // If there's an error in the Alma attributes...
        if (key_exists('error', $attributes)) {
            $authz->setMatch(['Alma user not found', $attributes['error']]);  // Set the error message
            $authz->setErrors(true);  // Set the error flag
            return $this->returnAuthz($authz);  // Return an error
        }

        // ANY ALMA $authz_type (just make sure there's a matching Alma user)
        if ($authzType == 'any_alma') {  // If the authorization field type is 'none'...
            $authz->setAuthorized(true);
            return $this->returnAuthz($authz);
        }

        // ALMA ROLE $authz_type (only users with matching user_role)
        if ($authzType == 'user_role') {  // If the authorization field type is 'user_role'...
            $userRoles = $attributes['user_role'];  // Get the user roles
            $matchingRoles = [];  // Initialize a matching roles list

            // Iterate through the user roles to check for a match
            foreach ($userRoles as $userRole) {  // For each user role...
                if ($userRole['status']['value'] == 'ACTIVE') {  // Only look at active roles
                    if (in_array($userRole['role_type']['value'], $authzMembers)) {  // If the user role is in the authorized members list...
                        $matchingRoles[] = $userRole['role_type']['value'];  // ...add the role to the matching roles list
                    }
                }
            }

            // Now check the matching role list count
            if (count($matchingRoles) == 0) {  // If there are no matching roles...
                return $this->returnAuthz($authz);  // ...deny access
            }

            // If we're still here (phew!), we had at least one matching role...
            $authz->setAuthorized(true);  // ...so grant access
            $authz->setMatch($matchingRoles);  // ...set the matching roles
            dump($authz);
            return $this->returnAuthz($authz);  // ...and return the authorization
        }

        // ALMA GROUP $authz_type (only users with matching user_group)
        if ($authzType == 'user_group') {
            foreach ($attributes['user_group'] as $userGroup) {
                if (in_array($userGroup['value'], $authzMembers)) {
                    $authz->setAuthorized(true);
                    $authz->setMatch([$userGroup['value']]);
                    return $this->returnAuthz($authz);
                }
            }
            // If we're still here, the user group isn't authorized
            return $this->returnAuthz($authz);  // Deny access
        }

        // And if we got this far, something ain't right...
        $authz->setMatch(['Unknown authorization type: ' . $authzType,]);
        $authz->setErrors(true);

        return $this->returnAuthz($authz);
    }

    /**
     * Get the Alma user attributes
     *
     * @param string $user
     * @param string $almaCode
     *
     * @return array
     *
     * @throws TransportExceptionInterface
     */
    private function get_alma_attributes(string $user, string $almaCode): array
    {
        $userApiCall = 'http://webservices.wrlc2k.wrlc.org:8002/lookup/patrons?uid=' . $user . '&inst=' . $almaCode;  // Set the Alma API call

        return $this->session_api_call($userApiCall);  // Return the response
    }

    /**
     * Make the API call
     *
     * @param string $endpoint
     *
     * @return array
     *
     * @throws TransportExceptionInterface
     */
    private function session_api_call(string $endpoint): array
    {
        $client = HttpClient::create();  // Create a new HTTP client
        $response = $client->request('GET', $endpoint);  // Make the API call

        try {
            return $response->toArray();  // Return the response as an array
        }
        catch (ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            return ['error' => $e->getMessage()];
        }  // Return the error message
    }

    /**
     * Get the authorized members
     *
     * @param InstitutionService $institutionService
     *
     * @return array
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
     * @return array
     */
    private function returnAuthz(Authz $authz): array
    {
        return [
            'authorized' => $authz->getAuthorized(),
            'authzType' => $authz->getInstitutionService()->getAuthzType(),
            'authzMembers' => $authz->getInstitutionService()->getAuthzMembers(),
            'match' => $authz->getMatch(),
            'errors' => $authz->getErrors(),
        ];
    }
}