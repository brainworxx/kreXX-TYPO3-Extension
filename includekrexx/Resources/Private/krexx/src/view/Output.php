<?php
/**
 * @file
 *   Code generation functions for kreXX
 *   kreXX: Krumo eXXtended
 *
 *   This is a debugging tool, which displays structured information
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

namespace Brainworxx\Krexx\View;

use Brainworxx\Krexx\Framework\Config;
use Brainworxx\Krexx\Analysis;
use Brainworxx\Krexx\Framework\Chunks;
use Brainworxx\Krexx\Framework\Toolbox;

/**
 * This class hosts the code generation functions.
 *
 * @package Krexx
 */
class Output {

  public static $headerSend = FALSE;

  /**
   * Outputs a string, either to the browser or file.
   *
   * Wrapper for sendOutputToBrowser() and saveOutputToFile()
   *
   * @param string $string
   *   The generated DOM so far, for the output.
   * @param bool $ignore_local_settings
   *   Are we ignoring local settings?
   */
  public static function outputNow($string, $ignore_local_settings = FALSE) {
    if (Config::getConfigValue('output', 'destination', $ignore_local_settings) == 'file') {
      // Save it to a file.
      Chunks::saveDechunkedToFile($string);
    }
    else {
      // Send it to the browser.
      Chunks::sendDechunkedToBrowser($string);
    }
  }

  /**
   * Simply outputs the Header of kreXX.
   *
   * @param string $headline
   *   The headline, displayed in the header.
   * @param bool $ignore_local_settings
   *   Are we ignoring local cookie settings? Should only be
   *   TRUE when we render the settings menu only.
   *
   * @return string
   *   The generated markup
   */
  public static function outputHeader($headline, $ignore_local_settings = FALSE) {

    // Do we do an output as file?
    $output_as_file = (Config::getConfigValue('output', 'destination') == 'file');
    // When we have a normal file output, and ignore the local settings,
    // it means we are currently rendering the frontend "Edit local settings"
    // mask but outputting the rest into a file.
    // We need to render the CSS/JS for the frontend, because we have dual
    // output (frontend and file).
    $dual_output = ($output_as_file && $ignore_local_settings);

    if (!self::$headerSend || $dual_output == TRUE) {
      // Send doctype and css/js only once.
      self::$headerSend = TRUE;
      return SkinRender::renderHeader('<!DOCTYPE html>', $headline, self::outputCssAndJs());
    }
    else {
      return SkinRender::renderHeader('', $headline, '');
    }
  }

  /**
   * Simply renders the footer and output current settings.
   *
   * @param array $caller
   *   Where was kreXX initially invoked from.
   * @param bool $is_expanded
   *   Are we rendering an expanded footer?
   *   TRUE when we render the settings menu only.
   *
   * @return string
   *   The generated markup.
   */
  public static function outputFooter($caller, $is_expanded = FALSE) {
    // Wrap an expandable around to save space.
    $anon_function = function ($params) {
      $config = $params[0];
      $source = $params[1];
      $config_output = '';
      foreach ($config as $section_name => $section_data) {
        $params_expandable = array(
          $section_data,
          $source[$section_name]);

        // Render a whole section.
        $anonfunction = function ($params) {
          // $section_name = $params[0];
          $section_data = $params[0];
          $source = $params[1];
          $section_output = '';
          foreach ($section_data as $parameter_name => $parameter_value) {
            // Render the single value.
            // We need to find out where the value comes from.
            $config = Config::getFeConfig($parameter_name);
            $editable = $config[0];
            $type = $config[1];

            if ($type != 'None') {
              if ($editable) {
                $section_output .= SkinRender::renderSingleEditableChild($parameter_name, htmlspecialchars($parameter_value), $source[$parameter_name], $type, $parameter_name);
              }
              else {
                $section_output .= SkinRender::renderSingleChild($parameter_value, $parameter_name, htmlspecialchars($parameter_value), FALSE, $source[$parameter_name], '', $parameter_name, '', '=>', TRUE);
              }
            }
          }
          return $section_output;
        };
        $config_output .= SkinRender::renderExpandableChild($section_name, 'Config', $anonfunction, $params_expandable, '. . .');
      }
      // Render the dev-handle field.
      $config_output .= SkinRender::renderSingleEditableChild('Local open function', Config::getDevHandler(), '\krexx::', 'Input', 'localFunction');
      // Render the reset-button which will delete the debug-cookie.
      $config_output .= SkinRender::renderButton('resetbutton', 'Reset local settings', 'resetbutton');
      return $config_output;
    };

    // Now we need to stitch together the content of the ini file
    // as well as it's path.
    if (!is_readable(Config::getPathToIni())) {
      // Project settings are not accessible
      // tell the user, that we are using fallback settings.
      $path = 'Krexx.ini not found, using factory settings';
      // $config = array();
    }
    else {
      $path = 'Current configuration';
    }

    $my_config = Config::getWholeConfiguration();
    $source = $my_config[0];
    $config = $my_config[1];

    $parameter = array($config, $source);

    $config_output = SkinRender::renderExpandableChild($path, Config::getPathToIni(), $anon_function, $parameter, '', '', 'currentSettings', $is_expanded);
    return SkinRender::renderFooter($caller, $config_output, $is_expanded);
  }

  /**
   * Outputs the CSS and JS.
   *
   * @return string
   *   The generated markup.
   */
  public Static Function outputCssAndJs() {
    // Get the css file.
    $css = Toolbox::getFileContents(Config::$krexxdir . 'resources/skins/' . SkinRender::$skin . '/skin.css');
    // Remove whitespace.
    $css = preg_replace('/\s+/', ' ', $css);

    // Adding our DOM tools to the js.
    if (is_readable(Config::$krexxdir . 'resources/jsLibs/kdt.min.js')) {
      $js = Toolbox::getFileContents(Config::$krexxdir . 'resources/jsLibs/kdt.min.js');
    }
    else {
      $js = Toolbox::getFileContents(Config::$krexxdir . 'resources/jsLibs/kdt.js');
    }

    // Krexx.js is comes directly form the template.
    if (is_readable(Config::$krexxdir . 'resources/skins/' . SkinRender::$skin . '/krexx.min.js')) {
      $js .= Toolbox::getFileContents(Config::$krexxdir . 'resources/skins/' . SkinRender::$skin . '/krexx.min.js');
    }
    else {
      $js .= Toolbox::getFileContents(Config::$krexxdir . 'resources/skins/' . SkinRender::$skin . '/krexx.js');
    }


    return SkinRender::renderCssJs($css, $js);
  }

  /**
   * Outputs a backtrace.
   *
   * We need to format this one a little bit different than a
   * normal array.
   *
   * @param array $backtrace
   *   The backtrace.
   *
   * @return string
   *   The rendered backtrace.
   */
  public static function outputBacktrace(array $backtrace) {
    $output = '';

    // Add the sourcecode to our backtrace.
    $backtrace = Toolbox::addSourcecodeToBacktrace($backtrace);

    foreach ($backtrace as $step => $step_data) {
      $name = $step;
      $type = 'Stack Frame';
      $parameter = $step_data;
      $anon_function = function($parameter){
        $output = '';
        // We are handling the following values here:
        // file, line, function, object, type, args, sourcecode.
        $step_data = $parameter;
        // File.
        if (isset($step_data['file'])) {
          $output .= SkinRender::renderSingleChild($step_data['file'], 'File', $step_data['file'], 'string ' . strlen($step_data['file']));
        }
        // Line.
        if (isset($step_data['line'])) {
          $output .= SkinRender::renderSingleChild($step_data['line'], 'Line no.', $step_data['line'], 'integer');
        }
        // Sourcecode, is escaped by now.
        if (isset($step_data['sourcecode'])) {
          $output .= SkinRender::renderSingleChild($step_data['sourcecode'], 'Sourcecode', '. . .', 'PHP');
        }
        // Function.
        if (isset($step_data['function'])) {
          $output .= SkinRender::renderSingleChild($step_data['function'], 'Last called function', $step_data['function'], 'string ' . strlen($step_data['function']));
        }
        // Object.
        if (isset($step_data['object'])) {
          $output .= Analysis\Objects::analyseObject($step_data['object'], 'Calling object');
        }
        // Type.
        if (isset($step_data['type'])) {
          $output .= SkinRender::renderSingleChild($step_data['type'], 'Call type', $step_data['type'], 'string ' . strlen($step_data['type']));
        }
        // Args.
        if (isset($step_data['args'])) {
          $output .= Analysis\Variables::analyseArray($step_data['args'], 'Arguments from the call');
        }

        return $output;
      };
      $output .= SkinRender::renderExpandableChild($name, $type, $anon_function, $parameter);
    }

    return $output;
  }

}