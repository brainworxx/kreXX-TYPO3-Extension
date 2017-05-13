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

/**
 * Trying to coax the current template/layout/partial file out of the fluid framework.
 */
class Tx_Includekrexx_Rewrite_AnalysisCallerCallerFinderFluidOld extends AbstractCaller
{
    protected $view;

    /**
     * Reflection of our view.
     *
     * @var \ReflectionClass
     */
    protected $viewReflection;

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
     * The kreXX debug viewhelper class
     *
     * @var Tx_Includekrexx_ViewHelpers_DebugViewHelper
     */
    protected $debugViewhelper;

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
     * Trying to get our stuff together.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(\Brainworxx\Krexx\Service\Factory\Pool $pool)
    {
        parent::__construct($pool);

        /** @var \Tx_Includekrexx_ViewHelpers_DebugViewHelper $debugViewhelper */
        $debugViewhelper = $this->pool->registry->get('DebugViewHelper');

        $this->view = $debugViewhelper->getView();
        $this->viewReflection = new \ReflectionClass($this->view);
        $this->renderingContext = $debugViewhelper->getRenderingContext();

        // Get the parsed template and the rendering type from the rendering stack
        $renderingStackEntry = $this->retrieveLastRenderingStackEntry();
        if ($renderingStackEntry !== false) {
            if (isset($renderingStackEntry['parsedTemplate'])) {
                $this->parsedTemplate = $renderingStackEntry['parsedTemplate'];
            } else {
                $this->error = true;
            }
            if (isset($renderingStackEntry['type'])) {
                $this->renderingType = $renderingStackEntry['type'];
            } else {
                $this->error = true;
            }
        }
    }

    /**
     * Retrieves the rendering stack straight out of the view.
     *
     * @return bool|array
     */
    protected function retrieveLastRenderingStackEntry()
    {
        if ($this->viewReflection->hasProperty('renderingStack')) {
            $renderingStackReflection = $this->viewReflection->getProperty('renderingStack');
            $renderingStackReflection->setAccessible(true);
            $renderingStack = $renderingStackReflection->getValue($this->view);
            $pos = count($renderingStack) -1;
            if (isset($renderingStack[$pos])) {
                return $renderingStack[$pos];
            } else {
                // No rendering stack, no template file  :-(
                $this->error = true;
                return false;
            }
        } else {
            // No rendering stack, no template file  :-(
            $this->error = true;
            return false;
        }
    }


    /**
     * {@inheritdoc}
     */
    public function findCaller()
    {
        // Did we get our stuff together so far?
        if ($this->error) {
            // Something went wrong!
            return array(
                'file' => 'n/a',
                'line' => 'n/a',
                'varname' => 'fluidvar',
            );
        }

        $path = 'n/a';

        // RENDERING_TEMPLATE = 1
        if ($this->renderingType === 1) {
            $path = $this->getTemplatePath();
        }

        // RENDERING_PARTIAL = 2
        if ($this->renderingType === 2) {
            $path = $this->getPartialPath();
        }

        // RENDERING_PARTIAL = 3
        if ($this->renderingType === 3) {
            $path = $this->getLayoutPath();
        }

         return array(
             'file' => $this->fileService->filterFilePath($path),
             'line' => 'n/a',
             'varname' => 'fluidvar',
         );
    }

    /**
     * Getting the template file path by using reflections.
     *
     * @return string
     *   The template filename and it's path.
     */
    protected function getTemplatePath()
    {
        $result = 'n/a';

        if ($this->viewReflection->hasMethod('getTemplatePathAndFilename')) {
            $templatePathAndFilenameReflection = $this->viewReflection->getMethod('getTemplatePathAndFilename');
            $templatePathAndFilenameReflection->setAccessible(true);
            $result = $templatePathAndFilenameReflection->invoke($this->view);
        }

        return $result;
    }

    /**
     * Getting the layout file path by using reflections.
     *
     * @return string
     *   The layout filename and it's path.
     */
    protected function getLayoutPath()
    {
        $result = 'n/a';

        if ($this->viewReflection->hasMethod('getLayoutPathAndFilename')) {
            $fileName = $this->parsedTemplate->getLayoutName($this->renderingContext);
            $layoutPathAndFilenameReflection = $this->viewReflection->getMethod('getLayoutPathAndFilename');
            $layoutPathAndFilenameReflection->setAccessible(true);
            $result = $layoutPathAndFilenameReflection->invoke($this->view, $fileName);
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

        if ($this->viewReflection->hasProperty('partialIdentifierCache')) {
            $partialIdentifierCacheReflection = $this->viewReflection->getProperty('partialIdentifierCache');
            $partialIdentifierCacheReflection->setAccessible(true);
            $partialIdentifierCache = $partialIdentifierCacheReflection->getValue($this->view);

            foreach ($partialIdentifierCache as $fileName => $realIdentifyer) {
                if (strpos($realIdentifyer, $hash) !== false) {
                    // We've got our real identifyer, yay. :-|
                    $getPartialPathAndFilenameReflection = $this->viewReflection
                        ->getMethod('getPartialPathAndFilename');
                    $getPartialPathAndFilenameReflection->setAccessible(true);
                    $result = $getPartialPathAndFilenameReflection->invoke($this->view, $fileName);
                    break;
                }
            }
        }
        return $result;

    }
}
