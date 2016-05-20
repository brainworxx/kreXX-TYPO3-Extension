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
use Brainworxx\Krexx\Framework\Toolbox;
use Brainworxx\Krexx\Analysis\Objects\Objects;
use Brainworxx\Krexx\Analysis\Variables;

/**
 * This class hosts the code generation functions.
 *
 * @package Brainworxx\Krexx\View
 */
class Output
{

    public static $headerSend = false;

    /**
     * Simply outputs the Header of kreXX.
     *
     * @param string $headline
     *   The headline, displayed in the header.
     *
     * @return string
     *   The generated markup
     */
    public static function outputHeader($headline)
    {

        // Do we do an output as file?
        if (!self::$headerSend) {
            // Send doctype and css/js only once.
            self::$headerSend = true;
            return SkinRender::renderHeader('<!DOCTYPE html>', $headline, self::outputCssAndJs());
        } else {
            return SkinRender::renderHeader('', $headline, '');
        }
    }

    /**
     * Simply renders the footer and output current settings.
     *
     * @param array $caller
     *   Where was kreXX initially invoked from.
     * @param bool $isExpanded
     *   Are we rendering an expanded footer?
     *   TRUE when we render the settings menu only.
     *
     * @return string
     *   The generated markup.
     */
    public static function outputFooter($caller, $isExpanded = false)
    {
        // Wrap an expandable around to save space.
        $anonFunction = function ($params) {
            $config = $params[0];
            $source = $params[1];
            $configOutput = '';
            foreach ($config as $sectionName => $sectionData) {
                $paramsExpandable = array(
                    $sectionData,
                    $source[$sectionName]
                );

                // Render a whole section.
                $anonFunction = function ($params) {
                    $sectionData = $params[0];
                    $source = $params[1];
                    $sectionOutput = '';
                    foreach ($sectionData as $parameterName => $parameterValue) {
                        // Render the single value.
                        // We need to find out where the value comes from.
                        $config = Config::getFeConfig($parameterName);
                        $editable = $config[0];
                        $type = $config[1];

                        if ($type != 'None') {
                            if ($editable) {
                                $sectionOutput .= SkinRender::renderSingleEditableChild(
                                    $parameterName,
                                    htmlspecialchars($parameterValue),
                                    $source[$parameterName],
                                    $type,
                                    $parameterName
                                );
                            } else {
                                $sectionOutput .= SkinRender::renderSingleChild(
                                    $parameterValue,
                                    $parameterName,
                                    htmlspecialchars($parameterValue),
                                    $source[$parameterName],
                                    $parameterName
                                );
                            }
                        }
                    }
                    return $sectionOutput;
                };
                $configOutput .= SkinRender::renderExpandableChild(
                    $sectionName,
                    'Config',
                    $anonFunction,
                    $paramsExpandable,
                    '. . .'
                );
            }
            // Render the dev-handle field.
            $configOutput .= SkinRender::renderSingleEditableChild(
                'Local open function',
                Config::getDevHandler(),
                '\krexx::',
                'Input',
                'localFunction'
            );
            // Render the reset-button which will delete the debug-cookie.
            $configOutput .= SkinRender::renderButton('resetbutton', 'Reset local settings', 'resetbutton');
            return $configOutput;
        };

        // Now we need to stitch together the content of the ini file
        // as well as it's path.
        if (!is_readable(Config::getPathToIni())) {
            // Project settings are not accessible
            // tell the user, that we are using fallback settings.
            $path = 'Krexx.ini not found, using factory settings';
            // $config = array();
        } else {
            $path = 'Current configuration';
        }

        $wholeConfig = Config::getWholeConfiguration();
        $source = $wholeConfig[0];
        $config = $wholeConfig[1];

        $parameter = array($config, $source);

        $configOutput = SkinRender::renderExpandableChild(
            $path,
            Config::getPathToIni(),
            $anonFunction,
            $parameter,
            '',
            '',
            'currentSettings',
            $isExpanded
        );
        return SkinRender::renderFooter($caller, $configOutput, $isExpanded);
    }

    /**
     * Outputs the CSS and JS.
     *
     * @return string
     *   The generated markup.
     */
    public static function outputCssAndJs()
    {
        // Get the css file.
        $css = Toolbox::getFileContents(Config::$krexxdir . 'resources/skins/' . SkinRender::$skin . '/skin.css');
        // Remove whitespace.
        $css = preg_replace('/\s+/', ' ', $css);

        // Adding our DOM tools to the js.
        if (is_readable(Config::$krexxdir . 'resources/jsLibs/kdt.min.js')) {
            $jsFile = Config::$krexxdir . 'resources/jsLibs/kdt.min.js';
        } else {
            $jsFile = Config::$krexxdir . 'resources/jsLibs/kdt.js';
        }
        $js = Toolbox::getFileContents($jsFile);

        // Krexx.js is comes directly form the template.
        if (is_readable(Config::$krexxdir . 'resources/skins/' . SkinRender::$skin . '/krexx.min.js')) {
            $jsFile = Config::$krexxdir . 'resources/skins/' . SkinRender::$skin . '/krexx.min.js';
        } else {
            $jsFile = Config::$krexxdir . 'resources/skins/' . SkinRender::$skin . '/krexx.js';
        }
        $js .= Toolbox::getFileContents($jsFile);

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
    public static function outputBacktrace(array $backtrace)
    {
        $output = '';

        // Add the sourcecode to our backtrace.
        $backtrace = Toolbox::addSourcecodeToBacktrace($backtrace);

        foreach ($backtrace as $step => $stepData) {
            $name = $step;
            $type = 'Stack Frame';
            $parameter = $stepData;
            $anonFunction = function ($parameter) {
                $output = '';
                // We are handling the following values here:
                // file, line, function, object, type, args, sourcecode.
                $stepData = $parameter;
                // File.
                if (isset($stepData['file'])) {
                    $output .= SkinRender::renderSingleChild(
                        $stepData['file'],
                        'File',
                        $stepData['file'],
                        'string ' . strlen($stepData['file'])
                    );
                }
                // Line.
                if (isset($stepData['line'])) {
                    $output .= SkinRender::renderSingleChild(
                        $stepData['line'],
                        'Line no.',
                        $stepData['line'],
                        'integer'
                    );
                }
                // Sourcecode, is escaped by now.
                if (isset($stepData['sourcecode'])) {
                    $output .= SkinRender::renderSingleChild(
                        $stepData['sourcecode'],
                        'Sourcecode',
                        '. . .',
                        'PHP'
                    );
                }
                // Function.
                if (isset($stepData['function'])) {
                    $output .= SkinRender::renderSingleChild(
                        $stepData['function'],
                        'Last called function',
                        $stepData['function'],
                        'string ' . strlen($stepData['function'])
                    );
                }
                // Object.
                if (isset($stepData['object'])) {
                    $output .= Objects::analyseObject(
                        $stepData['object'],
                        'Calling object'
                    );
                }
                // Type.
                if (isset($stepData['type'])) {
                    $output .= SkinRender::renderSingleChild(
                        $stepData['type'],
                        'Call type',
                        $stepData['type'],
                        'string ' . strlen($stepData['type'])
                    );
                }
                // Args.
                if (isset($stepData['args'])) {
                    $output .= Variables::analyseArray(
                        $stepData['args'],
                        'Arguments from the call'
                    );
                }

                return $output;
            };
            $output .= SkinRender::renderExpandableChild($name, $type, $anonFunction, $parameter);
        }

        return $output;
    }
}
