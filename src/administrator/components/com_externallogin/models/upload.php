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

use Joomla\CMS\Table\Table;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * Upload Model of External Login component.
 *
 * @since       2.0.0
 */
class ExternalloginModelUpload extends Joomla\CMS\MVC\Model\AdminModel
{
    /**
     * Returns a reference to the a Table object, always creating it.
     *
     * @param string $type The table type to instantiate
     * @param string $prefix A prefix for the table class name. Optional.
     * @param array $config Configuration array for model. Optional.
     *
     * @return Table A database object
     *
     * @see     JModel::getTable
     * @since	2.0.0
     */
    public function getTable($type = 'Server', $prefix = 'ExternalloginTable', $config = [])
    {
        return $this->getMVCFactory()->createTable($type, $prefix, $config);
    }

    /**
     * Method to get the record form.
     *
     * @param array $data data for the form
     * @param bool $loadData true if the form is to load its own data (default case), false if not
     *
     * @return mixed A JForm object on success, false on failure
     *
     * @see    JModelForm::getForm
     * @since  2.0.0
     */
    public function getForm($data = [], $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm('com_externallogin.upload', 'upload', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return mixed the data for the form
     *
     * @since  2.0.0
     */
    protected function loadFormData()
    {
        return $this->getItem();
    }
}
