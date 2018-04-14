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
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Errorhandler;

use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * This class hosts all functions which all error handlers will share
 *
 * @package Brainworxx\Krexx\Errorhandler
 */
abstract class AbstractError
{

    /**
     * Translates an error into someting more human readable.
     *
     * @var array
     */
    protected $errorTranslation = array(
        E_ERROR => array('Fatal', 'traceFatals'),
        E_WARNING => array('Warning', 'traceWarnings'),
        E_PARSE => array('Parse error', 'traceFatals'),
        E_NOTICE => array('Notice', 'traceNotices'),
        E_CORE_ERROR => array('PHP startup error', 'traceFatals'),
        E_CORE_WARNING => array('PHP startup warning', 'traceWarnings'),
        E_COMPILE_ERROR => array('Zend scripting fatal error', 'traceFatals'),
        E_COMPILE_WARNING => array('Zend scripting warning', 'traceWarnings'),
        E_USER_ERROR => array('User defined error', 'traceFatals'),
        E_USER_WARNING => array('User defined warning', 'traceWarnings'),
        E_USER_NOTICE => array('User defined notice', 'traceNotices'),
        E_STRICT => array('Strict notice', 'traceNotices'),
        E_RECOVERABLE_ERROR => array('Catchable fatal error', 'traceFatals'),
        E_DEPRECATED => array('Deprecated warning', 'traceWarnings'),
        E_USER_DEPRECATED => array('User defined deprecated warning', 'traceWarnings'),
    );

    /**
     * Here we store all relevant data.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * Stores if the handler is active.
     *
     * Decides if the registered shutdown function should
     * do anything, in case we decide later that we do not
     * want to interfere.
     *
     * @var bool
     */
    protected $isActive = false;

    /**
     * Injects the pool.
     *
     * @param Pool $pool
     *   The pool, where we store the classes we need.
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * Decides, if the handler does anything.
     *
     * @return bool
     *   Returns TRUE when kreXX is active and this
     *   handler is active
     */
    protected function getIsActive()
    {
        // We will only handle errors when kreXX and the handler
        // itself is enabled.
        return $this->isActive && !$this->pool->config->getSetting(Fallback::SETTING_DISABLED);
    }

    /**
     * Translates the error number into human readable text.
     *
     * It also includes the corresponding config
     * setting, so we can decide if we want to output
     * anything.
     *
     * @param int $errorint
     *   The error number.
     *
     * @return array
     *   The translated type and the setting.
     */
    protected function translateErrorType($errorint)
    {
        if (isset($this->errorTranslation[$errorint]) === true) {
            return $this->errorTranslation[$errorint];
        }

        // Fallback to 'unknown'.
        return array('Unknown error', 'unknown');
    }
}
