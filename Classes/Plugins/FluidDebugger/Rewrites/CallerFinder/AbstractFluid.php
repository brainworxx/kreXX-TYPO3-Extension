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

namespace Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder;

use Brainworxx\Includekrexx\Bootstrap\Bootstrap;
use Brainworxx\Krexx\Analyse\Caller\AbstractCaller;
use Brainworxx\Krexx\Analyse\Caller\BacktraceConstInterface;
use Brainworxx\Krexx\Service\Factory\Pool;
use ReflectionException;

/**
 * Contains all methods, that are used by the fluid caller finder classes.
 *
 * @package Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder
 */
abstract class AbstractFluid extends AbstractCaller implements BacktraceConstInterface
{
    const FLUID_VARIABLE = 'fluidvar';
    const FLUID_NOT_AVAILABLE = 'n/a';

    /**
     * @var \TYPO3\CMS\Fluid\View\AbstractTemplateView|\TYPO3Fluid\Fluid\View\ViewInterface
     */
    protected $view;

    /**
     * A reflection of the view that we are currently rendering.
     *
     * @var \ReflectionClass
     */
    protected $viewReflection;

    /**
     * What we are currently rendering.
     *
     * 1 = template file
     * 2 = partial file
     * 3 = layout file
     *
     * @var integer
     */
    protected $renderingType;

    /**
     * Have we encountered an error during our initialization phase?
     *
     * @var bool
     */
    protected $error = false;

    /**
     * The rendering context of the template.
     *
     * @var \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface|\TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface
     */
    protected $renderingContext;

    /**
     * @var mixed
     */
    protected $parsedTemplate;

    /**
     * The line in the template file. that we were able to resolve.
     *
     * @var string
     */
    protected $line = self::FLUID_NOT_AVAILABLE;

    /**
     * The variable name, that we were able to resolve.
     *
     * @var string
     */
    protected $varname;

    /**
     * The regex should look something like this:
     */
     // \s*<krexx:debug value="{(.*)}"\/>\s*/u
    /**
     * Meh, the regex un-comments the doc-comment.
     *
     * {@inheritdoc}
     */
    protected $callPattern = [
        ['<krexx:debug>{', '}<\/krexx:debug>'],
        ['<krexx:debug value="{', '}" \/>'],
        ['<krexx:debug value="{', '}"\/>'],
        ['<krexx:log>{', '}<\/krexx:log>'],
        ['<krexx:log value="{', '}" \/>'],
        ['<krexx:log value="{', '}"\/>']
    ];

    /**
     * Trying to get our stuff together.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        parent::__construct($pool);

        // Handling the injections.
        $this->varname = static::FLUID_VARIABLE;
        $this->view = $this->pool->registry->get('view');
        $this->viewReflection = $this->pool->registry->get('viewReflection');
        $this->renderingContext = $this->pool->registry->get('renderingContext');

        // Assign the parsed template and the render type.
        $this->assignParsedTemplateRenderType();
    }

    /**
     * Retrieves the rendering stack straight out of the view.
     */
    protected function assignParsedTemplateRenderType()
    {
        if ($this->viewReflection->hasProperty('renderingStack') === false) {
            // No rendering stack, no template file  :-(
            $this->error = true;
            return;
        }

        try {
            $renderingStackRef = $this->viewReflection->getProperty('renderingStack');
            $renderingStackRef->setAccessible(true);
            $renderingStack = $renderingStackRef->getValue($this->view);
        } catch (ReflectionException $e) {
            $this->error = true;
            return;
        }

        $pos = count($renderingStack) - 1;
        if (
            isset($renderingStack[$pos]) &&
            isset($renderingStack[$pos]['parsedTemplate']) &&
            isset($renderingStack[$pos]['type'])
        ) {
            $this->parsedTemplate = $renderingStack[$pos]['parsedTemplate'];
            $this->renderingType = $renderingStack[$pos]['type'];
            return;
        }

        // There was a rendering stack, but we were unable to get anything out of it.
        $this->error = true;
    }

    /**
     * {@inheritdoc}
     */
    public function findCaller(string $headline, $data): array
    {
        // Did we get our stuff together so far?
        if ($this->error === true) {
            // Something went wrong!
            return [
                static::TRACE_FILE => static::FLUID_NOT_AVAILABLE,
                static::TRACE_LINE => static::FLUID_NOT_AVAILABLE,
                static::TRACE_VARNAME => static::FLUID_VARIABLE,
                static::TRACE_TYPE => $this->getType('Fluid analysis', static::FLUID_VARIABLE, $data),
                static::TRACE_DATE => date('d-m-Y H:i:s', time()),
                static::TRACE_URL => $this->getCurrentUrl(),
            ];
        }

        return $this->resolveCallerArrayByRenderType($data);
    }

    /**
     * Resolving the caller array, depending on the render type:
     *   - template
     *   - partial
     *   - layout
     *
     * @param mixed $data
     *   The variable that was passes to the viewHelper.
     *
     * @return array
     *   The caller array.
     */
    protected function resolveCallerArrayByRenderType($data): array
    {
        switch ($this->renderingType) {
            case 1:
                // RENDERING_TEMPLATE = 1
                $path = $this->getTemplatePath();
                break;
            case 2:
                // RENDERING_PARTIAL = 2
                $path = $this->getPartialPath();
                break;
            case 3:
                // RENDERING_LAYOUT = 3
                $path = $this->getLayoutPath();
                break;
            default:
                $path = static::FLUID_NOT_AVAILABLE;
        }

        // Trying to resolve the line as well as the variable name, if possible.
        $this->resolveVarname($path);

         return [
             static::TRACE_FILE => $this->pool->fileService->filterFilePath($path),
             static::TRACE_LINE => $this->line,
             static::TRACE_VARNAME => $this->varname,
             static::TRACE_TYPE => $this->getType('Fluid analysis', $this->varname, $data),
             static::TRACE_DATE => date('d-m-Y H:i:s', time()),
             static::TRACE_URL => $this->getCurrentUrl(),
         ];
    }

    /**
     * Get the analysis type for the metadata and the page title.
     *
     * @param string $headline
     *   The headline from the call. We will use this one, if not empty.
     * @param string $varname
     *   The name of the variable that we were able to determine.
     * @param mixed $data
     *   The variable tht we are analysing.
     *
     * @return string
     *   The analysis type.
     */
    protected function getType(string $headline, string $varname, $data): string
    {
        if (is_object($data) === true) {
            $type = get_class($data);
        } else {
            $type = gettype($data);
        }
        return $headline . ' of ' . $varname . ', ' . $type;
    }

    /**
     * Resolve the variable name and the line number of the
     * debug call from fluid.
     *
     * @param string $filePath
     *   The path to the template file we need to parse.
     */
    protected function resolveVarname(string $filePath)
    {
        // Retrieve the call from the sourcecode file.
        if ($this->pool->fileService->fileIsReadable($filePath) === false) {
            // File is not readable. We can not do this.
            // Fallback to the standard values in the class header.
            return ;
        }

        // Define the fallback.
        $this->varname = static::FLUID_VARIABLE;

        $fileContent = $this->pool->fileService->getFileContents($filePath, false);
        foreach ($this->callPattern as $funcname) {
            // This little baby tries to resolve everything inside the
            // brackets of the kreXX call.
            preg_match_all('/\s*' . $funcname[0] . '(.*)' . $funcname[1] . '\s*/u', $fileContent, $name);

            // Found something!
            // Check if we already have more than one.
            if (isset($name[1][0]) === true && count($name[1]) === 1) {
                $this->varname =  $this->checkForComplicatedStuff(
                    $this->pool->encodingService->encodeString(trim($name[1][0], " \t\n\r\0\x0B"))
                );
                return;
            }
        }
    }

    /**
     * Check, if we have a varname, at all, or are looking at something more
     * complicated like: {val1: 'something', val2: 'something else'}
     *
     * . . . or even worse stuff . . .
     * If we find such a thing, we need to create a variable in the source
     * gen, via <variable.set /> first, to access it.
     *
     * @param string $varname
     *   The resolved varname, so far.
     *
     * @return string
     *   The variable name, which may or may not have changed.
     */
    protected function checkForComplicatedStuff(string $varname): string
    {
        // We check for : and -> to see if we are facing some inline stuff
        if (strpos($varname, ':') !== false || strpos($varname, '->') !== false) {
            if (version_compare(Bootstrap::getTypo3Version(), '8.6', '>=')) {
                // Variable set is native to 8.6 and beyond.
                $code = '<f:variable value="{' . $varname . '}" name="fluidvar" /> {';
            } else {
                $code = '<v:variable.set value="{' . $varname . '}" name="fluidvar" /> {';
            }
            $this->pool->codegenHandler->setComplicatedWrapperLeft($code);

            $varname = static::FLUID_VARIABLE;
        }

        return $varname;
    }

    /**
     * Getting the template file path by using reflections.
     *
     * @return string
     *   The template filename and it's path.
     */
    abstract protected function getTemplatePath(): string;

    /**
     * Get the current used layout file from the fluid framework.
     *
     * @return string
     *   The layout filename and it's path.
     */
    abstract protected function getLayoutPath(): string;

    /**
     * Try to figure out the currently rendered partial file from somewhere deep
     * within the fluid framework. So dirty.
     *
     * @return string
     *   The partial filename and it's path.
     */
    abstract protected function getPartialPath(): string;
}
