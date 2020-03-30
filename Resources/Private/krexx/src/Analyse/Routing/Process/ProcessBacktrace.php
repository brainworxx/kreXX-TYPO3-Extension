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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Routing\Process;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\Analyse\BacktraceStep;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Config\Fallback;

/**
 * Processing of a backtrace. No abstract for you, because we are dealing with
 * an array here.
 *
 * @package Brainworxx\Krexx\Analyse\Routing\Process
 */
class ProcessBacktrace extends AbstractCallback
{
    /**
     * Wrapper around the process method, so we can use this one as a callback.
     *
     * @return string
     *   The generated DOM.
     */
    public function callMe(): string
    {
        return $this->handle($this->parameters[static::PARAM_DATA]);
    }

    /**
     * Processes the model according to the type of the variable.
     *
     * @param array $backtrace
     *
     * @deprecated
     *   Will be removed. Use $this->handle;
     *
     * @codeCoverageIgnore
     *   We will not test methods that are deprecated.
     *
     * @return string
     */
    public function process(&$backtrace = []): string
    {
        return $this->handle($backtrace);
    }

    /**
     * Do a backtrace analysis.
     *
     * @param array $backtrace
     *   The backtrace, which may (or may not) come from other sources.
     *   If omitted, a new debug_backtrace() will be retrieved.
     *
     * @return string
     *   The rendered backtrace.
     */
    public function handle(&$backtrace = []): string
    {
        if (empty($backtrace) === true) {
            $backtrace = $this->getBacktrace();
        }

        $output = '';
        $maxStep = (int) $this->pool->config->getSetting(Fallback::SETTING_MAX_STEP_NUMBER);
        $stepCount = count($backtrace);

        // Remove steps according to the configuration.
        if ($maxStep < $stepCount) {
            $this->pool->messages->addMessage('omittedBacktrace', [($maxStep + 1), $stepCount], true);
        } else {
            // We will not analyse more steps than we actually have.
            $maxStep = $stepCount;
        }

        for ($step = 1; $step <= $maxStep; ++$step) {
            $output .= $this->pool->render->renderExpandableChild(
                $this->pool->createClass(Model::class)
                    ->setName($step)
                    ->setType(static::TYPE_STACK_FRAME)
                    ->addParameter(static::PARAM_DATA, $backtrace[$step - 1])
                    ->injectCallback(
                        $this->pool->createClass(BacktraceStep::class)
                    )
            );
        }

        return $output;
    }

    /**
     * Get the backtrace, and remove all steps that were caused by kreXX.
     *
     * @return array
     *   The scrubbed backtrace.
     */
    protected function getBacktrace(): array
    {
        // Remove the fist step from the backtrace,
        // because that is the internal function in kreXX.
        $backtrace = debug_backtrace();

        // We remove all steps that came from inside the kreXX lib.
        $krexxScr = KREXX_DIR . 'src';
        foreach ($backtrace as $key => $step) {
            if (isset($step[static::TRACE_FILE]) && strpos($step[static::TRACE_FILE], $krexxScr) !== false) {
                unset($backtrace[$key]);
            } else {
                // No need to go further, because we should have passed the
                // kreXX part.
                break;
            }
        }

        // Reset the array keys, because the 0 is now missing.
        return array_values($backtrace);
    }
}
