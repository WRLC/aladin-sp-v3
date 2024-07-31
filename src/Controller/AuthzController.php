<?php

namespace App\Controller;

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
     * @throws TransportExceptionInterface
     */
    public function authz(InstitutionService $institutionService, string $user): array
    {
        // Get the institutional Service's authorization type
        $authzType = $institutionService->getAuthzType();

        // NONE $authzType (Just make sure they're authenticated, which they already are)
        if ($authzType == 'none') {  // If the authorization field type is 'none'...
            return [  // ...grant access
                'authorized' => true,  // Access Granted
                'authzType' => $authzType,  // Authorization type
                'authzMembers' => [],  // Empty authorized members
                'match' => [],  // Empty matched values
                'errors' => false,  // No errors
            ];
        }

        // Get authz members
        $authzMembers = $institutionService->getAuthzMembers();  // Get the authorized members
        $authzList = [];  // Initialize the authorized list (need array of member values for matching)
        foreach ($authzMembers as $authzMember) {  // For each authorized member object...
            $authzList[] = $authzMember->getMember();  // ...add the member value to the authorized list
        }
        if ($authzType != 'any_alma') {
            if (count($authzMembers) == 0) {  // If the institutional service has no authorized members...
                return [  // ...deny access
                    'authorized' => false,  // Access Denied
                    'authzType' => $authzType,  // Authorization type
                    'authzMembers' => ['None',],  // Empty authorized members
                    'match' => [],  // Empty matched values
                    'errors' => false,  // No errors
                ];
            }

        }

        // IDP USER ID $authzType (only users with matching User ID)
        if ($authzType == 'user_id') {
            if (in_array($user, $authzList)) {  // If the user is in the authorized members list...
                return [ // ...grant access
                    'authorized' => true,  // Access Granted
                    'authzType' => $authzType,  // Authorization type
                    'authzMembers' => [],  // Empty authorized members (for security)
                    'match' => [],  // Empty matching user (already displayed in results)
                    'errors' => false,  // No errors
                ];
            }
            else {  // If the user is not in the authorized members list...
                return [  // ...deny access
                    'authorized' => false,  // Access Denied
                    'authzType' => $authzType,  // Authorization type
                    'authzMembers' => [],  // Empty authorized members (for security)
                    'match' => [],  // Empty matching users
                    'errors' => false,  // No errors
                ];
            }
        }

        // Get the alma user attributes, because everything else depends on them
        $almaCode = $institutionService->getInstitution()->getAlmaLocationCode();  // Alma IZ code
        $attributes = $this->get_alma_attributes($user, $almaCode);  // Alma user attributes

        // If there's an error in the Alma attributes...
        if (key_exists('error', $attributes)) {
            return [  // Return an error
                'authorized' => false,  // Access Denied
                'authzType' => [],  // Empty authorization type
                'authzMembers' => [],  // Empty authorized members
                'match' => ['Alma user not found:', $attributes['error']],  // Error message
                'errors' => true,  // Errors
            ];
        }

        // ANY ALMA $authz_type (just make sure there's a matching Alma user)
        if ($authzType == 'any_alma') {  // If the authorization field type is 'none'...
            return [  // ...grant access
                'authorized' => true,  // Access Granted
                'authzType' => $authzType,  // Authorization type
                'authzMembers' => [],  // Empty authorized members
                'match' => [],  // Empty matched values
                'errors' => false,  // No errors
            ];
        }

        // ALMA ROLE $authz_type (only users with matching user_role)
        if ($authzType == 'user_role') {  // If the authorization field type is 'user_role'...
            $userRoles = $attributes['user_role'];  // Get the user roles
            $matchingRoles = [];  // Initialize a matching roles list

            // Iterate through the user roles to check for a match
            foreach ($userRoles as $userRole) {  // For each user role...
                if ($userRole['status']['value'] == 'ACTIVE') {  // Only look at active roles
                    if (in_array($userRole['role_type']['value'], $authzList)) {  // If the user role is in the authorized members list...
                        $matchingRoles[] = $userRole['role_type']['value'];  // ...add the role to the matching roles list
                    }
                }
            }

            // Now check the matching role list count
            if (count($matchingRoles) == 0) {  // If there are no matching roles...
                return [  // ...deny access
                    'authorized' => false,  // Access Denied
                    'authzType' => $authzType,  // Authorization type
                    'authzMembers' => $authzMembers,  // Authorized members
                    'match' => [],  // Empty matched values
                    'errors' => false,  // No errors
                ];
            }

            // If we're still here (phew!), we had at least one matching role...
            return [  // ...so grant access
                'authorized' => true,  // Access Granted
                'authzType' => $authzType,  // Authorization type
                'authzMembers' => $authzMembers,  // Authorized members
                'match' => $matchingRoles,  // Matching roles
                'errors' => false,  // No errors
            ];
        }

        // ALMA GROUP $authz_type (only users with matching user_group)
        if ($authzType == 'user_group') {
            foreach ($attributes['user_group'] as $userGroup) {
                if (in_array($userGroup['value'], $authzList)) {
                    return [
                        'authorized' => true,  // Access Granted
                        'authzType' => $authzType,  // Authorization type
                        'authzMembers' => $authzMembers,  // Authorized groups
                        'match' => [$userGroup['value'],],  // Matching group
                        'errors' => false,  // No errors
                    ];
                }
            }
            // If we're still here, the user group isn't authorized
            return [
                'authorized' => false,  // Access Denied
                'authzType' => $authzType,  // Authorization type
                'authzMembers' => $authzMembers,  // Authorized members
                'match' => [],  // No matching groups
                'errors' => false,  // No errors
            ];
        }

        // And if we got this far, something ain't right...
        return [
            'authorized' => false,  // Access Denied
            'authzType' => $authzType,  // Authorization type
            'authzMembers' => $authzMembers,  // Authorized members
            'match' => ['Unknown authorization type: ' . $authzType,],  // Error message
            'errors' => true,  // Errors
        ];
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function get_alma_attributes(string $user, string $almaCode): array
    {
        $userApiCall = 'http://webservices.wrlc2k.wrlc.org:8002/lookup/patrons?uid=' . $user . '&inst=' . $almaCode;  // Set the Alma API call

        return $this->session_api_call($userApiCall);  // Return the response
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function session_api_call($endpoint): array
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
}