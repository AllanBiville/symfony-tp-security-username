<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LoginFormAuthentificatorAuthenticator extends AbstractFormLoginAuthenticator implements PasswordAuthenticatedInterface
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private $entityManager;
    private $urlGenerator;
    private $csrfTokenManager;
    private $passwordEncoder;
    private $client;
    private $logger;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator, CsrfTokenManagerInterface $csrfTokenManager, UserPasswordEncoderInterface $passwordEncoder, HttpClientInterface $client)
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->client = $client;
    }

    public function supports(Request $request)
    {
        return self::LOGIN_ROUTE === $request->attributes->get('_route')
        && $request->isMethod('POST');
    }

    public function getCredentials(Request $request)
    {
        $credentials = [
            'username' => $request->request->get('username'),
            'password' => $request->request->get('password'),
            'csrf_token' => $request->request->get('_csrf_token'),
        ];
        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['username']
        );

        return $credentials;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $em = $this->entityManager;
        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }
        $username = $credentials['username'];
        $password = random_bytes(15);
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $credentials['username']]);

        if (!$user) {
            $user = new User();
            $user->setUsername($username);
            $user->setPassword($this->passwordEncoder->encodePassword($user, $password));
            $em->persist($user);
            $em->flush();
        }
        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        $logger = $this->logger;
        $em = $this->entityManager;
        $username = $credentials['username'];
        $password = $credentials['password'];
        $response = $this->client->request(
            'POST',
            'https://api.ecoledirecte.com/v3/login.awp',
            [
                'body' => 'data={
                    "identifiant": "' . $username . '",
                    "motdepasse":"' . urlencode($password) . '"
                }',
            ]
        );
        // $logger->warning("ecoledirecte".json_decode(print_r($response,true)));
        //  $logger->warning("ecoledirecte". print_r(json_decode($response->getContent(), true)));
        // $logger->warning("ecoledirecte" . print_r(json_decode($response->getContent()), true));
        $ecoleDirecteResponse = json_decode($response->getContent());
        $ecoleDirecteCode = $ecoleDirecteResponse->code;
        $ecoleDirecteMessage = $ecoleDirecteResponse->message;

        $ecoleDirectePrenom = $ecoleDirecteResponse->data->accounts[0]->prenom;
        $ecoleDirecteClasse = $ecoleDirecteResponse->data->accounts[0]->profile->classe->libelle;
        $logger->warning("ecoleDirectePrenom = '" . print_r($ecoleDirectePrenom, true)."'");
        $logger->warning("ecoleDirecteClasse = '" . print_r($ecoleDirecteClasse, true)."'");
         $logger->warning("ecoleDirecteAll" . print_r(json_decode($response->getContent()), true));

        
        // if ($ecoleDirecteCode == 200) {
        //     return true;
        // } else {
        //     $em->remove($user);
        //     $em->flush();
        //     return false;
        // }
        return false;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function getPassword($credentials): ?string
    {
        return $credentials['password'];
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('home'));
        throw new \Exception('TODO: provide a valid redirect inside ' . __FILE__);
    }

    protected function getLoginUrl()
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
