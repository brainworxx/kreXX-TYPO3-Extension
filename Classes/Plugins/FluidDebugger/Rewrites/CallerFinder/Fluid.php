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
 *   kreXX Copyright (C) 2014-2023 Brainworxx GmbH
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

use ReflectionClass;
use ReflectionException;
use TYPO3Fluid\Fluid\View\TemplatePaths;

/**
 * Trying to coax the current template/layout/partial file out of the fluid framework.
 */
class Fluid extends AbstractFluid
{
    /**
     * {@inheritdoc}
     */
    protected function getTemplatePath(): string
    {
        $controllerName = $this->renderingContext->getControllerName();
        $actionName = $this->renderingContext->getControllerAction();
        $format = $this->renderingContext->getTemplatePaths()->getFormat();
        return $this->renderingContext->getTemplatePaths()
            ->resolveTemplateFileForControllerAndActionAndFormat($controllerName, $actionName, $format);
    }

    /**
     * {@inheritdoc}
     */
    protected function getLayoutPath(): string
    {
        // Resolve the layout file without any hacks by the framework.
        $fileName = $this->parsedTemplate->getLayoutName($this->renderingContext);
        return $this->renderingContext->getTemplatePaths()
            ->getLayoutPathAndFilename($fileName);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPartialPath(): string
    {
        $result = static::FLUID_NOT_AVAILABLE;
        $identifier = explode('_', $this->parsedTemplate->getIdentifier());
        // The thing here is, that this identifier is actually not the real
        // identifier. The one used to get the 'filename' looks like:
        // partial_Deep/Blargh_8dd0b1c4bad125962c0e339d5d5012c10226f77f
        // while this one looks like:
        // partial_Deep_Blargh_8dd0b1c4bad125962c0e339d5d5012c10226f77f
        // We are using the hash value, hoping not to run into any
        // collision.
        if (!isset($identifier[count($identifier) - 1])) {
            // No hash, no filename!
            return $result;
        }
        $hash = $identifier[count($identifier) - 1];
        $templatePath = $this->renderingContext->getTemplatePaths();

        try {
            $templatePathRef = new ReflectionClass($templatePath);
            if ($templatePathRef->hasProperty('resolvedIdentifiers')) {
                $resolvedIdentifiersRef = $templatePathRef->getProperty('resolvedIdentifiers');
                $resolvedIdentifiersRef->setAccessible(true);
                $resolvedIdentifiers = $resolvedIdentifiersRef->getValue($templatePath);
                $result = $this->resolveTemplateName($resolvedIdentifiers, $hash, $templatePath);
            }
        } catch (ReflectionException $e) {
            // Do nothing. We return the already existing empty result.
        }

        return $result;
    }

    /**
     * @param mixed $resolvedIdentifiers
     *   This should be an array, depending on how successful the script so far was.
     * @param string $hash
     *   The hash form the temp template path.
     * @param TemplatePaths $templatePath
     *   The class that does the resolving of the internal stuff.
     *
     * @return string
     *   The actual template name.
     */
    protected function resolveTemplateName($resolvedIdentifiers, string $hash, TemplatePaths $templatePath): string
    {
        if (!isset($resolvedIdentifiers['partials'])) {
            // Unable to identify the partial.
            return static::FLUID_NOT_AVAILABLE;
        }

        foreach ($resolvedIdentifiers['partials'] as $fileName => $realIdentifier) {
            if (strpos($realIdentifier, $hash) !== false) {
                // We've got our filename!
                return $templatePath->getPartialPathAndFilename($fileName);
            }
        }

        // Nothing found.
        return static::FLUID_NOT_AVAILABLE;
    }
}
