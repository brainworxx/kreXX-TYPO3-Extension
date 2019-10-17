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

use ReflectionException;

/**
 * Trying to coax the current template/layout/partial file out of the fluid framework.
 *
 * @package Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder
 */
class FluidOld extends AbstractFluid
{

    /**
     * {@inheritdoc}
     */
    protected function getTemplatePath()
    {
        $result = 'n/a';

        if ($this->viewReflection->hasMethod('getTemplatePathAndFilename')) {
            try {
                $templatePathAndFilenameReflection = $this->viewReflection->getMethod('getTemplatePathAndFilename');
                $templatePathAndFilenameReflection->setAccessible(true);
                $result = $templatePathAndFilenameReflection->invoke($this->view, null);
            } catch (ReflectionException $e) {
                // Do nothing.
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getLayoutPath()
    {
        $result = 'n/a';

        if ($this->viewReflection->hasMethod('getLayoutPathAndFilename')) {
            $fileName = $this->parsedTemplate->getLayoutName($this->renderingContext);
            try {
                $layoutPathAndFilenameReflection = $this->viewReflection->getMethod('getLayoutPathAndFilename');
                $layoutPathAndFilenameReflection->setAccessible(true);
                $result = $layoutPathAndFilenameReflection->invoke($this->view, $fileName);
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
    protected function getPartialPath()
    {
        $result = 'n/a';

        // Getting the hash of a partial file from the classname of the compiled
        // render class. Oh boy, this must be the most hacky thing I have ever
        // written.
        $identifier = explode('_', get_class($this->parsedTemplate));
        $hash = $identifier[count($identifier) -1];

        try {
            if ($this->viewReflection->hasProperty('partialIdentifierCache')) {
                $partialIdentifierCacheReflection = $this->viewReflection->getProperty('partialIdentifierCache');
                $partialIdentifierCacheReflection->setAccessible(true);
                $partialIdentifierCache = $partialIdentifierCacheReflection->getValue($this->view);

                foreach ($partialIdentifierCache as $fileName => $realIdentifier) {
                    if (strpos($realIdentifier, $hash) !== false) {
                        // We've got our real identifier, yay. :-|
                        $getPartialPathAndFilenameReflection = $this->viewReflection
                            ->getMethod('getPartialPathAndFilename');
                        $getPartialPathAndFilenameReflection->setAccessible(true);
                        $result = $getPartialPathAndFilenameReflection->invoke($this->view, $fileName);
                        break;
                    }
                }
            }
        } catch (ReflectionException $e) {
            // Do nothing. We return an empty result later.
        }

        return $result;
    }
}
