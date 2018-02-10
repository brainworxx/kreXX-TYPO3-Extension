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
// redeclare the Tx_Includekrexx_Controller_IndexController and throw
// a fatal.
if (class_exists('Tx_Includekrexx_Controller_FormConfigController')) {
    return;
}

use Brainworxx\Krexx\Service\Config\Fallback;

/**
 * Backend controller for the kreXX typo3 extension
 */
class Tx_Includekrexx_Controller_FormConfigController extends Tx_Includekrexx_Controller_CompatibilityController
{

    /**
     * Here we sore, if we did have problems saving the form.
     *
     * @var bool
     */
    protected $allOk = true;

    /**
     * Whitelist of the vales we are accepting.
     *
     * @var array
     */
    protected $allowedValues = array('full', 'display', 'none');

    /**
     * Shows the configuration for the FE editing.
     */
    public function editAction()
    {
        $this->checkProductiveSetting();

        // Has kreXX something to say? Maybe a writeprotected logfolder?
        foreach ($this->getTranslatedMessages() as $message) {
            $this->addMessage($message, $this->LLL('general.error.title'), t3lib_FlashMessage::ERROR);
        }

        $iniConfig = $this->pool->config->iniConfig;

        $dropdown = array(
            'full' => $this->LLL('full'),
            'display' => $this->LLL('display'),
            'none' => $this->LLL('none')
        );

        $config = array();
        foreach ($this->pool->config->feConfigFallback as $settingsName => $fallback) {
            $config[$settingsName] = array();
            $config[$settingsName]['name'] = $settingsName;
            $config[$settingsName]['options'] = $dropdown;
            $config[$settingsName]['useFactorySettings'] = false;
            $config[$settingsName]['value'] =  $this->convertKrexxFeSetting(
                $iniConfig->getFeConfigFromFile($settingsName)
            );
            // Check if we have a value. If not, we need to load the
            // factory settings. We also need to set the info, if we
            // are using the factory settings, at all.
            if (is_null($config[$settingsName]['value'])) {
                $config[$settingsName]['value'] = $this->convertKrexxFeSetting(
                    $iniConfig->feConfigFallback[$settingsName][$iniConfig::RENDER]
                );
                $config[$settingsName]['useFactorySettings'] = true;
            }
        }

        $this->view->assign('config', $config);
        $this->addCssToView('Backend.css');
        $this->addJsToView('Backend.js');
        $this->assignFlashInfo();
    }

    /**
     * Saves the settings for the frontend editing.
     *
     * We are saving the values of the FE editing in the same file as
     * the rest of the kreXX settings. Since we are using different forms,
     * we need to check the values already set.
     */
    public function saveAction()
    {
        $arguments = $this->request->getArguments();
        $filepath = $this->pool->config->getPathToIniFile();

        // Check for writing permission.
        if (!is_writable(dirname($filepath))) {
            $this->allOk = false;
            $this->pool->messages->addMessage('file.not.writable', array($filepath));
        }
        // Check if the file does exist.
        if (is_file($filepath)) {
            // Get the old values . . .
            $oldValues = parse_ini_file($filepath, true);
            // . . . and remove our part.
            unset($oldValues['feEditing']);
        } else {
            $oldValues = array();
        }

        if (isset($arguments['action']) && $arguments['action'] == 'save' && $this->allOk) {
            // We need to correct the allowed settings, since we do not allow anything.
            unset($this->allowedSettingsNames[Fallback::SETTINGDESTINATION]);
            unset($this->allowedSettingsNames[Fallback::SETTINGMAXFILES]);
            unset($this->allowedSettingsNames[Fallback::SETTINGDEBUGMETHODS]);
            unset($this->allowedSettingsNames[Fallback::SETTINGIPRANGE]);

            // Iterating through the form.
            foreach ($arguments as $key => $data) {
                if (is_array($data) && $key != '__referrer') {
                    $oldValues = $this->processSection($data, $oldValues);
                }
            }

            // Now we must create the ini file.
            $ini = '';
            foreach ($oldValues as $key => $setting) {
                $ini .= '[' . $key . ']' . PHP_EOL;
                foreach ($setting as $settingName => $value) {
                    $ini .= $settingName . ' = "' . $value . '"' . PHP_EOL;
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
            // Got to remove some messages. We we will not queue them now.
            $this->pool->messages->removeKey('protected.folder.chunk');
            $this->pool->messages->removeKey('protected.folder.log');
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
     * Converts the kreXX FE config setting.
     *
     * Letting people choose what kind of form element will
     * be used does not really make sense. We will convert the
     * original kreXX settings to a more useable form for the editor.
     *
     * @param array $values
     *   The valuees we want to convert.
     *
     * @return string|null
     *   The converted values.
     */
    protected function convertKrexxFeSetting($values)
    {

        if (is_array($values)) {
            // Explanation:
            // full -> is editable and values will be accepted
            // display -> we will only display the settings
            // The original values include the name of a template partial
            // with the form element.
            if ($values['type'] == 'None') {
                // It's not visible, thus we do not accept any values from it.
                $result = 'none';
            }
            if ($values['editable'] == 'true' && $values['type'] != 'None') {
                // It's editable and visible.
                $result = 'full';
            }
            if ($values['editable'] == 'false' && $values['type'] != 'None') {
                // It's only visible.
                $result = 'display';
            }
        }
        return $result;
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
    protected function processSection(array $data, array $oldValues)
    {
        foreach ($data as $settingName => $value) {
            if (in_array($value, $this->allowedValues) && in_array($settingName, $this->allowedSettingsNames)) {
                // Whitelisted values are ok.
                $oldValues['feEditing'][$settingName] = $value;
            } else {
                // Validation failed!
                $this->allOk = false;
                $this->pool->messages->addMessage('value.not.allowed', array(htmlentities($value)));
            }
        }

        return $oldValues;
    }
}
