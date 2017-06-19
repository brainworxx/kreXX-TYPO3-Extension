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

        // We simply call the configured evaluation method.
        $callback = $this->evalSettings[$name];
        return $this->$callback($value, $name, $group);
    }

    /**
     * We check the dev handle for a-z and A-Z.
     *
     * @param string $value
     *   The value we want to evaluate
     * @param string $name
     *   The name of the value we are checking, needed for the feedback text.
     *
     * @return boolean
     *   Whether it does evaluate or not.
     */
    protected function evalDevHandle($value, $name)
    {
        $result = preg_match('/[^a-zA-Z]/', $value) === 0;
        if (!$result) {
            $this->pool->messages->addMessage('configError' . ucfirst($name));
        }
        return $result;
    }

    /**
     * We check one of the files for readability.
     *
     * @param string $value
     *   The value we want to evaluate
     * @param string $name
     *   The name of the value we are checking, needed for the feedback text.
     *
     * @return boolean
     *   Whether it does evaluate or not.
     */
    protected function evalSkin($value, $name)
    {
        $result = is_readable($this->pool->krexxDir . 'resources/skins/' . $value . '/header.html');
        if (!$result) {
            $this->pool->messages->addMessage('configError' . ucfirst($name));
        }
        return $result;
    }

    /**
     * We are expecting 'browser' or 'file'.
     *
     * @param string $value
     *   The value we want to evaluate
     * @param string $name
     *   The name of the value we are checking, needed for the feedback text.
     *
     * @return boolean
     *   Whether it does evaluate or not.
     */
    protected function evalDestination($value, $name)
    {
        $result = ($value === 'browser' || $value === 'file');
        if (!$result) {
            $this->pool->messages->addMessage('configError' . ucfirst($name));
        }
        return $result;
    }

    /**
     * Evaluating the IP range, by testing that it is not empty.
     *
     * @param string $value
     *   The value we want to evaluate
     * @param string $name
     *   The name of the value we are checking, needed for the feedback text.
     *
     * @return boolean
     *   Whether it does evaluate or not.
     */
    protected function evalIpRange($value, $name)
    {
        $result = empty($value);
        if ($result) {
            $this->pool->messages->addMessage('configError' . ucfirst($name));
        }

        return !$result;
    }

    /**
     * Evaluation the registering of the fatal error handler.
     * Works only in PHP5 and we are expecting a boolean.
     *
     * @param string $value
     *   The value we want to evaluate
     * @param string $name
     *   The name of the value we are checking, needed for the feedback text.
     * @param string $group
     *   The name of the group that we are evaluating, needed for the feedback
     *   text.
     *
     * @return boolean
     *   Whether it does evaluate or not.
     */
    protected function evalFatal($value, $name, $group)
    {
        // The feedback happens in the methods below.
        return $this->evalBool($value, $name, $group) && $this->evalPhp();
    }

    /**
     * Evaluation the maximum runtime, by looking at the server settings, as
     * well as checking for an integer value.
     *
     * @param string $value
     *   The value we want to evaluate
     * @param string $name
     *   The name of the value we are checking, needed for the feedback text.
     * @param string $group
     *   The name of the group that we are evaluating, needed for the feedback
     *   text.
     *
     * @return boolean
     *   Whether it does evaluate or not.
     */
    protected function evalMaxRuntime($value, $name, $group)
    {
        // Check for integer first.
        if (!$this->evalInt($value, $name, $group)) {
            return false;
        }

        $maxTime = (int)ini_get('max_execution_time');
        // We need a maximum runtime in the first place
        // and then check, if we have a value smaller than it.
        if ($maxTime <= 0) {
            // We were unable to get the maximum runtime from the server.
            // No need to check any further.
            return true;
        }
        if ($maxTime < (int)$value) {
            $this->pool->messages->addMessage(
                'configError' . ucfirst($name) . 'Big',
                array($maxTime)
            );
            return false;
        }

        return true;
    }

    /**
     * Evaluates a string of 'true' or 'false'.
     *
     * @param string $value
     *   The string we want to evaluate.
     * @param string $name
     *   The name of the value we are checking, needed for the feedback text.
     * @param string $group
     *   The name of the group that we are evaluating, needed for the feedback
     *   text.
     *
     * @return bool
     *   Whether it does evaluate or not.
     */
    protected function evalBool($value, $name, $group)
    {
        $result = ($value === 'true' || $value === 'false');
        if (!$result) {
            $this->pool->messages->addMessage('configErrorBool', array($group, $name));
        }
        return $result;
    }

    /**
     * Checks if the php version is lower then 7.0.0.
     *
     * @return bool
     *   Whether it does evaluate or not.
     */
    protected function evalPhp()
    {
        $result = version_compare(phpversion(), '7.0.0', '>=');
        if ($result) {
            $this->pool->messages->addMessage('configErrorRegisterAutomatically2');
        }

        return !$result;
    }

    /**
     * Evaluates a string of integer.
     *
     * It must be greater than 0 and smaller than 101.
     *
     * @param string $value
     *   The string we want to evaluate.
     * @param string $name
     *   The name of the value we are checking, needed for the feedback text.
     * @param string $group
     *   The name of the group that we are evaluating, needed for the feedback
     *   text.
     *
     * @return bool
     *   Whether it does evaluate or not.
     */
    protected function evalInt($value, $name, $group)
    {
        $result = ((int) $value) > 0;
        if (!$result) {
            $this->pool->messages->addMessage('configErrorInt', array($group, $name));
        }

        return $result;
    }

    /**
     * We do not evaluate this one.
     *
     * @return bool
     *   Always true, we do not eval this one.
     */
    protected function doNotEval()
    {
        return true;
    }
}
