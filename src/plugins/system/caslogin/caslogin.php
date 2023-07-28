<?php

/**
 * @package     External_Login
 * @subpackage  CAS Plugin
 * @author      Christophe Demko <chdemko@gmail.com>
 * @author      Ioannis Barounis <contact@johnbarounis.com>
 * @author      Alexandre Gandois <alexandre.gandois@etudiant.univ-lr.fr>
 * @copyright   Copyright (C) 2008-2018 Christophe Demko, Ioannis Barounis, Alexandre Gandois. All rights reserved.
 * @license     GNU General Public License, version 2. http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.chdemko.com
 */

use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Authentication\AuthenticationResponse;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

// No direct access to this file
defined('_JEXEC') or die;

JLoader::import('joomla.database.table');
Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_externallogin/tables');

JLoader::import('joomla.application.component.model');
BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_externallogin/models', 'ExternalloginModel');

JLoader::registerAlias('ExternalloginLogger', '\\Joomla\\CMS\\Log\\Logger\\ExternalloginLogger');
JLoader::register('ExternalloginLogger', JPATH_ADMINISTRATOR . '/components/com_externallogin/log/logger.php');
JLoader::register('ExternalloginLogEntry', JPATH_ADMINISTRATOR . '/components/com_externallogin/log/entry.php');
JLoader::register('ExternalloginHelper', JPATH_ADMINISTRATOR . '/components/com_externallogin/helpers/externallogin.php');
/**
 * External Login - CAS plugin.
 *
 * @package     External_Login
 * @subpackage  CAS Plugin
 *
 * @since       2.0.0
 */
class PlgSystemCaslogin extends \Joomla\CMS\Plugin\CMSPlugin
{
    /**
     * @var    ExternalloginTableServer
     * @since  2.0.0
     */
    protected $server;

    /**
     * @var    DOMXPath  The xpath object
     * @since  2.0.0
     */
    protected $xpath;

    /**
     * @var    DOMNode  The success node
     * @since  2.0.0
     */
    protected $success;

    /**
     * Constructor.
     *
     * @param   object  $subject  The object to observe
     * @param   array   $config   An array that holds the plugin configuration
     *
     * @since   2.0.0
     */
    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $this->loadLanguage();
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
     *
     * @param   string  $context  The calling context
     *
     * @return  array
     *
     * @since   2.0.0
     */
    public function onGetIcons($context)
    {
        if ($context == 'com_externallogin') {
            Factory::getDocument()->addStyleDeclaration(
                '.icon-caslogin {'
                    . 'width: 48px;'
                    . 'height: 48px;'
                    . 'background-image: url(../media/plg_system_caslogin/images/administrator/icon-48-caslogin.png);'
                    . 'background-position: center center;'
                    . '}'
            );

            return [
                [
                    'image' => 'icon-caslogin',
                    'link' => Route::_('index.php?option=com_externallogin&task=server.add&plugin=system.caslogin'),
                    'alt' => Text::_('PLG_SYSTEM_CASLOGIN_ALT'),
                    'text' => Text::_('PLG_SYSTEM_CASLOGIN_TEXT'),
                    'target' => '_parent',
                ],
            ];
        }
        return [];
    }

    /**
     * Get option.
     *
     * @param   string  $context  The calling context
     *
     * @return  array
     *
     * @since   2.0.0
     */
    public function onGetOption($context)
    {
        if ($context == 'com_externallogin') {
            return ['value' => 'system.caslogin', 'text' => 'PLG_SYSTEM_CASLOGIN_OPTION'];
        }
        return [];
    }

    /**
     * Prepare Form
     *
     * @param   Form  $form  The form to be altered.
     * @param   array  $data  The associated data for the form.
     *
     * @return	boolean
     *
     * @since	2.0.0
     */
    public function onContentPrepareForm($form, $data)
    {
        if (!($form instanceof Form)) {
            return false;
        }

        // Check we are manipulating a valid form.
        if ($form->getName() != 'com_externallogin.server.system.caslogin') {
            return true;
        }

        // Add the registration fields to the form.
        Form::addFormPath(dirname(__FILE__) . '/forms');
        $form->loadFile('cas', false);
        return true;
    }

    /**
     * After initialise event
     *
     * @return	void
     *
     * @since	2.0.0
     */
    public function onAfterInitialise()
    {
        // If the user is not connected
        if (!Factory::getUser()->guest) {
            return;
        }

        // Get the application
        /** @var \Joomla\CMS\Application\CMSApplication */
        $app = Factory::getApplication();

        // Get the dbo
        $db = Factory::getDbo();

        // Get the input
        $input = $app->input;

        // Get the service
        $service = Uri::getInstance();

        // Get the ticket and the server
        $ticket = $input->get('ticket');
        $serverID = $app->isClient('administrator') ? $input->get('server') : $app->getUserState('com_externallogin.server');
        if (empty($ticket) && empty($serverID)) {
            // Get CAS servers
            /** @var ExternalloginModelServers|false */
            $model = BaseDatabaseModel::getInstance('Servers', 'ExternalloginModel', ['ignore_request' => true]);
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

            // Try to auto-login for some servers
            foreach ($servers as $server) {
                $params = new Registry($server->params);
                $serverID = $server->id;
                if (boolval($params->get('autologin')) && !$app->getUserState('system.caslogin.autologin.' . $server->id)) {
                    $response = $this->verifyServerIsAlive($params);
                    // response is empty
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
                    $app->redirect($this->getUrl($params) . '/login?service=' . urlencode($service) . '&gateway=true');
                    break;
                }
            }
            return;
        }
        if (empty($ticket) && !empty($serverID)) {
            /** @var ExternalloginTable|bool */
            $server = Table::getInstance('Server', 'ExternalloginTable');
            if ($server && $server->load($serverID) && $server->plugin == 'system.caslogin') {
                // Log message
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

        // both ticket and server exist
        /** @var ExternalloginTable|bool */
        $server = Table::getInstance('Server', 'ExternalloginTable');

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
        // Store the xpath
        $this->xpath = $xpath;

        // Store the success node
        $this->success = $success->item(0);

        // Store the server
        $this->server = $server;

        // Get username
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

        // Check if user is enabled for cas login. Deny if not
        $query = $db->getQuery(true);
        $query->select("id");
        $query->from("#__users");
        $query->where($db->quoteName("username") . ' = ' . $db->quote($userName));
        $db->setQuery($query);

        try {
            $userID = $db->loadResult();
        } catch (Exception $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
        }

        // After check: true if user is activated for current server, else false
        $access = false;

        // Check if server is active for registered user, unregistered users should pass for reg.
        if (empty($userID)) {
            // User from CAS is a new user on this Joomla! instance
            $access = true;
        } else {
            $query = $db->getQuery(true);
            $query->select("server_id");
            $query->from("#__externallogin_users");
            $query->where("user_id = '$userID'");
            $db->setQuery($query);

            // Load the servers assigned to the user
            try {
                $servers = $db->loadColumn();
                // Check if current server is activated for the user
                if (empty($servers)) {
                    // No server is activated for this user - no access
                    $app->enqueueMessage(Text::_('PLG_SYSTEM_CASLOGIN_NO_ACTIVATED_SERVER'), 'error');
                    $access = false;
                } else {
                    foreach ($servers as $server) {
                        if ($server == $serverID) {
                            // Server is activated for this user - access granted
                            $access = true;
                            break;
                        }
                    }
                    // Current server is not activated for this user - no access
                    if (!$access) {
                        $app->enqueueMessage(Text::_('PLG_SYSTEM_CASLOGIN_NO_ACTIVATED_SERVER'), 'error');
                    }
                }
            } catch (Exception $e) {
                $app->enqueueMessage($e->getMessage(), 'error');
            }
        }

        // Log that access was denied
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
        // If the return url is for an Itemid, we look it up in the menu
        // in case it is a redirect to an external source
        $query = $service->getQuery(true);

        if (empty($return) && !empty($query) && count($query) === 1 && array_key_exists('Itemid', $query)) {
            $menu      = $app->getMenu();
            $menuEntry = $menu->getItem($query['Itemid']);

            if (!empty($menuEntry)) {
                $return = $menuEntry->link;
            }
        }

        if (empty($return)) {
            // Original way of determining the return url
            $return = 'index.php' . $service->toString(['query']);
        }

        if ($return == 'index.php?option=com_login') {
            $return = 'index.php';
        }

        $request = Factory::getApplication()->input->getInputForRequestMethod();

        // Prepare the connection process
        if ($app->isClient('administrator')) {
            $input->set('option', 'com_login');
            $input->set('task', 'login');
            $input->set(Session::getFormToken(), 1);

            // We are forced to encode the url in base64 as com_login uses this encoding
            $request->set('return', base64_encode($return));
            return;
        }

        // Detect redirect menu item from the params
        $redirect = $params->get('redirect');

        if (!empty($redirect) && (!$params->get('noredirect') || $return != 'index.php')) {
            $return = 'index.php?Itemid=' . $redirect;
        }

        $input->set('option', 'com_users');
        $input->set('task', 'user.login');
        $request->set('Itemid', 0);
        $input->post->set(Session::getFormToken(), 1);

        // We are forced to encode the url in base64 as com_users uses this encoding
        $request->set('return', base64_encode($return));
    }

    /**
     * Get Login URL
     *
     * @param   object  $server   The CAS server.
     * @param   string  $service  The asked service.
     *
     * @return	void|string
     *
     * @since	2.0.0
     */
    public function onGetLoginUrl($server, $service)
    {
        if ($server->plugin == 'system.caslogin') {
            // Return the login URL
            $url = $this->getUrl($server->params) . '/login?service=' . urlencode($service);

            if ($server->params->get('locale')) {
                [$locale, $country] = explode('-', Factory::getLanguage()->getTag());
                $url .= '&locale=' . $locale;
            }

            return $url;
        }
    }

    /**
     * External Login event
     *
     * @param   AuthenticationResponse  $response  Response to the login process
     *
     * @return	void|true
     *
     * @since	2.0.0
     */
    public function onExternalLogin(&$response)
    {
        if (!isset($this->success)) {
            return;
        }

        // Prepare response
        $server = $this->server;
        $params = $server->params;
        $sid = $server->id;
        $response->status = Authentication::STATUS_SUCCESS;
        $response->server = $server;
        $response->type = 'system.caslogin';
        $response->message = '';

        // Compute sanitized username. See libraries/src/Table/User.php (check function)
        $response->username = str_replace(
            ['<', '>', '"', "'", '%', ';', '(', ')', '&', '\\'],
            '',
            $this->xpath->evaluate($params->get('username_xpath'), $this->success)
        );

        // Compute sanitized email. See libraries/src/Table/User.php (check function)
        $response->email = str_replace(
            ['<', '>', '"', "'", '%', ';', '(', ')', '&', '\\'],
            '',
            $this->xpath->evaluate($params->get('email_xpath'), $this->success)
        );

        // Compute name
        $response->fullname = $this->xpath->evaluate($params->get('name_xpath'), $this->success);

        // Compute groups
        if (empty($params->get('group_xpath'))) {
            return true;
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
            return true;
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

        $response->groups = [];

        // Loop on each group attribute
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
                // Group is numeric
                $dbo = Factory::getDbo();
                $query = $dbo->getQuery(true);
                $query->select('id')->from('#__usergroups')->where('id = ' . (int) $group);
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

                // Group is not numeric, extract the groups
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

        return true;
    }

    /**
     * Get server URL
     *
     * @param   Registry  $params  The CAS parameters.
     *
     * @return	string  The server URL
     *
     * @since	2.0.0
     */
    protected function getUrl($params)
    {
        // Get the parameters
        $ssl = $params->get('ssl', 1);
        $url = $params->get('url');
        $dir = $params->get('dir');
        $port = (int) $params->get('port');

        // Return the server URL
        return 'http' . ($ssl == 1 ? 's' : '') . '://' . $url . ($port && $port != 443 ? (':' . $port) : '') . ($dir ? ('/' . $dir) : '');
    }

    /**
     * Redirect to CAS logout URL when a user logs out
     *
     * @param   array  $options  Array holding options (username, ...).
     *
     * @return	boolean  True on success
     *
     * @since	3.2.0
     */
    public function onUserAfterLogout($options)
    {
        /** @var \Joomla\CMS\Application\CMSApplication */
        $app = Factory::getApplication();
        $local = $app->input->get('local');
        // Local logout only?
        if (isset($local)) {
            return true;
        }
        $user = Factory::getUser($options['username']);
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from('#__externallogin_servers AS a');
        $query->leftJoin('#__externallogin_users AS e ON e.server_id = a.id');
        $query->where('a.plugin = ' . $db->quote('system.caslogin'));
        $query->where('e.user_id = ' . (int) $user->get('id'));
        $db->setQuery($query);
        $server = $db->loadObject();

        if (is_null($server)) {
            return true;
        }
        $params = new Registry($server->params);

        if (!boolval($params->get('autologout'))) {
            return true;
        }

        // Logout from CAS
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
            [$locale, $country] = explode('-', Factory::getLanguage()->getTag());
            $locale = '&locale=' . $locale;
        } else {
            $locale = '';
        }

        if ($params->get('logouturl')) {
            $redirect = $this->getUrl($params) . '/logout?service=' . urlencode($params->get('logouturl')) . $locale;
        } elseif ($app->input->get('return')) {
            $return = base64_decode($app->input->get('return', '', 'base64'));
            if (is_numeric($return)) {
                $return = ExternalloginHelper::url($return);
            }
            $redirect = $this->getUrl($params) . '/logout?service=' . urlencode($return) . $locale;
        } else {
            $redirect = $this->getUrl($params) . '/logout' . str_replace('&', '?', $locale);
        }

        $app->redirect($redirect);
        return true;
    }

    /**
     * @param Registry $params The CAS parameters.
     * @return string|bool
     */
    private function verifyServerIsAlive($params)
    {
        // Get the certificate information
        $certificateFile = $params->get('certificate_file', '');
        $certificatePath = $params->get('certificate_path', '');
        // Verify the service
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
     * @param Registry $params The CAS parameters.
     * @param string $ticket
     * @param string $service
     * @return string|bool
     */
    private function verifyServiceTicket($params, $ticket, $service)
    {
        // Get the certificate information
        $certificateFile = $params->get('certificate_file', '');
        $certificatePath = $params->get('certificate_path', '');

        // Verify the service
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
