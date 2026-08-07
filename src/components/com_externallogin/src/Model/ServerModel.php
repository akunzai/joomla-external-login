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

namespace Joomla\Component\Externallogin\Site\Model;

use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Externallogin\Administrator\Helper\ExternalloginHelper;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * Login Server Model of External Login component.
 *
 * @since       2.0.0
 */
class ServerModel extends ItemModel
{
    /**
     * Method to auto-populate the model state.
     *
     * @note  Calling getState in this method will result in recursion.
     *
     * @since  2.0.0
     */
    protected function populateState()
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
        $id = $input->get('server', 0, 'uint');
        $this->setState('server.id', $id);
        $redirect = $input->get('redirect', '', 'RAW');
        $this->setState('server.redirect', $redirect);
        $noRedirect = $input->get('noredirect');
        $this->setState('server.noredirect', $noRedirect);
        parent::populateState();
    }

    /**
     * Returns a reference to the a Table object, always creating it.
     *
     * @param string $type The table type to instantiate
     * @param string $prefix A prefix for the table class name. Optional.
     * @param array $config Configuration array for model. Optional.
     *
     * @return Table A database object
     *
     * @since	2.0.0
     */
    public function getTable($type = 'Server', $prefix = 'ExternalloginTable', $config = [])
    {
        return $this->getMVCFactory()->createTable($type, $prefix, $config);
    }

    /**
     * Returns the server.
     *
     * @return Uri|string the service URI
     *
     * @since	2.0.0
     */
    public function getItem($pk = null)
    {
        // Load the server
        $id = $this->getState('server.id');
        $item = $this->getTable();

        if (!$item->load($id) || $item->published != 1) {
            throw new Exception(Text::_('COM_EXTERNALLOGIN_ERROR_SERVER_UNPUBLISHED'));
        }

        /** @var CMSApplication */
        $app = Factory::getApplication();
        $menu = $app->getMenu()->getActive();

        $params = is_null($menu) ? new Registry() : $menu->getParams();

        // Compute the url
        $redirect = $this->getState(
            'server.redirect',
            $params->get(
                'redirect',
                $item->params->get(
                    'redirect',
                    ComponentHelper::getParams('com_externallogin')->get('redirect')
                )
            )
        );
        $noRedirect = $this->getState(
            'server.noredirect',
            $item->params->get(
                'noredirect',
                ComponentHelper::getParams('com_externallogin')->get('noredirect')
            )
        );

        if (!empty($redirect) && !$noRedirect) {
            $url = ExternalloginHelper::url($redirect);
        } else {
            $url = $app->getInput()->server->getString('HTTP_REFERER');

            if (empty($url) || !Uri::isInternal($url)) {
                $url = Route::_('index.php', true, $app->get('force_ssl') == 2 ? 1 : 0);
            }
        }

        // CAS/OIDC IdPs require an absolute service / redirect_uri. After a failed Joomla login,
        // users.login.form.data.return is often the bare relative "index.php"; passing that
        // through as the CAS service produces service=index.php and Keycloak "Client not found."
        // (Surfaced once #262 made retries from /component/users/login reachable.)
        $url = $this->toAbsoluteUrl($url);

        // Compute the URI
        $uri = Uri::getInstance($url);

        // Return the service/URL
        $user = $this->getCurrentUser();
        if (!$user->guest) {
            return $uri;
        }
        $app->setUserState('com_externallogin.server', $item->id);
        $dispatcher = Factory::getContainer()->get(DispatcherInterface::class);
        $event = new ContentPrepareEvent(
            'onGetLoginUrl',
            [
                'context' => 'com_externallogin',
                'subject' => $item,
            ]
        );
        $event->setArgument('service', $uri);
        $dispatcher->dispatch('onGetLoginUrl', $event);
        $results = $event->getArgument('result', []);

        if (empty($results)) {
            throw new Exception(Text::_('COM_EXTERNALLOGIN_ERROR_OCCURS'));
        }
        $result = is_array($results) ? $results[0] : $results;
        return $result;
    }

    /**
     * Expand a site-relative path into an absolute URL using the current site root.
     *
     * Bare values like "index.php" or root-relative "/foo" have no host; IdPs reject them
     * as service / redirect_uri parameters.
     */
    private function toAbsoluteUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return Uri::root();
        }

        // Already absolute (has a scheme) or protocol-relative.
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $url) || str_starts_with($url, '//')) {
            if (str_starts_with($url, '//')) {
                $scheme = Uri::getInstance(Uri::root())->getScheme() ?: 'https';

                return $scheme . ':' . $url;
            }

            return $url;
        }

        return rtrim(Uri::root(), '/') . '/' . ltrim($url, '/');
    }
}
