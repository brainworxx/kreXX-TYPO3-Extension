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

use \Brainworxx\Krexx\Framework\Config;
use \Brainworxx\Krexx\View\Messages;
use \Brainworxx\Krexx\View\Render;

// The 7.3'er autoloader tries to include this file twice, probably
// because of the class mappings above. I need to make sure not to
// redeclare the Tx_Includekrexx_Controller_IndexController and throw
// a fatal.
if (!class_exists('Tx_Includekrexx_Controller_FormConfigController')) {

  /**
   * Class Tx_Includekrexx_Controller_IndexController
   */
  class Tx_Includekrexx_Controller_FormConfigController extends Tx_Includekrexx_Controller_CompatibilityController {

    /**
     * List of all setting-names for which we are accepting values.
     *
     * @var array
     */
    protected $allowedSettingsNames = array(
      'skin',
      'memoryLeft',
      'maxRuntime',
      'folder',
      'maxfiles',
      'destination',
      'maxCall',
      'disabled',
      'detectAjax',
      'analyseProtected',
      'analysePrivate',
      'analyseTraversable',
      'debugMethods',
      'level',
      'analyseMethodsAtall',
      'analyseProtectedMethods',
      'analysePrivateMethods',
      'registerAutomatically',
      'backtraceAnalysis',
      'analyseConstants',
    );

    /**
     * Shows the configuration for the FE editing.
     */
    public function editAction() {
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
      $value['output']['skin'] = $this->convertKrexxFeSetting(Config::getFeConfigFromFile('skin'));
      $value['runtime']['memoryLeft'] = $this->convertKrexxFeSetting(Config::getFeConfigFromFile('memoryLeft'));
      $value['runtime']['maxRuntime'] = $this->convertKrexxFeSetting(Config::getFeConfigFromFile('maxRuntime'));
      $value['output']['folder'] = $this->convertKrexxFeSetting(Config::getFeConfigFromFile('folder'));
      $value['output']['maxfiles'] = $this->convertKrexxFeSetting(Config::getFeConfigFromFile('maxfiles'));
      $value['output']['destination'] = $this->convertKrexxFeSetting(Config::getFeConfigFromFile('destination'));
      $value['runtime']['maxCall'] = $this->convertKrexxFeSetting(Config::getFeConfigFromFile('maxCall'));
      $value['runtime']['disabled'] = $this->convertKrexxFeSetting(Config::getFeConfigFromFile('disabled'));
      $value['runtime']['detectAjax'] = $this->convertKrexxFeSetting(Config::getFeConfigFromFile('detectAjax'));
      $value['properties']['analyseProtected'] = $this->convertKrexxFeSetting(Config::getFeConfigFromFile('analyseProtected'));
      $value['properties']['analysePrivate'] = $this->convertKrexxFeSetting(Config::getFeConfigFromFile('analysePrivate'));
      $value['properties']['analyseTraversable'] = $this->convertKrexxFeSetting(Config::getFeConfigFromFile('analyseTraversable'));
      $value['properties']['analyseConstants'] = $this->convertKrexxFeSetting(Config::getFeConfigFromFile('analyseConstants'));
      $value['methods']['debugMethods'] = $this->convertKrexxFeSetting(Config::getFeConfigFromFile('debugMethods'));
      $value['runtime']['level'] = $this->convertKrexxFeSetting(Config::getFeConfigFromFile('level'));
      $value['methods']['analyseMethodsAtall'] = $this->convertKrexxFeSetting(Config::getFeConfigFromFile('analyseMethodsAtall'));
      $value['methods']['analyseProtectedMethods'] = $this->convertKrexxFeSetting(Config::getFeConfigFromFile('analyseProtectedMethods'));
      $value['methods']['analysePrivateMethods'] = $this->convertKrexxFeSetting(Config::getFeConfigFromFile('analysePrivateMethods'));
      $value['backtraceAndError']['registerAutomatically'] = $this->convertKrexxFeSetting(Config::getFeConfigFromFile('registerAutomatically'));
      $value['backtraceAndError']['backtraceAnalysis'] = $this->convertKrexxFeSetting(Config::getFeConfigFromFile('backtraceAnalysis'));

      // Are these actually set?
      foreach ($value as $mainkey => $setting) {
        foreach ($setting as $attribute => $config) {
          if (is_null($config)) {
            $data['factory'][$attribute] = TRUE;
            // We need to fill these values with the stuff from the factory settings!
            $value[$mainkey][$attribute] = $this->convertKrexxFeSetting(Config::$feConfigFallback[$attribute]);
          }
          else {
            $data['factory'][$attribute] = FALSE;
          }
        }
      }

      $this->view->assign('data', $data);
      $this->view->assign('value', $value);
    }

    /**
     * Saves the settings for the frontend editing.
     *
     * We are saving the values of the FE editing in the same file as
     * the rest of the kreXX settings. Since we are using different forms,
     * we need to check the values already set.
     */
    public function saveAction() {
      $arguments = $this->request->getArguments();
      $all_ok = TRUE;
      $filepath = Config::getPathToIni();
      // Whitelist of the vales we are accepting.
      $allowed_values = array('full', 'display', 'none');

      // Check for writing permission.
      if (!is_writable(dirname($filepath))) {
        $all_ok = FALSE;
        Messages::addMessage($this->LLL('file.not.writable', array($filepath)));
      }
      // Check if the file does exist.
      if (is_file($filepath)) {
        // Get the old values . . .
        $old_values = parse_ini_file($filepath, TRUE);
        // . . . and remove our part.
        unset($old_values['feEditing']);
      }
      else {
        $old_values = array();
      }

      if (isset($arguments['action']) && $arguments['action'] == 'save') {
        // Iterating through the form.
        foreach ($arguments as $key => $data) {
          if (is_array($data) && $key != '__referrer') {
            foreach ($data as $setting_name => $value) {
              if (in_array($value, $allowed_values) && in_array($setting_name, $this->allowedSettingsNames)) {
                // Whitelisted values are ok.
                $old_values['feEditing'][$setting_name] = $value;
              }
              else {
                // Validation failed!
                $all_ok = FALSE;
                Messages::addMessage($this->LLL('value.not.allowed', array(htmlentities($value))));
              }
            }
          }
        }

        // Now we must create the ini file.
        $ini = '';
        foreach ($old_values as $key => $setting) {
          $ini .= '[' . $key . ']' . PHP_EOL;
          foreach ($setting as $setting_name => $value) {
            $ini .= $setting_name . ' = "' . $value . '"' . PHP_EOL;
          }
        }

        // Now we should write the file!
        if ($all_ok) {
          if (file_put_contents($filepath, $ini) === FALSE) {
            $all_ok = FALSE;
            Messages::addMessage($this->LLL('file.not.writable', array($filepath)));
          }
        }

        // Something went wrong, we need to tell the user.
        if (!$all_ok) {
          // Got to remove some messages. We we will not queue them now.
          Messages::removeKey('protected.folder.chunk');
          Messages::removeKey('protected.folder.log');
          foreach ($this->getTranslatedMessages() as $message) {
            $this->addMessage($message, $this->LLL('save.fail.title'), t3lib_FlashMessage::ERROR);
          }

        }
        else {
          $this->addMessage($this->LLL('save.success.text', array($filepath)), $this->LLL('save.success.title'), t3lib_FlashMessage::OK);
        }
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
    protected function convertKrexxFeSetting($values) {
      // $result = 'none';
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
