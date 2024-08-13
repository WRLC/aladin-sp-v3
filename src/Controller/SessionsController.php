<?php

namespace App\Controller;

use App\Entity\Config;
use Doctrine\ORM\EntityManagerInterface;
use Memcached;
use SimpleSAML\Error\Exception;
use SimpleSAML\Utils\Auth;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SessionsController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route('/sessions', name: 'sessions')]
    public function memcached(EntityManagerInterface $entityManager): Response
    {
        $auth = new Auth();
        $auth->requireAdmin();

        $m = new Memcached();
        $mServer = $entityManager->getRepository(Config::class)->findOneBy(['name' => 'memcached_host'])->getValue();
        $mServerPort = $entityManager->getRepository(Config::class)->findOneBy(['name' => 'memcached_port'])->getValue();
        $m->addServer($mServer, $mServerPort);
        $items = $this->getAllKeys($mServer, $mServerPort);
        $memcached = [];
        foreach ($items as $item) {
            if (str_starts_with($item, '_')) {
                $raw = $m->get($item);
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
        $orderedMemcached = [];
        foreach ($memcached as $key => $value) {
            $orderedMemcached[$key] = $value['Expiration'];
        }
        array_multisort($orderedMemcached, SORT_ASC, $memcached);
        return $this->render('sessions/index.html.twig', ['memcached' => $memcached]);
    }

    /**
     * @throws Exception
     */
    #[Route('/sessions/clear', name: 'sessions_clear')]
    public function memcachedClear(EntityManagerInterface $entityManager, Request $request): Response
    {
        $auth = new Auth();
        $auth->requireAdmin();

        $form = $this->createFormBuilder()
            ->add('clear', SubmitType::class, [
                'label' => 'Clear',
                'attr' => ['class' => 'btn btn-danger'],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $m = new Memcached();
            $mServer = $entityManager->getRepository(Config::class)->findOneBy(['name' => 'memcached_host'])->getValue();
            $mServerPort = $entityManager->getRepository(Config::class)->findOneBy(['name' => 'memcached_port'])->getValue();
            $m->addServer($mServer, $mServerPort);
            $items = $this->getAllKeys($mServer, $mServerPort);
            foreach ($items as $item) {
                if (str_starts_with($item, '_')) {
                    $m->delete($item);
                }
            }
            $this->addFlash('success', 'All Aladin-SP sessions cleared');
            return $this->redirectToRoute('sessions');
        }
        return $this->render('sessions/clear.html.twig', ['form' => $form]);
    }

    /**
     * @throws Exception
     */
    #[Route('/sessions/clear/{key}', name: 'session_clear')]
    public function memcachedClearKey(EntityManagerInterface $entityManager, string $key): Response
    {
        $auth = new Auth();
        $auth->requireAdmin();

        $m = new Memcached();
        $mServer = $entityManager->getRepository(Config::class)->findOneBy(['name' => 'memcached_host'])->getValue();
        $mServerPort = $entityManager->getRepository(Config::class)->findOneBy(['name' => 'memcached_port'])->getValue();
        $m->addServer($mServer, $mServerPort);
        $m->delete($key);
        $this->addFlash('success', 'Session ' . $key . ' cleared');
        return $this->redirectToRoute('sessions');
    }

    /**
     * @throws Exception
     */
    private function getAllKeys(string $host, int $port): array
    {
        $allKeys = [];
        $sock = fsockopen($host, $port, $errno, $errstr);
        if ($sock === false) {
            throw new Exception("Error connection to server {$host} on port {$port}: ({$errno}) {$errstr}");
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
            if (fwrite($sock, "lru_crawler metadump {$slabNr}\n") === false) {
                throw new Exception('Error writing to socket');
            }

            $count = 0;
            while (($line = fgets($sock)) !== false) {
                $line = trim($line);
                if ($line === 'END') {
                    break;
                }

                // key=foobar exp=1596440293 la=1596439293 cas=8492 fetch=no cls=24 size=14908
                if (preg_match('!^key=(\S+)!', $line, $matches)) {
                    $allKeys[] = $matches[1];
                    $count++;
                }
            }
        }

        if (fclose($sock) === false) {
            throw new Exception('Error closing socket');
        }

        return $allKeys;
    }
}