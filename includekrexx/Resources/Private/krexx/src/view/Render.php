<?php
/**
 * @file
 *   Render functions for kreXX
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

namespace Brainworxx\Krexx\View;

use Brainworxx\Krexx\Analysis;
use Brainworxx\Krexx\Framework;

/**
 * This class hosts the internal rendering functions.
 *
 * @package Krexx
 */
class Render extends Help {

  /**
   * Counts how often kreXX was called.
   *
   * @var int
   */
  public static $KrexxCount = 0;

  /**
   * Name of the skin currently in use.
   *
   * Gets set as soon as the css is being loaded.
   *
   * @var string
   */
  public static $skin;

  /**
   * Renders a "single child", containing a single not expandable value.
   *
   * Depending on how many characters
   * are in there, it may be toggelable.
   *
   * @param string $data
   *   The initial data rendered.
   * @param string $name
   *   The Name, what we render here.
   * @param string $normal
   *   The normal content. Content using linebreaks should get
   *   rendered into $extra.
   * @param bool $extra
   *   If set, the data text will be rendered inside the yellow square.
   * @param string $type
   *   The type of the analysed variable, in a string.
   * @param string $strlen
   *   The length of the string. In this function, the string which gets
   *   displayed is already not just escaped, but completely encoded. We need
   *   the value from the original string, which we get from the method, which
   *   calls this one.
   * @param string $help_id
   *   The id of the help text we want to display here.
   * @param string $connector1
   *   The connector1 type to the parent class / array.
   * @param string $connector2
   *   The connector2 type to the parent class / array.
   *
   * @return string
   *   The generated markup from the template files.
   */
  Public static function renderSingleChild($data, $name = '', $normal = '', $extra = FALSE, $type = '', $strlen = '', $help_id = '', $connector1 = '', $connector2 = '') {
    // This one is a little bit more complicated than the others,
    // because it assembles some partials and stitches them together.
    $template = self::getTemplateFileContent('singleChild');
    $part_expand = '';
    $part_callable = '';
    $part_extra = '';
    if ($extra) {
      // We have a lot of text, so we render this one expandable (yellow box).
      $part_expand = self::getTemplateFileContent('singleChildExpand');
    }
    if (is_callable($data)) {
      // Add callable partial.
      $part_callable = self::getTemplateFileContent('singleChildCallable');
    }
    if ($extra) {
      // Add the yellow box for large output text.
      $part_extra = self::getTemplateFileContent('singleChildExtra');
    }
    // Stitching the classes together, depending on the types.
    $type_array = explode(' ', $type);
    $type_classes = '';
    foreach ($type_array as $type_class) {
      $type_class = 'k' . $type_class;
      $type_classes .= $type_class .  ' ';
    }

    // Stitching it together.
    $template = str_replace('{expand}', $part_expand, $template);
    $template = str_replace('{callable}', $part_callable, $template);
    $template = str_replace('{extra}', $part_extra, $template);
    $template = str_replace('{name}', $name, $template);
    $template = str_replace('{type}', $type, $template);
    $template = str_replace('{type-classes}', $type_classes, $template);
    $template = str_replace('{strlen}', $strlen, $template);
    $template = str_replace('{normal}', $normal, $template);
    $template = str_replace('{data}', $data, $template);
    $template = str_replace('{help}', self::renderHelp($help_id), $template);
    $template = str_replace('{connector1}', self::renderConnector($connector1), $template);
    return str_replace('{connector2}', self::renderConnector($connector2), $template);
  }

  /**
   * Render a block of a detected recursion.
   *
   * If the recursion is an object, a click should jump to the original
   * analysis data.
   *
   * @param string $name
   *   We might want to tell the user how to actually access it.
   * @param string $value
   *   We might want to tell the user what this actually is.
   * @param string $dom_id
   *   The id of the analysis data, a click on the recursion should jump to it.
   * @param string $connector1
   *   The connector1 type to the parent class / array.
   * @param string $connector2
   *   The connector2 type to the parent class / array.
   *
   * @return string
   *   The generated markup from the template files.
   */
  Public Static Function renderRecursion($name = '', $value = '', $dom_id = '', $connector1 = '', $connector2 = '') {
    $template = self::getTemplateFileContent('recursion');
    // Replace our stuff in the partial.
    $template = str_replace('{name}', $name, $template);
    $template = str_replace('{domId}', $dom_id, $template);
    $template = str_replace('{value}', $value, $template);
    $template = str_replace('{connector1}', self::renderConnector($connector1), $template);
    return str_replace('{connector2}', self::renderConnector($connector2), $template);
  }

  /**
   * Renders the kreXX header.
   *
   * @param string $doctype
   *   The doctype from the configuration.
   * @param string $headline
   *   The headline, what is actually analysed.
   * @param string $css_js
   *   The CSS and JS in a string.
   *
   * @return string
   *   The generated markup from the template files.
   */
  Public static function renderHeader($doctype, $headline, $css_js) {
    $template = self::getTemplateFileContent('header');
    // Replace our stuff in the partial.
    $template = str_replace('{version}', Framework\Config::$version, $template);
    $template = str_replace('{doctype}', $doctype, $template);
    $template = str_replace('{KrexxCount}', self::$KrexxCount, $template);
    $template = str_replace('{headline}', $headline, $template);
    $template = str_replace('{cssJs}', $css_js, $template);
    $template = str_replace('{KrexxId}', Analysis\Hive::getMarker(), $template);
    $template = str_replace('{search}', self::renderSearch(), $template);

    return $template;
  }

  /**
   * Renders the search button and the search menu.
   *
   * @return string
   *   The generated markup from the template files.
   */
  public static function renderSearch() {
    $template = self::getTemplateFileContent('search');
    $template = str_replace('{KrexxId}', Analysis\Hive::getMarker(), $template);
    return $template;
  }

  /**
   * Renders the kreXX footer.
   *
   * @param array $caller
   *   The caller of kreXX.
   * @param string $config_output
   *   The pregenerated configuration markup.
   *
   * @return string
   *   The generated markup from the template files.
   */
  Public static function renderFooter($caller, $config_output) {
    $template = self::getTemplateFileContent('footer');
    // Replace our stuff in the partial.
    if (!isset($caller['file'])) {
      // When we have no caller, we will not render it.
      $template = str_replace('{caller}', '', $template);
    }
    else {
      $template = str_replace('{caller}', self::renderCaller($caller['file'], $caller['line']), $template);
    }
    $template = str_replace('{configInfo}', $config_output, $template);
    return $template;
  }

  /**
   * Renders a nest with a anonymous function in the middle.
   *
   * @param \Closure $anon_function
   *   The anonymous function generates the raw output which is rendered
   *   into the nest.
   * @param mixed $parameter
   *   The parameters for the anonymous function.
   * @param string $dom_id
   *   The dom_id in the markup, in case we have a recursion, so we can jump
   *   directly to the first analysis result.
   * @param bool $is_expanded
   *   The only expanded nest is the settings menu, when we render only the
   *   settings menu.
   *
   * @return string
   *   The generated markup from the template files.
   */
  Public static function renderNest(\Closure $anon_function, &$parameter, $dom_id = '', $is_expanded = FALSE) {
    $template = self::getTemplateFileContent('nest');
    // Replace our stuff in the partial.
    if (strlen($dom_id)) {
      $dom_id = 'id="' . $dom_id . '"';
    }
    $template = str_replace('{domId}', $dom_id, $template);
    // Are we expanding this one?
    if ($is_expanded) {
      $style = '';
    }
    else {
      $style = 'khidden';
    }
    $template = str_replace('{style}', $style, $template);
    return str_replace('{mainfunction}', $anon_function($parameter), $template);
  }

  /**
   * Simply outputs the css and js stuff.
   *
   * @param string $css
   *   The CSS, rendered into the template.
   * @param string $js
   *   The JS, rendered into the template.
   *
   * @return string
   *   The generated markup from the template files.
   */
  Public static function renderCssJs($css, $js) {
    $template = self::getTemplateFileContent('cssJs');
    // Replace our stuff in the partial.
    $template = str_replace('{css}', $css, $template);
    $template = str_replace('{js}', $js, $template);
    return $template;
  }

  /**
   * Renders a expandable child with a callback in the middle.
   *
   * @param string $name
   *   Replacement for the {name} in the template file.
   * @param string $type
   *   Replacement for the {type} in the template file.
   * @param \Closure $anon_function
   *   The anonymous function generates the raw output which is rendered.
   * @param mixed $parameter
   *   The parameters for the anonymous function.
   * @param string $additional
   *   Replacement for the {additional} in the template file.
   * @param string $dom_id
   *   The DOM id in the markup, in case we need to jup to this analysis result.
   * @param string $help_id
   *   The help id for this output, if available.
   * @param bool $is_expanded
   *   Is this one expanded from the beginning?
   *   TRUE when we render the settings menu only.
   * @param string $connector1
   *   The connector1 type to the parent class / array.
   * @param string $connector2
   *   The connector2 type to the parent class / array.
   *
   * @return string
   *   The generated markup from the template files.
   */
  Public static function renderExpandableChild($name, $type, \Closure $anon_function, &$parameter, $additional = '', $dom_id = '', $help_id = '', $is_expanded = FALSE, $connector1 = '', $connector2 = '') {
    // Check for emergency break.
    if (!Analysis\Internals::checkEmergencyBreak()) {
      // Normally, this should not show up, because the Chunks class will not
      // output anything, except a JS alert.
      Messages::addMessage("Emergency break for large output during rendering process.\n\nYou should try to switch to file output.");
      return '';
    }


    if ($name == '' && $type == '') {
      // Without a Name or Type I only display the Child with a Node.
      $template = self::getTemplateFileContent('expandableChildSimple');
      // Replace our stuff in the partial.
      return str_replace('{mainfunction}', Framework\Chunks::chunkMe($anon_function($parameter)), $template);
    }
    else {
      // We need to render this one normally.
      $template = self::getTemplateFileContent('expandableChildNormal');
      // Replace our stuff in the partial.
      $template = str_replace('{name}', $name, $template);
      $template = str_replace('{type}', $type, $template);

      // Explode the type to get the class names right.
      $types = explode(' ', $type);
      $css_type = '';
      foreach ($types as $type) {
        $css_type .= ' k' . $type;
      }
      $template = str_replace('{ktype}', $css_type, $template);

      $template = str_replace('{additional}', $additional, $template);
      $template = str_replace('{help}', self::renderHelp($help_id), $template);
      $template = str_replace('{connector1}', self::renderConnector($connector1), $template);
      $template = str_replace('{connector2}', self::renderConnector($connector2), $template);

      // Is it expanded?
      if ($is_expanded) {
        $template = str_replace('{isExpanded}', 'opened', $template);
      }
      else {
        $template = str_replace('{isExpanded}', '', $template);
      }
      return str_replace('{nest}', Framework\Chunks::chunkMe(self::renderNest($anon_function, $parameter, $dom_id, $is_expanded)), $template);
    }
  }

  /**
   * Loads a template file from the skin folder.
   *
   * @param string $what
   *   Filename in the skin folder without the ".html" at the end.
   *
   * @return string
   *   The template file, without whitespaces.
   */
  protected static function getTemplateFileContent($what) {
    static $file_cache = array();
    if (!isset($file_cache[$what])) {
      $file_cache[$what] = preg_replace('/\s+/', ' ', Framework\Toolbox::getFileContents(Framework\Config::$krexxdir . 'resources/skins/' . self::$skin . '/' . $what . '.html'));
    }
    return $file_cache[$what];
  }

  /**
   * Renders a simple editable child node.
   *
   * @param string $name
   *   The Name, what we render here.
   * @param string $normal
   *   The normal content. Content using linebreaks should get rendered
   *   into $extra.
   * @param string $source
   *   Source of the setting.
   * @param string $input_type
   *   Currently we have a true/false dropdown and a text input.
   *   Values can be 'text' or 'dropdown'.
   * @param string $help_id
   *   The help id for this output, if available.
   *
   * @return string
   *   The generated markup from the template files.
   */
  Public static function renderSingleEditableChild($name, $normal, $source, $input_type, $help_id = '') {
    $template = self::getTemplateFileContent('singleEditableChild');
    $element = self::getTemplateFileContent('single' . $input_type);

    $element = str_replace('{name}', $name, $element);
    $element = str_replace('{value}', $normal, $element);

    // For dropdown elements, we need to render the options.
    if ($input_type == 'Select') {
      $option = self::getTemplateFileContent('single' . $input_type . 'Options');

      // Here we store what the list of possible values.
      switch ($name) {
        case "destination":
          // Frontend or file.
          $value_list = array('frontend', 'file');
          break;

        case "backtraceAnalysis":
          // Norm al or deep analysis.
          $value_list = array('normal', 'deep');
          break;

        case "skin":
          // Get a list of all skin folders.
          $value_list = self::getSkinList();
          break;

        default:
          // true/false
          $value_list = array('true', 'false');
          break;

      }

      // Paint it.
      $options = '';
      foreach ($value_list as $value) {
        if ($value == $normal) {
          // This one is selected.
          $selected = 'selected="selected"';
        }
        else {
          $selected = '';
        }
        $options .= str_replace(array(
            '{text}',
            '{value}',
            '{selected}',
        ), array(
          $value,
          $value,
          $selected,
        ), $option);
      }
      // Now we replace the options in the output.
      $element = str_replace('{options}', $options, $element);
    }

    $template = str_replace('{name}', $name, $template);
    $template = str_replace('{source}', $source, $template);
    $template = str_replace('{normal}', $element, $template);
    $template = str_replace('{type}', 'editable', $template);
    $template = str_replace('{help}', self::renderHelp($help_id), $template);

    return $template;
  }

  /**
   * Renders a simple button.
   *
   * @param string $name
   *   The classname of the button, used to assign js functions to it.
   * @param string $text
   *   The text displayed on the button.
   * @param string $help_id
   *   The ID of the help text.
   *
   * @return string
   *   The generated markup from the template files.
   */
  Public static function renderButton($name = '', $text = '', $help_id = '') {
    $template = self::getTemplateFileContent('singleButton');
    $template = str_replace('{help}', self::renderHelp($help_id), $template);

    $template = str_replace('{text}', $text, $template);
    return str_replace('{class}', $name, $template);
  }

  /**
   * Renders the second part of the fatal error handler.
   *
   * @param string $type
   *   The type of the error (should always be fatal).
   * @param string $errstr
   *   The string from the error.
   * @param string $errfile
   *   The file where the error occurred.
   * @param string $errline
   *   The line number where the error occurred.
   * @param string $source
   *   Part of the source code, where the error occurred.
   *
   * @return string
   *   The template file, with all markers replaced.
   */
  public static function renderFatalMain($type, $errstr, $errfile, $errline, $source) {
    $template = self::getTemplateFileContent('fatalMain');

    // Insert our values.
    $template = str_replace('{type}', $type, $template);
    $template = str_replace('{errstr}', $errstr, $template);
    $template = str_replace('{file}', $errfile, $template);
    $template = str_replace('{source}', $source, $template);
    $template = str_replace('{KrexxCount}', self::$KrexxCount, $template);

    return str_replace('{line}', $errline, $template);
  }

  /**
   * Renders the header part of the fatal error handler.
   *
   * @param string $css_js
   *   The css and js from the template.
   * @param string $doctype
   *   The configured doctype.
   *
   * @return string
   *   The templatefile, with all markers replaced.
   */
  public static function renderFatalHeader($css_js, $doctype) {
    $template = self::getTemplateFileContent('fatalHeader');

    // Insert our values.
    $template = str_replace('{cssJs}', $css_js, $template);
    $template = str_replace('{version}', Framework\Config::$version, $template);
    $template = str_replace('{doctype}', $doctype, $template);
    $template = str_replace('{search}', self::renderSearch(), $template);

    return str_replace('{KrexxId}', Analysis\Hive::getMarker(), $template);
  }

  /**
   * Renders all internal messages.
   *
   * @param array $messages
   *   The current messages.
   *
   * @return string
   *   The generates html output
   */
  public static function renderMessages(array $messages) {
    $template = self::getTemplateFileContent('message');
    $result = '';

    foreach ($messages as $message) {
      $temp = str_replace('{class}', $message['class'], $template);
      $result .= str_replace('{message}', $message['message'], $temp);
    }

    return $result;
  }

  /**
   * Renders the footer part, where we display from where krexx was called.
   *
   * @param string $file
   *   The file from where krexx was called.
   * @param string $line
   *   The line number from where krexx was called.
   *
   * @return string
   *   The generated markup from the template files.
   */
  protected static function renderCaller($file, $line) {
    $template = self::getTemplateFileContent('caller');
    $template = str_replace('{callerFile}', $file, $template);
    return str_replace('{callerLine}', $line, $template);
  }

  /**
   * Renders the helptext.
   *
   * @param string $help_id
   *   The ID of the helptext.
   *
   * @see \Krexx\Help
   *
   * @return string
   *   The generated markup from the template files.
   */
  protected static function renderHelp($help_id) {
    $help_text = self::getHelp($help_id);
    if ($help_text != '') {
      return str_replace('{help}', $help_text, self::getTemplateFileContent('help'));
    }
    else {
      return '';
    }
  }

  /**
   * Gets a list of all available skins for the frontend config.
   *
   * @return array
   *   An array with the skinnames.
   */
  public static function getSkinList() {
    // Static cache to make it a little bit faster.
    static $list = array();

    if (count($list) == 0) {
      // Get the list.
      $list = array_filter(glob(Framework\Config::$krexxdir . 'resources/skins/*'), 'is_dir');
      // Now we need to filter it, we only want the names, not the full path.
      foreach ($list as &$path) {
        $path = str_replace(Framework\Config::$krexxdir . 'resources/skins/', '', $path);
      }
    }

    return $list;
  }

  /**
   * Renders the line of the sourcecode, from where the backtrace is coming.
   *
   * @param string $class_name
   *   The class name where the sourcecode is from.
   * @param string $line_no
   *   The kine number from the file.
   * @param string $source_code
   *   Part of the sourcecode, where the backtrace is coming from.
   *
   * @return string
   *   The generated markup from the template files.
   */
  public static function renderBacktraceSourceLine($class_name, $line_no, $source_code) {
    $template = self::getTemplateFileContent('backtraceSourceLine');
    $template = str_replace('{className}', $class_name, $template);
    $template = str_replace('{lineNo}', $line_no, $template);

    return str_replace('{sourceCode}', $source_code, $template);
  }

  /**
   * Renders the hr.
   *
   * @return string
   *   The generated markup from the template file.
   */
  public static function renderSingeChildHr() {
    return self::getTemplateFileContent('singleChildHr');
  }

  /**
   * Renders the connector between analysis objects, params and results.
   *
   * @param string $connector
   *   The data to be displayed.
   *
   * @return string
   *   The rendered connector.
   */
  public static function renderConnector($connector) {
    if (!empty($connector)) {
      $template = self::getTemplateFileContent('connector');
      return str_replace('{connector}', $connector, $template);
    }
    else {
      return '';
    }

  }
}
