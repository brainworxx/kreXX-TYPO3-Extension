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
 *   kreXX Copyright (C) 2014-2017 Brainworxx GmbH
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

/**
 * Configuration controller for the kreXX typo3 extension
 */
class Tx_Includekrexx_Controller_ConfigController extends Tx_Includekrexx_Controller_CompatibilityController
{

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

        $data = array();
        // Setting possible form values.
        foreach ($this->pool->render->getSkinList() as $skin) {
            $data['skins'][$skin] = $skin;
        }
        $data['destination'] = array(
            'browser' => $this->LLL('browser'),
            'file' => $this->LLL('file'),
        );
        $data['bool'] = array(
            'true' => $this->LLL('true'),
            'false' => $this->LLL('false'),
        );
        $data['backtrace'] = array(
            'normal' => $this->LLL('normal'),
            'deep' => $this->LLL('deep'),
        );

        // Assigning the help stuff to the template.
        $data['title'] = array();
        $value = array();
        foreach ($this->allowedSettingsNames as $settingsName) {
            $data['title'][$settingsName] = $this->LLL($settingsName);
        }

        $iniConfig = $this->pool->config->iniConfig;

        // See, if we have any values in the configuration file.
        $value['output']['skin'] = $iniConfig->getConfigFromFile(
            'output',
            'skin'
        );
        $value['runtime']['memoryLeft'] = $iniConfig->getConfigFromFile(
            'runtime',
            'memoryLeft'
        );
        $value['runtime']['maxRuntime'] = $iniConfig->getConfigFromFile(
            'runtime',
            'maxRuntime'
        );
        $value['output']['maxfiles'] = $iniConfig->getConfigFromFile(
            'output',
            'maxfiles'
        );
        $value['output']['destination'] = $iniConfig->getConfigFromFile(
            'output',
            'destination'
        );
        $value['runtime']['maxCall'] = $iniConfig->getConfigFromFile(
            'runtime',
            'maxCall'
        );
        $value['output']['disabled'] = $iniConfig->getConfigFromFile(
            'output',
            'disabled'
        );
        $value['output']['iprange'] = $iniConfig->getConfigFromFile(
            'output',
            'iprange'
        );
        $value['runtime']['detectAjax'] = $iniConfig->getConfigFromFile(
            'runtime',
            'detectAjax'
        );
        $value['properties']['analyseProtected'] = $iniConfig->getConfigFromFile(
            'properties',
            'analyseProtected'
        );
        $value['properties']['analysePrivate'] = $iniConfig->getConfigFromFile(
            'properties',
            'analysePrivate'
        );
        $value['properties']['analyseConstants'] = $iniConfig->getConfigFromFile(
            'properties',
            'analyseConstants'
        );
        $value['properties']['analyseTraversable'] = $iniConfig->getConfigFromFile(
            'properties',
            'analyseTraversable'
        );
        $value['methods']['debugMethods'] = $iniConfig->getConfigFromFile(
            'methods',
            'debugMethods'
        );
        $value['runtime']['level'] = $iniConfig->getConfigFromFile(
            'runtime',
            'level'
        );
        $value['methods']['analyseProtectedMethods'] = $iniConfig->getConfigFromFile(
            'methods',
            'analyseProtectedMethods'
        );
        $value['methods']['analysePrivateMethods'] = $iniConfig->getConfigFromFile(
            'methods',
            'analysePrivateMethods'
        );
        $value['methods']['analyseGetter'] = $iniConfig->getConfigFromFile(
            'methods',
            'analyseGetter'
        );
        $value['backtraceAndError']['registerAutomatically'] = $iniConfig->getConfigFromFile(
            'backtraceAndError',
            'registerAutomatically'
        );
        $value['backtraceAndError']['maxStepNumber'] = $iniConfig->getConfigFromFile(
            'backtraceAndError',
            'maxStepNumber'
        );
        $value['runtime']['useScopeAnalysis'] = $iniConfig->getConfigFromFile(
            'runtime',
            'useScopeAnalysis'
        );

        // Are these actually set?
        foreach ($value as $mainkey => $setting) {
            foreach ($setting as $attribute => $config) {
                if (is_null($config)) {
                    $data['factory'][$attribute] = true;
                    // We need to fill these values with the stuff from the
                    // factory settings!
                    $value[$mainkey][$attribute] = $this->pool->config->configFallback[$mainkey][$attribute];
                } else {
                    $data['factory'][$attribute] = false;
                }
            }
        }

        $this->view->assign('data', $data);
        $this->view->assign('value', $value);
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
        $allOk = true;
        $filepath = $this->pool->config->getPathToIniFile();


        // Check for writing permission.
        if (!is_writable(dirname($filepath))) {
            $allOk = false;
            $this->pool->messages->addMessage('file.not.writable', array($filepath));
        }

        // Check if the file does exist.
        if (is_file($filepath)) {
            $oldValues = parse_ini_file($filepath, true);
        } else {
            $oldValues = array();
        }

        // We must preserve the section 'feEditing'.
        // Everything else will be overwritten.
        $oldValues = array('feEditing' => $oldValues['feEditing']);

        if (isset($arguments['action']) && $arguments['action'] == 'save' && $allOk) {
            // Iterating through the form.
            foreach ($arguments as $section => $data) {
                if (is_array($data) && in_array($section, $this->allowedSections)) {
                    // We've got a section key.
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
                                $allOk = false;
                            }
                        }
                    }
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
            if ($allOk &&
                file_put_contents($filepath, $ini) === false
            ) {
                $allOk = false;
                $this->pool->messages->addMessage('file.not.writable', array($filepath));
            }
        }

        // Something went wrong, we need to tell the user.
        if (!$allOk) {
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
}
