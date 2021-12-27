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
 *   kreXX Copyright (C) 2014-2021 Brainworxx GmbH
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

namespace Brainworxx\Krexx\View;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Interface for the render class, defining all public methods.
 */
interface RenderInterface
{
    /**
     * Injects the pool and initialize the skin path.
     *
     * @param Pool $pool
     *   The pool, where we store the classes we need.
     */
    public function __construct(Pool $pool);

    /**
     * Render a block of a detected recursion.
     *
     * If the recursion is an object, a click should jump to the original
     * analysis data.
     *
     * @param Model $model
     *   The model, which hosts all the data we need.
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderRecursion(Model $model): string;

    /**
     * Renders the kreXX header.
     *
     * @param string $headline
     *   The headline, what is actually analysed.
     * @param string $cssJs
     *   The CSS and JS in a string.
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderHeader(string $headline, string $cssJs): string;

    /**
     * Renders the kreXX footer.
     *
     * @param array $caller
     *   The caller of kreXX.
     * @param Model $model
     *   The pregenerated configuration markup.
     * @param bool $configOnly
     *   Info if we are only displaying the configuration
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderFooter(array $caller, Model $model, bool $configOnly = false): string;

    /**
     * Simply outputs the css and js stuff.
     *
     * @param string $css
     *   The CSS, rendered into the template.
     * @param string $javascript
     *   The JS, rendered into the template.
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderCssJs(string $css, string $javascript): string;

    /**
     * Renders an expandable child with a callback in the middle.
     *
     * @param Model $model
     *   The model, which hosts all the data we need.
     * @param bool $isExpanded
     *   Is this one expanded from the beginning?
     *   TRUE when we render the settings menu only.
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderExpandableChild(Model $model, bool $isExpanded = false): string;

    /**
     * Renders a simple editable child node.
     *
     * @param Model $model
     *   The model, which hosts all the data we need.
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderSingleEditableChild(Model $model): string;

    /**
     * Renders a simple button.
     *
     * @param Model $model
     *   The model, which hosts all the data we need.
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderButton(Model $model): string;

    /**
     * Renders the second part of the fatal error handler.
     *
     * @param string $errstr
     *   The string from the error.
     * @param string $errfile
     *   The file where the error occurred.
     * @param int $errline
     *   The line number where the error occurred.
     *
     * @return string
     *   The template file, with all markers replaced.
     */
    public function renderFatalMain(string $errstr, string $errfile, int $errline): string;

    /**
     * Renders the header part of the fatal error handler.
     *
     * @param string $cssJs
     *   The css and js from the template.
     * @param string $errorType
     *   The error type, for the big, fat headline.
     *
     * @return string
     *   The template file, with all markers replaced.
     */
    public function renderFatalHeader(string $cssJs, string $errorType): string;

    /**
     * Renders all internal messages.
     *
     * @param \Brainworxx\Krexx\View\Message[] $messages
     *   The current messages.
     *
     * @return string
     *   The generates html output
     */
    public function renderMessages(array $messages): string;

    /**
     * Renders the line of the sourcecode, from where the backtrace is coming.
     *
     * @param string $className
     *   The class name where the sourcecode is from.
     * @param int $lineNo
     *   The line number from the file.
     * @param string $sourceCode
     *   Part of the sourcecode, where the backtrace is coming from.
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderBacktraceSourceLine(string $className, int $lineNo, string $sourceCode): string;

    /**
     * Renders the hr.
     *
     * @return string
     *   The generated markup from the template file.
     */
    public function renderSingeChildHr(): string;

    /**
     * Render a simple line break.
     *
     * @return string
     */
    public function renderLinebreak(): string;
}
