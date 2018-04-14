<?php
/**
 * kreXX: Krumo eXXtended
 *
 * kreXX is a debugging tool, which displays structured information
 * about any PHP object. It is a nice replacement for print_r() or var_dump()
 * which are used by a lot of PHP developers.
 *
 * kreXX is a fork of Krumo, which was originally written by:
 * Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author
 *   brainworXX GmbH <info@brainworxx.de>
 *
 * @license
 *   http://opensource.org/licenses/LGPL-2.1
 *
 *   GNU Lesser General Public License Version 2.1
 *
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
 *
 *   This library is free software; you can redistribute it and/or modify it
 *   under the terms of the GNU Lesser General Public License as published by
 *   the Free Software Foundation; either version 2.1 of the License, or (at
 *   your option) any later version.
 *   This library is distributed in the hope that it will be useful, but WITHOUT
 *   ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 *   FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 *   for more details.
 *   You should have received a copy of the GNU Lesser General Public License
 *   along with this library; if not, write to the Free Software Foundation,
 *   Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

// The 7.3'er autoloader tries to include this file twice, probably
// because of the class mappings above. I need to make sure not to
// redeclare the Tx_Includekrexx_Controller_HelpController and throw
// a fatal.
if (class_exists('Tx_Includekrexx_Controller_ConfigController')) {
    return;
}

use Brainworxx\Krexx\Service\Config\Fallback;

/**
 * Configuration controller for the kreXX typo3 extension
 */
class Tx_Includekrexx_Controller_ConfigController extends Tx_Includekrexx_Controller_CompatibilityController
{
    /**
     * Here we sore, if we did have problems saving the form.
     *
     * @var bool
     */
    protected $allOk = true;

    /**
     * Shows the edit config screen.
     */
    public function editAction()
    {
        $this->checkProductiveSetting();

        // Has kreXX something to say? Maybe a writeprotected logfolder?
        foreach ($this->getTranslatedMessages() as $message) {
            $this->addMessage($message, $this->LLL('general.error.title'), t3lib_FlashMessage::ERROR);
        }

        $config = array();
        foreach ($this->pool->config->feConfigFallback as $settingsName => $fallback) {
            // Stitch together the settings in the template.
            $group = $fallback[Fallback::SECTION];
            $config[$settingsName] = array();
            $config[$settingsName]['name'] = $settingsName;
            $config[$settingsName]['helptext'] = $this->LLL($settingsName);
            $config[$settingsName]['value'] = $this->pool->config->iniConfig->getConfigFromFile($group, $settingsName);
            $config[$settingsName]['group'] = $group;
            $config[$settingsName]['useFactorySettings'] = false;
            // Check if we have a value. If not, we need to load the
            // factory settings. We also need to set the info, if we
            // are using the factory settings, at all.
            if (is_null($config[$settingsName]['value'])) {
                $config[$settingsName]['value'] = $fallback[Fallback::VALUE];
                $config[$settingsName]['useFactorySettings'] = true;
            }
        }

        // Adding the dropdown values.
        $dropdown = array();
        $dropdown['skins'] = array();
        foreach ($this->pool->render->getSkinList() as $skin) {
            $dropdown['skins'][$skin] = $skin;
        }
        $dropdown[Fallback::SETTING_DESTINATION] = array(
            'browser' => $this->LLL('browser'),
            'file' => $this->LLL('file'),
        );
        $dropdown['bool'] = array(
            'true' => $this->LLL('true'),
            'false' => $this->LLL('false'),
        );

        $this->view->assign('config', $config);
        $this->view->assign('dropdown', $dropdown);
        $this->addCssToView('Backend.css');
        $this->addJsToView('Backend.js');
        $this->assignFlashInfo();
    }

    /**
     * Saves the kreXX configuration.
     */
    public function saveAction()
    {
        $arguments = $this->request->getArguments();
        $filepath = $this->pool->config->getPathToIniFile();
        $oldValues = array();

        // Check for writing permission.
        if (!is_writable(dirname($filepath))) {
            $this->allOk = false;
            $this->pool->messages->addMessage('file.not.writable', array($filepath));
        }

        // Check if the file does exist.
        if (is_file($filepath)) {
            $oldValues = parse_ini_file($filepath, true);
        }

        // We must preserve the section 'feEditing'.
        // Everything else will be overwritten.
        $oldValues = array('feEditing' => $oldValues['feEditing']);

        if (isset($arguments['action']) && $arguments['action'] == 'save' && $this->allOk) {
            // Iterating through the form.
            foreach ($arguments as $section => $data) {
                if (is_array($data) && in_array($section, $this->allowedSections)) {
                    // We've got a section key.
                    $oldValues = $this->processSection($section, $data, $oldValues);
                }
            }
            // Now we must create the ini file.
            $ini = '';
            foreach ($oldValues as $key => $setting) {
                $ini .= '[' . $key . ']' . PHP_EOL;
                if (is_array($setting)) {
                    foreach ($setting as $settingName => $value) {
                        $ini .= $settingName . ' = "' . $value . '"' . PHP_EOL;
                    }
                }
            }

            // Now we should write the file!
            if ($this->allOk &&
                file_put_contents($filepath, $ini) === false
            ) {
                $this->allOk = false;
                $this->pool->messages->addMessage('file.not.writable', array($filepath));
            }
        }

        // Something went wrong, we need to tell the user.
        if (!$this->allOk) {
            foreach ($this->getTranslatedMessages() as $message) {
                $this->addMessage($message, $this->LLL('save.fail.title'), t3lib_FlashMessage::ERROR);
            }
        } else {
            $this->addMessage(
                $this->LLL('save.success.text', array($filepath)),
                $this->LLL('save.success.title'),
                t3lib_FlashMessage::OK
            );
        }

        $this->redirect('edit');
    }

    /**
     * Processing of the section values.
     *
     * @param $section
     *   The name of the section that we are processing.
     * @param array $data
     *   The data from that section.
     * @param array $oldValues
     *   The old valued that we are supplementing.
     *
     * @return array
     *   The supplemented old values.
     */
    protected function processSection($section, array $data, array $oldValues)
    {
        foreach ($data as $settingName => $value) {
            if (in_array($settingName, $this->allowedSettingsNames)) {
                // We escape the value, just in case, since we can not
                // whitelist it.
                $value = htmlspecialchars(preg_replace('/\s+/', '', $value));
                // Evaluate the setting!
                if ($this->pool->config->security->evaluateSetting($section, $settingName, $value)) {
                    $oldValues[$section][$settingName] = $value;
                } else {
                    // Validation failed! kreXX will generate a message,
                    // which we will display
                    // at the buttom.
                    $this->allOk = false;
                }
            }
        }
        return $oldValues;
    }
}
