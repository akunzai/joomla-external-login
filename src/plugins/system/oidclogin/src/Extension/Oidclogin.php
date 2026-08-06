<?php

/**
 * @author      Charley Wu <akunzai@gmail.com>
 * @copyright   Copyright (C) 2008-2026 Christophe Demko, Ioannis Barounis, Alexandre Gandois, Charley Wu. All rights reserved.
 * @license     GNU General Public License, version 2. http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @link        https://github.com/akunzai/joomla-external-login
 */

namespace Joomla\Plugin\System\Oidclogin\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Authentication\AuthenticationResponse;
use Joomla\CMS\Cache\CacheController;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Event\Result\ResultAwareInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Externallogin\Administrator\Authentication\ExternalAuthenticationResponse;
use Joomla\Component\Externallogin\Administrator\Service\Logger\ExternalloginLogEntry;
use Joomla\Component\Externallogin\Administrator\Table\ServerTable;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\Event;
use Joomla\Http\HttpFactory;
use Joomla\Plugin\System\Oidclogin\Claims\ClaimsResolver;
use Joomla\Plugin\System\Oidclogin\Jwk\JwkConverter;
use Joomla\Registry\Registry;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Signer\Rsa\Sha384;
use Lcobucci\JWT\Signer\Rsa\Sha512;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;
use RuntimeException;
use Throwable;

/**
 * External Login - OIDC (OpenID Connect) plugin.
 *
 * @since 5.2.0
 */
class Oidclogin extends CMSPlugin
{
    /**
     * @var ServerTable|null
     */
    protected $server;

    /**
     * The merged ID Token + UserInfo claims for the current login attempt.
     *
     * @var array<string, mixed>
     */
    protected array $claims = [];

    /**
     * The externallogin server row bound to the user being logged out, captured by
     * onUserLogout (before Joomla destroys the session) for onUserAfterLogout to redirect with,
     * once the local session is already gone.
     *
     * @var object|null
     */
    protected $logoutServer;

    /**
     * The id_token retained from the user's OIDC login, captured by onUserLogout from session
     * state before Joomla's own onUserLogout listener destroys it.
     */
    protected ?string $logoutIdToken = null;

    /**
     * Constructor.
     *
     * @param DispatcherInterface $dispatcher The event dispatcher
     * @param array $config An array that holds the plugin configuration
     */
    public function __construct(DispatcherInterface $dispatcher, array $config = [])
    {
        parent::__construct($dispatcher, $config);
        $this->loadLanguage();
        require_once JPATH_ADMINISTRATOR . '/components/com_externallogin/src/Service/Logger/ExternalloginLogger.php';
        Log::addLogger(
            ['logger' => 'externallogin', 'db_table' => '#__externallogin_logs', 'plugin' => 'system-oidclogin'],
            Log::ALL,
            [
                'system-oidclogin-autologin',
                'system-oidclogin-login',
                'system-oidclogin-logout',
                'system-oidclogin-verify',
                'system-oidclogin-discovery',
                'system-oidclogin-groups',
            ]
        );
    }

    /**
     * Get icons.
     */
    public function onGetIcons(Event $event): void
    {
        /** @var CMSApplication */
        $app = Factory::getApplication();
        $context = $event->getArgument('context');
        if ($context == 'com_externallogin') {
            // Ensure language is loaded for translation
            $this->loadLanguage();
            $wa = $app->getDocument()->getWebAssetManager();
            $wa->addInlineStyle(
                '.icon-oidclogin {'
                    . 'width: 48px;'
                    . 'height: 48px;'
                    . 'background-image: url(../media/plg_system_oidclogin/images/administrator/icon-48-oidclogin.png);'
                    . 'background-position: center center;'
                    . '}'
            );
            $result   = $event->getArgument('result', []);
            $result[] = [
                [
                    'image'  => 'icon-oidclogin',
                    'link'   => Route::_('index.php?option=com_externallogin&task=server.add&plugin=system.oidclogin'),
                    'alt'    => Text::_('PLG_SYSTEM_OIDCLOGIN_ALT'),
                    'text'   => Text::_('PLG_SYSTEM_OIDCLOGIN_TEXT'),
                    'target' => '_parent',
                ],
            ];

            if ($event instanceof ResultAwareInterface) {
                $event->addResult($result);
            } else {
                $event->setArgument('result', $result);
            }
        }
    }

    /**
     * Get option.
     */
    public function onGetOption(Event $event): void
    {
        $context = $event->getArgument('context');

        if ($context == 'com_externallogin') {
            // Ensure language is loaded for translation
            $this->loadLanguage();
            $result   = $event->getArgument('result', []);
            $result[] = ['value' => 'system.oidclogin', 'text' => 'PLG_SYSTEM_OIDCLOGIN_OPTION'];

            if ($event instanceof ResultAwareInterface) {
                $event->addResult($result);
            } else {
                $event->setArgument('result', $result);
            }
        }
    }

    /**
     * Prepare Form.
     */
    public function onContentPrepareForm(PrepareFormEvent $event): void
    {
        $form = $event->getForm();

        if ($form->getName() != 'com_externallogin.server.system.oidclogin') {
            return;
        }

        // Ensure language is loaded for form labels
        $this->loadLanguage();
        Form::addFormPath(dirname(__DIR__, 2) . '/forms');
        $form->loadFile('oidc', false);
    }

    /**
     * Get Login URL.
     *
     * Redirects to the IdP's discovered authorization_endpoint using
     * Authorization Code Flow with PKCE. The state/nonce/code_verifier for
     * this attempt are stored in the Joomla session (user state), not the
     * database, mirroring how Caslogin stores its own per-attempt state.
     */
    public function onGetLoginUrl(Event $event): void
    {
        $server = $event->getArgument('subject');

        if (!$server || $server->plugin != 'system.oidclogin') {
            return;
        }

        /** @var Registry $params */
        $params = $server->params;
        $discovery = $this->getDiscoveryDocument($params, $server->id);

        if ($discovery === null || empty($discovery['authorization_endpoint'])) {
            return;
        }

        $service = $event->getArgument('service');

        if ($service instanceof Uri) {
            $service = (string) $service;
        }

        $state = bin2hex(random_bytes(16));
        $nonce = bin2hex(random_bytes(16));
        $verifier = $this->base64UrlEncode(random_bytes(32));
        $challenge = $this->base64UrlEncode(hash('sha256', $verifier, true));

        /** @var CMSApplication */
        $app = Factory::getApplication();
        $app->setUserState('system.oidclogin.state.' . $server->id, $state);
        $app->setUserState('system.oidclogin.nonce.' . $server->id, $nonce);
        $app->setUserState('system.oidclogin.verifier.' . $server->id, $verifier);
        $app->setUserState('system.oidclogin.redirect.' . $server->id, $service);

        $query = [
            'response_type' => 'code',
            'client_id' => (string) $params->get('client_id'),
            'redirect_uri' => $service,
            'scope' => 'openid profile email',
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ];

        if ($params->get('locale')) {
            [$locale] = explode('-', Factory::getApplication()->getLanguage()->getTag());
            $query['ui_locales'] = $locale;
        }

        $separator = str_contains((string) $discovery['authorization_endpoint'], '?') ? '&' : '?';
        $url = $discovery['authorization_endpoint'] . $separator . http_build_query($query);

        $this->log($params, 'log_login', 'system-oidclogin-login', 'Redirecting to authorization endpoint on server ' . $server->id, Log::INFO);

        if ($event instanceof ResultAwareInterface) {
            $event->addResult($url);
        } else {
            $event->setArgument('result', $url);
        }
    }

    /**
     * After initialise event.
     *
     * Detects the return leg of an authorization-code login attempt this
     * plugin itself initiated (a "code"/"state"/"error" query parameter),
     * exchanges the code for tokens, verifies the ID Token, merges in
     * UserInfo claims, and rewires the request so Joomla's own login
     * machinery picks it up — mirroring Caslogin::onAfterInitialise().
     */
    public function onAfterInitialise(): void
    {
        // Ensure language is loaded for translation (early-lifecycle timing gap, issue #251)
        $this->loadLanguage();

        /** @var CMSApplication */
        $app = Factory::getApplication();
        $user = $app->getIdentity();

        if (!$user->guest) {
            return;
        }

        $input = $app->getInput();
        $code = $input->get('code', '', 'RAW');
        $state = $input->get('state', '', 'RAW');
        $error = $input->get('error', '', 'RAW');

        if (!$code && !$error) {
            return;
        }

        $serverID = $app->isClient('administrator') ? $input->get('server') : $app->getUserState('com_externallogin.server');

        if (!$serverID) {
            return;
        }

        /** @var MVCFactoryServiceInterface */
        $component = $app->bootComponent('com_externallogin');
        $mvcFactory = $component->getMVCFactory();
        /** @var ServerTable|bool $server */
        $server = $mvcFactory->createTable('Server', 'Administrator');

        if (!$server || !$server->load($serverID) || $server->plugin != 'system.oidclogin') {
            return;
        }

        /** @var Registry $params */
        $params = $server->params;
        $expectedState = $app->getUserState('system.oidclogin.state.' . $serverID);
        $nonce = $app->getUserState('system.oidclogin.nonce.' . $serverID);
        $verifier = $app->getUserState('system.oidclogin.verifier.' . $serverID);
        $redirectUri = $app->getUserState('system.oidclogin.redirect.' . $serverID);

        // One-time values: clear them regardless of the outcome below.
        $app->setUserState('system.oidclogin.state.' . $serverID, null);
        $app->setUserState('system.oidclogin.nonce.' . $serverID, null);
        $app->setUserState('system.oidclogin.verifier.' . $serverID, null);
        $app->setUserState('system.oidclogin.redirect.' . $serverID, null);

        if ($error || !$code || !$state || !is_string($expectedState) || !hash_equals($expectedState, (string) $state)) {
            $this->log(
                $params,
                'log_login',
                'system-oidclogin-login',
                'Rejected OIDC callback on server ' . $serverID . ($error ? (': ' . $error) : ' (state mismatch)'),
                Log::WARNING
            );

            return;
        }

        $this->log($params, 'log_login', 'system-oidclogin-login', 'Attempt to login using authorization code on server ' . $serverID, Log::INFO);

        $discovery = $this->getDiscoveryDocument($params, $serverID);

        if ($discovery === null) {
            return;
        }

        $tokens = $this->exchangeCode(
            $params,
            $discovery,
            (string) $code,
            is_string($verifier) ? $verifier : null,
            is_string($redirectUri) ? $redirectUri : '',
            $serverID
        );

        if ($tokens === null || empty($tokens['id_token'])) {
            return;
        }

        $idTokenClaims = $this->verifyIdToken(
            $params,
            $discovery,
            (string) $tokens['id_token'],
            is_string($nonce) ? $nonce : null,
            $serverID
        );

        if ($idTokenClaims === null) {
            return;
        }

        $userInfoClaims = $this->fetchUserInfo($params, $discovery, (string) ($tokens['access_token'] ?? ''), $serverID);

        if ($userInfoClaims === null) {
            return;
        }

        if (isset($idTokenClaims['sub'], $userInfoClaims['sub']) && $idTokenClaims['sub'] !== $userInfoClaims['sub']) {
            $this->log($params, 'log_verify', 'system-oidclogin-verify', 'UserInfo "sub" mismatch on server ' . $serverID, Log::ERROR);

            return;
        }

        $this->claims = array_merge($idTokenClaims, $userInfoClaims);
        $this->server = $server;

        // Retained for RP-Initiated Logout's id_token_hint. Must be read back from session state
        // during onUserLogout, before Joomla's own onUserLogout listener destroys the session.
        $app->setUserState('system.oidclogin.idtoken.' . $serverID, (string) $tokens['id_token']);

        $service = Uri::getInstance();

        foreach (['code', 'state', 'error', 'error_description', 'session_state', 'iss'] as $var) {
            $service->delVar($var);
        }

        $query = $service->getQuery(true);
        $return = '';

        if (!empty($query) && count($query) === 1 && array_key_exists('Itemid', $query)) {
            $menu      = $app->getMenu();
            $menuEntry = $menu->getItem($query['Itemid']);

            if (!empty($menuEntry)) {
                $return = $menuEntry->link;
            }
        }

        if (!$return) {
            $return = 'index.php' . $service->toString(['query']);
        }

        if ($return == 'index.php?option=com_login') {
            $return = 'index.php';
        }

        $request = $input->getInputForRequestMethod();

        if ($app->isClient('administrator')) {
            $input->set('option', 'com_login');
            $input->set('task', 'login');
            $input->set(Session::getFormToken(), 1);
            $request->set('return', base64_encode($return));

            return;
        }

        $redirect = $params->get('redirect');

        if (!empty($redirect) && (!$params->get('noredirect') || $return != 'index.php')) {
            $return = 'index.php?Itemid=' . $redirect;
        }

        $input->set('option', 'com_users');
        $input->set('task', 'user.login');
        $request->set('Itemid', 0);
        $input->post->set(Session::getFormToken(), 1);
        $request->set('return', base64_encode($return));
    }

    /**
     * External Login event.
     */
    public function onExternalLogin(Event $event): void
    {
        /** @var AuthenticationResponse */
        $response = $event->getArgument('response');

        if (!$response || !$this->server) {
            return;
        }

        $extResponse = ExternalAuthenticationResponse::fromResponse($response);
        $server = $this->server;
        /** @var Registry $params */
        $params = $server->params;

        $username = ClaimsResolver::resolve($this->claims, (string) $params->get('username_claim', 'preferred_username'));
        $email = ClaimsResolver::resolve($this->claims, (string) $params->get('email_claim', 'email'));
        $fullname = ClaimsResolver::resolve($this->claims, (string) $params->get('name_claim', 'name'));

        if (!is_string($username) || $username === '' || !is_string($email) || $email === '') {
            $this->log($params, 'log_login', 'system-oidclogin-login', 'Missing username/email claim on server ' . $server->id, Log::WARNING);

            return;
        }

        // @phpstan-ignore assign.propertyType
        $extResponse->status = Authentication::STATUS_SUCCESS;
        $extResponse->server = $server;
        $extResponse->type = 'system.oidclogin';
        $extResponse->message = '';

        $extResponse->username = str_replace(
            ['<', '>', '"', "'", '%', ';', '(', ')', '&', '\\'],
            '',
            $username
        );

        $extResponse->email = str_replace(
            ['<', '>', '"', "'", '%', ';', '(', ')', '&', '\\'],
            '',
            $email
        );

        $extResponse->fullname = is_string($fullname) ? $fullname : $extResponse->username;

        if ($response !== $extResponse) {
            // @phpstan-ignore assign.propertyType
            $response->status = $extResponse->status;
            $response->type = $extResponse->type;
            $response->username = $extResponse->username;
            $response->email = $extResponse->email;
            $response->fullname = $extResponse->fullname;
        }

        $event->setArgument('response', $extResponse);

        if ($event instanceof ResultAwareInterface) {
            $event->addResult(true);
        } else {
            $results = $event->getArgument('result', []);
            $results[] = true;
            $event->setArgument('result', $results);
        }
    }

    /**
     * User logout event.
     *
     * Captures the externallogin server and retained id_token for the user being logged out,
     * while the session (and thus the id_token stored in it) is still intact — Joomla's own
     * onUserLogout listener destroys the session immediately after this event, before
     * onUserAfterLogout fires.
     *
     * @param array{id: int, username: string} $user
     * @param array<string, mixed> $options
     *
     * @return bool
     */
    public function onUserLogout($user, $options = [])
    {
        /** @var CMSApplication */
        $app = Factory::getApplication();
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('a.*')
            ->from('#__externallogin_servers AS a')
            ->leftJoin('#__externallogin_users AS e ON e.server_id = a.id')
            ->where('a.plugin = ' . $db->quote('system.oidclogin'))
            ->where('e.user_id = ' . (int) $user['id']);
        $db->setQuery($query);
        $server = $db->loadObject();

        if (is_null($server)) {
            return true;
        }

        $params = new Registry($server->params);

        if (!boolval($params->get('autologout'))) {
            return true;
        }

        $idToken = $app->getUserState('system.oidclogin.idtoken.' . $server->id);

        if (!is_string($idToken) || $idToken === '') {
            return true;
        }

        $this->logoutServer = $server;
        $this->logoutIdToken = $idToken;

        return true;
    }

    /**
     * Redirect to the OIDC end_session_endpoint (RP-Initiated Logout) when a user logs out.
     *
     * Degrades to a local-only Joomla logout — never a hard failure — when autologout is off,
     * no id_token was captured, or the IdP's discovery document has no end_session_endpoint.
     */
    public function onUserAfterLogout($options)
    {
        /** @var CMSApplication */
        $app = Factory::getApplication();
        $local = $app->getInput()->get('local');

        if (isset($local) || $this->logoutServer === null || $this->logoutIdToken === null) {
            return true;
        }

        $server = $this->logoutServer;
        $params = new Registry($server->params);

        $this->log(
            $params,
            'log_logout',
            'system-oidclogin-logout',
            'Logout of user "' . $options['username'] . '" on server ' . $server->id,
            Log::INFO
        );

        // A null discovery document means the fetch itself failed; getDiscoveryDocument() has
        // already logged that unconditionally, so don't log a second, misleading message here.
        $discovery = $this->getDiscoveryDocument($params, $server->id);

        if ($discovery === null) {
            return true;
        }

        if (empty($discovery['end_session_endpoint'])) {
            $this->log(
                $params,
                'log_logout',
                'system-oidclogin-logout',
                'No end_session_endpoint in discovery document on server ' . $server->id . ', falling back to local-only logout',
                Log::WARNING
            );

            return true;
        }

        $query = [
            'id_token_hint' => $this->logoutIdToken,
        ];

        $postLogoutRedirect = $params->get('post_logout_redirect');

        if (!empty($postLogoutRedirect)) {
            $query['post_logout_redirect_uri'] = $postLogoutRedirect;
        }

        $separator = str_contains((string) $discovery['end_session_endpoint'], '?') ? '&' : '?';
        $redirect = $discovery['end_session_endpoint'] . $separator . http_build_query($query);

        $app->redirect($redirect, 302);

        return true;
    }

    /**
     * Fetch (and cache) the OIDC discovery document for a server's issuer.
     *
     * @param Registry $params
     * @param int|string $serverID
     *
     * @return array<string, mixed>|null
     */
    private function getDiscoveryDocument($params, $serverID): ?array
    {
        $issuer = rtrim((string) $params->get('issuer'), '/');
        $cache = $this->getCacheController();
        $id = 'discovery.' . md5($issuer);

        $cached = $cache->cache->get($id);

        if ($cached !== false) {
            $document = json_decode((string) $cached, true);

            if (is_array($document)) {
                return $document;
            }
        }

        try {
            $http = (new HttpFactory())->getHttp();
            $timeout = (int) $params->get('timeout', 5);
            $response = $http->get($issuer . '/.well-known/openid-configuration', [], $timeout);

            if ($response->getStatusCode() !== 200) {
                throw new RuntimeException('Unexpected HTTP status ' . $response->getStatusCode());
            }

            $document = json_decode((string) $response->getBody(), true);

            if (
                !is_array($document)
                || empty($document['authorization_endpoint'])
                || empty($document['token_endpoint'])
                || empty($document['jwks_uri'])
            ) {
                throw new RuntimeException('Malformed discovery document');
            }
        } catch (Throwable $e) {
            // Unconditional: no usable cache and the live fetch failed, so login is about to hard-fail —
            // this must always be visible, regardless of the log_discovery toggle.
            Log::add(
                new ExternalloginLogEntry(
                    'Unable to fetch discovery document on server ' . $serverID . ': ' . $e->getMessage(),
                    Log::ERROR,
                    'system-oidclogin-discovery'
                )
            );

            return null;
        }

        $this->log($params, 'log_discovery', 'system-oidclogin-discovery', 'Successful discovery on server ' . $serverID, Log::INFO);
        $cache->cache->store(json_encode($document), $id);

        return $document;
    }

    /**
     * Fetch (and cache) the IdP's JWKS document.
     *
     * @param Registry $params
     * @param int|string $serverID
     *
     * @return array<string, mixed>|null
     */
    private function getJwks($params, string $jwksUri, $serverID, bool $forceRefresh = false): ?array
    {
        $cache = $this->getCacheController();
        $id = 'jwks.' . md5($jwksUri);

        if (!$forceRefresh) {
            $cached = $cache->cache->get($id);

            if ($cached !== false) {
                $jwks = json_decode((string) $cached, true);

                if (is_array($jwks)) {
                    return $jwks;
                }
            }
        }

        try {
            $http = (new HttpFactory())->getHttp();
            $timeout = (int) $params->get('timeout', 5);
            $response = $http->get($jwksUri, [], $timeout);

            if ($response->getStatusCode() !== 200) {
                throw new RuntimeException('Unexpected HTTP status ' . $response->getStatusCode());
            }

            $jwks = json_decode((string) $response->getBody(), true);

            if (!is_array($jwks) || empty($jwks['keys'])) {
                throw new RuntimeException('Malformed JWKS document');
            }
        } catch (Throwable $e) {
            // Unconditional: no usable cache and the live fetch failed, so login is about to hard-fail —
            // this must always be visible, regardless of the log_verify toggle.
            Log::add(
                new ExternalloginLogEntry(
                    'Unable to fetch JWKS on server ' . $serverID . ': ' . $e->getMessage(),
                    Log::ERROR,
                    'system-oidclogin-verify'
                )
            );

            return null;
        }

        $cache->cache->store(json_encode($jwks), $id);

        return $jwks;
    }

    /**
     * Verify an ID Token's signature and standard claims against the IdP's
     * JWKS, refetching the JWKS once if the token's "kid" isn't cached yet
     * (accommodates routine IdP key rotation).
     *
     * @param Registry $params
     * @param array<string, mixed> $discovery
     * @param int|string $serverID
     *
     * @return array<string, mixed>|null
     */
    private function verifyIdToken($params, array $discovery, string $idToken, ?string $nonce, $serverID): ?array
    {
        try {
            $token = (new Parser(new JoseEncoder()))->parse($idToken);
        } catch (Throwable $e) {
            $this->log($params, 'log_verify', 'system-oidclogin-verify', 'Malformed ID token on server ' . $serverID . ': ' . $e->getMessage(), Log::ERROR);

            return null;
        }

        if (!$token instanceof UnencryptedToken) {
            $this->log($params, 'log_verify', 'system-oidclogin-verify', 'Unsupported ID token type on server ' . $serverID, Log::ERROR);

            return null;
        }

        $kid = $token->headers()->get('kid');
        $kid = is_string($kid) ? $kid : null;
        $jwksUri = (string) $discovery['jwks_uri'];

        $jwks = $this->getJwks($params, $jwksUri, $serverID);

        if ($jwks === null) {
            return null;
        }

        $jwk = JwkConverter::find($jwks, $kid);

        if ($jwk === null) {
            // The key might be missing because the IdP just rotated its signing keys; refetch once.
            $jwks = $this->getJwks($params, $jwksUri, $serverID, true);

            if ($jwks === null) {
                return null;
            }

            $jwk = JwkConverter::find($jwks, $kid);
        }

        if ($jwk === null) {
            $this->log($params, 'log_verify', 'system-oidclogin-verify', 'No matching JWKS key "' . $kid . '" on server ' . $serverID, Log::ERROR);

            return null;
        }

        $key = JwkConverter::toKey($jwk);
        $signer = $this->getSigner((string) $token->headers()->get('alg'));

        if ($key === null || $signer === null) {
            $this->log($params, 'log_verify', 'system-oidclogin-verify', 'Unsupported JWKS key or signing algorithm on server ' . $serverID, Log::ERROR);

            return null;
        }

        $isValid = (new Validator())->validate(
            $token,
            new SignedWith($signer, $key),
            new IssuedBy((string) $discovery['issuer']),
            new PermittedFor((string) $params->get('client_id')),
            new LooseValidAt(SystemClock::fromUTC())
        );

        if (!$isValid) {
            $this->log($params, 'log_verify', 'system-oidclogin-verify', 'ID token failed validation on server ' . $serverID, Log::ERROR);

            return null;
        }

        $claims = $token->claims()->all();

        if ($nonce !== null && $nonce !== '' && ($claims['nonce'] ?? null) !== $nonce) {
            $this->log($params, 'log_verify', 'system-oidclogin-verify', 'ID token nonce mismatch on server ' . $serverID, Log::ERROR);

            return null;
        }

        $this->log($params, 'log_verify', 'system-oidclogin-verify', 'Successful ID token verification on server ' . $serverID, Log::INFO);

        return $claims;
    }

    /**
     * Exchange an authorization code for tokens.
     *
     * @param Registry $params
     * @param array<string, mixed> $discovery
     * @param int|string $serverID
     *
     * @return array<string, mixed>|null
     */
    private function exchangeCode($params, array $discovery, string $code, ?string $verifier, string $redirectUri, $serverID): ?array
    {
        try {
            $http = (new HttpFactory())->getHttp();
            $timeout = (int) $params->get('timeout', 5);

            $data = [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'client_id' => (string) $params->get('client_id'),
                'client_secret' => (string) $params->get('client_secret'),
            ];

            if (!empty($verifier)) {
                $data['code_verifier'] = $verifier;
            }

            $response = $http->post(
                (string) $discovery['token_endpoint'],
                $data,
                ['Content-Type' => 'application/x-www-form-urlencoded'],
                $timeout
            );

            if ($response->getStatusCode() !== 200) {
                throw new RuntimeException('Unexpected HTTP status ' . $response->getStatusCode());
            }

            $tokens = json_decode((string) $response->getBody(), true);

            if (!is_array($tokens) || empty($tokens['id_token'])) {
                throw new RuntimeException('Malformed token response');
            }
        } catch (Throwable $e) {
            $this->log($params, 'log_verify', 'system-oidclogin-verify', 'Token exchange failed on server ' . $serverID . ': ' . $e->getMessage(), Log::WARNING);

            return null;
        }

        $this->log($params, 'log_verify', 'system-oidclogin-verify', 'Successful token exchange on server ' . $serverID, Log::INFO);

        return $tokens;
    }

    /**
     * Call the UserInfo endpoint.
     *
     * @param Registry $params
     * @param array<string, mixed> $discovery
     * @param int|string $serverID
     *
     * @return array<string, mixed>|null
     */
    private function fetchUserInfo($params, array $discovery, string $accessToken, $serverID): ?array
    {
        if (empty($discovery['userinfo_endpoint']) || $accessToken === '') {
            $this->log($params, 'log_verify', 'system-oidclogin-verify', 'No UserInfo endpoint or access token on server ' . $serverID, Log::ERROR);

            return null;
        }

        try {
            $http = (new HttpFactory())->getHttp();
            $timeout = (int) $params->get('timeout', 5);
            $response = $http->get(
                (string) $discovery['userinfo_endpoint'],
                ['Authorization' => 'Bearer ' . $accessToken],
                $timeout
            );

            if ($response->getStatusCode() !== 200) {
                throw new RuntimeException('Unexpected HTTP status ' . $response->getStatusCode());
            }

            $claims = json_decode((string) $response->getBody(), true);

            if (!is_array($claims) || empty($claims['sub'])) {
                throw new RuntimeException('Malformed UserInfo response');
            }
        } catch (Throwable $e) {
            $this->log($params, 'log_verify', 'system-oidclogin-verify', 'UserInfo request failed on server ' . $serverID . ': ' . $e->getMessage(), Log::WARNING);

            return null;
        }

        $this->log($params, 'log_verify', 'system-oidclogin-verify', 'Successful UserInfo request on server ' . $serverID, Log::INFO);

        return $claims;
    }

    /**
     * Get a cache controller for discovery/JWKS caching, TTL taken from
     * Joomla's global "cachetime" configuration.
     */
    private function getCacheController(): CacheController
    {
        /** @var CacheControllerFactoryInterface $factory */
        $factory = Factory::getContainer()->get(CacheControllerFactoryInterface::class);

        return $factory->createCacheController('callback', [
            'defaultgroup' => 'plg_system_oidclogin',
            'caching' => true,
        ]);
    }

    /**
     * Resolve the Lcobucci\JWT signer for a JWS "alg" header value.
     */
    private function getSigner(string $alg): ?Signer
    {
        return match ($alg) {
            'RS256' => new Sha256(),
            'RS384' => new Sha384(),
            'RS512' => new Sha512(),
            default => null,
        };
    }

    /**
     * Log a message when the given server parameter toggle is enabled,
     * mirroring Caslogin's log_* pattern.
     *
     * @param Registry $params
     */
    private function log($params, string $toggle, string $category, string $message, int $priority): void
    {
        if ($params->get($toggle, 0)) {
            Log::add(new ExternalloginLogEntry($message, $priority, $category));
        }
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
