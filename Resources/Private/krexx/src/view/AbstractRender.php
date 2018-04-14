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
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
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
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Protected helper methods for the real render class.
 *
 * @package Brainworxx\Krexx\View
 */
abstract class AbstractRender implements RenderInterface
{
    const MARKER_CALLER_FILE = '{callerFile}';
    const MARKER_CALLER_LINE = '{callerLine}';
    const MARKER_HELP = '{help}';
    const MARKER_HELP_TITLE = '{helptitle}';
    const MARKER_HELP_TEXT = '{helptext}';
    const MARKER_CONNECTOR = '{connector}';
    const MARKER_KREXX_ID = '{KrexxId}';
    const MARKER_STYLE = '{style}';
    const MARKER_MAIN_FUNCTION = '{mainfunction}';
    const MARKER_DOM_ID = '{domId}';

    /**
     * Here we store all relevant data.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * The name of the current skin.
     *
     * @var string
     */
    protected $skinPath;

    /**
     * {@inheritdoc}
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
        $this->skinPath = KREXX_DIR . 'resources/skins/' .
            $this->pool->config->getSetting(Fallback::SETTING_SKIN) . '/';
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
        return str_replace(
            array(
                static::MARKER_CALLER_FILE,
                static::MARKER_CALLER_LINE,
            ),
            array(
                $file,
                $line,
            ),
            $this->getTemplateFileContent('caller')
        );
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
    protected function renderHelp(Model $model)
    {
        $data = $model->getJson();

        // Test if we have anything to display at all.
        if (empty($data) === true) {
            return '';
        }

        // We have at least something to display here.
        $helpRow = $this->getTemplateFileContent('helprow');
        $helpContent = '';

        // Add the stuff from the json after the help text, if any.
        foreach ($data as $title => $text) {
            $helpContent .= str_replace(
                array(static::MARKER_HELP_TITLE, static::MARKER_HELP_TEXT),
                array($title, $text),
                $helpRow
            );
        }

        // Add it into the wrapper.
        return str_replace(static::MARKER_HELP, $helpContent, $this->getTemplateFileContent('help'));
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
        if (empty($connector) === true) {
            return '';
        }

        return str_replace(
            static::MARKER_CONNECTOR,
            $connector,
            $this->getTemplateFileContent('connector')
        );
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
            static::MARKER_KREXX_ID,
            $this->pool->recursionHandler->getMarker(),
            $this->getTemplateFileContent('search')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSkinList()
    {
        // Static cache to make it a little bit faster.
        static $list = array();

        if (empty($list) === true) {
            // Get the list.
            $list = array_filter(glob(KREXX_DIR . 'resources/skins/*'), 'is_dir');
            // Now we need to filter it, we only want the names, not the full path.
            foreach ($list as &$path) {
                $path = str_replace(KREXX_DIR . 'resources/skins/', '', $path);
            }
        }

        return $list;
    }

    /**
     * Renders a nest with a anonymous function in the middle.
     *
     * @param Model $model
     *   The model, which hosts all the data we need.
     * @param boolean $isExpanded
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
            $style = 'khidden';
        }

        return str_replace(
            array(
                static::MARKER_STYLE,
                static::MARKER_MAIN_FUNCTION,
                static::MARKER_DOM_ID,
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

        if (isset($fileCache[$what]) === true) {
            return $fileCache[$what];
        }

        $fileCache[$what] = preg_replace(
            '/\s+/',
            ' ',
            $this->pool->fileService->getFileContents($this->skinPath . $what . '.html')
        );
        return $fileCache[$what];
    }

    /**
     * Some special escaping for the json output
     *
     * @param array $array
     *   The string we want to special-escape
     * @return string
     *   The json from the array.
     */
    protected function encodeJson(array $array)
    {
        // No data, no json!
        if (empty($array) === true) {
            return '';
        }

        return json_encode($this->jsonEscape($array));
    }

    /**
     * Do some special escaping for the json and data attribute output.
     *
     * @param string|array $data
     *
     * @return string|array
     *   The escaped json
     */
    protected function jsonEscape($data)
    {
        // Our js has some problems with single quotes and escaped quotes.
        // We remove them as well as linebreaks.
        // Unicode greater-than and smaller-then values.
        return str_replace(
            array(
                '"',
                "'",
                '&quot;',
                '&lt;',
                '&gt;',
            ),
            array(
                "\\u0027",
                "\\u0022",
                "\\u0027",
                "\\u276E",
                "\\u02C3",
            ),
            $data
        );
    }

    /**
     * Generates a data attribute, to be inserted into the HTML tags.
     * If no value is in the data, we return an empty string.
     * Double quotes gets replaced by &#34;
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
        }

        return ' data-' . $name . '="' . str_replace('"', '&#34;', $data) . '" ';
    }
}
