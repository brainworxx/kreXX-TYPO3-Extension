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

namespace Brainworxx\Krexx\View\Skins\SmokyGrey;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\View\Skins\Hans\ConstInterface;
use Brainworxx\Krexx\View\Skins\RenderSmokyGrey;

trait ExpandableChild
{

    /**
     * The array we use for the string replace.
     *
     * @var array
     */
    protected $renderExpandableChildSgArray = [
        ConstInterface::MARKER_NAME,
        ConstInterface::MARKER_TYPE,
        ConstInterface::MARKER_K_TYPE,
        ConstInterface::MARKER_NORMAL,
        ConstInterface::MARKER_CONNECTOR_RIGHT,
        ConstInterface::MARKER_GEN_SOURCE,
        ConstInterface::MARKER_NEST,
        ConstInterface::MARKER_SOURCE_BUTTON,
        ConstInterface::MARKER_CODE_WRAPPER_LEFT,
        ConstInterface::MARKER_CODE_WRAPPER_RIGHT,
        RenderSmokyGrey::MARKER_ADDITIONAL_JSON,
    ];

    /**
     * {@inheritDoc}
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
            $this->renderExpandableChildSgArray,
            [
                $model->getName(),
                $model->getType(),
                $this->retrieveTypeClasses($model),
                $model->getNormal(),
                $this->renderConnectorRight($model->getConnectorRight(128)),
                $this->generateDataAttribute(static::DATA_ATTRIBUTE_SOURCE, $gencode),
                $this->pool->chunks->chunkMe($this->renderNest($model, false)),
                $this->renderSourceButtonSg($gencode, $model),
                $this->generateDataAttribute(
                    static::DATA_ATTRIBUTE_WRAPPER_L,
                    $this->pool->codegenHandler->generateWrapperLeft()
                ),
                $this->generateDataAttribute(
                    static::DATA_ATTRIBUTE_WRAPPER_R,
                    $this->pool->codegenHandler->generateWrapperRight()
                ),
                $this->generateDataAttribute(static::DATA_ATTRIBUTE_JSON, $this->encodeJson($model->getJson())),
            ],
            $this->getTemplateFileContent(static::FILE_EX_CHILD_NORMAL)
        );
    }

    /**
     * Render the source button.
     *
     * @param string $gencode
     *   The generated source.
     * @param Model $model
     *   The model.
     *
     * @return string
     *   Th rendered HTML.
     */
    protected function renderSourceButtonSg($gencode, Model $model)
    {
        if ($gencode === ';stop;' ||
            empty($gencode) === true ||
            $this->pool->codegenHandler->getAllowCodegen() === false
        ) {
            // Remove the button marker, because here is nothing to add.
            return '';
        } else {
            // Add the button.
            return str_replace(
                static::MARKER_LANGUAGE,
                $model->getConnectorLanguage(),
                $this->getTemplateFileContent(static::FILE_SOURCE_BUTTON)
            );
        }
    }
}
