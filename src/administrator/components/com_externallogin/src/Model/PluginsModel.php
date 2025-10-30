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

namespace Joomla\Component\Externallogin\Administrator\Model;

use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Event\DispatcherInterface;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * Plugins Model of External Login component.
 *
 * @since       2.0.0
 */
class PluginsModel extends BaseDatabaseModel
{
    /**
     * Get plugins.
     *
     * @return array Array of buttons
     *
     * @since  2.0.0
     */
    public function getItems()
    {
        $items = [];
        // Include buttons defined by published external login plugins
        PluginHelper::importPlugin('system');
        $dispatcher = Factory::getContainer()->get(DispatcherInterface::class);
        $event = new ContentPrepareEvent(
            'onGetIcons',
            [
                'context' => 'com_externallogin',
                'subject' => $this,
            ]
        );
        $dispatcher->dispatch('onGetIcons', $event);
        $arrays = $event->getArgument('result', []);
        foreach ($arrays as $response) {
            foreach ($response as $plugin) {
                $items[] = $plugin;
            }
        }
        return $items;
    }
}
