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
 *   kreXX Copyright (C) 2014-2016 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Service\View;

use Brainworxx\Krexx\Model\Simple;

/**
 * Render methods.
 *
 * It get extended by the render class of the used skin, so every skin can do
 * some special stuff.
 *
 * @package Brainworxx\Krexx\Service\View
 */
class Render extends Help
{
    /**
     * Renders a "single child", containing a single not expandable value.
     *
     * Depending on how many characters are in there, it may be toggelable.
     *
     * @param Simple $model
     *   The model, which hosts all the data we need.
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderSingleChild(Simple $model)
    {
        // This one is a little bit more complicated than the others,
        // because it assembles some partials and stitches them together.
        $template = $this->getTemplateFileContent('singleChild');
        $partExpand = '';
        $partCallable = '';
        $partExtra = '';
        $data = $model->getData();

        if (strlen($data) > strlen($model->getNormal())) {
            $extra = true;
        } else {
            $extra = false;
        }

        if ($extra) {
            // We have a lot of text, so we render this one expandable (yellow box).
            $partExpand = $this->getTemplateFileContent('singleChildExpand');
        }
        if (is_callable($data)) {
            // Add callable partial.
            $partCallable = $this->getTemplateFileContent('singleChildCallable');
        }
        if ($extra) {
            // Add the yellow box for large output text.
            $partExtra = $this->getTemplateFileContent('singleChildExtra');
        }
        // Stitching the classes together, depending on the types.
        $typeArray = explode(' ', $model->getType());
        $typeClasses = '';
        foreach ($typeArray as $typeClass) {
            $typeClass = 'k' . $typeClass;
            $typeClasses .= $typeClass . ' ';
        }

        // Generating our code and adding the Codegen button, if there is something
        // to generate.
        $gensource = $this->storage->codegenHandler->generateSource($model);
        if (empty($gensource)) {
            // Remove the markers, because here is nothing to add.
            $template = str_replace('{gensource}', '', $template);
            $template = str_replace('{sourcebutton}', '', $template);
        } else {
            // We add the buttton and the code.
            $template = str_replace('{gensource}', $gensource, $template);
            $template = str_replace('{sourcebutton}', $this->getTemplateFileContent('sourcebutton'), $template);
        }

        // Stitching it together.
        $template = str_replace('{expand}', $partExpand, $template);
        $template = str_replace('{callable}', $partCallable, $template);
        $template = str_replace('{extra}', $partExtra, $template);
        $template = str_replace('{name}', $model->getName(), $template);
        $template = str_replace('{type}', $model->getType(), $template);
        $template = str_replace('{type-classes}', $typeClasses, $template);
        $template = str_replace('{normal}', $model->getNormal(), $template);
        $template = str_replace('{data}', $data, $template);
        $template = str_replace('{help}', $this->renderHelp($model->getHelpid()), $template);
        $template = str_replace('{connector1}', $this->renderConnector($model->getConnector1()), $template);
        $template = str_replace('{gensource}', $gensource, $template);
        return str_replace('{connector2}', $this->renderConnector($model->getConnector2()), $template);
    }

    /**
     * Render a block of a detected recursion.
     *
     * If the recursion is an object, a click should jump to the original
     * analysis data.
     *
     * @param Simple $model
     *   The model, which hosts all the data we need.
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderRecursion(Simple $model)
    {
        $template = $this->getTemplateFileContent('recursion');

        // Generating our code and adding the Codegen button, if there is
        // something to generate.
        $gencode = $this->storage->codegenHandler->generateSource($model);

        if (empty($gencode)) {
            // Remove the markers, because here is nothing to add.
            $template = str_replace('{gensource}', '', $template);
            $template = str_replace('{sourcebutton}', '', $template);
        } else {
            // We add the buttton and the code.
            $template = str_replace('{gensource}', $gencode, $template);
        }

        // Replace our stuff in the partial.
        $template = str_replace('{name}', $model->getName(), $template);
        $template = str_replace('{domId}', $model->getDomid(), $template);
        $template = str_replace('{normal}', $model->getType(), $template);
        $template = str_replace('{connector1}', $this->renderConnector($model->getConnector1()), $template);

        return str_replace('{connector2}', $this->renderConnector($model->getConnector2()), $template);
    }

    /**
     * Renders the kreXX header.
     *
     * @param string $doctype
     *   The doctype from the configuration.
     * @param string $headline
     *   The headline, what is actually analysed.
     * @param string $cssJs
     *   The CSS and JS in a string.
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderHeader($doctype, $headline, $cssJs)
    {
        $template = $this->getTemplateFileContent('header');
        // Replace our stuff in the partial.
        $template = str_replace('{version}', $this->storage->config->version, $template);
        $template = str_replace('{doctype}', $doctype, $template);
        $template = str_replace('{KrexxCount}', $this->storage->emergencyHandler->getKrexxCount(), $template);
        $template = str_replace('{headline}', $headline, $template);
        $template = str_replace('{cssJs}', $cssJs, $template);
        $template = str_replace('{KrexxId}', $this->storage->recursionHandler->getMarker(), $template);
        $template = str_replace('{search}', $this->renderSearch(), $template);
        $template = str_replace('{messages}', $this->storage->messages->outputMessages(), $template);

        return $template;
    }

    /**
     * Renders the search button and the search menu.
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderSearch()
    {
        $template = $this->getTemplateFileContent('search');
        $template = str_replace('{KrexxId}', $this->storage->recursionHandler->getMarker(), $template);
        return $template;
    }

    /**
     * Renders the kreXX footer.
     *
     * @param array $caller
     *   The caller of kreXX.
     * @param string $configOutput
     *   The pregenerated configuration markup.
     * @param boolean $configOnly
     *   Info if we are only displaying the configuration
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderFooter($caller, $configOutput, $configOnly = false)
    {
        $template = $this->getTemplateFileContent('footer');
        // Replace our stuff in the partial.
        if (!isset($caller['file'])) {
            // When we have no caller, we will not render it.
            $template = str_replace('{caller}', '', $template);
        } else {
            $template = str_replace('{caller}', $this->renderCaller($caller['file'], $caller['line']), $template);
        }
        $template = str_replace('{configInfo}', $configOutput, $template);
        return $template;
    }

    /**
     * Renders a nest with a anonymous function in the middle.
     *
     * @param Simple $model
     *   The model, which hosts all the data we need.
     * @param bool $isExpanded
     *   The only expanded nest is the settings menu, when we render only the
     *   settings menu.
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderNest(Simple $model, $isExpanded = false)
    {
        $template = $this->getTemplateFileContent('nest');
        // Replace our stuff in the partial.
        $domid = '';
        if (strlen($model->getDomid())) {
            $domid = 'id="' . $model->getDomid() . '"';
        }
        $template = str_replace('{domId}', $domid, $template);
        // Are we expanding this one?
        if ($isExpanded) {
            $style = '';
        } else {
            $style = 'khidden';
        }
        $template = str_replace('{style}', $style, $template);
        return str_replace('{mainfunction}', $model->renderMe(), $template);
    }

    /**
     * Simply outputs the css and js stuff.
     *
     * @param string $css
     *   The CSS, rendered into the template.
     * @param string $js
     *   The JS, rendered into the template.
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderCssJs($css, $js)
    {
        $template = $this->getTemplateFileContent('cssJs');
        // Replace our stuff in the partial.
        $template = str_replace('{css}', $css, $template);
        $template = str_replace('{js}', $js, $template);
        return $template;
    }

    /**
     * Renders a expandable child with a callback in the middle.
     *
     * @param Simple $model
     *   The model, which hosts all the data we need.
     * @param bool $isExpanded
     *   Is this one expanded from the beginning?
     *   TRUE when we render the settings menu only.
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderExpandableChild(Simple $model, $isExpanded = false)
    {
        // Check for emergency break.
        if (!$this->storage->emergencyHandler->checkEmergencyBreak()) {
            return '';
        }

        // We need to render this one normally.
        $template = $this->getTemplateFileContent('expandableChildNormal');
        // Replace our stuff in the partial.
        $template = str_replace('{name}', $model->getName(), $template);
        $template = str_replace('{type}', $model->getType(), $template);

        // Explode the type to get the class names right.
        $types = explode(' ', $model->getType());
        $cssType = '';
        foreach ($types as $singleType) {
            $cssType .= ' k' . $singleType;
        }
        $template = str_replace('{ktype}', $cssType, $template);

        $template = str_replace('{additional}', $model->getAdditional(), $template);
        $template = str_replace('{help}', $this->renderHelp($model->getHelpid()), $template);
        $template = str_replace('{connector1}', $this->renderConnector($model->getConnector1()), $template);
        $template = str_replace('{connector2}', $this->renderConnector($model->getConnector2()), $template);

        // Generating our code and adding the Codegen button, if there is
        // something to generate.
        $gencode = $this->storage->codegenHandler->generateSource($model);
        $template = str_replace('{gensource}', $gencode, $template);
        if ($gencode === '.stop.' || empty($gencode)) {
            // Remove the button marker, because here is nothing to add.
            $template = str_replace('{sourcebutton}', '', $template);
        } else {
            // Add the button.
            $template = str_replace('{sourcebutton}', $this->getTemplateFileContent('sourcebutton'), $template);
        }

        // Is it expanded?
        if ($isExpanded) {
            $template = str_replace('{isExpanded}', 'kopened', $template);
        } else {
            $template = str_replace('{isExpanded}', '', $template);
        }
        return str_replace(
            '{nest}',
            $this->storage->chunks->chunkMe($this->renderNest($model, $isExpanded)),
            $template
        );

    }

    /**
     * Loads a template file from the skin folder.
     *
     * @param string $what
     *   Filename in the skin folder without the ".html" at the end.
     *
     * @return string
     *   The template file, without whitespaces.
     */
    protected function getTemplateFileContent($what)
    {
        static $fileCache = array();

        if (!isset($fileCache[$what])) {
            $fileCache[$what] = preg_replace(
                '/\s+/',
                ' ',
                $this->storage->getFileContents(
                    $this->storage->config->krexxdir .
                    'resources/skins/' .
                    $this->storage->config->getConfigValue('output', 'skin') .
                    '/' .
                    $what .
                    '.html'
                )
            );
        }
        return $fileCache[$what];
    }

    /**
     * Renders a simple editable child node.
     *
     * @param Simple $model
     *   The model, which hosts all the data we need.
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderSingleEditableChild(Simple $model)
    {
        $template = $this->getTemplateFileContent('singleEditableChild');
        $element = $this->getTemplateFileContent('single' . $model->getType());

        $element = str_replace('{name}', $model->getData(), $element);
        $element = str_replace('{value}', $model->getName(), $element);

        // For dropdown elements, we need to render the options.
        if ($model->getType() === 'Select') {
            $option = $this->getTemplateFileContent('single' . $model->getType() . 'Options');

            // Here we store what the list of possible values.
            switch ($model->getData()) {
                case "destination":
                    // Frontend or file.
                    $valueList = array('frontend', 'file');
                    break;

                case "backtraceAnalysis":
                    // Normal or deep analysis.
                    $valueList = array('deep', 'normal');
                    break;

                case "skin":
                    // Get a list of all skin folders.
                    $valueList = $this->storage->config->getSkinList();
                    break;

                default:
                    // true/false
                    $valueList = array('true', 'false');
                    break;
            }

            // Paint it.
            $options = '';
            foreach ($valueList as $value) {
                if ($value === $model->getName()) {
                    // This one is selected.
                    $selected = 'selected="selected"';
                } else {
                    $selected = '';
                }
                $options .= str_replace(array(
                    '{text}',
                    '{value}',
                    '{selected}',
                ), array(
                    $value,
                    $value,
                    $selected,
                ), $option);
            }
            // Now we replace the options in the output.
            $element = str_replace('{options}', $options, $element);
        }

        $template = str_replace('{name}', $model->getData(), $template);
        $template = str_replace('{source}', $model->getNormal(), $template);
        $template = str_replace('{normal}', $element, $template);
        $template = str_replace('{type}', 'editable', $template);
        $template = str_replace('{help}', $this->renderHelp($model->getHelpid()), $template);

        return $template;
    }

    /**
     * Renders a simple button.
     *
     * @param Simple $model
     *   The model, which hosts all the data we need.
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderButton(Simple $model)
    {
        $template = $this->getTemplateFileContent('singleButton');
        $template = str_replace('{help}', $this->renderHelp($model->getHelpid()), $template);

        $template = str_replace('{text}', $model->getNormal(), $template);
        return str_replace('{class}', $model->getName(), $template);
    }

    /**
     * Renders the second part of the fatal error handler.
     *
     * @param string $type
     *   The type of the error (should always be fatal).
     * @param string $errstr
     *   The string from the error.
     * @param string $errfile
     *   The file where the error occurred.
     * @param int $errline
     *   The line number where the error occurred.
     *
     * @return string
     *   The template file, with all markers replaced.
     */
    public function renderFatalMain($type, $errstr, $errfile, $errline)
    {
        $template = $this->getTemplateFileContent('fatalMain');

        $from = $errline -5;
        $to = $errline +5;
        $source = $this->storage->readSourcecode($errfile, $errline -1, $from -1, $to -1);

        // Insert our values.
        $template = str_replace('{type}', $type, $template);
        $template = str_replace('{errstr}', $errstr, $template);
        $template = str_replace('{file}', $errfile, $template);
        $template = str_replace('{source}', $source, $template);
        $template = str_replace('{KrexxCount}', $this->storage->emergencyHandler->getKrexxCount(), $template);

        return str_replace('{line}', $errline, $template);
    }

    /**
     * Renders the header part of the fatal error handler.
     *
     * @param string $cssJs
     *   The css and js from the template.
     * @param string $doctype
     *   The configured doctype.
     *
     * @return string
     *   The templatefile, with all markers replaced.
     */
    public function renderFatalHeader($cssJs, $doctype)
    {
        $template = $this->getTemplateFileContent('fatalHeader');

        // Insert our values.
        $template = str_replace('{cssJs}', $cssJs, $template);
        $template = str_replace('{version}', $this->storage->config->version, $template);
        $template = str_replace('{doctype}', $doctype, $template);
        $template = str_replace('{search}', $this->renderSearch(), $template);

        return str_replace('{KrexxId}', $this->storage->recursionHandler->getMarker(), $template);
    }

    /**
     * Renders all internal messages.
     *
     * @param array $messages
     *   The current messages.
     *
     * @return string
     *   The generates html output
     */
    public function renderMessages(array $messages)
    {
        $template = $this->getTemplateFileContent('message');
        $result = '';

        foreach ($messages as $message) {
            $temp = str_replace('{class}', $message['class'], $template);
            $result .= str_replace('{message}', $message['message'], $temp);
        }

        return $result;
    }

    /**
     * Renders the footer part, where we display from where krexx was called.
     *
     * @param string $file
     *   The file from where krexx was called.
     * @param string $line
     *   The line number from where krexx was called.
     *
     * @return string
     *   The generated markup from the template files.
     */
    protected function renderCaller($file, $line)
    {
        $template = $this->getTemplateFileContent('caller');
        $template = str_replace('{callerFile}', $file, $template);
        return str_replace('{callerLine}', $line, $template);
    }

    /**
     * Renders the helptext.
     *
     * @param string $helpid
     *   The ID of the helptext.
     *
     * @see Help
     *
     * @return string
     *   The generated markup from the template files.
     */
    protected function renderHelp($helpid)
    {
        $helpText = $this->getHelp($helpid);
        if ($helpText != '') {
            return str_replace('{help}', $helpText, $this->getTemplateFileContent('help'));
        } else {
            return '';
        }
    }

    /**
     * Renders the line of the sourcecode, from where the backtrace is coming.
     *
     * @param string $className
     *   The class name where the sourcecode is from.
     * @param string $lineNo
     *   The kine number from the file.
     * @param string $sourceCode
     *   Part of the sourcecode, where the backtrace is coming from.
     *
     * @return string
     *   The generated markup from the template files.
     */
    public function renderBacktraceSourceLine($className, $lineNo, $sourceCode)
    {
        $template = $this->getTemplateFileContent('backtraceSourceLine');
        $template = str_replace('{className}', $className, $template);
        $template = str_replace('{lineNo}', $lineNo, $template);

        return str_replace('{sourceCode}', $sourceCode, $template);
    }

    /**
     * Renders the hr.
     *
     * @return string
     *   The generated markup from the template file.
     */
    public function renderSingeChildHr()
    {
        return $this->getTemplateFileContent('singleChildHr');
    }

    /**
     * Renders the connector between analysis objects, params and results.
     *
     * @param string $connector
     *   The data to be displayed.
     *
     * @return string
     *   The rendered connector.
     */
    public function renderConnector($connector)
    {
        if (!empty($connector)) {
            $template = $this->getTemplateFileContent('connector');
            return str_replace('{connector}', $connector, $template);
        } else {
            return '';
        }
    }
}
