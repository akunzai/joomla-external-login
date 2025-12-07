<?php

/**
 * @author      Christophe Demko <chdemko@gmail.com>
 * @copyright   Copyright (C) 2008-2018 Christophe Demko. All rights reserved.
 * @license     GNU General Public License, version 2. http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @link        https://github.com/akunzai/joomla-external-login
 */

namespace Joomla\Plugin\User\Cbexternallogin\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;

/**
 * External Login - Community Builder External Login Plugin.
 *
 * @since       2.0.0
 */
class Cbexternallogin extends CMSPlugin
{
    /**
     * Constructor.
     *
     * @param DispatcherInterface $dispatcher The event dispatcher
     * @param array $config An array that holds the plugin configuration
     *
     * @since   2.0.0
     */
    public function __construct(DispatcherInterface $dispatcher, array $config = [])
    {
        parent::__construct($dispatcher, $config);
        $this->loadLanguage();
    }

    /**
     * This method should handle any login logic and report back to the subject.
     *
     * @param array $user Holds the user data
     * @param array $options Array holding options (remember, autoregister, group)
     *
     * @return bool True on success
     *
     * @since	2.0.0
     */
    public function onUserLogin($user, $options = [])
    {
        // User comes from external login plugin and community builder is installed and enabled
        if (isset($user['server']) && ComponentHelper::getComponent('com_comprofiler', true)->enabled) {
            // Verify if user is stored into community builder
            $dbo = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $dbo->getQuery(true);
            $query->select('COUNT(*)');
            $query->from('#__comprofiler');
            $query->where('id = ' . (int) $user['id']);
            $dbo->setQuery($query);

            // User does not exist in community builder
            if ($dbo->loadResult() == 0) {
                // Prepare query for insertion in community builder
                $query = $dbo->getQuery(true);
                $query->insert('#__comprofiler');
                $query->columns('id, user_id, confirmed, approved');

                // Server is set to autoregister
                if ($user['server']->params->get('autoregister', 0)) {
                    $query->values((int) $user['id'] . ',' . (int) $user['id'] . ', 1, 1');
                }
                // Server is not set to autoregister
                else {
                    $query->values((int) $user['id'] . ',' . (int) $user['id'] . ', 0, 0');
                }

                $dbo->setQuery($query);
                $dbo->execute();
            }
        }

        return true;
    }
}
