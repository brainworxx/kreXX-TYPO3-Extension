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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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

use Brainworxx\Krexx\Controller\AbstractController;
use Brainworxx\Krexx\Controller\ErrorController;

/**
 * PHP 5.x fatal error handler.
 *
 * @deprecated
 *   Since 3.1.0. Will be removed when dropping PHP support.
 *
 * @package Brainworxx\Krexx\Errorhandler
 */
class Fatal extends AbstractError
{

    /**
     * The current backtrace from the registered tick callback.
     *
     * PHP deletes it's own stack, when we encounter a fatal error.
     * The ticked callback solves this, because it will store a
     * backtrace here.
     *
     * @var array
     *
     * @see $this->tickCallback().
     */
    protected $tickedBacktrace = [];

    /**
     * Registered tick callback.
     *
     * It stores a backtrace in $this->tickedBacktrace.
     */
    public function tickCallback()
    {
        $this->tickedBacktrace = debug_backtrace();
    }

    /**
     * Setter function for $this->isActive.
     *
     * We store there whether this handler should do
     * anything during shutdown, in case we decide after
     * registering, that we do not want to interfere.
     *
     * @param bool $value
     *   Whether the handler is active or not.
     */
    public function setIsActive($value)
    {
        $this->isActive = $value;
    }

    /**
     * The registered shutdown callback handles fatal errors.
     *
     * In case that this handler is active, it will check whether
     * a fatal error has happened and give additional info like
     * backtrace, object analysis of the backtrace and code samples
     * to all stations in the backtrace.
     */
    public function shutdownCallback()
    {
        $error = error_get_last();

        // Do we have an error at all?
        if ($error  !== null && $this->getIsActive() === true) {
            // Do we need to check this one, according to our settings?
            $translatedError = $this->translateErrorType($error[static::TRACE_TYPE]);
            if ($translatedError[1] === 'traceFatals') {
                // We also need to prepare some Data we want to display.
                $errorType = $this->translateErrorType($error[static::TRACE_TYPE]);

                // We prepare the error as far as we can here.
                // The adding of the sourcecode happens in the controller.
                $errorData = [
                    static::TRACE_TYPE => $errorType[0] . ' Error',
                    static::TRACE_ERROR_STRING => $error['message'],
                    static::TRACE_ERROR_FILE => $error[static::TRACE_FILE],
                    static::TRACE_ERROR_LINE => $error[static::TRACE_LINE],
                    'handler' => __FUNCTION__,
                    static::TRACE_FILE => $error[static::TRACE_FILE],
                    static::TRACE_BACKTRACE => $this->tickedBacktrace,
                ];

                // Tell static main class, that we start a new analysis, to
                // prevent an infinite loop.
                AbstractController::$analysisInProgress = true;
                $this->pool
                    ->createClass(ErrorController::class)
                    ->errorAction($errorData);
            }
        }
    }
}
