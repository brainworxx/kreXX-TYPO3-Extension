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

namespace Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder;

use ReflectionException;

/**
 * Trying to coax the current template/layout/partial file out of the fluid framework.
 */
class FluidOld extends AbstractFluid
{

    /**
     * {@inheritdoc}
     */
    protected function getTemplatePath(): string
    {
        $result = static::FLUID_NOT_AVAILABLE;

        if ($this->viewReflection->hasMethod('getTemplatePathAndFilename')) {
            try {
                $templatePathRef = $this->viewReflection->getMethod('getTemplatePathAndFilename');
                $templatePathRef->setAccessible(true);
                $result = $templatePathRef->invoke($this->view, null);
            } catch (ReflectionException $e) {
                // Do nothing.
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getLayoutPath(): string
    {
        $result = static::FLUID_NOT_AVAILABLE;

        if ($this->viewReflection->hasMethod('getLayoutPathAndFilename')) {
            $fileName = $this->parsedTemplate->getLayoutName($this->renderingContext);
            try {
                $layoutPathRef = $this->viewReflection->getMethod('getLayoutPathAndFilename');
                $layoutPathRef->setAccessible(true);
                $result = $layoutPathRef->invoke($this->view, $fileName);
            } catch (ReflectionException $e) {
                // Do nothing.
            }
        }

        return $result;
    }

    /**
     * Try to figure out the currently rendered partial file from somewhere deep
     * within the fluid framework. So dirty.
     *
     * @return string
     *   The partial filename and it's path.
     */
    protected function getPartialPath(): string
    {
        // Getting the hash of a partial file from the classname of the compiled
        // render class. Oh boy, this must be the most hacky thing I have ever
        // written.
        $identifier = explode('_', get_class($this->parsedTemplate));
        $hash = $identifier[count($identifier) - 1];

        try {
            $partialIdentCacheRef = $this->viewReflection->getProperty('partialIdentifierCache');
            $partialIdentCacheRef->setAccessible(true);
            $partialIdentifierCache = $partialIdentCacheRef->getValue($this->view);

            foreach ($partialIdentifierCache as $fileName => $realIdentifier) {
                if (strpos($realIdentifier, $hash) !== false) {
                    // We've got our real identifier, yay. :-|
                    $getPartialPathFilenameRef = $this->viewReflection
                        ->getMethod('getPartialPathAndFilename');
                    $getPartialPathFilenameRef->setAccessible(true);
                    return $getPartialPathFilenameRef->invoke($this->view, $fileName);
                }
            }
        } catch (ReflectionException $e) {
            // Do nothing. We return an empty result later.
        }

        return static::FLUID_NOT_AVAILABLE;
    }
}
