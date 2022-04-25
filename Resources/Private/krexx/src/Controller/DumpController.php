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
 *   kreXX Copyright (C) 2014-2022 Brainworxx GmbH
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

use Brainworxx\Krexx\Analyse\Caller\BacktraceConstInterface;
use Brainworxx\Krexx\Analyse\Caller\ExceptionCallerFinder;
use Brainworxx\Krexx\Analyse\Model;

/**
 * "Controller" for the dump (aka analysis) "action".
 */
class DumpController extends AbstractController implements BacktraceConstInterface
{
    /**
     * Dump information about a variable.
     *
     * Here everything starts and ends (well, unless we are only outputting
     * the settings editor).
     *
     * @param mixed $data
     *   The variable we want to analyse.
     * @param string $message
     *   If set, we are in logging mode. We use this as a variable name then.
     * @param string $level
     *   The log level, if available.
     *
     * @return $this;
     *   Return $this for chaining.
     */
    public function dumpAction(&$data, string $message = '', string $level = 'debug'): DumpController
    {
        if ($this->pool->emergencyHandler->checkMaxCall()) {
            // Called too often, we might get into trouble here!
            return $this;
        }

        // Find caller.
        if ($data instanceof \Brainworxx\Krexx\Logging\Model) {
            $this->callerFinder = $this->pool->createClass(ExceptionCallerFinder::class);
        }
        $caller = $this->callerFinder->findCaller($message, $data);
        $caller[static::TRACE_LEVEL] = $level;

        // We will only allow code generation, if we were able to determine the
        // variable name or if we are not in logging mode.
        $message === '' ? $this->pool->scope->setScope($caller[static::TRACE_VARNAME]) :
            $this->pool->codegenHandler->setAllowCodegen(false);

        // Start the magic.
        $analysis = $this->pool->routing->analysisHub(
            $this->pool->createClass(Model::class)->setData($data)->setName($caller[static::TRACE_VARNAME])
        );

        // Detect the encoding on the start-chunk-string of the analysis
        // for a complete encoding picture.
        $this->pool->chunks->detectEncoding($analysis);

        // Now that our analysis is done, we must check if there was an emergency
        // break.
        if ($this->pool->emergencyHandler->checkEmergencyBreak()) {
            return $this;
        }

        // Add the caller as metadata to the chunks class. It will be saved as
        // additional info, in case we are logging to a file.
        $this->pool->chunks->addMetadata($caller);

        // We need to get the footer before the generating of the header,
        // because we need to display messages in the header from the configuration.
        $footer = $this->outputFooter($caller);

        $this->outputService
            ->addChunkString($this->pool->render->renderHeader($caller[static::TRACE_TYPE], $this->outputCssAndJs()))
            ->addChunkString($analysis)
            ->addChunkString($footer)
            ->finalize();

        return $this;
    }
}
