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

namespace Brainworxx\Krexx\Controller;

use Brainworxx\Krexx\Service\Config\Fallback;

/**
 * "Controller" for the fatal error handler "action"
 *
 * @package Brainworxx\Krexx\Controller
 */
class ErrorController extends AbstractController
{
    /**
     * Renders the info to the error, warning or notice.
     *
     * @param array $errorData
     *   The data from the error. This should be a backtrace
     *   with code samples.
     *
     * @return $this
     *   Return $this for chaining
     */
    public function errorAction(array $errorData)
    {
        $this->pool->reset();

        // Get the main part.
        $main = $this->pool->render->renderFatalMain(
            $errorData['type'],
            $errorData['errstr'],
            $errorData['errfile'],
            $errorData['errline']
        );

        // Get the backtrace.
        $backtrace = $this->pool
            ->createClass('Brainworxx\\Krexx\\Analyse\\Routing\\Process\\ProcessBacktrace')
            ->process($errorData['backtrace']);

        if ($this->pool->emergencyHandler->checkEmergencyBreak() === true) {
            return $this;
        }

        // Detect the encoding on the start-chunk-string of the analysis
        // for a complete encoding picture.
        $this->pool->chunks->detectEncoding($main);

        // Detect the encoding on the start-chunk-string of the analysis
        // for a complete encoding picture.
        $this->pool->chunks->detectEncoding($backtrace);

        // Get the header.
        if (static::$headerSend === true) {
            $header = $this->pool->render->renderFatalHeader('', '<!DOCTYPE html>');
        } else {
            $header = $this->pool->render->renderFatalHeader($this->outputCssAndJs(), '<!DOCTYPE html>');
        }

        // Get the footer.
        $footer = $this->outputFooter(array());

        // Get the messages.
        $messages = $this->pool->messages->outputMessages();

        // Add the caller as metadata to the chunks class. It will be saved as
        // additional info, in case we are logging to a file.
        $this->pool->chunks->addMetadata(
            array(
                'file' => $errorData['errfile'],
                'line' => $errorData['errline'] + 1,
                'varname' => ' Fatal Error',
            )
        );

        if ($this->pool->config->getSetting(Fallback::SETTING_DESTINATION) === Fallback::VALUE_FILE) {
            // Save it to a file.
            $this->pool->chunks->saveDechunkedToFile($header . $messages . $main . $backtrace . $footer);
        } else {
            // Send it to the browser.
            $this->pool->chunks->sendDechunkedToBrowser($header . $messages . $main . $backtrace . $footer);
        }

        return $this;
    }

    /**
     * Register the fatal error handler.
     *
     * @return $this
     *   Return $this for chaining
     */
    public function registerFatalAction()
    {
        // As of PHP Version 7.0.2, the register_tick_function() causes PHP to
        // crash, with a connection reset! We need to check the version to avoid
        // this, and then tell the dev what happened.
        // Not to mention that fatals got removed anyway.
        if (version_compare(phpversion(), '7.0.0', '>=')) {
            // Too high! 420 Method Failure :-(
            $this->pool->messages->addMessage($this->pool->messages->getHelp('php7yellow'));
            krexx($this->pool->messages->getHelp('php7'));

            // Just return, there is nothing more to do here.
            return $this;
        }

        $this->pool->reset();
        // Do we need another shutdown handler?
        if (is_object($this::$krexxFatal) === false) {
            $this::$krexxFatal = $this->pool->createClass('Brainworxx\\Krexx\\Errorhandler\\Fatal');
            declare(ticks = 1);
            register_shutdown_function(
                array(
                    $this::$krexxFatal,
                    'shutdownCallback',
                )
            );
        }

        $this::$krexxFatal->setIsActive(true);
        $this->fatalShouldActive = true;
        register_tick_function(array($this::$krexxFatal, 'tickCallback'));

        return $this;
    }

    /**
     * "Unregister" the fatal error handler.
     *
     * Actually we can not unregister it. We simply tell it to not activate
     * and we unregister the tick function which provides us with the
     * backtrace.
     *
     * @return $this
     *   Return $this for chaining
     */
    public function unregisterFatalAction()
    {
        if ($this::$krexxFatal !== null) {
            // Now we need to tell the shutdown function, that is must
            // not do anything on shutdown.
            $this::$krexxFatal->setIsActive(false);
            unregister_tick_function(array($this::$krexxFatal, 'tickCallback'));
        }

        $this->fatalShouldActive = false;

        return $this;
    }
}
