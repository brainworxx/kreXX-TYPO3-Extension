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

namespace Brainworxx\Krexx\View;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Misc\File;

abstract class AbstractRender
{
    /**
     * Here we store all relevant data.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * The file service, used to read and write files.
     *
     * @var File
     */
    protected $fileService;

    /**
     * Injects the pool.
     *
     * @param Pool $pool
     *   The pool, where we store the classes we need.
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
        $this->fileService = $pool->createClass('Brainworxx\\Krexx\\Service\\Misc\\File');
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
        $result = str_replace('{callerFile}', $file, $this->getTemplateFileContent('caller'));
        return str_replace('{callerLine}', $line, $result);
    }

    /**
     * Renders the helptext.
     *
     * @param Model $model
     *   The ID of the helptext.
     *
     * @see Usage
     *
     * @return string
     *   The generated markup from the template files.
     */
    protected function renderHelp($model)
    {
        $helpId = $model->getHelpid();
        $data = $model->getJson();
        $helpContent = '';

        // Test if we have anything to display at all.
        if (empty($helpId) && empty($data)) {
            return '';
        }

         // Add the normal help info
        $helpRow = $this->getTemplateFileContent('helprow');
        if (!empty($helpId)) {
            $helpContent = str_replace(
                array('{helptitle}', '{helptext}'),
                array('Help', $this->pool->messages->getHelp($helpId)),
                $helpRow
            );
        }

        // Add the stuff from the json here.
        foreach ($data as $title => $text) {
            $helpContent .= str_replace(
                array('{helptitle}', '{helptext}'),
                array($title, $text),
                $helpRow
            );
        }

        // Add it into the wrapper.
        return str_replace('{help}', $helpContent, $this->getTemplateFileContent('help'));
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
    protected function renderConnector($connector)
    {
        if (!empty($connector)) {
            return str_replace(
                '{connector}',
                $connector,
                $this->getTemplateFileContent('connector')
            );
        } else {
            return '';
        }
    }

    /**
     * Renders the search button and the search menu.
     *
     * @return string
     *   The generated markup from the template files.
     */
    protected function renderSearch()
    {
        return str_replace(
            '{KrexxId}',
            $this->pool->recursionHandler->getMarker(),
            $this->getTemplateFileContent('search')
        );
    }

    /**
     * Gets a list of all available skins for the frontend config.
     *
     * @return array
     *   An array with the skinnames.
     */
    public function getSkinList()
    {
        // Static cache to make it a little bit faster.
        static $list = array();

        if (empty($list)) {
            // Get the list.
            $list = array_filter(glob($this->pool->krexxDir . 'resources/skins/*'), 'is_dir');
            // Now we need to filter it, we only want the names, not the full path.
            foreach ($list as &$path) {
                $path = str_replace($this->pool->krexxDir . 'resources/skins/', '', $path);
            }
        }

        return $list;
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
        if ($isExpanded) {
            $style = '';
        } else {
            $style = 'khidden';
        }

        return str_replace(
            array(
                '{style}',
                '{mainfunction}',
                '{domId}',
            ),
            array(
                $style,
                $model->renderMe(),
                $domid,
            ),
            $this->getTemplateFileContent('nest')
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
                $this->fileService->getFileContents(
                    $this->pool->krexxDir .
                    'resources/skins/' .
                    $this->pool->config->getSetting('skin') .
                    '/' .
                    $what .
                    '.html'
                )
            );
        }
        return $fileCache[$what];
    }

    /**
     * Generates a data attribute, to be inserted into the HTML tags.
     * If no value is in the data, we return an empty string.
     *
     * @param string $name
     *   The name of the attribute (without the 'data-' in front
     * @param string $data
     *   The value. Must be string.
     *
     * @return string
     *   The generated data attribute.
     */
    protected function generateDataAttribute($name, $data)
    {
        if ($data  === '') {
            return '';
        } else {
            return ' data-' . $name . '=\'' . $data . '\' ';
        }

    }

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
    abstract public function renderSingleChild(Model $model);

    /**
     * Render a block of a detected recursion.
     *
     * If the recursion is an object, a click should jump to the original
     * analysis data.
     *
     * @param Model $model
     *   The model, which hosts all the data we need.
     *
     * @return string
     *   The generated markup from the template files.
     */
    abstract public function renderRecursion(Model $model);

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
    abstract public function renderHeader($doctype, $headline, $cssJs);

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
    abstract public function renderFooter($caller, $configOutput, $configOnly = false);



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
    abstract public function renderCssJs(&$css, &$js);

    /**
     * Renders a expandable child with a callback in the middle.
     *
     * @param Model $model
     *   The model, which hosts all the data we need.
     * @param bool $isExpanded
     *   Is this one expanded from the beginning?
     *   TRUE when we render the settings menu only.
     *
     * @return string
     *   The generated markup from the template files.
     */
    abstract public function renderExpandableChild(Model $model, $isExpanded = false);

    /**
     * Renders a simple editable child node.
     *
     * @param Model $model
     *   The model, which hosts all the data we need.
     *
     * @return string
     *   The generated markup from the template files.
     */
    abstract public function renderSingleEditableChild(Model $model);

    /**
     * Renders a simple button.
     *
     * @param Model $model
     *   The model, which hosts all the data we need.
     *
     * @return string
     *   The generated markup from the template files.
     */
    abstract public function renderButton(Model $model);

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
    abstract public function renderFatalMain($type, $errstr, $errfile, $errline);

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
    abstract public function renderFatalHeader($cssJs, $doctype);

    /**
     * Renders all internal messages.
     *
     * @param array $messages
     *   The current messages.
     *
     * @return string
     *   The generates html output
     */
    abstract public function renderMessages(array $messages);

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
    abstract public function renderBacktraceSourceLine($className, $lineNo, $sourceCode);

    /**
     * Renders the hr.
     *
     * @return string
     *   The generated markup from the template file.
     */
    abstract public function renderSingeChildHr();
}
