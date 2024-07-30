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
        $almaCode = $institutionService->getInstitution()->getAlmaLocationCode();  // Get the institution's Alma location code
        $authzType = $institutionService->getAuthzType()->getType();  // Get the institutional Service's authorization type
        $authzMembers = [];
        $match = [];
        $errors = false;
        foreach ($institutionService->getAuthzMembers() as $member) {
            $authzMembers[] = $member->getMember();  // Get the institutional Service's authorization members
        }  // Get the institutional Service's authorization members

        // FREE
        if ($authzType == 'free') {  // If the authorization type is 'user'
            $attributes = $this->get_alma_attributes($user, $almaCode);  // Get the user's Alma attributes
            if (key_exists('error', $attributes)) {  // If there's an error in the Alma attributes...
                $authorized = false;  // ...set the authorized flag to false
                $match[] = $attributes['error'];  // ...set the user's values to the error message
                $errors = true;
            }
            else {  // If there's no error in the Alma attributes (i.e., the user exists)...
                $authorized = true;  // ...set the authorized flag to true
            }
        }

        // GROUP
        elseif ($authzType == 'group') {  // If the authorization type is 'group'

            if (count($authzMembers) == 0) {  // If the institutional service has no authorized groups
                $authorized = false;  // ...set the authorized flag to false
            }
            else {  // If the institutional service has authorized groups
                $attributes = $this->get_alma_attributes($user, $almaCode);  // Get the user's Alma attributes
                if (key_exists('error', $attributes)) {  // If there's an error in the Alma attributes...
                    $authorized = false;  // ...set the authorized flag to false
                    $match[] = $attributes['error'];  // ...set the user's values to the error message
                    $errors = true;
                } else {
                    $group = $attributes['user_group']['value'];  // Get the user's group
                    $authorized = in_array($group, $authzMembers);  // Set the authorized flag to true if the user's group is in the authorized groups
                    $match[] = $group;  // Set the user's values
                }
            }
        }

        // ROLE
        elseif ($authzType == 'role') {  // If the authorization type is 'role'...
            if (count($authzMembers) == 0) {  // If the institutional service has no authorized groups
                $authorized = false;  // Set the authorized flag to false
            }
            else {  // If the institutional service has authorized groups
                $attributes = $this->get_alma_attributes($user, $almaCode);  // ...get the user's Alma attributes
                if (key_exists('error', $attributes)) {  // If there's an error in the Alma attributes...
                    $authorized = false;  // ...set the authorized flag to false
                    $match[] = $attributes['error'];  // ...set the user's values to the error message
                    $errors = true;
                } else {
                    $roles = $attributes['user_role'];  // ...get the user's roles
                    $activeRoles = [];  // ...initialize an empty list of active roles
                    foreach ($roles as $role) {  // For each user role...
                        if ($role['status']['value'] == 'ACTIVE') {  // ...if the role is active...
                            $activeRoles[] = $role['role_type']['value'];  // ...add the role to the list of active roles
                        }
                    }
                    if (count($activeRoles) == 0) {  // If the user has no active roles...
                        $authorized = false;  // ...set the authorized flag to false
                    } else {  // If the user has active roles
                        $match = array_intersect($activeRoles, $authzMembers);  // Set the user's matched values
                        $authorized = count($match) > 0;  // Set the authorized flag to true if there are any matches
                    }
                }
            }
        }

        // USER
        elseif ($authzType == 'user') {  // If the authorization type is 'user'...
            if (count($authzMembers) == 0) {  // If the institutional service has no authorized groups
                $authorized = false;  // ...set the authorized flag to false
            }
            else {
                $attributes = $this->get_alma_attributes($user, $almaCode);  // ...get the user's Alma attributes
                if (key_exists('error', $attributes)) {  // If there's an error in the Alma attributes...
                    $authorized = false;  // ...set the authorized flag to false
                    $match[] = $attributes['error'];  // ...set the user's values to the error message
                    $errors = true;
                } else  {  // If there's no error in the Alma attributes (i.e., the user exists)...
                    if (!in_array($user, $authzMembers)) {  // ...if the user ID is not in the authorized users list...
                        $authorized = false;  // ...set the authorized flag to false
                    } else {  // ...if the user ID is in the authorized users...
                        $authorized = true;  // ...set the authorized flag to true
                    }
                }
            }
        }

        // DEFAULT
        else {  // If the authorization type is anything else, it's not a recognized authz type, so...
            $authorized = false;  // ...set the authorized flag to false
            $errors = true;
            $match[] = 'Unknown authorization type: ' . $authzType;  // ...set the user's values to the error message
        }

        return [
            'authorized' => $authorized,  // Return the authorized flag
            'authzType' => $authzType,  // Return the authorization type
            'authzMembers' => $authzMembers,  // Return the authorized members
            'match' => $match,  // Return the matched values
            'errors' => $errors,  // Return the errors flag
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