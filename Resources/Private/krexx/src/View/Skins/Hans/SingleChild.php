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

trait SingleChild
{
    /**
     * The array we use for the string replace.
     *
     * @var array
     */
    protected $renderSingleChildArray = [
        ConstInterface::MARKER_GEN_SOURCE,
        ConstInterface::MARKER_SOURCE_BUTTON,
        ConstInterface::MARKER_EXPAND,
        ConstInterface::MARKER_CALLABLE,
        ConstInterface::MARKER_EXTRA,
        ConstInterface::MARKER_NAME,
        ConstInterface::MARKER_TYPE,
        ConstInterface::MARKER_TYPE_CLASSES,
        ConstInterface::MARKER_NORMAL,
        ConstInterface::MARKER_CONNECTOR_LEFT,
        ConstInterface::MARKER_CONNECTOR_RIGHT,
        ConstInterface::MARKER_CODE_WRAPPER_LEFT,
        ConstInterface::MARKER_CODE_WRAPPER_RIGHT,
        ConstInterface::MARKER_HELP,
    ];

    /**
     * Renders a "single child", containing a single not expandable value.
     *
     * Depending on how many characters are in there, it may be toggleable.
     *
     * @param Model $model
     *   The model, which hosts all the data we need.
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderSingleChild(Model $model)
    {
        // This one is a little bit more complicated than the others,
        // because it assembles some partials and stitches them together.
        $partExpand = '';
        if ($model->getHasExtra() === true) {
            // We have a lot of text, so we render this one expandable (yellow box).
            $partExpand = 'kexpand';
        }

        // Generating our code.
        $gensource = $this->pool->codegenHandler->generateSource($model);

        // Stitching it together.
        return str_replace(
            $this->renderSingleChildArray,
            [
                $this->generateDataAttribute(static::DATA_ATTRIBUTE_SOURCE, $gensource),
                $this->renderSourceButton($gensource),
                $partExpand,
                $this->renderCallable($model),
                $this->renderExtra($model),
                $model->getName(),
                $model->getType(),
                $this->retrieveTypeClasses($model),
                $model->getNormal(),
                $this->renderConnectorLeft($model->getConnectorLeft()),
                $this->renderConnectorRight($model->getConnectorRight()),
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
            $this->getTemplateFileContent(static::FILE_SI_CHILD)
        );
    }

    /**
     * Render the source button, if there is any source to add.
     *
     * @param string $gensource
     *   The source we want to display.
     *
     * @return string
     *   The rendered HTML output.
     */
    protected function renderSourceButton($gensource)
    {
        if (empty($gensource) === true || $this->pool->codegenHandler->getAllowCodegen() === false) {
             // No source button for you!
            return '';
        }

        return $this->getTemplateFileContent(static::FILE_SOURCE_BUTTON);
    }

    /**
     * Render the 'extra' part of the singe child output.
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model.
     *
     * @return string
     *   The rendered HTML output.
     */
    protected function renderExtra(Model $model)
    {
        if ($model->getHasExtra() === true) {
            return str_replace(
                static::MARKER_DATA,
                $model->getData(),
                $this->getTemplateFileContent(static::FILE_SI_CHILD_EX)
            );
        }

        return '';
    }

    /**
     * Generate the HTML to display the callable info.
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model.
     *
     * @return string
     *   The rendered HTML
     */
    protected function renderCallable(Model $model)
    {
        if ($model->getIsCallback() === true) {
            // Add callable partial.
            return str_replace(
                static::MARKER_NORMAL,
                $model->getNormal(),
                $this->getTemplateFileContent(static::FILE_SI_CHILD_CALL)
            );
        }

        return '';
    }
}
