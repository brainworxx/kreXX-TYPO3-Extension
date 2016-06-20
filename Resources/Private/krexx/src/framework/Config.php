<?php
/**
 * @file
 *   Configuration functions for kreXX
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

namespace Brainworxx\Krexx\Framework;

use Brainworxx\Krexx\View\Help;
use Brainworxx\Krexx\View\Messages;

/**
 * This class hosts the kreXX configuration functions.
 *
 * @package Brainworxx\Krexx\Framework
 */
class Config
{

    /**
     * Is the code generation allowed? We only allow it during a normal analysis.
     *
     * @var bool
     */
    public static $allowCodegen = false;

    /**
     * Stores if kreXX is actually enabled.
     *
     * @var bool
     */
    protected static $isEnabled = true;

    /**
     * Fallback settings, in case there is nothing in the config ini.
     *
     * @var array
     */
    public static $configFallback = array(
        'runtime' => array(
            'disabled' => 'false',
            'detectAjax' => 'true',
            'level' => '5',
            'maxCall' => '10',
            'memoryLeft' => '64',
            'maxRuntime' => '60',
        ),
        'output' => array(
            'skin' => 'smoky-grey',
            'destination' => 'frontend',
            'folder' => 'log',
            'maxfiles' => '10',
        ),
        'properties' => array(
            'analyseProtected' => 'false',
            'analysePrivate' => 'false',
            'analyseConstants' => 'true',
            'analyseTraversable' => 'true',
        ),
        'methods' => array(
            'analyseMethodsAtall' => 'true',
            'analyseProtectedMethods' => 'false',
            'analysePrivateMethods' => 'false',
            'debugMethods' => 'debug,__toArray,toArray,__toString,toString,_getProperties,__debugInfo',
        ),
        'backtraceAndError' => array(
            'registerAutomatically' => 'false',
            'backtraceAnalysis' => 'deep',
        ),
    );

    public static $feConfigFallback = array(
        'analyseMethodsAtall' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'analyseProtectedMethods' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'analysePrivateMethods' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'analyseProtected' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'analysePrivate' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'analyseConstants' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'analyseTraversable' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'debugMethods' => array(
            'type' => 'Input',
            'editable' => 'false',
        ),
        'level' => array(
            'type' => 'Input',
            'editable' => 'true',
        ),
        'maxCall' => array(
            'type' => 'Input',
            'editable' => 'true',
        ),
        'disabled' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'destination' => array(
            'type' => 'Select',
            'editable' => 'false',
        ),
        'maxfiles' => array(
            'type' => 'None',
            'editable' => 'false',
        ),
        'folder' => array(
            'type' => 'None',
            'editable' => 'false',
        ),
        'skin' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'registerAutomatically' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'detectAjax' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'backtraceAnalysis' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'memoryLeft' => array(
            'type' => 'Input',
            'editable' => 'true',
        ),
        'maxRuntime' => array(
            'type' => 'Input',
            'editable' => 'true',
        ),
        'Local open function' => array(
            'type' => 'Input',
            'editable' => 'true',
        ),
    );

    /**
     * The directory where kreXX is stored.
     *
     * @var string
     */
    public static $krexxdir;

    /**
     * Known Problems with debug functions, which will most likely cause a fatal.
     *
     * Used by \Krexx\Objects::pollAllConfiguredDebugMethods() to determine
     * if we might expect problems.
     *
     * @var array
     */
    protected static $debugMethodsBlacklist = array(

        // TYPO3 viewhelpers dislike this function.
        // In the TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper the private
        // $viewHelperNode might not be an object, and trying to render it might
        // cause a fatal error!
        'TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper' => '__toString',
        'ReflectionClass' => '__toString',
        // Deleting all rows from the DB via typo3 reopsitory is NOT a good
        // debug method!
        'RepositoryInterface' => 'removeAll',
        'Tx_Extbase_Persistence_RepositoryInterface' => 'removeAll',
    );

    /**
     * Caching for the local settings.
     *
     * @var array
     */
    protected static $localConfig = array();

    /**
     * Path to the configuration file.
     *
     * @var string
     */
    protected static $pathToIni;

    /**
     * The kreXX version.
     *
     * @var string
     */
    public static $version = '1.4.2 dev';

    /**
     * Get\Set kreXX state: whether it is enabled or disabled.
     *
     * @param bool $state
     *   Optional, to enable or disable kreXX manually.
     *
     * @return bool
     *   Returns whether kreXX is enabled or not.
     */
    public static function isEnabled($state = null)
    {

        // Enable kreXX.
        if (!is_null($state)) {
            self::$isEnabled = $state;
            return self::$isEnabled;
        }

        // Disabled in the ini or in the local settings?
        if (Config::getConfigValue('runtime', 'disabled') == 'true') {
            // self::$isEnabled = FALSE;
            return false;
        }

        // Check for ajax and cli.
        if (Toolbox::isRequestAjaxOrCli()) {
            return false;
        }
        return self::$isEnabled;
    }

    /**
     * Returns values from kreXX's configuration.
     *
     * @param string $group
     *   The group inside the ini of the value that we want to read.
     * @param string $name
     *   The name of the config value.
     *
     * @return string
     *   The value.
     */
    public static function getConfigValue($group, $name)
    {
        // Do some caching.
        if (isset(self::$localConfig[$group][$name])) {
            return self::$localConfig[$group][$name];
        }

        // Do we have a value in the cookies?
        $localSetting = self::getConfigFromCookies($group, $name);
        // Do we have a value in the ini?
        $iniSettings = self::getConfigFromFile($group, $name);
        if (isset($localSetting)) {
            // We must not overwrite a disabled=true with local cookie settings!
            // Otherwise it could get enabled locally,
            // which might be a security issue.
            if (($name == 'disabled' && $localSetting == 'false') ||
                ($name == 'destination' && !is_null($iniSettings))) {
                // Do nothing.
                // We ignore this setting.
            } else {
                self::$localConfig[$group][$name] = $localSetting;
                return $localSetting;
            }
        }


        if (isset($iniSettings)) {
            self::$localConfig[$group][$name] = $iniSettings;
            return $iniSettings;
        }

        // Nothing yet? Give back factory settings.
        self::$localConfig[$group][$name] = self::$configFallback[$group][$name];
        return self::$configFallback[$group][$name];
    }

    /**
     * Here we overwrite the local settings.
     *
     * When we are handling errors and are analysing objects, we should
     * output protected and private variables of a class, outputting as
     * much info as possible.
     *
     * @param array $newSettings
     *   Part of the array we want to overwrite.
     */
    public static function overwriteLocalSettings(array $newSettings)
    {
        self::krexxArrayMerge(self::$localConfig, $newSettings);
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
    protected static function krexxArrayMerge(array &$oldArray, array &$newArray)
    {
        foreach ($newArray as $key => $value) {
            if (!isset($oldArray[$key])) {
                // We simply add it.
                $oldArray[$key] = $value;
            } else {
                // We have already a value.
                if (is_array($value)) {
                    // Add our array recursively.
                    self::krexxArrayMerge($oldArray[$key], $value);
                } else {
                    // It's not an array, we simply overwrite the value.
                    $oldArray[$key] = $value;
                }
            }
        }
    }

    /**
     * Returns the whole configuration as an array.
     *
     * The source of the value (factory, ini or cookie)
     * is also included. We need this one for the display
     * on the frontend.
     * We display here the invalid settings (if we have
     * any,so the user can correct it.
     *
     * @return array
     *   The configuration with the source.
     */
    public static function getWholeConfiguration()
    {
        // We may have some project settings in the ini
        // as well as some in the cookies, but some may be missing.
        $source = array();
        $config = array();
        $cookieConfig = array();

        // Get Settings from the cookies. We do not correct them,
        // so the dev can correct them, in case there are wrong values.
        if (isset($_COOKIE['KrexxDebugSettings'])) {
            $cookieConfig = json_decode($_COOKIE['KrexxDebugSettings'], true);
        }

        // We must remove the cookie settings for which we do not accept
        // any values. They might contain wrong values.
        foreach ($cookieConfig as $name => $data) {
            $paramConfig = self::getFeConfig($name);
            if ($paramConfig[0] === false) {
                // We act as if we have not found the value. Configurations that are
                // not editable on the frontend will be ignored!
                unset($cookieConfig[$name]);
            }
        }

        // Get Settings from the ini file.
        $configini = (array)parse_ini_string(Toolbox::getFileContents(self::getPathToIni()), true);

        foreach (self::$configFallback as $sectionName => $sectionData) {
            foreach ($sectionData as $parameterName => $parameterValue) {
                // Get cookie settings.
                if (isset($cookieConfig[$parameterName])) {
                    // We check them, if they are correct. Normally, we would do this,
                    // when we get the value via self::getConfigFromCookies(), but we
                    // should feedback the dev about the settings.
                    self::evaluateSetting('', $parameterName, $cookieConfig[$parameterName]);
                    $config[$sectionName][$parameterName] = htmlspecialchars($cookieConfig[$parameterName]);
                    $source[$sectionName][$parameterName] = 'local cookie settings';
                } else {
                    // File settings.
                    if (isset($configini[$sectionName][$parameterName])) {
                        $config[$sectionName][$parameterName] = htmlspecialchars(
                            $configini[$sectionName][$parameterName]
                        );
                        $source[$sectionName][$parameterName] = 'Krexx ini settings';
                        continue;
                    } else {
                        // Nothing yet? Return factory settings.
                        $config[$sectionName][$parameterName] = $parameterValue;
                        $source[$sectionName][$parameterName] = 'factory settings';
                    }
                }
            }
        }
        $result = array(
            $source,
            $config,
        );
        return $result;
    }

    /**
     * Returns the developer handle from the cookies.
     *
     * @return string
     *   The Developer handle.
     */
    public static function getDevHandler()
    {
        return self::getConfigFromCookies('deep', 'Local open function');
    }

    /**
     * Gets the path to the ini file.
     *
     * In typo3, it is not a good idea to store the config
     * settings inside the module directory. When an update is
     * triggered, all settings will be lost. So wen need a functionality
     * to point kreXX to another directory for it's config.
     *
     * @return string
     *   The path to the ini file.
     */
    public static function getPathToIni()
    {
        if (!isset(self::$pathToIni)) {
            $configini = (array)parse_ini_string(Toolbox::getFileContents(self::$krexxdir . 'KrexxConfig.ini'), true);
            if (isset($configini['pathtoini']['pathtoini'])) {
                self::$pathToIni = $configini['pathtoini']['pathtoini'];
            } else {
                self::$pathToIni = self::$krexxdir . 'Krexx.ini';
            }
        }
        return self::$pathToIni;
    }

    /**
     * Setter for the path to the ini file.
     *
     * @param string $path
     *   The path to the ini file
     */
    public static function setPathToIni($path)
    {
        self::$pathToIni = $path;
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
            $config = (array)parse_ini_string(Toolbox::getFileContents(self::getPathToIni()), true);
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
    public static function getConfigFromCookies($group, $name)
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

        $paramConfig = self::getFeConfig($name);
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

        // We are not evaluating the group "feEditing". The values there
        // need to be translated to kreXX readable settings. Each value in
        // there is a name of a preset. When the name is not found, we
        // translate this to the preset with the least privileges (no edit
        // and no display).
        if ($group == 'feEditing') {
            // Logging options can never be changed in the frontend.
            // The debug methods will also not be editable.
            switch ($name) {
                case 'destination':
                    $result = false;
                    break;

                case 'folder':
                    $result = false;
                    break;

                case 'maxfiles':
                    $result = false;
                    break;

                case 'debugMethods':
                    $result = false;
                    break;

                default:
                    $result = true;
                    break;
            }
            return $result;
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
     * Get the configuration of the frontend config form.
     *
     * @param string $parameterName
     *   The parameter you want to render.
     *
     * @return array
     *   The configuration (is it editable, a dropdown, a textfield, ...)
     */
    public static function getFeConfig($parameterName)
    {
        static $config = array();

        if (!isset($config[$parameterName])) {
            // Load it from the file.
            $filevalue = self::getFeConfigFromFile($parameterName);
            if (!is_null($filevalue)) {
                $config[$parameterName] = $filevalue;
            }
        }

        // Do we have a value?
        if (isset($config[$parameterName])) {
            $type = $config[$parameterName]['type'];
            $editable = $config[$parameterName]['editable'];
        } else {
            // Fallback to factory settings.
            if (isset(self::$feConfigFallback[$parameterName])) {
                $type = self::$feConfigFallback[$parameterName]['type'];
                $editable = self::$feConfigFallback[$parameterName]['editable'];
            } else {
                // Unknown parameter.
                $type = 'None';
                $editable = 'false';
            }
        }
        if ($editable === 'true') {
            $editable = true;
        } else {
            $editable = false;
        }

        return array($editable, $type);
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
     * Determines if a debug function is blacklisted in s specific class.
     *
     * @param object $data
     *   The class we are analysing.
     * @param string $call
     *   The function name we want to call.
     *
     * @return bool
     *   Whether the function is allowed to be called.
     */
    public static function isAllowedDebugCall($data, $call)
    {

        foreach (self::$debugMethodsBlacklist as $classname => $method) {
            if (is_a($data, $classname) && $call == $method) {
                // We have a winner, this one is blacklisted!
                return false;
            }
        }
        // Nothing found?
        return true;
    }
}
