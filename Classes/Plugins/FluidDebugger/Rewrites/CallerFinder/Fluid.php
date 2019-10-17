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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder;

use ReflectionClass;
use ReflectionException;

/**
 * Trying to coax the current template/layout/partial file out of the fluid framework.
 *
 * @package Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder
 */
class Fluid extends AbstractFluid
{

    /**
     * {@inheritdoc}
     */
    protected function getTemplatePath()
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
    protected function getLayoutPath()
    {
        // Resolve the layout file without any hacks by the framework.
        $fileName = $this->parsedTemplate->getLayoutName($this->renderingContext);
        return $this->renderingContext->getTemplatePaths()
            ->getLayoutPathAndFilename($fileName);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPartialPath()
    {
        $result = 'n/a';
        $identifier = explode('_', $this->parsedTemplate->getIdentifier());
        // The thing here is, that this identifier is actually not the real
        // identifier. The one used to get the 'filename' looks like:
        // partial_Deep/Blargh_8dd0b1c4bad125962c0e339d5d5012c10226f77f
        // while this one looks like:
        // partial_Deep_Blargh_8dd0b1c4bad125962c0e339d5d5012c10226f77f
        // We are using the hash value, hoping not to run into any
        // collision.
        if (!isset($identifier[count($identifier) -1])) {
            // No hash, no filename!
            return $result;
        }
        $hash = $identifier[count($identifier) -1];
        $templatePath = $this->renderingContext->getTemplatePaths();

        try {
            $templatePathReflection = new ReflectionClass($templatePath);
            if ($templatePathReflection->hasProperty('resolvedIdentifiers')) {
                $resolvedIdentifiersReflection = $templatePathReflection->getProperty('resolvedIdentifiers');
                $resolvedIdentifiersReflection->setAccessible(true);
                $resolvedIdentifiers = $resolvedIdentifiersReflection->getValue($templatePath);

                if (isset($resolvedIdentifiers['partials'])) {
                    foreach ($resolvedIdentifiers['partials'] as $fileName => $realIdentifier) {
                        if (strpos($realIdentifier, $hash) !== false) {
                            // We've got our filename!
                            $result = $templatePath->getPartialPathAndFilename($fileName);
                            break;
                        }
                    }
                }
            }
        } catch (ReflectionException $e) {
            // Do nothing. We return the already existing empty result.
        }

        return $result;
    }
}
