<?php

/** @noinspection PhpUnused */

namespace App\Controller;

use Memcached;
use SimpleSAML\Error\Exception;
use SimpleSAML\Utils\Auth;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class SessionsController
 */
class SessionsController extends AbstractController
{
    private string $memcachedHost;

    private string $memcachedPort;

    /**
     * SessionsController constructor.
     *
     * @param string $memcachedHost
     * @param string $memcachedPort
     */
    public function __construct(
        string $memcachedHost,
        string $memcachedPort
    ) {
        $this->memcachedHost = $memcachedHost;
        $this->memcachedPort = $memcachedPort;
    }
    /**
     * Display all Aladin-SP sessions
     *
     * @return Response
     *
     * @throws Exception
     */
    #[Route('/sessions', name: 'sessions')]
    public function memcached(): Response
    {
        // Check if user is admin
        $auth = new Auth();
        $auth->requireAdmin();

        $memConn = $this->createMemcachedConnection();  // Create memcached connection
        $memcached = $this->getOrderedAladin($memConn);  // Order memcached data by expiration date

        // Render the sessions page
        return $this->render('sessions/index.html.twig', ['memcached' => $memcached]);
    }

    /**
     * Clear all Aladin-SP sessions
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    #[Route('/sessions/clear', name: 'sessions_clear')]
    public function memcachedClear(Request $request): Response
    {
        // Check if user is admin
        $auth = new Auth();
        $auth->requireAdmin();

        // Create form
        $form = $this->createFormBuilder()
            ->add('clear', SubmitType::class, [
                'label' => 'Clear',
                'attr' => ['class' => 'btn btn-danger'],
            ])
            ->getForm();

        $form->handleRequest($request);  // Handle form request

        if ($form->isSubmitted() && $form->isValid()) {  // If form is submitted and valid
            $memConn = $this->createMemcachedConnection();  // Create memcached connection
            $memcached = $this->getOrderedAladin($memConn);  // Filter out non-Aladin-SP sessions

            foreach ($memcached as $key => $item) {  // Loop through all items
                $memConn->delete($key);  // Delete the item
            }

            // Add success message and redirect to sessions page
            $this->addFlash('success', 'All Aladin-SP sessions cleared');
            return $this->redirectToRoute('sessions');
        }

        // Render the clear sessions page
        return $this->render('sessions/clear.html.twig', ['form' => $form]);
    }

    /**
     * Clear a specific Aladin-SP session
     *
     * @param Request $request
     * @param string $key
     * @param string|null $index
     * @param string|null $slug
     * @return Response
     *
     * @throws Exception
     */
    #[Route('/sessions/clear/{key}', name: 'session_clear')]
    #[Route('/institution/{index}/sessions/clear/{key}/', name: 'institution_session_clear')]
    #[Route('/service/{slug}/sessions/clear/{key}/', name: 'service_session_clear')]
    public function sessionClear(Request $request, string $key, string $index = null, string $slug = null): Response
    {
        // Check if user is admin
        $auth = new Auth();
        $auth->requireAdmin();

        $memConn = $this->createMemcachedConnection(); // Create memcached connection
        $memConn->delete($key);  // Delete the session

        $this->addFlash('success', 'Session ' . $key . ' cleared');  // Add success message

        // Redirect to the appropriate page
        if ($request->attributes->get('_route') === 'institution_session_clear') {  // If route is institution session clear
            return $this->redirectToRoute('show_institution', ['index' => $index]);  // Redirect to institution sessions page
        }
        if ($request->attributes->get('_route') === 'service_session_clear') {  // If route is service session clear
            return $this->redirectToRoute('show_service', ['slug' => $slug]);  // Redirect to service sessions page
        }

        // Default redirect to sessions page
        return $this->redirectToRoute('sessions');
    }

    /**
     * Create a memcached connection
     **
     * @return Memcached
     */
    public function createMemcachedConnection(): Memcached
    {
        $memcached = new Memcached();  // Create a new Memcached object

        $memcached->addServer($this->memcachedHost, intval($this->memcachedPort));  // Add the memcached server

        return $memcached;  // Return the memcached object
    }

    /**
     * Get all Aladin-SP sessions ordered by expiration date
     *
     * @param Memcached $memConn
     *
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function getOrderedAladin(Memcached $memConn): array
    {
        $items = $this->getAllKeys($memConn);  // Get all keys from memcached
        $memcached = $this->getAllMemcached($memConn, $items);  // Get all memcached data for the given keys
        $memcached = $this->filterAladin($memcached);  // Filter out non-Aladin-SP sessions

        return $this->orderMemcachedData($memcached);  // Order memcached data by expiration date
    }

    /**
     * Get all current keys from memcached
     *
     * @param Memcached $memcached
     *
     * @return array<string>|Exception
     *
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    private function getAllKeys(Memcached $memcached): array | Exception
    {
        $host = $memcached->getServerList()[0]['host'];
        $port = $memcached->getServerList()[0]['port'];

        $allKeys = [];
        $sock = fsockopen($host, $port, $errno, $errstr);
        if ($sock === false) {
            throw new Exception("Error connection to server $host on port $port: ($errno) $errstr");
        }

        if (fwrite($sock, "stats items\n") === false) {
            throw new Exception("Error writing to socket");
        }

        $slabCounts = [];
        while (($line = fgets($sock)) !== false) {
            $line = trim($line);
            if ($line === 'END') {
                break;
            }

            // STAT items:8:number 3
            if (preg_match('!^STAT items:(\d+):number (\d+)$!', $line, $matches)) {
                $slabCounts[$matches[1]] = (int)$matches[2];
            }
        }

        foreach ($slabCounts as $slabNr => $slabCount) {
            if (fwrite($sock, "lru_crawler metadump $slabNr\n") === false) {
                throw new Exception('Error writing to socket');
            }

            while (($line = fgets($sock)) !== false) {
                $line = trim($line);
                if ($line === 'END') {
                    break;
                }

                // key=foobar exp=1596440293 la=1596439293 cas=8492 fetch=no cls=24 size=14908
                if (preg_match('!^key=(\S+)!', $line, $matches)) {
                    $allKeys[] = $matches[1];
                }
            }
        }

        if (fclose($sock) === false) {
            return new Exception('Error closing socket');
        }

        return $allKeys;
    }

    /**
     * Get all memcached data for the given keys
     *
     * @param Memcached $memConn
     * @param array<string> $keys
     *
     * @return array<string, mixed>
     */
    private function getAllMemcached(Memcached $memConn, array $keys): array
    {
        $memcached = [];
        foreach ($keys as $item) {
            if (str_starts_with($item, '_')) {
                $raw = $memConn->get($item);
                $lines = explode("\n", $raw);
                $memcached[$item] = [];
                foreach ($lines as $line) {
                    $parts = explode('=', $line);
                    if (count($parts) === 2) {
                        $memcached[$item][$parts[0]] = $parts[1];
                    }
                }
            }
        }
        return $memcached;
    }

    /**
     * Order memcached data by expiration date
     *
     * @param array<string, mixed> $memcached
     *
     * @return array<string, mixed>
     */
    private function orderMemcachedData(array $memcached): array
    {
        $orderedMemcached = [];
        foreach ($memcached as $key => $value) {
            $orderedMemcached[$key] = $value['Expiration'];
        }
        array_multisort($orderedMemcached, SORT_ASC, $memcached);

        return $memcached;
    }

    /**
     * Filter out non-Aladin-SP sessions
     *
     * @param array<string, mixed> $memcached
     *
     * @return array<string, mixed>
     */
    private function filterAladin(array $memcached): array
    {
        $sessions = [];  // Initialize sessions array
        foreach ($memcached as $key => $value) {  // Loop through all items
            if (key_exists('UserName', $value)) {  // If item contains a UserName, it's an Aladin-SP session
                $sessions[$key] = $value;  // Add the session to the sessions array
            }
        }
        return $sessions;  // Return the sessions array
    }
}
