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

namespace Brainworxx\Krexx\View\Skins\Hans;

use Brainworxx\Krexx\Analyse\Model;

trait ExpandableChild
{
    /**
     * The array we use for the string replace.
     *
     * @var array
     */
    protected $renderExpandableChildHansArray = [
        ConstInterface::MARKER_NAME,
        ConstInterface::MARKER_TYPE,
        ConstInterface::MARKER_K_TYPE,
        ConstInterface::MARKER_NORMAL,
        ConstInterface::MARKER_CONNECTOR_LEFT,
        ConstInterface::MARKER_CONNECTOR_RIGHT,
        ConstInterface::MARKER_GEN_SOURCE,
        ConstInterface::MARKER_SOURCE_BUTTON,
        ConstInterface::MARKER_IS_EXPANDED,
        ConstInterface::MARKER_NEST,
        ConstInterface::MARKER_CODE_WRAPPER_LEFT,
        ConstInterface::MARKER_CODE_WRAPPER_RIGHT,
        ConstInterface::MARKER_HELP,
    ];

    /**
     * {@inheritdoc}
     */
    public function renderExpandableChild(Model $model, $isExpanded = false)
    {
        // Check for emergency break.
        if ($this->pool->emergencyHandler->checkEmergencyBreak() === true) {
            return '';
        }

        // Generating our code.
        $gencode = $this->pool->codegenHandler->generateSource($model);

        return str_replace(
            $this->renderExpandableChildHansArray,
            [
                $model->getName(),
                $model->getType(),
                $this->retrieveTypeClasses($model),
                $model->getNormal(),
                $this->renderConnectorLeft($model->getConnectorLeft()),
                $this->renderConnectorRight($model->getConnectorRight(128)),
                $this->generateDataAttribute(static::DATA_ATTRIBUTE_SOURCE, $gencode),
                $this->renderSourceButtonWithStop($gencode),
                $this->retrieveOpenedClass($isExpanded),
                $this->pool->chunks->chunkMe($this->renderNest($model, $isExpanded)),
                $this->generateDataAttribute(
                    static::DATA_ATTRIBUTE_WRAPPER_L,
                    $this->pool->codegenHandler->generateWrapperLeft()
                ),
                $this->generateDataAttribute(
                    static::DATA_ATTRIBUTE_WRAPPER_R,
                    $this->pool->codegenHandler->generateWrapperRight()
                ),
                $this->renderHelp($model),
            ],
            $this->getTemplateFileContent(static::FILE_EX_CHILD_NORMAL)
        );
    }

    /**
     * Return 'kopened', if expanded.
     *
     * @param bool $isExpanded
     *   Well? Is it?
     *
     * @return string
     *   The css class name.
     */
    protected function retrieveOpenedClass($isExpanded)
    {
        if ($isExpanded === true) {
            return 'kopened';
        }

        return '';
    }

    /**
     * Render the source button.
     *
     * @param string $gencode
     *   The generated source.
     *
     * @return string
     *   Th rendered HTML.
     */
    protected function renderSourceButtonWithStop($gencode)
    {
        if ($gencode === ';stop;' ||
            empty($gencode) === true ||
            $this->pool->codegenHandler->getAllowCodegen() === false
        ) {
            // Remove the button marker, because here is nothing to add.
            return '';
        } else {
            // Add the button.
            return $this->getTemplateFileContent(static::FILE_SOURCE_BUTTON);
        }
    }

    /**
     * Renders a nest with a anonymous function in the middle.
     *
     * @param Model $model
     *   The model, which hosts all the data we need.
     * @param bool $isExpanded
     *   The only expanded nest is the settings menu, when we render only the
     *   settings menu.
     *
     * @return string
     *   The generated markup from the template files.
     */
    protected function renderNest(Model $model, $isExpanded = false)
    {
        // Get the dom id.
        $domid = $model->getDomid();
        if ($domid !== '') {
            $domid = 'id="' . $domid . '"';
        }

        // Are we expanding this one?
        if ($isExpanded === true) {
            $style = '';
        } else {
            $style = static::STYLE_HIDDEN;
        }

        return str_replace(
            [
                static::MARKER_STYLE,
                static::MARKER_MAIN_FUNCTION,
                static::MARKER_DOM_ID,
            ],
            [
                $style,
                $model->renderMe(),
                $domid,
            ],
            $this->getTemplateFileContent(static::FILE_NEST)
        );
    }
}
