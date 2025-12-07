<?php

/**
 * @author      Christophe Demko <chdemko@gmail.com>
 * @author      Ioannis Barounis <contact@johnbarounis.com>
 * @author      Alexandre Gandois <alexandre.gandois@etudiant.univ-lr.fr>
 * @copyright   Copyright (C) 2008-2018 Christophe Demko, Ioannis Barounis, Alexandre Gandois. All rights reserved.
 * @license     GNU General Public License, version 2. http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @link        https://github.com/akunzai/joomla-external-login
 */

namespace Joomla\Plugin\System\Caslogin\Extension;

defined('_JEXEC') or die;

use DOMDocument;
use DOMXPath;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Authentication\AuthenticationResponse;
use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Event\Result\ResultAwareInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\Component\Externallogin\Administrator\Helper\ExternalloginHelper;
use Joomla\Component\Externallogin\Administrator\Model\ServersModel;
use Joomla\Component\Externallogin\Administrator\Service\Logger\ExternalloginLogEntry;
use Joomla\Component\Externallogin\Administrator\Table\ServerTable;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\Event;
use Joomla\Registry\Registry;

/**
 * External Login - CAS plugin.
 *
 * @since 2.0.0
 */
class Caslogin extends CMSPlugin
{
    /**
     * @var ServerTable
     */
    protected $server;

    /**
     * @var DOMXPath The xpath object
     */
    protected $xpath;

    /**
     * @var \DOMNode|\DOMNameSpaceNode|null The success node
     */
    protected $success;

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
            ['logger' => 'externallogin', 'db_table' => '#__externallogin_logs', 'plugin' => 'system-caslogin'],
            Log::ALL,
            [
                'system-caslogin-logout',
                'system-caslogin-login',
                'system-caslogin-verify',
                'system-caslogin-xml',
                'system-caslogin-autologin',
                'system-caslogin-groups',
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
            $wa = $app->getDocument()->getWebAssetManager();
            $wa->addInlineStyle(
                '.icon-caslogin {'
                    . 'width: 48px;'
                    . 'height: 48px;'
                    . 'background-image: url(../media/plg_system_caslogin/images/administrator/icon-48-caslogin.png);'
                    . 'background-position: center center;'
                    . '}'
            );
            $result   = $event->getArgument('result', []);
            $result[] = [
                [
                    'image'  => 'icon-caslogin',
                    'link'   => Route::_('index.php?option=com_externallogin&task=server.add&plugin=system.caslogin'),
                    'alt'    => Text::_('PLG_SYSTEM_CASLOGIN_ALT'),
                    'text'   => Text::_('PLG_SYSTEM_CASLOGIN_TEXT'),
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
            $result[] = ['value' => 'system.caslogin', 'text' => 'PLG_SYSTEM_CASLOGIN_OPTION'];

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

        if ($form->getName() != 'com_externallogin.server.system.caslogin') {
            return;
        }

        // Ensure language is loaded for form labels
        $this->loadLanguage();
        Form::addFormPath(dirname(__DIR__, 2) . '/forms');
        $form->loadFile('cas', false);
    }

    /**
     * After initialise event.
     */
    public function onAfterInitialise(): void
    {
        /** @var CMSApplication */
        $app = Factory::getApplication();
        $user = $app->getIdentity();

        if (!$user->guest) {
            return;
        }

        $db  = Factory::getContainer()->get(DatabaseInterface::class);
        $input = $app->getInput();
        $service = Uri::getInstance();
        $ticket = $input->get('ticket');
        $serverID = $app->isClient('administrator') ? $input->get('server') : $app->getUserState('com_externallogin.server');
        /** @var MVCFactoryServiceInterface */
        $component = $app->bootComponent('com_externallogin');
        $mvcFactory = $component->getMVCFactory();

        if (!$ticket && !$serverID) {
            /** @var ServersModel $model */
            $model = $mvcFactory->createModel('Servers', 'Administrator', ['ignore_request' => true]);

            if (!$model) {
                return;
            }

            $model->setState('filter.published', 1);
            $model->setState('filter.plugin', 'system.caslogin');
            $model->setState('list.start', 0);
            $model->setState('list.limit', 0);
            $model->setState('list.ordering', 'a.ordering');
            $model->setState('list.direction', 'ASC');
            $servers = $model->getItems();

            foreach ($servers as $server) {
                $params = new Registry($server->params);
                $serverID = $server->id;

                if (boolval($params->get('autologin')) && !$app->getUserState('system.caslogin.autologin.' . $server->id)) {
                    $response = $this->verifyServerIsAlive($params);

                    if (empty($response)) {
                        if ($params->get('log_verify', 0)) {
                            Log::add(
                                new ExternalloginLogEntry(
                                    'Unsuccessful verification of server ' . $serverID,
                                    Log::WARNING,
                                    'system-caslogin-verify'
                                )
                            );
                        }

                        continue;
                    }

                    if ($params->get('log_verify', 0)) {
                        Log::add(
                            new ExternalloginLogEntry(
                                'Successful verification of server ' . $serverID,
                                Log::INFO,
                                'system-caslogin-verify'
                            )
                        );
                    }

                    if ($params->get('log_autologin', 0)) {
                        Log::add(
                            new ExternalloginLogEntry(
                                'Trying autologin on server ' . $serverID,
                                Log::INFO,
                                'system-caslogin-autologin'
                            )
                        );
                    }

                    $app->setUserState('com_externallogin.server', $server->id);
                    $app->setUserState('system.caslogin.autologin.' . $server->id, 1);
                    $app->redirect($this->getUrl($params) . '/login?service=' . urlencode($service) . '&gateway=true', 302);
                    break;
                }
            }

            return;
        }

        if (!$ticket && $serverID !== null) {
            /** @var ServerTable|bool $server */
            $server = $mvcFactory->createTable('Server', 'Administrator');

            if ($server && $server->load($serverID) && $server->plugin == 'system.caslogin') {
                if ($server->params->get('log_autologin', 0)) {
                    Log::add(
                        new ExternalloginLogEntry(
                            'Autologin failed on server ' . $serverID,
                            Log::INFO,
                            'system-caslogin-autologin'
                        )
                    );
                }
            }

            return;
        }

        /** @var ServerTable|bool */
        $server = $mvcFactory->createTable('Server', 'Administrator');

        if (!$server || !$server->load($serverID) || $server->plugin != 'system.caslogin') {
            return;
        }

        $params = $server->params;

        if ($params->get('log_login', 0)) {
            Log::add(
                new ExternalloginLogEntry(
                    'Attempt to login using ticket "' . $ticket . '" on server ' . $serverID,
                    Log::INFO,
                    'system-caslogin-login'
                )
            );
        }

        $service->delVar('ticket');
        $response = $this->verifyServiceTicket($params, $ticket, $service);

        if (empty($response)) {
            if ($params->get('log_verify', 0)) {
                Log::add(
                    new ExternalloginLogEntry(
                        'Unsuccessful verification of server ' . $serverID,
                        Log::WARNING,
                        'system-caslogin-verify'
                    )
                );
            }

            return;
        }

        if ($params->get('log_verify', 0)) {
            Log::add(
                new ExternalloginLogEntry(
                    'Successful verification of server ' . $serverID,
                    Log::INFO,
                    'system-caslogin-verify'
                )
            );
        }

        if ($params->get('log_xml', 0)) {
            Log::add(
                new ExternalloginLogEntry(
                    'Analyzing XML response on server ' . $serverID . "\n" . $response,
                    Log::INFO,
                    'system-caslogin-xml'
                )
            );
        }

        $dom = new DOMDocument();

        if (!$dom->loadXML($response)) {
            Log::add(
                new ExternalloginLogEntry(
                    'Unsuccessful analysis of XML response on server ' . $serverID,
                    Log::WARNING,
                    'system-caslogin-xml'
                )
            );

            return;
        }

        if ($params->get('log_xml', 0)) {
            Log::add(
                new ExternalloginLogEntry(
                    'Successful analysis of XML response on server ' . $serverID,
                    Log::INFO,
                    'system-caslogin-xml'
                )
            );
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('cas', 'http://www.yale.edu/tp/cas');
        $success = $xpath->query('/cas:serviceResponse/cas:authenticationSuccess[1]');

        if (!$success || $success->length == 0) {
            if ($params->get('log_xml', 0)) {
                Log::add(
                    new ExternalloginLogEntry(
                        'Unsuccessful login on server ' . $serverID,
                        Log::INFO,
                        'system-caslogin-xml'
                    )
                );
            }

            return;
        }

        $this->xpath = $xpath;
        $this->success = $success->item(0);
        $this->server = $server;

        $userName = $this->xpath->evaluate('string(cas:user)', $this->success);

        if ($params->get('log_xml', 0)) {
            Log::add(
                new ExternalloginLogEntry(
                    'Successful login on server ' . $serverID . ' for CAS user "' .
                        $this->xpath->evaluate('string(cas:user)', $this->success) . '"',
                    Log::INFO,
                    'system-caslogin-xml'
                )
            );
        }

        $query = $db->getQuery(true);
        $query->select('id')
            ->from('#__users')
            ->where($db->quoteName('username') . ' = ' . $db->quote($userName));
        $db->setQuery($query);

        try {
            $userID = $db->loadResult();
        } catch (Exception $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
        }

        $access = false;

        if (empty($userID)) {
            $access = true;
        } else {
            $query = $db->getQuery(true);
            $query->select('server_id')
                ->from('#__externallogin_users')
                ->where('user_id = ' . (int) $userID);
            $db->setQuery($query);

            try {
                $servers = $db->loadColumn();

                if (empty($servers)) {
                    $app->enqueueMessage(Text::_('PLG_SYSTEM_CASLOGIN_NO_ACTIVATED_SERVER'), 'error');
                    $access = false;
                } else {
                    foreach ($servers as $server) {
                        if ($server == $serverID) {
                            $access = true;
                            break;
                        }
                    }

                    if (!$access) {
                        $app->enqueueMessage(Text::_('PLG_SYSTEM_CASLOGIN_NO_ACTIVATED_SERVER'), 'error');
                    }
                }
            } catch (Exception $e) {
                $app->enqueueMessage($e->getMessage(), 'error');
            }
        }

        if (!$access) {
            Log::add(
                new ExternalloginLogEntry(
                    'Unsuccessful login on server ' . $serverID . ', user not activated for this server',
                    Log::INFO,
                    'system-caslogin-xml'
                )
            );

            return;
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
     * Get Login URL.
     */
    public function onGetLoginUrl(Event $event): void
    {
        $server = $event->getArgument('subject');
        $service = $event->getArgument('service');

        if ($server && $server->plugin == 'system.caslogin') {
            if ($service instanceof Uri) {
                $service = (string) $service;
            }

            $url = $this->getUrl($server->params) . '/login?service=' . urlencode($service);

            if ($server->params->get('locale')) {
                [$locale] = explode('-', Factory::getApplication()->getLanguage()->getTag());
                $url .= '&locale=' . $locale;
            }

            if ($event instanceof ResultAwareInterface) {
                $event->addResult($url);
            } else {
                $event->setArgument('result', $url);
            }
        }
    }

    /**
     * External Login event.
     */
    public function onExternalLogin(Event $event): void
    {
        /** @var AuthenticationResponse */
        $response = $event->getArgument('response');

        if (!$response || !isset($this->success)) {
            return;
        }

        $server = $this->server;
        $params = $server->params;
        $sid = $server->id;
        // @phpstan-ignore assign.propertyType
        $response->status = Authentication::STATUS_SUCCESS;
        // @phpstan-ignore property.notFound
        $response->server = $server;
        $response->type = 'system.caslogin';
        // @phpstan-ignore property.notFound
        $response->message = '';

        $response->username = str_replace(
            ['<', '>', '"', "'", '%', ';', '(', ')', '&', '\\'],
            '',
            $this->xpath->evaluate($params->get('username_xpath'), $this->success)
        );

        $response->email = str_replace(
            ['<', '>', '"', "'", '%', ';', '(', ')', '&', '\\'],
            '',
            $this->xpath->evaluate($params->get('email_xpath'), $this->success)
        );

        $response->fullname = $this->xpath->evaluate($params->get('name_xpath'), $this->success);


        // Set the modified response back to the event
        $event->setArgument('response', $response);

        if (empty($params->get('group_xpath'))) {
            // Add result to the result array
            if ($event instanceof ResultAwareInterface) {
                $event->addResult(true);
            } else {
                $results = $event->getArgument('result', []);
                $results[] = true;
                $event->setArgument('result', $results);
            }
            return;
        }

        $groups = $this->xpath->query($params->get('group_xpath'), $this->success);

        if (empty($groups) || $groups->length === 0) {
            if ($params->get('log_groups', 0)) {
                Log::add(
                    new ExternalloginLogEntry(
                        'Unsuccessful detection of groups for user "' . $response->username . '" on server ' . $sid,
                        Log::WARNING,
                        'system-caslogin-groups'
                    )
                );
            }

            // Add result to the result array
            if ($event instanceof ResultAwareInterface) {
                $event->addResult(true);
            } else {
                $results = $event->getArgument('result', []);
                $results[] = true;
                $event->setArgument('result', $results);
            }
            return;
        }

        if ($params->get('log_groups', 0)) {
            Log::add(
                new ExternalloginLogEntry(
                    'Successful detection of groups for user "' . $response->username . '" on server ' . $sid,
                    Log::INFO,
                    'system-caslogin-groups'
                )
            );
        }
        // @phpstan-ignore property.notFound
        $response->groups = [];

        for ($i = 0; $i < $groups->length; $i++) {
            $group = (string) $groups->item($i)->nodeValue;

            if (is_numeric($group) && $params->get('group_integer', 0)) {
                if ($params->get('log_groups', 0)) {
                    Log::add(
                        new ExternalloginLogEntry(
                            'Found integer group ' . $group . ' of groups for user "' . $response->username . '" on server ' . $sid,
                            Log::INFO,
                            'system-caslogin-groups'
                        )
                    );
                }

                $dbo = Factory::getContainer()->get(DatabaseInterface::class);
                $query = $dbo->getQuery(true);
                $query->select('id')
                    ->from('#__usergroups')
                    ->where('id = ' . (int) $group);
                $dbo->setQuery($query);

                if ($dbo->loadResult()) {
                    if ($params->get('log_groups', 0)) {
                        Log::add(
                            new ExternalloginLogEntry(
                                'Added integer group ' . $group . ' of groups for user "' . $response->username . '" on server ' . $sid,
                                Log::INFO,
                                'system-caslogin-groups'
                            )
                        );
                    }
                    $response->groups[] = $group;
                }
            } else {
                if ($params->get('log_groups', 0)) {
                    Log::add(
                        new ExternalloginLogEntry(
                            'Found string group(s) "' . $group . '" for user "' . $response->username . '" on server ' . $sid,
                            Log::INFO,
                            'system-caslogin-groups'
                        )
                    );
                }

                $newGroups = (array) ExternalloginHelper::getGroups($group, $params->get('group_separator', ''));
                $response->groups = array_merge($response->groups, $newGroups);

                if ($params->get('log_groups', 0)) {
                    $message = empty($newGroups)
                        ? 'No Joomla! groups found from "' . $group . '" on server ' . $sid
                        : 'Added groups (' . implode(',', $newGroups) . ') for user "' .  $response->username . '" on server ' . $sid;
                    Log::add(
                        new ExternalloginLogEntry(
                            $message,
                            Log::INFO,
                            'system-caslogin-groups'
                        )
                    );
                }
            }
        }

        $event->setArgument('response', $response);

        // Add result to the result array
        if ($event instanceof ResultAwareInterface) {
            $event->addResult(true);
        } else {
            $results = $event->getArgument('result', []);
            $results[] = true;
            $event->setArgument('result', $results);
        }
    }

    /**
     * Get server URL.
     *
     * @param Registry $params The server parameters
     */
    protected function getUrl($params)
    {
        $ssl = $params->get('ssl', 1);
        $url = $params->get('url');
        $dir = $params->get('dir');
        $port = (int) $params->get('port');

        return 'http' . ($ssl == 1 ? 's' : '') . '://' . $url . ($port && $port != 443 ? (':' . $port) : '') . ($dir ? ('/' . $dir) : '');
    }

    /**
     * Redirect to CAS logout URL when a user logs out.
     */
    public function onUserAfterLogout($options)
    {
        /** @var CMSApplication */
        $app = Factory::getApplication();
        $local = $app->getInput()->get('local');

        if (isset($local)) {
            return true;
        }

        $user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserByUsername($options['username']);
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('*')
            ->from('#__externallogin_servers AS a')
            ->leftJoin('#__externallogin_users AS e ON e.server_id = a.id')
            ->where('a.plugin = ' . $db->quote('system.caslogin'))
            ->where('e.user_id = ' . (int) $user->get('id'));
        $db->setQuery($query);
        $server = $db->loadObject();

        if (is_null($server)) {
            return true;
        }

        $params = new Registry($server->params);

        if (!boolval($params->get('autologout'))) {
            return true;
        }

        if ($params->get('log_logout', 0)) {
            Log::add(
                new ExternalloginLogEntry(
                    'Logout of user "' . $options['username'] . '" on server ' . $server->id,
                    Log::INFO,
                    'system-caslogin-logout'
                )
            );
        }

        if ($params->get('locale')) {
            [$locale] = explode('-', Factory::getApplication()->getLanguage()->getTag());
            $locale = '&locale=' . $locale;
        } else {
            $locale = '';
        }

        if ($params->get('logouturl')) {
            $redirect = $this->getUrl($params) . '/logout?service=' . urlencode($params->get('logouturl')) . $locale;
        } elseif ($app->getInput()->get('return')) {
            $return = base64_decode($app->getInput()->get('return', '', 'base64'));

            if (is_numeric($return)) {
                $return = ExternalloginHelper::url($return);
            }

            $redirect = $this->getUrl($params) . '/logout?service=' . urlencode($return) . $locale;
        } else {
            $redirect = $this->getUrl($params) . '/logout' . str_replace('&', '?', $locale);
        }

        $app->redirect($redirect, 302);

        return true;
    }

    /**
     * Verify server availability.
     */
    private function verifyServerIsAlive($params)
    {
        $certificateFile = $params->get('certificate_file', '');
        $certificatePath = $params->get('certificate_path', '');

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_URL, $this->getUrl($params));
        curl_setopt($curl, CURLOPT_TIMEOUT, $params->get('timeout'));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $certificateFile || $certificatePath);
        curl_setopt($curl, CURLOPT_CAINFO, $certificateFile);
        curl_setopt($curl, CURLOPT_CAPATH, $certificatePath);
        $result = curl_exec($curl);
        curl_close($curl);

        return $result;
    }

    /**
     * Verify service ticket.
     */
    private function verifyServiceTicket($params, $ticket, $service)
    {
        $certificateFile = $params->get('certificate_file', '');
        $certificatePath = $params->get('certificate_path', '');

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $curl,
            CURLOPT_URL,
            $this->getUrl($params) . ($params->get('cas_v3') ? '/p3' : '') .
                '/serviceValidate?ticket=' . $ticket . '&service=' . urlencode($service)
        );
        curl_setopt($curl, CURLOPT_TIMEOUT, $params->get('timeout'));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $certificateFile || $certificatePath);
        curl_setopt($curl, CURLOPT_CAINFO, $certificateFile);
        curl_setopt($curl, CURLOPT_CAPATH, $certificatePath);
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }
}
