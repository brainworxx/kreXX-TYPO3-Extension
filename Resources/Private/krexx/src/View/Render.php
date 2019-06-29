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

namespace Brainworxx\Krexx\View;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Config\Fallback;

/**
 * Render methods.
 *
 * It get extended by the render class of the used skin, so every skin can do
 * some special stuff.
 *
 * @package Brainworxx\Krexx\View
 */
class Render extends AbstractRender
{

    const MARKER_NAME = '{name}';
    const MARKER_NORMAL = '{normal}';
    const MARKER_CONNECTOR_LEFT = '{connectorLeft}';
    const MARKER_CONNECTOR_RIGHT = '{connectorRight}';
    const MARKER_GEN_SOURCE = '{gensource}';
    const MARKER_VERSION = '{version}';
    const MARKER_KREXX_COUNT = '{KrexxCount}';
    const MARKER_HEADLINE = '{headline}';
    const MARKER_CSS_JS = '{cssJs}';
    const MARKER_SEARCH = '{search}';
    const MARKER_MESSAGES = '{messages}';
    const MARKER_MESSAGE = '{message}';
    const MARKER_ENCODING = '{encoding}';
    const MARKER_CONFIG_INFO = '{configInfo}';
    const MARKER_CALLER = '{caller}';
    const MARKER_CSS = '{css}';
    const MARKER_JS = '{js}';
    const MARKER_DATA = '{data}';
    const MARKER_SOURCE_BUTTON = '{sourcebutton}';
    const MARKER_EXPAND = '{expand}';
    const MARKER_CALLABLE = '{callable}';
    const MARKER_EXTRA = '{extra}';
    const MARKER_TYPE = '{type}';
    const MARKER_TYPE_CLASSES = '{type-classes}';
    const MARKER_CODE_WRAPPER_LEFT = '{codewrapperLeft}';
    const MARKER_CODE_WRAPPER_RIGHT = '{codewrapperRight}';
    const MARKER_K_TYPE = '{ktype}';
    const MARKER_IS_EXPANDED = '{isExpanded}';
    const MARKER_NEST = '{nest}';
    const MARKER_ID = '{id}';
    const MARKER_VALUE = '{value}';
    const MARKER_TEXT = '{text}';
    const MARKER_SELECTED = '{selected}';
    const MARKER_SOURCE = '{source}';
    const MARKER_OPTIONS = '{options}';
    const MARKER_CLASS = '{class}';
    const MARKER_ERROR_STRING = '{errstr}';
    const MARKER_FILE = '{file}';
    const MARKER_LINE = '{line}';
    const MARKER_CLASS_NAME = '{className}';
    const MARKER_LINE_NO = '{lineNo}';
    const MARKER_SOURCE_CODE = '{sourceCode}';
    const MARKER_PLUGINS = '{plugins}';

    /**
     * {@inheritdoc}
     */
    public function renderRecursion(Model $model)
    {
        return str_replace(
            [
                static::MARKER_NAME,
                static::MARKER_DOM_ID,
                static::MARKER_NORMAL,
                static::MARKER_CONNECTOR_LEFT,
                static::MARKER_CONNECTOR_RIGHT,
                static::MARKER_GEN_SOURCE,
                static::MARKER_HELP,
            ],
            [
                $model->getName(),
                $model->getDomid(),
                $model->getNormal(),
                $this->renderConnectorLeft($model->getConnectorLeft()),
                $this->renderConnectorRight($model->getConnectorRight()),
                $this->generateDataAttribute(
                    static::DATA_ATTRIBUTE_SOURCE,
                    $this->pool->codegenHandler->generateSource($model)
                ),
                $this->renderHelp($model),
            ],
            $this->getTemplateFileContent(static::FILE_RECURSION)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renderHeader($headline, $cssJs)
    {
        return str_replace(
            [
                static::MARKER_VERSION,
                static::MARKER_KREXX_COUNT,
                static::MARKER_HEADLINE,
                static::MARKER_CSS_JS,
                static::MARKER_KREXX_ID,
                static::MARKER_SEARCH,
                static::MARKER_MESSAGES,
                static::MARKER_ENCODING,
            ],
            [
                $this->pool->config->version,
                $this->pool->emergencyHandler->getKrexxCount(),
                $headline,
                $cssJs,
                $this->pool->recursionHandler->getMarker(),
                $this->renderSearch(),
                $this->pool->messages->outputMessages(),
                $this->pool->chunks->getOfficialEncoding(),
            ],
            $this->getTemplateFileContent(static::FILE_HEADER)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renderFooter(array $caller, Model $model, $configOnly = false)
    {
        if (isset($caller[static::TRACE_FILE]) === true) {
            $callerString = $this->renderCaller($caller);
        } else {
             // When we have no caller, we will not render it.
            $callerString = '';
        }

        return str_replace(
            [
                static::MARKER_CONFIG_INFO,
                static::MARKER_CALLER,
                static::MARKER_PLUGINS,
            ],
            [
                $this->renderExpandableChild($model, $configOnly),
                $callerString,
                $this->renderPluginList(),
            ],
            $this->getTemplateFileContent(static::FILE_FOOTER)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renderCssJs(&$css, &$javascript)
    {
        return str_replace(
            [static::MARKER_CSS, static::MARKER_JS],
            [$css, $javascript],
            $this->getTemplateFileContent(static::FILE_CSSJS)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renderSingleChild(Model $model)
    {
        // This one is a little bit more complicated than the others,
        // because it assembles some partials and stitches them together.
        $partExpand = '';
        $partCallable = '';
        $partExtra = '';

        if ($model->getHasExtra() === true) {
            // We have a lot of text, so we render this one expandable (yellow box).
            $partExpand = 'kexpand';
            // Add the yellow box for large output text.
            $partExtra = str_replace(
                static::MARKER_DATA,
                $model->getData(),
                $this->getTemplateFileContent(static::FILE_SI_CHILD_EX)
            );
        }

        if ($model->getIsCallback() === true) {
            // Add callable partial.
            $partCallable = str_replace(
                static::MARKER_NORMAL,
                $model->getNormal(),
                $this->getTemplateFileContent(static::FILE_SI_CHILD_CALL)
            );
        }

        // Stitching the classes together, depending on the types.
        $typeClasses = '';
        foreach (explode(' ', $model->getType()) as $typeClass) {
            $typeClasses .= 'k' . $typeClass . ' ';
        }

        // Generating our code and adding the Codegen button, if there is something
        // to generate.
        $gensource = $this->pool->codegenHandler->generateSource($model);

        if (empty($gensource) === true || $this->pool->codegenHandler->getAllowCodegen() === false) {
            // Remove the markers, because here is nothing to add.
            $sourcebutton = '';
        } else {
            // We add the buttton and the code.
            $sourcebutton = $this->getTemplateFileContent(static::FILE_SOURCE_BUTTON);
        }

        // Stitching it together.
        return str_replace(
            [
                static::MARKER_GEN_SOURCE,
                static::MARKER_SOURCE_BUTTON,
                static::MARKER_EXPAND,
                static::MARKER_CALLABLE,
                static::MARKER_EXTRA,
                static::MARKER_NAME,
                static::MARKER_TYPE,
                static::MARKER_TYPE_CLASSES,
                static::MARKER_NORMAL,
                static::MARKER_CONNECTOR_LEFT,
                static::MARKER_CONNECTOR_RIGHT,
                static::MARKER_CODE_WRAPPER_LEFT,
                static::MARKER_CODE_WRAPPER_RIGHT,
                static::MARKER_HELP,
            ],
            [
                $this->generateDataAttribute(static::DATA_ATTRIBUTE_SOURCE, $gensource),
                $sourcebutton,
                $partExpand,
                $partCallable,
                $partExtra,
                $model->getName(),
                $model->getType(),
                $typeClasses,
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
     * {@inheritdoc}
     */
    public function renderExpandableChild(Model $model, $isExpanded = false)
    {
        // Check for emergency break.
        if ($this->pool->emergencyHandler->checkEmergencyBreak() === true) {
            return '';
        }

        // Explode the type to get the class names right.
        $cssType = '';
        foreach (explode(' ', $model->getType()) as $singleType) {
            $cssType .= ' k' . $singleType;
        }

        // Generating our code and adding the Codegen button, if there is
        // something to generate.
        $gencode = $this->pool->codegenHandler->generateSource($model);
        if ($gencode === ';stop;' ||
            empty($gencode) === true ||
            $this->pool->codegenHandler->getAllowCodegen() === false
        ) {
            // Remove the button marker, because here is nothing to add.
            $sourceButton = '';
        } else {
            // Add the button.
            $sourceButton = $this->getTemplateFileContent(static::FILE_SOURCE_BUTTON);
        }

        // Is it expanded?
        if ($isExpanded === true) {
            $expandedClass = 'kopened';
        } else {
            $expandedClass = '';
        }

        return str_replace(
            [
                static::MARKER_NAME,
                static::MARKER_TYPE,
                static::MARKER_K_TYPE,
                static::MARKER_NORMAL,
                static::MARKER_CONNECTOR_LEFT,
                static::MARKER_CONNECTOR_RIGHT,
                static::MARKER_GEN_SOURCE,
                static::MARKER_SOURCE_BUTTON,
                static::MARKER_IS_EXPANDED,
                static::MARKER_NEST,
                static::MARKER_CODE_WRAPPER_LEFT,
                static::MARKER_CODE_WRAPPER_RIGHT,
                static::MARKER_HELP,
            ],
            [
                $model->getName(),
                $model->getType(),
                $cssType,
                $model->getNormal(),
                $this->renderConnectorLeft($model->getConnectorLeft()),
                $this->renderConnectorRight($model->getConnectorRight(128)),
                $this->generateDataAttribute(static::DATA_ATTRIBUTE_SOURCE, $gencode),
                $sourceButton,
                $expandedClass,
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
     * {@inheritdoc}
     */
    public function renderSingleEditableChild(Model $model)
    {
        $element = str_replace(
            [
                static::MARKER_ID,
                static::MARKER_VALUE,
            ],
            [
                $model->getDomid(),
                $model->getName()
            ],
            $this->getTemplateFileContent('single' . $model->getType())
        );
        $options = '';

        // For dropdown elements, we need to render the options.
        if ($model->getType() === Fallback::RENDER_TYPE_SELECT) {
            // Here we store what the list of possible values.
            if ($model->getDomid() === Fallback::SETTING_SKIN) {
                // Get a list of all skin folders.
                $valueList = $this->pool->config->getSkinList();
            } else {
                $valueList = ['true', 'false'];
            }

            // Paint it.
            foreach ($valueList as $value) {
                if ($value === $model->getName()) {
                    // This one is selected.
                    $selected = 'selected="selected"';
                } else {
                    $selected = '';
                }

                $options .= str_replace(
                    [static::MARKER_TEXT, static::MARKER_VALUE, static::MARKER_SELECTED],
                    [$value, $value, $selected],
                    $this->getTemplateFileContent(static::FILE_SI_SELECT_OPTIONS)
                );
            }
        }

        return str_replace(
            [
                static::MARKER_NAME,
                static::MARKER_SOURCE,
                static::MARKER_NORMAL,
                static::MARKER_TYPE,
                static::MARKER_HELP,
            ],
            [
                $model->getData(),
                $model->getNormal(),
                str_replace(static::MARKER_OPTIONS, $options, $element),
                Fallback::RENDER_EDITABLE,
                $this->renderHelp($model),
            ],
            $this->getTemplateFileContent(static::FILE_SI_EDIT_CHILD)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renderButton(Model $model)
    {
        return str_replace(
            [
                static::MARKER_TEXT,
                static::MARKER_CLASS,
                static::MARKER_HELP,
            ],
            [
                $model->getNormal(),
                $model->getName(),
                $this->renderHelp($model),
            ],
            $this->getTemplateFileContent(static::FILE_SI_BUTTON)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renderFatalMain($errstr, $errfile, $errline)
    {
        $readFrom = $errline -6;
        $readTo = $errline +5;
        $source = $this->pool->fileService->readSourcecode($errfile, $errline -1, $readFrom, $readTo -1);

        return str_replace(
            [
                static::MARKER_ERROR_STRING,
                static::MARKER_FILE,
                static::MARKER_SOURCE,
                static::MARKER_KREXX_COUNT,
                static::MARKER_LINE,
            ],
            [
                $errstr,
                $errfile,
                $source,
                $this->pool->emergencyHandler->getKrexxCount(),
                $errline
            ],
            $this->getTemplateFileContent(static::FILE_FATAL_MAIN)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renderFatalHeader($cssJs, $errorType)
    {
        return str_replace(
            [
                static::MARKER_CSS_JS,
                static::MARKER_VERSION,
                static::MARKER_SEARCH,
                static::MARKER_KREXX_ID,
                static::MARKER_TYPE
            ],
            [
                $cssJs,
                $this->pool->config->version,
                $this->renderSearch(),
                $this->pool->recursionHandler->getMarker(),
                $errorType
            ],
            $this->getTemplateFileContent(static::FILE_FATAL_HEADER)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renderMessages(array $messages)
    {
        $result = '';
        $messageTemplate = $this->getTemplateFileContent(static::FILE_MESSAGE);
        foreach ($messages as $message) {
            $result .= str_replace(static::MARKER_MESSAGE, $message, $messageTemplate);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function renderBacktraceSourceLine($className, $lineNo, $sourceCode)
    {
        return str_replace(
            [
                static::MARKER_CLASS_NAME,
                static::MARKER_LINE_NO,
                static::MARKER_SOURCE_CODE,
            ],
            [
                $className,
                $lineNo,
                $sourceCode,
            ],
            $this->getTemplateFileContent(static::FILE_BACKTRACE_SOURCELINE)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renderSingeChildHr()
    {
        return $this->getTemplateFileContent(static::FILE_SI_HR);
    }

    public function renderLinebreak()
    {
        return $this->getTemplateFileContent(static::FILE_BR);
    }
}
