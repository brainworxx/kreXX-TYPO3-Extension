<?php
/**
 * @file
 *   Backend controller for the kreXX typo3 extension
 *   kreXX: Krumo eXXtended
 *
 *   kreXX is a debugging tool, which displays structured information
 *   about any PHP object. It is a nice replacement for print_r() or var_dump()
 *   which are used by a lot of PHP developers.
 *
 *   kreXX is a fork of Krumo, which was originally written by:
 *   Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author brainworXX GmbH <info@brainworxx.de>
 *
 * @license http://opensource.org/licenses/LGPL-2.1
 *   GNU Lesser General Public License Version 2.1
 *
 *   kreXX Copyright (C) 2014-2016 Brainworxx GmbH
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
if (!class_exists('Tx_Includekrexx_Controller_FormConfigController')) {

    /**
     * Class Tx_Includekrexx_Controller_IndexController
     */
    class Tx_Includekrexx_Controller_FormConfigController extends Tx_Includekrexx_Controller_CompatibilityController
    {

        /**
         * Shows the configuration for the FE editing.
         */
        public function editAction()
        {
            // Has kreXX something to say? Maybe a writeprotected logfolder?
            foreach ($this->getTranslatedMessages() as $message) {
                $this->addMessage($message, $this->LLL('general.error.title'), t3lib_FlashMessage::ERROR);
            }

            $data = array();
            $value = array();
            // Setting possible form values.
            $data['settings'] = array(
                'full' => $this->LLL('full'),
                'display' => $this->LLL('display'),
                'none' => $this->LLL('none')
            );

            // See, if we have any values in the configuration file.
            $value['output']['skin'] = $this->convertKrexxFeSetting(
                $this->krexxStorage->config->getFeConfigFromFile('skin')
            );
            $value['runtime']['memoryLeft'] = $this->convertKrexxFeSetting(
                $this->krexxStorage->config->getFeConfigFromFile('memoryLeft')
            );
            $value['runtime']['maxRuntime'] = $this->convertKrexxFeSetting(
                $this->krexxStorage->config->getFeConfigFromFile('maxRuntime')
            );
            $value['runtime']['maxCall'] = $this->convertKrexxFeSetting(
                $this->krexxStorage->config->getFeConfigFromFile('maxCall')
            );
            $value['runtime']['disabled'] = $this->convertKrexxFeSetting(
                $this->krexxStorage->config->getFeConfigFromFile('disabled')
            );
            $value['runtime']['detectAjax'] = $this->convertKrexxFeSetting(
                $this->krexxStorage->config->getFeConfigFromFile('detectAjax')
            );
            $value['properties']['analyseProtected'] = $this->convertKrexxFeSetting(
                $this->krexxStorage->config->getFeConfigFromFile('analyseProtected')
            );
            $value['properties']['analysePrivate'] = $this->convertKrexxFeSetting(
                $this->krexxStorage->config->getFeConfigFromFile('analysePrivate')
            );
            $value['properties']['analyseTraversable'] = $this->convertKrexxFeSetting(
                $this->krexxStorage->config->getFeConfigFromFile('analyseTraversable')
            );
            $value['properties']['analyseConstants'] = $this->convertKrexxFeSetting(
                $this->krexxStorage->config->getFeConfigFromFile('analyseConstants')
            );
            $value['runtime']['level'] = $this->convertKrexxFeSetting(
                $this->krexxStorage->config->getFeConfigFromFile('level')
            );
            $value['methods']['analyseMethodsAtall'] = $this->convertKrexxFeSetting(
                $this->krexxStorage->config->getFeConfigFromFile('analyseMethodsAtall')
            );
            $value['methods']['analyseProtectedMethods'] = $this->convertKrexxFeSetting(
                $this->krexxStorage->config->getFeConfigFromFile('analyseProtectedMethods')
            );
            $value['methods']['analysePrivateMethods'] = $this->convertKrexxFeSetting(
                $this->krexxStorage->config->getFeConfigFromFile('analysePrivateMethods')
            );
            $value['backtraceAndError']['registerAutomatically'] = $this->convertKrexxFeSetting(
                $this->krexxStorage->config->getFeConfigFromFile('registerAutomatically')
            );
            $value['backtraceAndError']['backtraceAnalysis'] = $this->convertKrexxFeSetting(
                $this->krexxStorage->config->getFeConfigFromFile('backtraceAnalysis')
            );

            // Are these actually set?
            foreach ($value as $mainkey => $setting) {
                foreach ($setting as $attribute => $config) {
                    if (is_null($config)) {
                        $data['factory'][$attribute] = true;
                        // We need to fill these values with the stuff from the factory settings!
                        $value[$mainkey][$attribute] = $this->convertKrexxFeSetting(
                            $this->krexxStorage->config->feConfigFallback[$attribute]
                        );
                    } else {
                        $data['factory'][$attribute] = false;
                    }
                }
            }

            $this->view->assign('data', $data);
            $this->view->assign('value', $value);
            $this->addCssToView('Backend.css');
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
            $allOk = true;
            $filepath = $this->krexxStorage->config->getPathToIni();
            // Whitelist of the vales we are accepting.
            $allowedValues = array('full', 'display', 'none');

            // Check for writing permission.
            if (!is_writable(dirname($filepath))) {
                $allOk = false;
                $this->krexxStorage->messages->addKey('file.not.writable', array($filepath));
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

            if (isset($arguments['action']) && $arguments['action'] == 'save' && $allOk) {
                // We need to correct the allowed settings, since we do not allow anything.
                unset($this->allowedSettingsNames['destination']);
                unset($this->allowedSettingsNames['folder']);
                unset($this->allowedSettingsNames['maxfiles']);
                unset($this->allowedSettingsNames['debugMethods']);

                // Iterating through the form.
                foreach ($arguments as $key => $data) {
                    if (is_array($data) && $key != '__referrer') {
                        foreach ($data as $settingName => $value) {
                            if (in_array($value, $allowedValues) &&
                                in_array($settingName, $this->allowedSettingsNames)) {
                                // Whitelisted values are ok.
                                $oldValues['feEditing'][$settingName] = $value;
                            } else {
                                // Validation failed!
                                $allOk = false;
                                $this->krexxStorage->messages->addKey('value.not.allowed', array(htmlentities($value)));
                            }
                        }
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
                if ($allOk) {
                    if (file_put_contents($filepath, $ini) === false) {
                        $allOk = false;
                        $this->krexxStorage->messages->addKey('file.not.writable', array($filepath));
                    }
                }
            }

            // Something went wrong, we need to tell the user.
            if (!$allOk) {
                // Got to remove some messages. We we will not queue them now.
                $this->krexxStorage->messages->removeKey('protected.folder.chunk');
                $this->krexxStorage->messages->removeKey('protected.folder.log');
                foreach ($this->getTranslatedMessages() as $message) {
                    $this->addMessage(
                        $message,
                        $this->LLL('save.fail.title'),
                        t3lib_FlashMessage::ERROR
                    );
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
    }
}
