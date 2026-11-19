<?php

/**
 * @author      Charley Wu <akunzai@gmail.com>
 * @copyright   Copyright (C) 2008-2026 Christophe Demko, Ioannis Barounis, Alexandre Gandois, Charley Wu. All rights reserved.
 * @license     GNU General Public License, version 2. http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @link        https://github.com/akunzai/joomla-external-login
 */

namespace Joomla\Plugin\System\Oidclogin\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\PasswordField;

/**
 * Write-only password field: the stored value is never rendered into the page.
 *
 * Submitting it blank keeps the stored value (see Oidclogin::onContentBeforeValidateData),
 * so a blank field on an existing server means "unchanged".
 *
 * @since 5.2.1
 */
class SecretField extends PasswordField
{
    /**
     * @var string
     */
    protected $type = 'Secret';

    /**
     * @return array<string, mixed>
     */
    protected function getLayoutData()
    {
        $data = parent::getLayoutData();
        $data['value'] = '';

        return $data;
    }
}
