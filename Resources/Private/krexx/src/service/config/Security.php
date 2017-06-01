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

namespace Brainworxx\Krexx\Service\Config;

/**
 * Security measures for the configuration
 *
 * @package Brainworxx\Krexx\Service\Config
 */
class Security extends Fallback
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
    public function evaluateSetting($group, $name, $value)
    {
        if ($group === 'feEditing') {
            // Logging options can never be changed in the frontend.
            // The debug methods will also not be editable.
            return !in_array($name, $this->feConfigNoEdit);
        }


        // We must evaluate it.
        $result = false;
        switch ($name) {
            case 'analyseProtectedMethods':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->pool->messages->addMessage('configErrorMethodsProtected');
                }
                break;

            case 'analysePrivateMethods':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->pool->messages->addMessage('configErrorMethodsPrivate');
                }
                break;

            case 'analyseProtected':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->pool->messages->addMessage('configErrorPropertiesProtected');
                }
                break;

            case 'analysePrivate':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->pool->messages->addMessage('configErrorPropertiesPrivate');
                }
                break;

            case 'analyseConstants':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->pool->messages->addMessage('configErrorPropertiesConstants');
                }
                break;


            case 'analyseTraversable':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->pool->messages->addMessage('configErrorTraversable');
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
                    $this->pool->messages->addMessage('configErrorLevel');
                }
                break;

            case 'maxCall':
                // We expect an integer.
                $result = $this->evalInt($value);
                if (!$result) {
                    $this->pool->messages->addMessage('configErrorMaxCall');
                }
                break;

            case 'disabled':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->pool->messages->addMessage('configErrorDisabled');
                }
                break;

            case 'detectAjax':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->pool->messages->addMessage('configErrorDetectAjax');
                }
                break;

            case 'destination':
                // We expect 'frontend', 'file' or 'direct.
                if ($value === 'browser' || $value === 'file') {
                    $result = true;
                }
                if (!$result) {
                    $this->pool->messages->addMessage('configErrorDestination');
                }
                break;

            case 'maxfiles':
                // We expect an integer.
                $result = $this->evalInt($value);
                if (!$result) {
                    $this->pool->messages->addMessage('configErrorMaxfiles');
                }
                break;

            case 'skin':
                // We check the directory and one of the files for readability.
                if (is_readable($this->pool->krexxDir . 'resources/skins/' . $value . '/header.html')) {
                    $result = true;
                }
                if (!$result) {
                    $this->pool->messages->addMessage('configErrorSkin');
                }
                break;

            case 'Local open function':
                // The Developer handle, we check it for values that are not
                // a-z and A-Z.
                $devHandle = preg_match('/[^a-zA-Z]/', $value);
                $result = empty($devHandle);
                if (!$result) {
                    $this->pool->messages->addMessage('configErrorHandle');
                }
                break;

            case 'traceFatals':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->pool->messages->addMessage('configErrorTraceFatals');
                }
                break;

            case 'traceWarnings':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->pool->messages->addMessage('configErrorTraceWarnings');
                }
                break;

            case 'traceNotices':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->pool->messages->addMessage('configErrorTraceNotices');
                }
                break;

            case 'registerAutomatically':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->pool->messages->addMessage('configErrorRegisterAuto');
                }
                // We also expect the php version to be lower than 7.
                if ($result) {
                    $result = $this->evalPhp();
                    if (!$result) {
                        $this->pool->messages->addMessage('configErrorPhp7');
                    }
                }
                break;

            case 'iprange':
                // We expect an array of ip's after an explode.
                // But we are not validating every singe one of them.
                // We are just making sure that we get a list.
                $result = trim($value);
                $result = !empty($result);
                if (!$result) {
                    $this->pool->messages->addMessage('configErrorIpList');
                }
                break;

            case 'analyseGetter':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->pool->messages->addMessage('configErrorAnalyseGetter');
                }
                break;

            case 'memoryLeft':
                // We expect an integer.
                $result = $this->evalInt($value);
                if (!$result) {
                    $this->pool->messages->addMessage('configErrorMemory');
                }
                break;

            case 'maxRuntime':
                // We expect an integer not greater than the max runtime of the
                // server.
                $result = $this->evalInt($value);
                if (!$result) {
                    $this->pool->messages->addMessage('configErrorMaxRuntime');
                } else {
                    // OK, we got an int, now to see if it is smaller than the
                    // configured max runtime.
                    $maxTime = (int)ini_get('max_execution_time');
                    $value = (int)$value;
                    if ($maxTime > 0 && $maxTime < $value) {
                        // Too big!
                        $this->pool->messages->addMessage('configErrorMaxRuntimeBig', array($maxTime));
                        $result = false;
                    }
                }
                break;

            case 'useScopeAnalysis':
                // We expect a bool.
                $result = $this->evalBool($value);
                if (!$result) {
                    $this->pool->messages->addMessage('configErrorUseScopeAnalysis');
                }
                break;

            case 'maxStepNumber':
                // We expect an integer.
                $result = $this->evalInt($value);
                if (!$result) {
                    $this->pool->messages->addMessage('configErrorMaxStepNumber');
                }

                break;

            default:
                // Unknown settings,
                // return false, just in case.
                break;
        }

        return $result;
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
        return ($value === 'true' || $value === 'false');
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
        }

        return true;
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
        return ((int) $value > 0);
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
        // Check if the class itself is blacklisted.
        foreach ($this->debugClassBlacklist as $classname) {
            if (is_a($data, $classname)) {
                // No debug methods for you.
                return false;
            }
        }

        // Check for a class / method combination.
        foreach ($this->debugMethodsBlacklist as $classname => $methodLlist) {
            if (is_a($data, $classname) && in_array($call, $methodLlist)) {
                // We have a winner, this one is blacklisted!
                return false;
            }
        }
        // Nothing found?
        return true;
    }

    /**
     * Checks if the current client ip is allowed.
     *
     * @param string $whitelist
     *   The ip whitelist.
     *
     * @return bool
     *   Whether the current client ip is allowed or not.
     */
    public function isAllowedIp($whitelist)
    {
        if (empty($_SERVER['REMOTE_ADDR'])) {
            $remote = '';
        } else {
            $remote = $_SERVER['REMOTE_ADDR'];
        }

        // Fallback to the Chin Leung implementation.
        // @author Chin Leung
        // @see https://stackoverflow.com/questions/35559119/php-ip-address-whitelist-with-wildcards
        $whitelist = explode(',', $whitelist);
        if (in_array($remote, $whitelist)) {
            // If the ip is matched, return true.
            return true;
        }

        // Check the wildcards.
        foreach ($whitelist as $ip) {
            $ip = trim($ip);
            $wildcardPos = strpos($ip, '*');
            # Check if the ip has a wildcard
            if ($wildcardPos !== false && substr($remote, 0, $wildcardPos) . '*' === $ip) {
                return true;
            }
        }

        return false;
    }
}
