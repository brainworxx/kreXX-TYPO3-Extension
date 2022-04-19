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

namespace Brainworxx\Krexx\View\Skins\Hans;

use Brainworxx\Krexx\Analyse\Model;

/**
 * Trait SingleChild
 *
 * @deprecated
 *   Since 4.0.0. Use ExpandableChild in stead.
 *
 * @codeCoverageIgnore
 *   We will not test deprecated code.
 */
trait SingleChild
{
    /**
     * @var string[]
     */
    private $markerSingleChild = [
        '{gensource}',
        '{sourcebutton}',
        '{expand}',
        '{extra}',
        '{name}',
        '{type}',
        '{type-classes}',
        '{normal}',
        '{connectorLeft}',
        '{connectorRight}',
        '{codewrapperLeft}',
        '{codewrapperRight}',
        '{help}',
    ];

    /**
     * @var string
     */
    private $markerSingleChildCallable = '{normal}';

    /**
     * @var string
     */
    private $markerSingleChildExtra = '{data}';

    /**
     * Renders a "single child", containing a single not expandable value.
     *
     * Depending on how many characters are in there, it may be toggleable.
     *
     * @param Model $model
     *   The model, which hosts all the data we need.
     *
     * @deprecated
     *   Since 4.0.0. Use renderExpandableChild instead.
     *
     * @codeCoverageIgnore
     *   We will not test deprecated methods.
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderSingleChild(Model $model): string
    {
        // Generating our code.
        /** @var \Brainworxx\Krexx\Analyse\Code\Codegen $codegenHandler */
        $codegenHandler = $this->pool->codegenHandler;
        $generateSource = $codegenHandler->generateSource($model);
        // Stitching it together.
        return str_replace(
            $this->markerSingleChild,
            [
                $this->generateDataAttribute(static::DATA_ATTRIBUTE_SOURCE, $generateSource),
                $this->renderSourceButton($generateSource),
                $model->hasExtra() === true ?  'kexpand' :  '',
                $this->renderExtra($model),
                $model->getName(),
                $model->getType(),
                $this->retrieveTypeClasses($model),
                $model->getNormal(),
                $this->renderConnectorLeft($model->getConnectorLeft()),
                $this->renderConnectorRight($model->getConnectorRight()),
                $this->generateDataAttribute(static::DATA_ATTRIBUTE_WRAPPER_L, $codegenHandler->generateWrapperLeft()),
                $this->generateDataAttribute(static::DATA_ATTRIBUTE_WRAPPER_R, $codegenHandler->generateWrapperRight()),
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
     * @deprecated
     *   Since 4.0.0. Use renderExpandableChild instead.
     *
     * @codeCoverageIgnore
     *   We will not test deprecated methods.
     *
     * @return string
     *   The rendered HTML output.
     */
    protected function renderSourceButton(string $gensource): string
    {
        if (empty($gensource) === true || $this->pool->codegenHandler->getAllowCodegen() === false) {
             // No source button for you!
            return '';
        }

        return $this->getTemplateFileContent(static::FILE_SOURCE_BUTTON);
    }

    /**
     * Generate the HTML to display the callable info.
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model.
     *
     * @deprecated
     *   Since 4.0.0. Will be removed.
     *
     * @codeCoverageIgnore
     *   We will not test deprecated methods.
     *
     * @return string
     *   The rendered HTML
     */
    protected function renderCallable(Model $model): string
    {
        if ($model->isCallback() === true) {
            // Add callable partial.
            return str_replace(
                $this->markerSingleChildCallable,
                $model->getNormal(),
                $this->getTemplateFileContent(static::FILE_SI_CHILD_CALL)
            );
        }

        return '';
    }

    /**
     * Getter of the single child for unit tests.
     *
     * @codeCoverageIgnore
     *   We are not testing the unit tests.
     *
     * @return string[]
     *   The marker array.
     */
    public function getMarkerSingleChild(): array
    {
        return $this->markerSingleChild;
    }

    /**
     * Getter of the extra for unit tests.
     *
     * @codeCoverageIgnore
     *   We are not testing the unit tests.
     *
     * @return string[]
     *   The marker array.
     */
    public function getMarkerSingleChildExtra(): array
    {
        return [$this->markerSingleChildExtra];
    }

    /**
     * Getter of the callable for unit tests.
     *
     * @codeCoverageIgnore
     *   We are not testing the unit tests.
     *
     * @return string[]
     *   The marker array.
     */
    public function getMarkerSingleChildCallable(): array
    {
        return [$this->markerSingleChildCallable];
    }
}
