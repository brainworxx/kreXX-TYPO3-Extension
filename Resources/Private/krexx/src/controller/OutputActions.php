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

namespace Brainworxx\Krexx\Controller;

use Brainworxx\Krexx\Errorhandler\Fatal;
use Brainworxx\Krexx\Analyse\Model;

/**
 * Controller actions (if you want to call them that).
 *
 * @package Brainworxx\Krexx\Controller
 */
class OutputActions extends Internals
{

    /**
     * Config for the 'deep' backtrace analysis.
     *
     * @var array
     */
    protected $configFatal = array(
        'analyseProtected' => 'true',
        'analysePrivate' => 'true',
        'analyseTraversable' => 'true',
        'analyseConstants' => 'true',
        'analyseProtectedMethods' => 'true',
        'analysePrivateMethods' => 'true',
    );

    /**
     * Dump information about a variable.
     *
     * Here everything starts and ends (well, unless we are only outputting
     * the settings editor).
     *
     * @param mixed $data
     *   The variable we want to analyse.
     * @param string $headline
     *   The headline of the markup we want to produce. Most likely the name of
     *   the variable.
     */
    public function dumpAction($data, $headline = '')
    {
        if ($this->storage->emergencyHandler->checkMaxCall()) {
            // Called too often, we might get into trouble here!
            return;
        }
        $this->storage->reset();

        // Find caller.
        $caller = $this->storage->callerFinder->findCaller();
        if ($headline != '') {
            $caller['type'] = $headline;
        } else {
            $caller['type'] = 'Analysis';
        }
        $this->storage->codegenHandler->setScope($caller['varname']);

        // Set the headline, if it's not set already.
        if (empty($headline)) {
            if (is_object($data)) {
                $headline = get_class($data);
            }
            if (is_array($data)) {
                $headline = 'array';
            }
            if (is_bool($data)) {
                $headline = 'boolean';
            }
            if (is_float($data)) {
                $headline = 'float';
            }
            if (is_int($data)) {
                $headline = 'integer';
            }
            if (is_null($data)) {
                $headline = 'null';
            }
            if (is_resource($data)) {
                $headline = 'resource';
            }
            if (is_string($data)) {
                $headline = 'string';
            }
        }

        // We need to get the footer before the generating of the header,
        // because we need to display messages in the header from the configuration.
        $footer = $this->outputFooter($caller);
        $this->storage->codegenHandler->checkAllowCodegen();

        // Enable code generation only if we were aqble to determine the varname.
        if ($caller['varname'] != '. . .') {
            // We were able to determine the variable name and can generate some
            // sourcecode.
            $headline = $caller['varname'];
        }

        // Start the magic.
        $model = new Model($this->storage);
        $model->setData($data)
            ->setName($caller['varname']);
        $analysis = $this->storage->routing->analysisHub($model);
        // Now that our analysis is done, we must check if there was an emergency
        // break.
        if (!$this->storage->emergencyHandler->checkEmergencyBreak()) {
            return;
        }

        $this->shutdownHandler->addChunkString($this->outputHeader($headline));
        $this->shutdownHandler->addChunkString($analysis);
        $this->shutdownHandler->addChunkString($footer);

        // Add the caller as metadata to the chunks class. It will be saved as
        // additional info, in case we are logging to a file.
        if ($this->storage->config->getSetting('destination') === 'file') {
            $this->storage->chunks->addMetadata($caller);
        }
    }

    /**
     * Outputs a backtrace.
     */
    public function backtraceAction()
    {
        if ($this->storage->emergencyHandler->checkMaxCall()) {
            // Called too often, we might get into trouble here!
            return;
        }

        $this->storage->reset();
        // We overwrite the local settings, so we can get as much info from
        // analysed objects as possible.
        $this->storage->config->overwriteLocalSettings($this->configFatal);

        // Find caller.
        $caller = $this->storage->callerFinder->findCaller();
        $caller['type'] = 'Backtrace';

        $this->storage->codegenHandler->setScope($caller['varname']);

        $headline = 'Backtrace';

        // Remove the fist step from the backtrace,
        // because that is the internal function in kreXX.
        $backtrace = debug_backtrace();
        unset($backtrace[0]);

        $footer = $this->outputFooter($caller);

        $analysis = $this->storage->routing->analysisBacktrace($backtrace, -1);
        // Now that our analysis is done, we must check if there was an emergency
        // break.
        if (!$this->storage->emergencyHandler->checkEmergencyBreak()) {
            return;
        }

        $this->shutdownHandler->addChunkString($this->outputHeader($headline));
        $this->shutdownHandler->addChunkString($analysis);
        $this->shutdownHandler->addChunkString($footer);

        // Add the caller as metadata to the chunks class. It will be saved as
        // additional info, in case we are logging to a file.
        if ($this->storage->config->getSetting('destination') === 'file') {
            $this->storage->chunks->addMetadata($caller);
        }

        // Reset our configuration for the other analysis calls.
        $this->storage->resetConfig();
    }

    /**
     * Outputs the edit settings dialog, without any analysis.
     */
    public function editSettingsAction()
    {
        if ($this->storage->emergencyHandler->checkMaxCall()) {
            // Called too often, we might get into trouble here!
            return;
        }

        $this->storage->reset();

        // We will not check this for the cookie config, to avoid people locking
        // themselves out.
        $this->storage->emergencyHandler->setEnable(false);


        // Find caller.
        $caller = $this->storage->callerFinder->findCaller();
        $caller['type'] = 'Cookie Configuration';
        $this->storage->chunks->addMetadata($caller);

        // Render it.
        $footer = $this->outputFooter($caller, true);
        $this->shutdownHandler->addChunkString($this->outputHeader('Edit local settings'));
        $this->shutdownHandler->addChunkString($footer);
        $this->storage->emergencyHandler->setEnable(true);
    }

    /**
     * Renders the info to the error, warning or notice.
     *
     * @param array $errorData
     *   The data from the error. This should be a backtrace
     *   with code samples.
     */
    public function errorAction(array $errorData)
    {
        $this->storage->reset();

        // We overwrite the local settings, so we can get as much info from
        // analysed objects as possible.
        $this->storage->config->overwriteLocalSettings($this->configFatal);

        // Get the header.
        if ($this->headerSend) {
            $header = $this->storage->render->renderFatalHeader('', '<!DOCTYPE html>');
        } else {
            $header = $this->storage->render->renderFatalHeader($this->outputCssAndJs(), '<!DOCTYPE html>');
        }

        // Get the main part.
        $main = $this->storage->render->renderFatalMain(
            $errorData['type'],
            $errorData['errstr'],
            $errorData['errfile'],
            $errorData['errline']
        );

        // Get the backtrace.
        $backtrace = $this->storage->routing->analysisBacktrace($errorData['backtrace'], -1);
        if (!$this->storage->emergencyHandler->checkEmergencyBreak()) {
            return;
        }

        // Get the footer.
        $footer = $this->outputFooter('');
        // Get the messages.
        $messages = $this->storage->messages->outputMessages();

        if ($this->storage->config->getSetting('destination') === 'file') {
            // Add the caller as metadata to the chunks class. It will be saved as
            // additional info, in case we are logging to a file.
            $this->storage->chunks->addMetadata(array(
                'file' => $errorData['errfile'],
                'line' => $errorData['errline'] + 1,
                'varname' => ' Fatal Error',
            ));

            // Save it to a file.
            $this->storage->chunks->saveDechunkedToFile($header . $messages . $main . $backtrace . $footer);
        } else {
            // Send it to the browser.
            $this->storage->chunks->sendDechunkedToBrowser($header . $messages . $main . $backtrace . $footer);
        }
    }

    /**
     * Register the fatal error handler.
     */
    public function registerFatalAction()
    {
        // As of PHP Version 7.0.2, the register_tick_function() causesPHP to crash,
        // with a connection reset! We need to check the version to avoid this, and
        // then tell the dev what happened.
        // Not to mention that fatals got removed anyway.
        if (version_compare(phpversion(), '7.0.0', '>=')) {
            // Too high! 420 Method Failure :-(
            $this->storage->messages->addMessage($this->storage->messages->getHelp('php7yellow'));
            krexx($this->storage->messages->getHelp('php7'));

            // Just return, there is nothing more to do here.
            return;
        }
        $this->storage->reset();
        // Do we need another shutdown handler?
        if (!is_object($this->krexxFatal)) {
            $this->krexxFatal = new Fatal($this->storage);
            declare(ticks = 1);
            register_shutdown_function(array(
              $this->krexxFatal,
              'shutdownCallback',
            ));
        }
        $this->krexxFatal->setIsActive(true);
        $this->fatalShouldActive = true;
        register_tick_function(array($this->krexxFatal, 'tickCallback'));
    }

    /**
     * "Unregister" the fatal error handler.
     *
     * Actually we can not unregister it. We simply tell it to not activate
     * and we unregister the tick function which provides us with the
     * backtrace.
     */
    public function unregisterFatalAction()
    {
        if (!is_null($this->krexxFatal)) {
            // Now we need to tell the shutdown function, that is must
            // not do anything on shutdown.
            $this->krexxFatal->setIsActive(false);
            unregister_tick_function(array($this->krexxFatal, 'tickCallback'));
        }
        $this->fatalShouldActive = false;
    }

    /**
     * Takes a "moment" for the benchmark test.
     *
     * @param string $string
     *   Defines a "moment" during a benchmark test.
     *   The string should be something meaningful, like "Model invoice db call".
     */
    public function timerAction($string)
    {
        // Did we use this one before?
        if (isset($this->counterCache[$string])) {
            // Add another to the counter.
            $this->counterCache[$string]++;
            $this->timekeeping['[' . $this->counterCache[$string] . ']' . $string] = microtime(true);
        } else {
            // First time counter, set it to 1.
            $this->counterCache[$string] = 1;
            $this->timekeeping[$string] = microtime(true);
        }
    }

    /**
     * Outputs the timer
     */
    public function timerEndAction()
    {
        $this->timerAction('end');
        // And we are done. Feedback to the user.
        $this->dumpAction($this->miniBenchTo($this->timekeeping), 'kreXX timer');
        // Reset the timer vars.
        $this->timekeeping = array();
        $this->counterCache = array();
    }

    /**
     * Simply outputs a formatted var_dump.
     *
     * This is an internal debugging function, because it is
     * rather difficult to debug a debugger, when your tool of
     * choice is the debugger itself.
     *
     * @param mixed $data
     *   The data for the var_dump.
     */
    public static function formattedVarDump($data)
    {
        echo '<pre>';
        var_dump($data);
        echo('</pre>');
    }
}
