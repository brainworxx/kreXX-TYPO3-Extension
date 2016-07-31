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

namespace Brainworxx\Krexx\Config;

use Brainworxx\Krexx\Framework\Toolbox;
use Brainworxx\Krexx\View\Help;
use Brainworxx\Krexx\View\Messages;

/**
 * Toolbox methods for the configuration.
 *
 * @package Brainworxx\Krexx\Config
 */
class Tools extends Fallback
{
    /**
     * Evaluate a single setting from the cookies or the ini file.
     *
     * @param string $group
     *   The group value in the ini.
     * @param string $name
     *   The name of the setting.
     * @param string $value
     *   The value to evaluate.
     *
     * @return bool
     *   If it was evaluated.
     */
    public static function evaluateSetting($group, $name, $value)
    {
        static $evaluated = array();

        if ($group == 'feEditing') {
            // Logging options can never be changed in the frontend.
            // The debug methods will also not be editable.
            if (in_array($name, self::$feConfigNoEdit)) {
                return false;
            } else {
                return true;
            }
        }

        if (!isset($evaluated[$name])) {
            // We must evaluate it.
            $result = false;
            switch ($name) {
                case 'analyseMethodsAtall':
                    // We expect a bool.
                    $result = self::evalBool($value);
                    if (!$result) {
                        Messages::addMessage(Help::getHelp('configErrorMethods'));
                        Messages::addKey('methods.analyseMethodsAtall.error');
                    }
                    break;

                case 'analyseProtectedMethods':
                    // We expect a bool.
                    $result = self::evalBool($value);
                    if (!$result) {
                        Messages::addMessage(Help::getHelp('configErrorMethodsProtected'));
                        Messages::addKey('methods.analyseProtectedMethods.error');
                    }
                    break;

                case 'analysePrivateMethods':
                    // We expect a bool.
                    $result = self::evalBool($value);
                    if (!$result) {
                        Messages::addMessage(Help::getHelp('configErrorMethodsPrivate'));
                        Messages::addKey('methods.analysePrivateMethods.error');
                    }
                    break;

                case 'analyseProtected':
                    // We expect a bool.
                    $result = self::evalBool($value);
                    if (!$result) {
                        Messages::addMessage(Help::getHelp('configErrorPropertiesProtected'));
                        Messages::addKey('properties.analyseProtected.error');
                    }
                    break;

                case 'analysePrivate':
                    // We expect a bool.
                    $result = self::evalBool($value);
                    if (!$result) {
                        Messages::addMessage(Help::getHelp('configErrorPropertiesPrivate'));
                        Messages::addKey('properties.analysePrivate.error');
                    }
                    break;

                case 'analyseConstants':
                    // We expect a bool.
                    $result = self::evalBool($value);
                    if (!$result) {
                        Messages::addMessage(Help::getHelp('configErrorPropertiesConstants'));
                        Messages::addKey('properties.analyseConstants.error');
                    }
                    break;


                case 'analyseTraversable':
                    // We expect a bool.
                    $result = self::evalBool($value);
                    if (!$result) {
                        Messages::addMessage(Help::getHelp('configErrorTraversable'));
                        Messages::addKey('properties.analyseTraversable.error');
                    }
                    break;

                case 'debugMethods':
                    // String that can get exploded, separated by a comma,
                    // might as well be a single function.
                    // We are not going to check this one.
                    $result = true;
                    break;

                case 'level':
                    // We expect an integer.
                    $result = self::evalInt($value);
                    if (!$result) {
                        Messages::addMessage(Help::getHelp('configErrorLevel'));
                        Messages::addKey('runtime.level.error');
                    }
                    break;

                case 'maxCall':
                    // We expect an integer.
                    $result = self::evalInt($value);
                    if (!$result) {
                        Messages::addMessage(Help::getHelp('configErrorMaxCall'));
                        Messages::addKey('runtime.maxCall.error');
                    }
                    break;

                case 'disabled':
                    // We expect a bool.
                    $result = self::evalBool($value);
                    if (!$result) {
                        Messages::addMessage(Help::getHelp('configErrorDisabled'));
                        Messages::addKey('runtime.disabled.error');
                    }
                    break;

                case 'detectAjax':
                    // We expect a bool.
                    $result = self::evalBool($value);
                    if (!$result) {
                        Messages::addMessage(Help::getHelp('configErrorDetectAjax'));
                        Messages::addKey('runtime.detectAjax.error');
                    }
                    break;

                case 'destination':
                    // We expect 'frontend' or 'file'
                    if ($value == 'frontend' || $value == 'file') {
                        $result = true;
                    }
                    if (!$result) {
                        Messages::addMessage(Help::getHelp('configErrorDestination'));
                        Messages::addKey('output.destination.error');
                    }
                    break;

                case 'maxfiles':
                    // We expect an integer.
                    $result = self::evalInt($value);
                    if (!$result) {
                        Messages::addMessage(Help::getHelp('configErrorMaxfiles'));
                        Messages::addKey('output.maxfiles.error');
                    }
                    break;

                case 'folder':
                    // Directory with write access.
                    // We also need to check, if the folder is properly protected.
                    $isWritable = is_writable(self::$krexxdir . $value);
                    $isProtected = Toolbox::isFolderProtected(self::$krexxdir . $value);
                    if ($isWritable && $isProtected) {
                        $result = true;
                    }
                    if (!$isWritable) {
                        Messages::addMessage(Help::getHelp('configErrorFolderWritable'));
                        Messages::addKey('output.folder.error.writable');
                    }
                    if (!$isProtected) {
                        Messages::addMessage(Help::getHelp('configErrorFolderProtection'));
                        Messages::addKey('output.folder.error.protected');
                    }
                    break;

                case 'skin':
                    // We check the directory and one of the files for readability.
                    if (is_readable(self::$krexxdir . 'resources/skins/' . $value . '/header.html')) {
                        $result = true;
                    }
                    if (!$result) {
                        Messages::addMessage(Help::getHelp('configErrorSkin'));
                        Messages::addKey('output.skin.error');
                    }
                    break;

                case 'Local open function':
                    // The Developer handle,
                    // we are not going to check this one, could be anything you can type.
                    $result = true;
                    break;

                case 'traceFatals':
                    // We expect a bool.
                    $result = self::evalBool($value);
                    if (!$result) {
                        Messages::addMessage(Help::getHelp('configErrorTraceFatals'));
                        Messages::addKey('errorHandling.traceFatals.error');
                    }
                    break;

                case 'traceWarnings':
                    // We expect a bool.
                    $result = self::evalBool($value);
                    if (!$result) {
                        Messages::addMessage(Help::getHelp('configErrorTraceWarnings'));
                        Messages::addKey('errorHandling.traceWarnings.error');
                    }
                    break;

                case 'traceNotices':
                    // We expect a bool.
                    $result = self::evalBool($value);
                    if (!$result) {
                        Messages::addMessage(Help::getHelp('configErrorTraceNotices'));
                        Messages::addKey('errorHandling.traceNotices.error');
                    }
                    break;

                case 'registerAutomatically':
                    // We expect a bool.
                    $result = self::evalBool($value);
                    if (!$result) {
                        Messages::addMessage(Help::getHelp('configErrorRegisterAuto'));
                        Messages::addKey('backtraceAndError.registerAutomatically.error');
                    }
                    // We also expect the php version to be lower than 7.
                    if ($result) {
                        $result = self::evalPhp();
                        if (!$result) {
                            Messages::addMessage(Help::getHelp('configErrorPhp7'));
                            Messages::addKey('backtraceAndError.registerAutomatically.php7');
                        }
                    }
                    break;

                case 'backtraceAnalysis':
                    // We expect "normal" or "deep"
                    if ($value == 'normal' || $value == 'deep') {
                        $result = true;
                    }
                    if (!$result) {
                        Messages::addMessage(Help::getHelp('configErrorBacktraceAnalysis'));
                        Messages::addKey('backtraceAndError.backtraceAnalysis.error');
                    }
                    break;

                case 'memoryLeft':
                    // We expect an integer.
                    $result = self::evalInt($value);
                    if (!$result) {
                        Messages::addMessage(Help::getHelp('configErrorMemory'));
                        Messages::addKey('runtime.memoryLeft.error');
                    }
                    break;

                case 'maxRuntime':
                    // We expect an integer not greater than the max runtime of the
                    // server.
                    $result = self::evalInt($value);
                    if (!$result) {
                        Messages::addMessage(Help::getHelp('configErrorMaxRuntime'));
                        Messages::addKey('runtime.maxRuntime.error');
                    } else {
                        // OK, we got an int, now to see if it is smaller than the
                        // configured max runtime.
                        $maxTime = (int)ini_get('max_execution_time');
                        $value = (int)$value;
                        if ($maxTime > 0 && $maxTime < $value) {
                            // Too big!
                            // @todo Get the string via Help::getHelp();
                            Messages::addMessage(
                                'Wrong configuration for: "runtime => maxRuntime"! Maximum for this server is: ' .
                                $maxTime .
                                ' The configured setting was not applied!'
                            );
                            Messages::addKey('runtime.maxRuntime.error.maximum', array($maxTime));
                            $result = false;
                        }
                    }
                    break;

                default:
                    // Unknown settings,
                    // return false, just in case.
                    break;
            }
            $evaluated[$name] = $result;
        }
        return $evaluated[$name];
    }

    /**
     * Get the config of the frontend config form from the file.
     *
     * @param string $parameterName
     *   The parameter you want to render.
     *
     * @return array
     *   The configuration (is it editable, a dropdown, a textfield, ...)
     */
    public static function getFeConfigFromFile($parameterName)
    {
        static $config = array();

        // Not loaded?
        if (!isset($config[$parameterName])) {
            // Get the human readable stuff from the ini file.
            $value = self::getConfigFromFile('feEditing', $parameterName);
            // Is it set?
            if (!is_null($value)) {
                // We need to translate it to a "real" setting.
                // Get the html control name.
                switch ($parameterName) {
                    case 'folder':
                        $type = 'Input';
                        break;

                    case 'maxfiles':
                        $type = 'Input';
                        break;

                    default:
                        // Nothing special, we get our value from the config class.
                        $type = self::$feConfigFallback[$parameterName]['type'];
                }
                // Stitch together the setting.
                switch ($value) {
                    case 'none':
                        $type = 'None';
                        $editable = 'false';
                        break;

                    case 'display':
                        $editable = 'false';
                        break;

                    case 'full':
                        $editable = 'true';
                        break;

                    default:
                        // Unknown setting.
                        // Fallback to no display, just in case.
                        $type = 'None';
                        $editable = 'false';
                        break;
                }
                $result = array(
                    'type' => $type,
                    'editable' => $editable,
                );
                // Remember the setting.
                $config[$parameterName] = $result;
            }
        }
        if (isset($config[$parameterName])) {
            return $config[$parameterName];
        }
        // Still here?
        return null;
    }

    /**
     * Evaluates a string of 'true' or 'false'.
     *
     * @param string $value
     *   The string we want to evaluate.
     *
     * @return bool
     *   Whether it does evaluate or not.
     */
    protected static function evalBool($value)
    {
        if ($value === 'true' || $value === 'false') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if the php veriosn is lower then 7.0.0.
     *
     * @return bool
     *   Whether it does evaluate or not.
     */
    protected static function evalPhp()
    {
        if (version_compare(phpversion(), '7.0.0', '>=')) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Evaluates a string of integer.
     *
     * It must be greater than 0 and smaller than 101.
     *
     * @param string $value
     *   The string we want to evaluate.
     *
     * @return bool
     *   Whether it does evaluate or not.
     */
    protected static function evalInt($value)
    {
        $value = (int)$value;
        if ($value > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * We merge recursively two arrays.
     *
     * We keep the keys and overwrite the original values
     * of the $oldArray.
     *
     * @param array $oldArray
     *   The array we want to change.
     * @param array $newArray
     *   The new values for the $oldArray.
     */
    protected static function arrayMerge(array &$oldArray, array &$newArray)
    {
        foreach ($newArray as $key => $value) {
            if (!isset($oldArray[$key])) {
                // We simply add it.
                $oldArray[$key] = $value;
            } else {
                // We have already a value.
                if (is_array($value)) {
                    // Add our array recursively.
                    self::arrayMerge($oldArray[$key], $value);
                } else {
                    // It's not an array, we simply overwrite the value.
                    $oldArray[$key] = $value;
                }
            }
        }
    }

    /**
     * Returns settings from the ini file.
     *
     * @param string $group
     *   The group name inside of the ini.
     * @param string $name
     *   The name of the setting.
     *
     * @return string
     *   The value from the file.
     */
    public static function getConfigFromFile($group, $name)
    {
        static $config = array();

        // Not loaded?
        if (empty($config)) {
            $config = (array)parse_ini_string(Toolbox::getFileContents(Config::getPathToIni()), true);
        }

        // Do we have a value in the ini?
        if (isset($config[$group][$name]) && self::evaluateSetting($group, $name, $config[$group][$name])) {
            return $config[$group][$name];
        }
        return null;
    }

    /**
     * Returns settings from the local cookies.
     *
     * @param string $group
     *   The name of the group inside the cookie.
     * @param string $name
     *   The name of the value.
     *
     * @return string|null
     *   The value.
     */
    protected static function getConfigFromCookies($group, $name)
    {
        static $config = array();

        // Not loaded?
        if (empty($config)) {
            // We have local settings.
            if (isset($_COOKIE['KrexxDebugSettings'])) {
                $setting = json_decode($_COOKIE['KrexxDebugSettings'], true);
            }
            if (isset($setting) && is_array($setting)) {
                $config = $setting;
            }
        }

        $paramConfig = FeConfig::getFeConfig($name);
        if ($paramConfig[0] === false) {
            // We act as if we have not found the value. Configurations that are
            // not editable on the frontend will be ignored!
            return null;
        }
        // Do we have a value in the cookies?
        if (isset($config[$name]) && self::evaluateSetting($group, $name, $config[$name])) {
            // We escape them, just in case.
            $value = htmlspecialchars($config[$name]);

            return $value;
        }
        // Still here?
        return null;
    }
}
