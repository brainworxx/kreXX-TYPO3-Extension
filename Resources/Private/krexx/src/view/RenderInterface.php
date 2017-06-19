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

namespace Brainworxx\Krexx\View;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Interface for the render class, defining all public methods.
 *
 * @package Brainworxx\Krexx\View
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
     * Renders a "single child", containing a single not expandable value.
     *
     * Depending on how many characters are in there, it may be toggleable.
     *
     * @param Model $model
     *   The model, which hosts all the data we need.
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderSingleChild(Model $model);

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
    public function renderRecursion(Model $model);

    /**
     * Renders the kreXX header.
     *
     * @param string $doctype
     *   The doctype from the configuration.
     * @param string $headline
     *   The headline, what is actually analysed.
     * @param string $cssJs
     *   The CSS and JS in a string.
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderHeader($doctype, $headline, $cssJs);

    /**
     * Renders the kreXX footer.
     *
     * @param array $caller
     *   The caller of kreXX.
     * @param string $configOutput
     *   The pregenerated configuration markup.
     * @param boolean $configOnly
     *   Info if we are only displaying the configuration
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderFooter($caller, $configOutput, $configOnly = false);

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
    public function renderCssJs(&$css, &$javascript);

    /**
     * Renders a expandable child with a callback in the middle.
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
    public function renderExpandableChild(Model $model, $isExpanded = false);

    /**
     * Renders a simple editable child node.
     *
     * @param Model $model
     *   The model, which hosts all the data we need.
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderSingleEditableChild(Model $model);

    /**
     * Renders a simple button.
     *
     * @param Model $model
     *   The model, which hosts all the data we need.
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderButton(Model $model);

    /**
     * Renders the second part of the fatal error handler.
     *
     * @param string $type
     *   The type of the error (should always be fatal).
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
    public function renderFatalMain($type, $errstr, $errfile, $errline);

    /**
     * Renders the header part of the fatal error handler.
     *
     * @param string $cssJs
     *   The css and js from the template.
     * @param string $doctype
     *   The configured doctype.
     *
     * @return string
     *   The template file, with all markers replaced.
     */
    public function renderFatalHeader($cssJs, $doctype);

    /**
     * Renders all internal messages.
     *
     * @param array $messages
     *   The current messages.
     *
     * @return string
     *   The generates html output
     */
    public function renderMessages(array $messages);

    /**
     * Renders the line of the sourcecode, from where the backtrace is coming.
     *
     * @param string $className
     *   The class name where the sourcecode is from.
     * @param string $lineNo
     *   The kine number from the file.
     * @param string $sourceCode
     *   Part of the sourcecode, where the backtrace is coming from.
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderBacktraceSourceLine($className, $lineNo, $sourceCode);

    /**
     * Renders the hr.
     *
     * @return string
     *   The generated markup from the template file.
     */
    public function renderSingeChildHr();

    /**
     * Gets a list of all available skins for the frontend config.
     *
     * @return array
     *   An array with the skinnames.
     */
    public function getSkinList();
}
