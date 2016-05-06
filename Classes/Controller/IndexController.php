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


// The mainproblem with 7.0 is, that compatibility6 may or may not be installed.
// If not, I have to put this thing here, hoping not to break anything!
if (!class_exists('Tx_Extbase_MVC_Controller_ActionController')) {
  /**
   * Class Tx_Extbase_MVC_Controller_ActionController
   */
  class Tx_Extbase_MVC_Controller_ActionController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {
  }
}
if (!class_exists('Tx_Extbase_MVC_Controller_Arguments')) {
  /**
   * Class Tx_Extbase_MVC_Controller_Arguments
   */
  class Tx_Extbase_MVC_Controller_Arguments extends \TYPO3\CMS\Extbase\Mvc\Controller\Arguments {
  }
}
if (!class_exists('t3lib_FlashMessage')) {
  /**
   * Class t3lib_FlashMessage
   */
  class t3lib_FlashMessage extends \TYPO3\CMS\Core\Messaging\FlashMessage {
  }
}

// The 7.3'er autoloader tries to include this file twice, probably
// because of the class mappings above. I need to make sure not to
// redeclare the Tx_Includekrexx_Controller_IndexController and throw
// a fatal.
if (!class_exists('Tx_Includekrexx_Controller_IndexController')) {
  /**
   * Class Tx_Includekrexx_Controller_IndexController
   */
  class Tx_Includekrexx_Controller_IndexController extends Tx_Extbase_MVC_Controller_ActionController {

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
     * List of all sections for which we are accepting values
     *
     * @var array
     */
    protected $allowedSections = array(
      'runtime',
      'output',
      'properties',
      'methods',
      'backtraceAndError',
    );

    /**
     * Simply display the help text from the fluid template.
     */
    public function usageHelpAction() {
      // Has kreXX something to say? Maybe a writeprotected logfolder?
      foreach ($this->getTranslatedMessages() as $message) {
        $this->addMessage($message, $this->LLL('general.error.title'), t3lib_FlashMessage::ERROR);
      }
    }

    /**
     * Simply display the help text from the fluid template.
     */
    public function configHelpAction() {
      // Has kreXX something to say? Maybe a writeprotected logfolder?
      foreach ($this->getTranslatedMessages() as $message) {
        $this->addMessage($message, $this->LLL('general.error.title'), t3lib_FlashMessage::ERROR);
      }
    }

    /**
     * Simply display the kreXX local browser configuration.
     */
    public function editLocalBrowserSettingsAction() {
      // Has kreXX something to say? Maybe a writeprotected logfolder?
      foreach ($this->getTranslatedMessages() as $message) {
        $this->addMessage($message, $this->LLL('general.error.title'), t3lib_FlashMessage::ERROR);
      }

      if (!Config::isEnabled(NULL, TRUE)) {
        // kreXX will not display anything, if it was disabled via:
        // - krexx::disable();
        // - Disable output --> true in the "Edit configuration file menu
        // We need to tell the user that krexx was disabled.
        $this->view->assign('is_disabled', TRUE);
      }
      else {
        $this->view->assign('is_disabled', FALSE);
      }
      \krexx::editSettings();
    }

    /**
     * Shows the configuration for the FE editing.
     */
    public function editFeConfigAction() {
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
     * Shows the edit config screen.
     */
    public function editConfigAction() {
      // Has kreXX something to say? Maybe a writeprotected logfolder?
      foreach ($this->getTranslatedMessages() as $message) {
        $this->addMessage($message, $this->LLL('general.error.title'), t3lib_FlashMessage::ERROR);
      }

      $data = array();
      $value = array();
      // Setting possible form values.
      foreach (Render::getSkinList() as $skin) {
        $data['skins'][$skin] = $skin;
      }
      $data['destination'] = array(
        'frontend' => $this->LLL('frontend'),
        'file' => $this->LLL('file')
      );
      $data['bool'] = array(
        'true' => $this->LLL('true'),
        'false' => $this->LLL('false')
      );
      $data['backtrace'] = array(
        'normal' => $this->LLL('normal'),
        'deep' => $this->LLL('deep')
      );

      // Setting the form help texts.
      $data['title'] = array(
        'localFunction' => $this->LLL('localFunction'),
        'analyseProtected' => $this->LLL('analyseProtected'),
        'analysePrivate' => $this->LLL('analysePrivate'),
        'analyseTraversable' => $this->LLL('analysePrivate'),
        'debugMethods' => $this->LLL('debugMethods'),
        'level' => $this->LLL('level'),
        'resetbutton' => $this->LLL('resetbutton'),
        'destination' => $this->LLL('destination'),
        'maxCall' => $this->LLL('maxCall'),
        'disabled' => $this->LLL('disabled'),
        'folder' => $this->LLL('folder'),
        'maxfiles' => $this->LLL('maxfiles'),
        'skin' => $this->LLL('skin'),
        'currentSettings' => $this->LLL('currentSettings'),
        'registerAutomatically' => $this->LLL('registerAutomatically'),
        'detectAjax' => $this->LLL('detectAjax'),
        'backtraceAnalysis' => $this->LLL('backtraceAnalysis'),
        'memoryLeft' => $this->LLL('memoryLeft'),
        'maxRuntime' => $this->LLL('maxRuntime'),
        'analyseMethodsAtall' => $this->LLL('analyseMethodsAtall'),
        'analyseProtectedMethods' => $this->LLL('analyseProtectedMethods'),
        'analysePrivateMethods' => $this->LLL('analysePrivateMethods'),
        'analyseConstants' => $this->LLL('analyseConstants'),
      );

      // See, if we have any values in the configuration file.
      $value['output']['skin'] = Config::getConfigFromFile('output', 'skin');
      $value['runtime']['memoryLeft'] = Config::getConfigFromFile('runtime', 'memoryLeft');
      $value['runtime']['maxRuntime'] = Config::getConfigFromFile('runtime', 'maxRuntime');
      $value['output']['folder'] = Config::getConfigFromFile('output', 'folder');
      $value['output']['maxfiles'] = Config::getConfigFromFile('output', 'maxfiles');
      $value['output']['destination'] = Config::getConfigFromFile('output', 'destination');
      $value['runtime']['maxCall'] = Config::getConfigFromFile('runtime', 'maxCall');
      $value['runtime']['disabled'] = Config::getConfigFromFile('runtime', 'disabled');
      $value['runtime']['detectAjax'] = Config::getConfigFromFile('runtime', 'detectAjax');
      $value['properties']['analyseProtected'] = Config::getConfigFromFile('properties', 'analyseProtected');
      $value['properties']['analysePrivate'] = Config::getConfigFromFile('properties', 'analysePrivate');
      $value['properties']['analyseConstants'] = Config::getConfigFromFile('properties', 'analyseConstants');
      $value['properties']['analyseTraversable'] = Config::getConfigFromFile('properties', 'analyseTraversable');
      $value['methods']['debugMethods'] = Config::getConfigFromFile('methods', 'debugMethods');
      $value['runtime']['level'] = Config::getConfigFromFile('runtime', 'level');
      $value['methods']['analyseMethodsAtall'] = Config::getConfigFromFile('methods', 'analyseMethodsAtall');
      $value['methods']['analyseProtectedMethods'] = Config::getConfigFromFile('methods', 'analyseProtectedMethods');
      $value['methods']['analysePrivateMethods'] = Config::getConfigFromFile('methods', 'analysePrivateMethods');
      $value['backtraceAndError']['registerAutomatically'] = Config::getConfigFromFile('backtraceAndError', 'registerAutomatically');
      $value['backtraceAndError']['backtraceAnalysis'] = Config::getConfigFromFile('backtraceAndError', 'backtraceAnalysis');

      // Are these actually set?
      foreach ($value as $mainkey => $setting) {
        foreach ($setting as $attribute => $config) {
          if (is_null($config)) {
            $data['factory'][$attribute] = TRUE;
            // We need to fill these values with the stuff from the
            // factory settings!
            $value[$mainkey][$attribute] = Config::$configFallback[$mainkey][$attribute];
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
     * Saves the kreXX configuration.
     */
    public function saveConfigAction() {
      $arguments = $this->request->getArguments();
      $all_ok = TRUE;
      $filepath = Config::getPathToIni();

      // Check for writing permission.
      if (!is_writable(dirname($filepath))) {
        $all_ok = FALSE;
        Messages::addMessage($this->LLL('file.not.writable', array($filepath)));
      }

      // Check if the file does exist.
      if (is_file($filepath)) {
        $old_values = parse_ini_file($filepath, TRUE);
      }
      else {
        $old_values = array();
      }

      // We must preserve the section 'feEditing'.
      // Everything else will be overwritten.
      $old_values = array('feEditing' => $old_values['feEditing']);

      if (isset($arguments['action']) && $arguments['action'] == 'saveConfig') {
        // Iterating through the form.
        foreach ($arguments as $section => $data) {
          if (is_array($data) && in_array($section, $this->allowedSections)) {
            // We've got a section key.
            foreach ($data as $setting_name => $value) {
              if (in_array($setting_name, $this->allowedSettingsNames)) {
                // We escape the value, just in case, since we can not
                // whitelist it.
                $value = htmlspecialchars(preg_replace('/\s+/', '', $value));
                // Evaluate the setting!
                if (Config::evaluateSetting($section, $setting_name, $value)) {
                  $old_values[$section][$setting_name] = $value;
                }
                else {
                  // Validation failed! kreXX will generate a message,
                  // which we will display
                  // at the buttom.
                  $all_ok = FALSE;
                }
              }
            }
          }
        }
      }

      // Now we must create the ini file.
      $ini = '';
      foreach ($old_values as $key => $setting) {
        $ini .= '[' . $key . ']' . PHP_EOL;
        if (is_array($setting)) {
          foreach ($setting as $setting_name => $value) {
            $ini .= $setting_name . ' = "' . $value . '"' . PHP_EOL;
          }
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
      $this->redirect('editConfig');
    }

    /**
     * Wrapper for the FlashMessage, which was changed in 7.0.
     *
     * @param string $text
     *   The text of the message.
     * @param string $title
     *   The title of the message
     * @param integer $severity
     *   The severity of the message.
     */
    protected function addMessage($text, $title, $severity) {
      if (empty($text)) {
        // No text, no message.
        return;
      }
      if (!isset($this->flashMessageContainer)) {
        $this->addFlashMessage($text, $title, $severity);
      }
      else {
        $this->flashMessageContainer->add($text, $title, $severity);
      }
    }

    /**
     * Saves the settings for the frontend editing.
     *
     * We are saving the values of the FE editing in the same file as
     * the rest of the kreXX settings. Since we are using different forms,
     * we need to check the values already set.
     */
    public function saveFeConfigAction() {
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

      if (isset($arguments['action']) && $arguments['action'] == 'saveFeConfig') {
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

      $this->redirect('editFeConfig');
    }

    /**
     * Injects the arguments
     *
     * @param Tx_Extbase_MVC_Controller_Arguments $arguments
     *   The arguments from the call to the controller.
     */
    public function injectArguments(Tx_Extbase_MVC_Controller_Arguments $arguments) {
      $this->arguments = $arguments;
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

    /**
     * Wrapper for the \TYPO3\CMS\Extbase\Utility\LocalizationUtility
     *
     * @param string $key
     *   The key we want to translate
     * @param null|array $args
     *   The strings from the controller we want to place inside the
     *   translation.
     *
     * @return string
     *   The translation itself.
     */
    protected function LLL($key, $args = NULL) {

      if ((int) TYPO3_version > 6) {
        // 7+ version.
        $result = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'includekrexx', $args);
      }
      else {
        // Version 4.5 until 6.2
        $result = Tx_Extbase_Utility_Localization::translate($key, 'includekrexx', $args);
      }

      return $result;
    }

    /**
     * Gets all messages from kreXX and translates them.
     *
     * @return array
     *   The translated messages.
     */
    protected function getTranslatedMessages() {
      $result = array();
      // Get the keys and the args.
      $keys = Messages::getKeys();

      foreach ($keys as $message) {
        // And translate them and add a linebreak.
        $result[] = $this->LLL($message['key'], $message['params']);
      }

      return $result;
    }
  }
}
