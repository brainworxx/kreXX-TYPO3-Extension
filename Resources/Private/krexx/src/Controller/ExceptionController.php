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
 *   kreXX Copyright (C) 2014-2025 Brainworxx GmbH
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

declare(strict_types=1);

namespace Brainworxx\Krexx\Controller;

use Brainworxx\Krexx\Analyse\Caller\ExceptionCallerFinder;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessBacktrace;
use Throwable;

/**
 * Handling exceptions.
 *
 * @deprecated
 *   Since 6.0.0
 *   Will be removed.
 *   Has anybody used this one since PHP 7.0 anyway?
 * @codeCoverageIgnore
 *   We will not test deprecated code.
 */
class ExceptionController extends AbstractController
{
    /**
     * Storing our singleton exception handler.
     *
     * @var ExceptionController
     */
    protected static ExceptionController $exceptionController;

    /**
     * Analysing the error object and generating the output.
     *
     * @param \Throwable $exception
     */
    public function exceptionAction(Throwable $exception): void
    {
        // Get the main part.
        $main = $this->pool->render->renderFatalMain(
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );

        // Get the backtrace.
        $trace = $exception->getTrace();
        $backtrace = $this->pool->createClass(ProcessBacktrace::class)->handle($trace);

        if ($this->pool->emergencyHandler->checkEmergencyBreak()) {
            return;
        }

        // Detect the encoding on the start-chunk-string of the analysis
        // for a complete encoding picture.
        $this->pool->chunks->detectEncoding($main . $backtrace);

        // Get the header, footer and messages
        $caller = $this->pool
            ->createClass(ExceptionCallerFinder::class)
            ->findCaller('', $exception);
        $footer = $this->outputFooter($caller);
        $header = $this->pool->render->renderFatalHeader($this->outputCssAndJs(), get_class($exception));
        $messages = $this->pool->messages->outputMessages();

         // Add the caller as metadata to the chunks class. It will be saved as
        // additional info, in case we are logging to a file.
        $this->pool->chunks->addMetadata($caller);

        $this->outputService->addChunkString($header)->addChunkString($messages)
            ->addChunkString($main)
            ->addChunkString($backtrace)
            ->addChunkString($footer)
            ->finalize();
    }

    /**
     * As simple wrapper around the set_exception_handler function, with a
     * little of singleton handling.
     *
     * @return $this
     *   Return $this, for chaining.
     */
    public function registerAction(): ExceptionController
    {
        if (empty(static::$exceptionController)) {
            static::$exceptionController = $this;
        }

        set_exception_handler([static::$exceptionController, 'exceptionAction']);

        return $this;
    }

    /**
     * As simple wrapper around the restore_exception_handler function.
     *
     * @return $this
     *   Return $this, for chaining.
     */
    public function unregisterAction(): ExceptionController
    {
        restore_exception_handler();

        return $this;
    }
}
