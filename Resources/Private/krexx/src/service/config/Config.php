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

namespace Brainworxx\Krexx\Service\Config;

/**
 * Access the debug settings here.
 *
 * @package Brainworxx\Krexx\Service\Config
 */
class Config extends Fallback
{
    /**
     * Setter for the enabling from sourcecode.
     *
     * Will only set it to true, if the
     *
     * @param bool $state
     *   Whether it it enabled, or not.
     */
    public function setEnabled($state)
    {
        $this->isEnabled = $state;
    }

    /**
     * Get\Set kreXX state: whether it is enabled or disabled.
     *
     * @return bool
     *   Returns whether kreXX is enabled or not.
     */
    public function getEnabled()
    {
        // Disabled in the ini or in the local settings?
        if ($this->getConfigValue('runtime', 'disabled') === 'true') {
            return false;
        }

        // Check for ajax and cli.
        if ($this->isRequestAjaxOrCli()) {
            return false;
        }

        // We will only return the real value, if there are no other,
        // more important settings.
        return $this->isEnabled;
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
    public function getConfigValue($group, $name)
    {
        // Do some caching.
        if (isset($this->localConfig[$group][$name])) {
            return $this->localConfig[$group][$name];
        }

        // Do we have a value in the cookies?
        $localSetting = $this->getConfigFromCookies($group, $name);
        if (isset($localSetting)) {
            // We must not overwrite a disabled=true with local cookie settings!
            // Otherwise it could get enabled locally, which might be a security
            // issue.
            if (($name === 'disabled' && $localSetting === 'false')) {
                // Do nothing.
                // We ignore this setting.
            } else {
                $this->localConfig[$group][$name] = $localSetting;
                return $localSetting;
            }
        }

        // Do we have a value in the ini?
        $iniSettings = $this->getConfigFromFile($group, $name);
        if (isset($iniSettings)) {
            $this->localConfig[$group][$name] = $iniSettings;
            return $iniSettings;
        }

        // Nothing yet? Give back factory settings.
        $this->localConfig[$group][$name] = $this->configFallback[$group][$name];
        return $this->configFallback[$group][$name];
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
    public function overwriteLocalSettings(array $newSettings)
    {
        $this->arrayMerge($this->localConfig, $newSettings);
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
    public function getWholeConfiguration()
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
            if (!is_array($cookieConfig)) {
                // Looks like we do not have a valid config here.
                $cookieConfig = array();
                $this->storage->messages->addMessage($this->storage->render->getHelp('configErrorLocal'));
            }
        }

        // We must remove the cookie settings for which we do not accept
        // any values. They might contain wrong values.
        foreach ($cookieConfig as $name => $data) {
            $paramConfig = $this->getFeConfig($name);
            if ($paramConfig[0] === false) {
                // We act as if we have not found the value. Configurations that are
                // not editable on the frontend will be ignored!
                unset($cookieConfig[$name]);
            }
        }

        // Get Settings from the ini file.
        $configini = (array)parse_ini_string($this->storage->getFileContents($this->getPathToIni()), true);

        // Overwrite the settings from the fallback.
        foreach ($this->configFallback as $sectionName => $sectionData) {
            foreach ($sectionData as $parameterName => $parameterValue) {
                // Get cookie settings.
                if (isset($cookieConfig[$parameterName])) {
                    // We check them, if they are correct. Normally, we would do this,
                    // when we get the value via self::getConfigFromCookies(), but we
                    // should feedback the dev about the settings.
                    $this->evaluateSetting('', $parameterName, $cookieConfig[$parameterName]);
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
    public function getDevHandler()
    {
        return $this->getConfigFromCookies('deep', 'Local open function');
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
    public function getPathToIni()
    {
        if (!isset($this->pathToIni)) {
            $configini = (array)parse_ini_string(
                $this->storage->getFileContents($this->krexxdir . 'KrexxConfig.ini'),
                true
            );
            if (isset($configini['pathtoini']['pathtoini'])) {
                $this->pathToIni = $configini['pathtoini']['pathtoini'];
            } else {
                $this->pathToIni = $this->krexxdir . 'Krexx.ini';
            }
        }
        return $this->pathToIni;
    }

    /**
     * Setter for the path to the ini file.
     *
     * Useful, if you don't want to save your ini file in the kreXX directory.
     *
     * @param string $path
     *   The path to the ini file
     */
    public function setPathToIni($path)
    {
        $this->pathToIni = $path;
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
    public function isAllowedDebugCall($data, $call)
    {

        foreach ($this->debugMethodsBlacklist as $classname => $method) {
            if (is_a($data, $classname) && $call === $method) {
                // We have a winner, this one is blacklisted!
                return false;
            }
        }
        // Nothing found?
        return true;
    }

    /**
     * Gets a list of all available skins for the frontend config.
     *
     * @return array
     *   An array with the skinnames.
     */
    public function getSkinList()
    {
        // Static cache to make it a little bit faster.
        static $list = array();

        if (empty($list)) {
            // Get the list.
            $list = array_filter(glob($this->krexxdir . 'resources/skins/*'), 'is_dir');
            // Now we need to filter it, we only want the names, not the full path.
            foreach ($list as &$path) {
                $path = str_replace($this->krexxdir . 'resources/skins/', '', $path);
            }
        }

        return $list;
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
    public function getFeConfig($parameterName)
    {
        static $config = array();

        if (!isset($config[$parameterName])) {
            // Load it from the file.
            $filevalue = $this->getFeConfigFromFile($parameterName);
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
            if (isset($this->feConfigFallback[$parameterName])) {
                $type = $this->feConfigFallback[$parameterName]['type'];
                $editable = $this->feConfigFallback[$parameterName]['editable'];
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
    public function evaluateSetting($group, $name, $value)
    {
        static $evaluated = array();

        if ($group === 'feEditing') {
            // Logging options can never be changed in the frontend.
            // The debug methods will also not be editable.
            if (in_array($name, $this->feConfigNoEdit)) {
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
                    $result = $this->evalBool($value);
                    if (!$result) {
                        $this->storage->messages->addMessage(
                            $this->storage->render->getHelp('configErrorMethods')
                        );
                        $this->storage->messages->addKey('methods.analyseMethodsAtall.error');
                    }
                    break;

                case 'analyseProtectedMethods':
                    // We expect a bool.
                    $result = $this->evalBool($value);
                    if (!$result) {
                        $this->storage->messages->addMessage(
                            $this->storage->render->getHelp('configErrorMethodsProtected')
                        );
                        $this->storage->messages->addKey('methods.analyseProtectedMethods.error');
                    }
                    break;

                case 'analysePrivateMethods':
                    // We expect a bool.
                    $result = $this->evalBool($value);
                    if (!$result) {
                        $this->storage->messages->addMessage(
                            $this->storage->render->getHelp('configErrorMethodsPrivate')
                        );
                        $this->storage->messages->addKey('methods.analysePrivateMethods.error');
                    }
                    break;

                case 'analyseProtected':
                    // We expect a bool.
                    $result = $this->evalBool($value);
                    if (!$result) {
                        $this->storage->messages->addMessage(
                            $this->storage->render->getHelp('configErrorPropertiesProtected')
                        );
                        $this->storage->messages->addKey('properties.analyseProtected.error');
                    }
                    break;

                case 'analysePrivate':
                    // We expect a bool.
                    $result = $this->evalBool($value);
                    if (!$result) {
                        $this->storage->messages->addMessage(
                            $this->storage->render->getHelp('configErrorPropertiesPrivate')
                        );
                        $this->storage->messages->addKey('properties.analysePrivate.error');
                    }
                    break;

                case 'analyseConstants':
                    // We expect a bool.
                    $result = $this->evalBool($value);
                    if (!$result) {
                        $this->storage->messages->addMessage(
                            $this->storage->render->getHelp('configErrorPropertiesConstants')
                        );
                        $this->storage->messages->addKey('properties.analyseConstants.error');
                    }
                    break;


                case 'analyseTraversable':
                    // We expect a bool.
                    $result = $this->evalBool($value);
                    if (!$result) {
                        $this->storage->messages->addMessage(
                            $this->storage->render->getHelp('configErrorTraversable')
                        );
                        $this->storage->messages->addKey('properties.analyseTraversable.error');
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
                    $result = $this->evalInt($value);
                    if (!$result) {
                        $this->storage->messages->addMessage(
                            $this->storage->render->getHelp('configErrorLevel')
                        );
                        $this->storage->messages->addKey('runtime.level.error');
                    }
                    break;

                case 'maxCall':
                    // We expect an integer.
                    $result = $this->evalInt($value);
                    if (!$result) {
                        $this->storage->messages->addMessage(
                            $this->storage->render->getHelp('configErrorMaxCall')
                        );
                        $this->storage->messages->addKey('runtime.maxCall.error');
                    }
                    break;

                case 'disabled':
                    // We expect a bool.
                    $result = $this->evalBool($value);
                    if (!$result) {
                        $this->storage->messages->addMessage(
                            $this->storage->render->getHelp('configErrorDisabled')
                        );
                        $this->storage->messages->addKey('runtime.disabled.error');
                    }
                    break;

                case 'detectAjax':
                    // We expect a bool.
                    $result = $this->evalBool($value);
                    if (!$result) {
                        $this->storage->messages->addMessage(
                            $this->storage->render->getHelp('configErrorDetectAjax')
                        );
                        $this->storage->messages->addKey('runtime.detectAjax.error');
                    }
                    break;

                case 'destination':
                    // We expect 'frontend' or 'file'
                    if ($value === 'frontend' || $value === 'file') {
                        $result = true;
                    }
                    if (!$result) {
                        $this->storage->messages->addMessage(
                            $this->storage->render->getHelp('configErrorDestination')
                        );
                        $this->storage->messages->addKey('output.destination.error');
                    }
                    break;

                case 'maxfiles':
                    // We expect an integer.
                    $result = $this->evalInt($value);
                    if (!$result) {
                        $this->storage->messages->addMessage(
                            $this->storage->render->getHelp('configErrorMaxfiles')
                        );
                        $this->storage->messages->addKey('output.maxfiles.error');
                    }
                    break;

                case 'folder':
                    // Directory with write access.
                    // We also need to check, if the folder is properly protected.
                    $isWritable = is_writable($this->krexxdir . $value);
                    $isProtected = $this->isFolderProtected($this->krexxdir . $value);
                    if ($isWritable && $isProtected) {
                        $result = true;
                    }
                    if (!$isWritable) {
                        $this->storage->messages->addMessage(
                            $this->storage->render->getHelp('configErrorFolderWritable')
                        );
                        $this->storage->messages->addKey('output.folder.error.writable');
                    }
                    if (!$isProtected) {
                        $this->storage->messages->addMessage(
                            $this->storage->render->getHelp('configErrorFolderProtection')
                        );
                        $this->storage->messages->addKey('output.folder.error.protected');
                    }
                    break;

                case 'skin':
                    // We check the directory and one of the files for readability.
                    if (is_readable($this->krexxdir . 'resources/skins/' . $value . '/header.html')) {
                        $result = true;
                    }
                    if (!$result) {
                        $this->storage->messages->addMessage(
                            $this->storage->render->getHelp('configErrorSkin')
                        );
                        $this->storage->messages->addKey('output.skin.error');
                    }
                    break;

                case 'Local open function':
                    // The Developer handle, we check it for values that are not
                    // a-z and A-Z.
                    $devHandle = preg_match('/[^a-zA-Z]/', $value);
                    if (empty($devHandle)) {
                        $result = true;

                    } else {
                        $result = false;
                    }
                    if (!$result) {
                        $this->storage->messages->addMessage(
                            $this->storage->render->getHelp('configErrorHandle')
                        );
                        $this->storage->messages->addKey('output.haqndle.error');
                    }
                    break;

                case 'traceFatals':
                    // We expect a bool.
                    $result = $this->evalBool($value);
                    if (!$result) {
                        $this->storage->messages->addMessage(
                            $this->storage->render->getHelp('configErrorTraceFatals')
                        );
                        $this->storage->messages->addKey('errorHandling.traceFatals.error');
                    }
                    break;

                case 'traceWarnings':
                    // We expect a bool.
                    $result = $this->evalBool($value);
                    if (!$result) {
                        $this->storage->messages->addMessage(
                            $this->storage->render->getHelp('configErrorTraceWarnings')
                        );
                        $this->storage->messages->addKey('errorHandling.traceWarnings.error');
                    }
                    break;

                case 'traceNotices':
                    // We expect a bool.
                    $result = $this->evalBool($value);
                    if (!$result) {
                        $this->storage->messages->addMessage(
                            $this->storage->render->getHelp('configErrorTraceNotices')
                        );
                        $this->storage->messages->addKey('errorHandling.traceNotices.error');
                    }
                    break;

                case 'registerAutomatically':
                    // We expect a bool.
                    $result = $this->evalBool($value);
                    if (!$result) {
                        $this->storage->messages->addMessage(
                            $this->storage->render->getHelp('configErrorRegisterAuto')
                        );
                        $this->storage->messages->addKey('backtraceAndError.registerAutomatically.error');
                    }
                    // We also expect the php version to be lower than 7.
                    if ($result) {
                        $result = $this->evalPhp();
                        if (!$result) {
                            $this->storage->messages->addMessage(
                                $this->storage->render->getHelp('configErrorPhp7')
                            );
                            $this->storage->messages->addKey('backtraceAndError.registerAutomatically.php7');
                        }
                    }
                    break;

                case 'backtraceAnalysis':
                    // We expect "normal" or "deep"
                    if ($value === 'normal' || $value === 'deep') {
                        $result = true;
                    }
                    if (!$result) {
                        $this->storage->messages->addMessage(
                            $this->storage->render->getHelp('configErrorBacktraceAnalysis')
                        );
                        $this->storage->messages->addKey('backtraceAndError.backtraceAnalysis.error');
                    }
                    break;

                case 'memoryLeft':
                    // We expect an integer.
                    $result = $this->evalInt($value);
                    if (!$result) {
                        $this->storage->messages->addMessage(
                            $this->storage->render->getHelp('configErrorMemory')
                        );
                        $this->storage->messages->addKey('runtime.memoryLeft.error');
                    }
                    break;

                case 'maxRuntime':
                    // We expect an integer not greater than the max runtime of the
                    // server.
                    $result = $this->evalInt($value);
                    if (!$result) {
                        $this->storage->messages->addMessage(
                            $this->storage->render->getHelp('configErrorMaxRuntime')
                        );
                        $this->storage->messages->addKey('runtime.maxRuntime.error');
                    } else {
                        // OK, we got an int, now to see if it is smaller than the
                        // configured max runtime.
                        $maxTime = (int)ini_get('max_execution_time');
                        $value = (int)$value;
                        if ($maxTime > 0 && $maxTime < $value) {
                            // Too big!
                            $this->storage->messages->addMessage(
                                $this->storage->render->getHelp('configErrorMaxRuntimeBig1') .
                                $maxTime .
                                $this->storage->render->getHelp('configErrorMaxRuntimeBig2')
                            );
                            $this->storage->messages->addKey('runtime.maxRuntime.error.maximum', array($maxTime));
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
    public function getFeConfigFromFile($parameterName)
    {
        static $config = array();

        // Not loaded?
        if (!isset($config[$parameterName])) {
            // Get the human readable stuff from the ini file.
            $value = $this->getConfigFromFile('feEditing', $parameterName);
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
                        $type = $this->feConfigFallback[$parameterName]['type'];
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
    protected function evalBool($value)
    {
        if ($value === 'true' || $value === 'false') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if the php version is lower then 7.0.0.
     *
     * @return bool
     *   Whether it does evaluate or not.
     */
    protected function evalPhp()
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
    protected function evalInt($value)
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
    protected function arrayMerge(array &$oldArray, array &$newArray)
    {
        foreach ($newArray as $key => $value) {
            if (!isset($oldArray[$key])) {
                // We simply add it.
                $oldArray[$key] = $value;
            } else {
                // We have already a value.
                if (is_array($value)) {
                    // Add our array recursively.
                    $this->arrayMerge($oldArray[$key], $value);
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
    public function getConfigFromFile($group, $name)
    {
        static $config = array();

        // Not loaded?
        if (empty($config)) {
            $config = (array)parse_ini_string($this->storage->getFileContents($this->getPathToIni()), true);
            if (empty($config)) {
                // Still empty means that there is no ini file. We add a dummy.
                // This will prevent the failing reload of the ini file.
                $config[] = 'dummy';
            }
        }

        // Do we have a value in the ini?
        if (isset($config[$group][$name]) && $this->evaluateSetting($group, $name, $config[$group][$name])) {
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
    protected function getConfigFromCookies($group, $name)
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

        $paramConfig = $this->getFeConfig($name);
        if ($paramConfig[0] === false) {
            // We act as if we have not found the value. Configurations that are
            // not editable on the frontend will be ignored!
            return null;
        }
        // Do we have a value in the cookies?
        if (isset($config[$name]) && $this->evaluateSetting($group, $name, $config[$name])) {
            // We escape them, just in case.
            $value = htmlspecialchars($config[$name]);

            return $value;
        }
        // Still here?
        return null;
    }

    /**
     * Check if the current request is an AJAX request.
     *
     * @return bool
     *   TRUE when this is AJAX, FALSE if not
     */
    protected function isRequestAjaxOrCli()
    {
        if ($this->getConfigValue('output', 'destination') != 'file') {
            // When we are not going to create a logfile, we send it to the browser.
            // Check for ajax.
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            ) {
                // Appending stuff after a ajax request will most likely
                // cause a js error. But there are moments when you actually
                // want to do this.
                if ($this->getConfigValue('runtime', 'detectAjax') === 'true') {
                    // We were supposed to detect ajax, and we did it right now.
                    return true;
                }
            }
            // Check for CLI.
            if (php_sapi_name() === "cli") {
                return true;
            }
        }
        // Still here? This means it's neither.
        return false;
    }

    /**
     * Checks for a .htaccess file with a 'deny from all' statement.
     *
     * @param string $path
     *   The path we want to check.
     *
     * @return bool
     *   Whether the path is protected.
     */
    protected function isFolderProtected($path)
    {
        $result = false;
        if (is_readable($path . '/.htaccess')) {
            $content = file($path . '/.htaccess');
            foreach ($content as $line) {
                // We have what we are looking for, a
                // 'deny from all', not to be confuse with
                // a '# deny from all'.
                if (strtolower(trim($line)) === 'deny from all') {
                    $result = true;
                    break;
                }
            }
        }
        return $result;
    }
}
