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
use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Event\Result\ResultAwareInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\Event;

/**
 * External Login - OIDC (OpenID Connect) plugin.
 *
 * @since 5.2.0
 */
class Oidclogin extends CMSPlugin
{
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
}
