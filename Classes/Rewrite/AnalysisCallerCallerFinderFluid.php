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

use Brainworxx\Krexx\Analyse\Caller\AbstractCaller;

class Tx_Includekrexx_Rewrite_AnalysisCallerCallerFinderFluid extends AbstractCaller
{
    /**
     * {@inheritdoc}
     */
    public function findCaller()
    {
        if (version_compare(TYPO3_version, '8.0', '<')) {
            // The hacky 4.6 till 7.6 path finder.
            $path = $this->findCallerByReflection();
        } else {
            // The new 8.0 and greater path finder
            $path = $this->findPathByFramework();
        }

        return array(
            'file' => $path,
            // I have no idae how to get the actual line from the view.
            'line' => 'n/a',
            // Without line, there is no real chance to get the varname.
            // Not to mention, that we may actually face something like
            // this as a 'varname':
            // {some:viewHelper(key: '{value}) -> some:anotherHelper(foo: 'bar'))
            // This would totally complicate things unnecessary.
            'varname' => 'fluidvar',
        );
    }

    /**
     * Find the path the hacky way, by using reflections to call a protected
     * method.
     *
     * @return string
     */
    protected function findCallerByReflection()
    {
        // Using a reflection to get access to date feels somewhat dirty.
        // Otoh, kreXX does the same thing all the time.
        // If anybody knows how to get the following data from the viewhelper
        // in a more clean way:
        // - Path and filename
        // - Line number
        // - Variable name
        // please add an issue at our tracker here:
        // https://github.com/brainworxx/kreXX-TYPO3-Extension/issues
        $fluidView = $this->pool->registry->get('FluidView');
        $viewReflection = new \ReflectionClass($fluidView);
        if ($viewReflection->hasMethod('getTemplatePathAndFilename')) {
            // The new way
            // Get it directly
            $templatePathAndFilenameReflection = $viewReflection->getMethod('getTemplatePathAndFilename');
            $templatePathAndFilenameReflection->setAccessible(true);
            $path = $templatePathAndFilenameReflection->invoke($fluidView);
        } else {
            // I have no idea how to get it from 4.5.
            // The method 'getTemplatePathAndFilename' was introduced in 4.6
            // and I am not going to copy it here.  :-/
            $path = 'calling template path not available';
        }

        return $path;
    }

    /**
     * Find the path via the fluid framework.
     *
     * @return string
     */
    protected function findPathByFramework()
    {
        $renderingContext = $this->pool->registry->get('FluidView')->getRenderingContext();
        $controllerName = $renderingContext->getControllerName();
        $actionName = $renderingContext->getControllerAction();
        $format = $renderingContext->getTemplatePaths()->getFormat();

        $path = $renderingContext->getTemplatePaths()
            ->resolveTemplateFileForControllerAndActionAndFormat($controllerName, $actionName, $format);

        return $path;
    }
}