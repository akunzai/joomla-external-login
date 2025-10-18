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

use Joomla\CMS\Factory;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * Plugins Model of External Login component.
 *
 * @since       2.0.0
 */
class ExternalloginModelPlugins extends Joomla\CMS\MVC\Model\BaseDatabaseModel
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
        $app = Factory::getApplication();
        $arrays = (array) $app->getDispatcher()->dispatch('onGetIcons', new Joomla\Event\Event('onGetIcons', ['com_externallogin']))->getArgument('result', []);

        foreach ($arrays as $response) {
            foreach ($response as $plugin) {
                $items[] = $plugin;
            }
        }

        return $items;
    }
}
