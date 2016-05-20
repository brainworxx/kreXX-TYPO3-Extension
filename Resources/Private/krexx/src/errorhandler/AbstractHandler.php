<?php
/**
 * @file
 *   Abstract errorhandler for kreXX
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

namespace Brainworxx\Krexx\Errorhandler;

use Brainworxx\Krexx\Analysis\Hive;
use Brainworxx\Krexx\Framework\Internals;
use Brainworxx\Krexx\Framework\Config;
use Brainworxx\Krexx\Framework\Chunks;
use Brainworxx\Krexx\View;

/**
 * This class hosts all functions which all error handlers will share
 * (as soon as they are written . . .)
 *
 * @package Brainworxx\Krexx\Errorhandler
 */
abstract class AbstractHandler
{

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
     * Decides, if the handler does anything.
     *
     * @return bool
     *   Returns TRUE when kreXX is active and this
     *   handler is active
     */
    protected function getIsActive()
    {
        if ($this->isActive && Config::isEnabled()) {
            // We will only handle errors when kreXX and the handler
            // itself is enabled.
            return true;
        } else {
            return false;
        }

    }

    /**
     * Renders the info to the error, warning or notice.
     *
     * @param array $errorData
     *   The data frm the error. This should be a backtrace
     *   with code samples.
     */
    protected function giveFeedback(array $errorData)
    {
        if ($this->isActive) {
            View\SkinRender::$KrexxCount++;
            Internals::$timer = time();

            // Setting template info.
            if (is_null(View\SkinRender::$skin)) {
                View\SkinRender::$skin = Config::getConfigValue('output', 'skin');
            }

            // Get the header.
            if (View\Output::$headerSend) {
                $header = View\SkinRender::renderFatalHeader('', '<!DOCTYPE html>');
            } else {
                $header = View\SkinRender::renderFatalHeader(View\Output::outputCssAndJs(), '<!DOCTYPE html>');
            }

            // Get the main part.
            $main = View\SkinRender::renderFatalMain(
                $errorData['type'],
                $errorData['errstr'],
                $errorData['errfile'],
                $errorData['errline'] + 1,
                $errorData['source']
            );
            // Get the backtrace.
            $backtrace = View\Output::outputBacktrace($errorData['backtrace']);
            // Get the footer.
            $footer = View\Output::outputFooter('');
            // Get the messages.
            $messages = View\Messages::outputMessages();

            if (Config::getConfigValue('output', 'destination') == 'file') {
                // Add the caller as metadata to the chunks class. It will be saved as
                // additional info, in case we are logging to a file.
                Chunks::addMetadata(array(
                    'file' => $errorData['errfile'],
                    'line' => $errorData['errline'] + 1,
                    'varname' => ' Fatal Error',
                ));

                // Save it to a file.
                Chunks::saveDechunkedToFile($header . $messages . $main . $backtrace . $footer);
            } else {
                // Send it to the browser.
                Chunks::sendDechunkedToBrowser($header . $messages . $main . $backtrace . $footer);
            }

            // Cleanup the hive, this removes all recursion markers.
            Hive::cleanupHive();
        }
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
        switch ($errorint) {
            case E_ERROR:
                $errorName = 'Fatal';
                $errorSetting = 'traceFatals';
                break;

            case E_WARNING:
                $errorName = 'Warning';
                $errorSetting = 'traceWarnings';
                break;

            case E_PARSE:
                $errorName = 'Parse error';
                $errorSetting = 'traceFatals';
                break;

            case E_NOTICE:
                $errorName = 'Notice';
                $errorSetting = 'traceNotices';
                break;

            case E_CORE_ERROR:
                $errorName = 'PHP startup error';
                $errorSetting = 'traceFatals';
                break;

            case E_CORE_WARNING:
                $errorName = 'PHP startup warning';
                $errorSetting = 'traceWarnings';
                break;

            case E_COMPILE_ERROR:
                $errorName = 'Zend scripting fatal error';
                $errorSetting = 'traceFatals';
                break;

            case E_COMPILE_WARNING:
                $errorName = 'Zend scripting warning';
                $errorSetting = 'traceWarnings';
                break;

            case E_USER_ERROR:
                $errorName = 'User defined error';
                $errorSetting = 'traceFatals';
                break;

            case E_USER_WARNING:
                $errorName = 'User defined warning';
                $errorSetting = 'traceWarnings';
                break;

            case E_USER_NOTICE:
                $errorName = 'User defined notice';
                $errorSetting = 'traceNotices';
                break;

            case E_STRICT:
                $errorName = 'Strict notice';
                $errorSetting = 'traceNotices';
                break;

            case E_RECOVERABLE_ERROR:
                $errorName = 'Catchable fatal error';
                $errorSetting = 'traceFatals';
                break;

            case E_DEPRECATED:
                $errorName = 'Deprecated warning';
                $errorSetting = 'traceWarnings';
                break;

            case E_USER_DEPRECATED:
                $errorName = 'User defined deprecated warning';
                $errorSetting = 'traceWarnings';
                break;

            default:
                $errorName = 'Unknown error';
                $errorSetting = 'unknown';
                break;
        }
        return array($errorName, $errorSetting);
    }
}
