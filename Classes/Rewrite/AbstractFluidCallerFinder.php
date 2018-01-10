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
 * Contains all methods, that are used by the fluid caller finder classes.
 */
abstract class Tx_Includekrexx_Rewrite_AbstractFluidCallerFinder  extends AbstractCaller
{
    /**
     * @var \TYPO3Fluid\Fluid\View\ViewInterface
     */
    protected $view;

    /**
     * A reflection of the view that we are currentlx rendering.
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
     * The kreXX debug viewhelper class
     *
     * @var Tx_Includekrexx_ViewHelpers_DebugViewHelper
     */
    protected $debugViewhelper;

    /**
     * Trying to get our stuff together.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(\Brainworxx\Krexx\Service\Factory\Pool $pool)
    {
        parent::__construct($pool);

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
            }
            // No rendering stack, no template file  :-(
            $this->error = true;
            return false;
        }

        // No rendering stack, no template file  :-(
        $this->error = true;
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function findCaller($headline, $data)
    {
        // Did we get our stuff together so far?
        if ($this->error) {
            // Something went wrong!
            return array(
                'file' => 'n/a',
                'line' => 'n/a',
                'varname' => 'fluidvar',
                'type' => $this->getType('Fluid analysis', 'fluidvar', $data),
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

        // RENDERING_LAYOUT = 3
        if ($this->renderingType === 3) {
            $path = $this->getLayoutPath();
        }

         return array(
             'file' => $this->pool->fileService->filterFilePath($path),
             'line' => 'n/a',
             'varname' => 'fluidvar',
             'type' => $this->getType('Fluid analysis', 'fluidvar', $data),
         );
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
    protected function getType($headline, $varname, $data)
    {

        if (is_object($data) === true) {
            $type = get_class($data);
        } else {
            $type = gettype($data);
        }
        return $headline . ' of ' . $varname . ', ' . $type;
    }

    /**
     * Getting the template file path by using reflections.
     *
     * @return string
     *   The template filename and it's path.
     */
    abstract protected function getTemplatePath();

    /**
     * Get the current used layout file from the fluid framework.
     *
     * @return string
     *   The layout filename and it's path.
     */
    abstract protected function getLayoutPath();

    /**
     * Try to figure out the currently rendered partial file from somewhere deep
     * within the fluid framework. So dirty.
     *
     * @return string
     *   The partial filename and it's path.
     */
    abstract protected function getPartialPath();
}