<?php
/**
 * @file
 *   Backend controller for for kreXX
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
 *   kreXX Copyright (C) 2014-2015 Brainworxx GmbH
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

// The mainproblem with 7.0 is, that compatibility6 may or may not be installed.
// If not, I have to put his thing here, hoping not to break anything!
if (!class_exists('Tx_Extbase_MVC_Controller_ActionController')) {
  class Tx_Extbase_MVC_Controller_ActionController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {
  }
}
if (!class_exists('Tx_Extbase_MVC_Controller_Arguments')) {
  class Tx_Extbase_MVC_Controller_Arguments extends \TYPO3\CMS\Extbase\Mvc\Controller\Arguments {
  }
}
if (!class_exists('t3lib_FlashMessage')) {
  class t3lib_FlashMessage extends \TYPO3\CMS\Core\Messaging\FlashMessage {
  }
}
class Tx_Includekrexx_Controller_IndexController extends Tx_Extbase_MVC_Controller_ActionController {

  /**
   * List of all setting-manes for which we are accepting values.
   *
   * @var array
   */
  protected $allowed_settings_names = array(
    'skin',
    'jsLib',
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
    'analysePublicMethods',
    'analyseProtectedMethods',
    'analysePrivateMethods',
    'registerAutomatically',
    'backtraceAnalysis');

  /**
   * List of all sections for which we are accepting values
   *
   * @var array
   */
  protected $allowed_sections = array(
    'render',
    'logging',
    'output',
    'deep',
    'methods',
    'errorHandling');

  /**
   * Simply display the help text from the fluid template.
   */
  public function usageHelpAction() {
  }

  /**
   * Simply display the help text from the fluid template.
   */
  public function configHelpAction() {
  }

  /**
   * Shows the configuration for the FE editing.
   */
  public function editFeConfigAction() {
    $data = array();
    $value = array();
    // Setting possible form values.
    $data['settings'] = array(
      'full' => 'full edit',
      'display' => 'display only',
      'none' => 'do not display');

    // See, if we have any values in the configuration file.
    $value['render']['skin'] = $this->convertKrexxFeSetting(\Krexx\Config::getFeConfigFromFile('skin'));
    $value['render']['jsLib'] = $this->convertKrexxFeSetting(\Krexx\Config::getFeConfigFromFile('jsLib'));
    $value['render']['memoryLeft'] = $this->convertKrexxFeSetting(\Krexx\Config::getFeConfigFromFile('memoryLeft'));
    $value['render']['maxRuntime'] = $this->convertKrexxFeSetting(\Krexx\Config::getFeConfigFromFile('maxRuntime'));
    $value['logging']['folder'] = $this->convertKrexxFeSetting(\Krexx\Config::getFeConfigFromFile('folder'));
    $value['logging']['maxfiles'] = $this->convertKrexxFeSetting(\Krexx\Config::getFeConfigFromFile('maxfiles'));
    $value['output']['destination'] = $this->convertKrexxFeSetting(\Krexx\Config::getFeConfigFromFile('destination'));
    $value['output']['maxCall'] = $this->convertKrexxFeSetting(\Krexx\Config::getFeConfigFromFile('maxCall'));
    $value['output']['disabled'] = $this->convertKrexxFeSetting(\Krexx\Config::getFeConfigFromFile('disabled'));
    $value['output']['detectAjax'] = $this->convertKrexxFeSetting(\Krexx\Config::getFeConfigFromFile('detectAjax'));
    $value['deep']['analyseProtected'] = $this->convertKrexxFeSetting(\Krexx\Config::getFeConfigFromFile('analyseProtected'));
    $value['deep']['analysePrivate'] = $this->convertKrexxFeSetting(\Krexx\Config::getFeConfigFromFile('analysePrivate'));
    $value['deep']['analyseTraversable'] = $this->convertKrexxFeSetting(\Krexx\Config::getFeConfigFromFile('analyseTraversable'));
    $value['deep']['debugMethods'] = $this->convertKrexxFeSetting(\Krexx\Config::getFeConfigFromFile('debugMethods'));
    $value['deep']['level'] = $this->convertKrexxFeSetting(\Krexx\Config::getFeConfigFromFile('level'));
    $value['methods']['analysePublicMethods'] = $this->convertKrexxFeSetting(\Krexx\Config::getFeConfigFromFile('analysePublicMethods'));
    $value['methods']['analyseProtectedMethods'] = $this->convertKrexxFeSetting(\Krexx\Config::getFeConfigFromFile('analyseProtectedMethods'));
    $value['methods']['analysePrivateMethods'] = $this->convertKrexxFeSetting(\Krexx\Config::getFeConfigFromFile('analysePrivateMethods'));
    $value['errorHandling']['registerAutomatically'] = $this->convertKrexxFeSetting(\Krexx\Config::getFeConfigFromFile('registerAutomatically'));
    $value['errorHandling']['backtraceAnalysis'] = $this->convertKrexxFeSetting(\Krexx\Config::getFeConfigFromFile('backtraceAnalysis'));

    // Are these actually set?
    foreach ($value as $mainkey => $setting) {
      foreach ($setting as $attribute => $config) {
        if (is_null($config)) {
          $data['factory'][$attribute] = TRUE;
          // We need to fill these values with the stuff from the factory settings!
          $value[$mainkey][$attribute] = $this->convertKrexxFeSetting(\Krexx\Config::$feConfigFallback[$attribute]);
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
    $data = array();
    $value = array();
    // Setting possible form values.
    foreach (Krexx\Render::getSkinList() as $skin) {
      $data['skins'][$skin] = $skin;
    }
    $data['destination'] = array('frontend' => 'frontend', 'file' => 'file');
    $data['bool'] = array('true' => 'true', 'false' => 'false');
    $data['backtrace'] = array('normal' => 'normal', 'deep' => 'deep');

    // Setting the form help texts
    $data['title'] = array(
      'localFunction' => strip_tags(\Krexx\Help::getHelp('localFunction')),
      'analyseProtected' => strip_tags(\Krexx\Help::getHelp('analyseProtected')),
      'analysePrivate' => strip_tags(\Krexx\Help::getHelp('analysePrivate')),
      'analyseTraversable' => strip_tags(\Krexx\Help::getHelp('analysePrivate')),
      'debugMethods' => 'Comma-separated list of used debug callback functions. A lot of frameworks offer these, toArray and toString beeing the most common. kreXX will try to call them, if they are available and display their provided data.',
      'level' => strip_tags(\Krexx\Help::getHelp('level')),
      'resetbutton' => strip_tags(\Krexx\Help::getHelp('resetbutton')),
      'destination' => strip_tags(\Krexx\Help::getHelp('destination')),
      'maxCall' => strip_tags(\Krexx\Help::getHelp('maxCall')),
      'disabled' => 'Here you can disable kreXX without uninstalling the whole module.',
      'folder' => strip_tags(\Krexx\Help::getHelp('folder')),
      'maxfiles' => strip_tags(\Krexx\Help::getHelp('maxfiles')),
      'skin' => strip_tags(\Krexx\Help::getHelp('skin')),
      'jsLib' => strip_tags(\Krexx\Help::getHelp('jsLib')),
      'currentSettings' => strip_tags(\Krexx\Help::getHelp('currentSettings')),
      'debugcookie' => strip_tags(\Krexx\Help::getHelp('debugcookie')),
      'registerAutomatically' => strip_tags(\Krexx\Help::getHelp('registerAutomatically')),
      'detectAjax' => strip_tags(\Krexx\Help::getHelp('detectAjax')),
      'backtraceAnalysis' => strip_tags(\Krexx\Help::getHelp('backtraceAnalysis')),
      'memoryLeft' => strip_tags(\Krexx\Help::getHelp('memoryLeft')),
      'maxRuntime' => strip_tags(\Krexx\Help::getHelp('maxRuntime')),
      'analysePublicMethods' => strip_tags(\Krexx\Help::getHelp('analysePublicMethods')),
      'analyseProtectedMethods' => strip_tags(\Krexx\Help::getHelp('analyseProtectedMethods')),
      'analysePrivateMethods' => strip_tags(\Krexx\Help::getHelp('analysePrivateMethods')));

    // See, if we have any values in the configuration file.
    $value['render']['skin'] = \Krexx\Config::getConfigFromFile('render', 'skin');
    $value['render']['jsLib'] = \Krexx\Config::getConfigFromFile('render', 'jsLib');
    $value['render']['memoryLeft'] = \Krexx\Config::getConfigFromFile('render', 'memoryLeft');
    $value['render']['maxRuntime'] = \Krexx\Config::getConfigFromFile('render', 'maxRuntime');
    $value['logging']['folder'] = \Krexx\Config::getConfigFromFile('logging', 'folder');
    $value['logging']['maxfiles'] = \Krexx\Config::getConfigFromFile('logging', 'maxfiles');
    $value['output']['destination'] = \Krexx\Config::getConfigFromFile('output', 'destination');
    $value['output']['maxCall'] = \Krexx\Config::getConfigFromFile('output', 'maxCall');
    $value['output']['disabled'] = \Krexx\Config::getConfigFromFile('output', 'disabled');
    $value['output']['detectAjax'] = \Krexx\Config::getConfigFromFile('output', 'detectAjax');
    $value['deep']['analyseProtected'] = \Krexx\Config::getConfigFromFile('deep', 'analyseProtected');
    $value['deep']['analysePrivate'] = \Krexx\Config::getConfigFromFile('deep', 'analysePrivate');
    $value['deep']['analyseTraversable'] = \Krexx\Config::getConfigFromFile('deep', 'analyseTraversable');
    $value['deep']['debugMethods'] = \Krexx\Config::getConfigFromFile('deep', 'debugMethods');
    $value['deep']['level'] = \Krexx\Config::getConfigFromFile('deep', 'level');
    $value['methods']['analysePublicMethods'] = \Krexx\Config::getConfigFromFile('methods', 'analysePublicMethods');
    $value['methods']['analyseProtectedMethods'] = \Krexx\Config::getConfigFromFile('methods', 'analyseProtectedMethods');
    $value['methods']['analysePrivateMethods'] = \Krexx\Config::getConfigFromFile('methods', 'analysePrivateMethods');
    $value['errorHandling']['registerAutomatically'] = \Krexx\Config::getConfigFromFile('errorHandling', 'registerAutomatically');
    $value['errorHandling']['backtraceAnalysis'] = \Krexx\Config::getConfigFromFile('errorHandling', 'backtraceAnalysis');

    // Are these actually set?
    foreach ($value as $mainkey => $setting) {
      foreach ($setting as $attribute => $config) {
        if (is_null($config)) {
          $data['factory'][$attribute] = TRUE;
          // We need to fill these values with the stuff from the factory settings!
          $value[$mainkey][$attribute] = \Krexx\Config::$configFallback[$mainkey][$attribute];
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
    $filepath = \Krexx\Config::getPathToIni();
    // We must preserve the section 'feEditing'.
    // Everything else will be overwritten
    $old_values = parse_ini_file($filepath, TRUE);
    $old_values = array('feEditing' => $old_values['feEditing']);

    if (isset($arguments['action']) && $arguments['action'] == 'saveConfig') {
      // Iterating through the form.
      foreach ($arguments as $section => $data) {
        if (is_array($data) && in_array($section, $this->allowed_sections)) {
          // We've got a section key.
          foreach ($data as $setting_name => $value) {
            if (in_array($setting_name, $this->allowed_settings_names)) {
              // We escape the value, just in case, since we can not whitelist it.
              $value = htmlspecialchars(preg_replace('/\s+/', '', $value));
              // Evaluate the setting!
              if (\krexx\Config::evaluateSetting($section,$setting_name, $value)) {
                $old_values[$section][$setting_name] = $value;
              }
              else {
                // Validation failed! kreXX will generate a message, which we will display
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
        \krexx\Messages::addMessage('Configuration file ' . $filepath . ' is not writeable!');
      }
    }
    // Something went wrong, we need to tell the user.
    if (!$all_ok) {
      $this->addMessage(strip_tags(\krexx\Messages::outputMessages()), "The settings were NOT saved.", t3lib_FlashMessage::ERROR);
    }
    else {
      $this->addMessage("The settings were saved to: <br /> " . $filepath, "The data was saved.", t3lib_FlashMessage::OK);
    }
    $this->redirect('editConfig');
  }

  /**
   * Wrapper for the FlashMessage, which was changed in 7.0.
   *
   * @param string $text
   * @param integer $severity
   */
  protected function addMessage($text, $title, $severity) {
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
    $filepath = \Krexx\Config::getPathToIni();
    // Whitelist of the vales we are accepting.
    $allowed_values = array('full', 'display', 'none');

    // Get the old values . . .
    $old_values = parse_ini_file($filepath, TRUE);
    // . . . and remove our part.
    unset($old_values['feEditing']);

    if (isset($arguments['action']) && $arguments['action'] == 'saveFeConfig') {
      // Iterating through the form.
      foreach ($arguments as $key => $data) {
        if (is_array($data) && $key != '__referrer') {
          foreach ($data as $setting_name => $value) {
            if (in_array($value, $allowed_values) && in_array($setting_name, $this->allowed_settings_names)) {
              // Whitelisted values are ok.
              $old_values['feEditing'][$setting_name] = $value;
            }
            else {
              // Validation failed!
              $all_ok = FALSE;
              \krexx\Messages::addMessage(htmlentities($value) . ' is not an allowed value!');
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
      if ($all_ok){
        if (file_put_contents($filepath, $ini) === FALSE) {
          $all_ok = FALSE;
          \krexx\Messages::addMessage('Configuration file ' . $filepath . ' is not writeable!');
        }
      }

      // Something went wrong, we need to tell the user.
      if (!$all_ok) {
        $this->addMessage(strip_tags(\krexx\Messages::outputMessages()), "The settings were NOT saved.", t3lib_FlashMessage::ERROR);
      }
      else {
        $this->addMessage("The settings were saved to: <br /> " . $filepath, "The data was saved.", t3lib_FlashMessage::OK);
      }
    }

    $this->redirect('editFeConfig');
  }

  /**
   * Injects the arguments
   *
   * @param Tx_Extbase_MVC_Controller_Arguments $arguments
   *          The arguments from the call to the controller.
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
   */
  protected function convertKrexxFeSetting($values) {
    if (is_array($values)) {
      // full -> is editable and values will be accepted.
      // display -> we will only display the settings.
      // The original values include the name of a template partial
      // with the form element.
      if ($values['type'] == 'None') {
        // It's not visible, thus we do not accept any values from it
        $result = 'none';
      }
      if ($values['editable'] == 'true' && $values['type'] != 'None') {
        // It's editable and visible
        $result = 'full';
      }
      if ($values['editable'] == 'false' && $values['type'] != 'None') {
        // It's only visible
        $result = 'display';
      }
      return $result;
    }
  }
}
