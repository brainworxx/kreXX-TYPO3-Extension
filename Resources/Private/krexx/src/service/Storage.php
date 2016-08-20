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

namespace Brainworxx\Krexx\Service;

use Brainworxx\Krexx\Controller\OutputActions;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Service\Flow\Recursion;
use Brainworxx\Krexx\Service\Flow\Routing;
use Brainworxx\Krexx\Service\Misc\Chunks;
use Brainworxx\Krexx\Service\Misc\Codegen;
use Brainworxx\Krexx\Service\View\Messages;
use Brainworxx\Krexx\Service\View\Render;

/**
 * Here we store all classes that we need.
 *
 * @package Brainworxx\Krexx\Service
 */
class Storage
{
    /**
     * The routing class.
     *
     * @var Routing
     */
    public $routing;

    /**
     * An instance of the recursion handler.
     *
     * It gets re-new()-d with every new call.
     *
     * @var Recursion
     */
    public $recursionHandler;

    /**
     * Generates code, if the variable can be reached.
     *
     * @var Codegen
     */
    public $codegenHandler;

    /**
     * Our emergency break handler.
     *
     * @var Emergency
     */
    public $emergencyHandler;

    /**
     * The instance of the render class from the skin.
     *
     * Gets loaded in the output footer.
     *
     * @var Render
     */
    public $render;

    /**
     * The configuration class.
     *
     * @var Config
     */
    public $config;

    /**
     * The messages handler.
     *
     * @var Messages
     */
    public $messages;

    /**
     * The chunks handler
     *
     * @var Chunks
     */
    public $chunks;

    /**
     * Our output controller.
     *
     * @var OutputActions
     */
    public $controller;

    /**
     * Initializes all needed classes.
     *
     * @param $krexxDir
     *   The directory, where kreXX is stored.
     */
    public function __construct($krexxDir)
    {
        // Initializes the configuration
        $this->config = new Config($this);
        $this->config->krexxdir = $krexxDir;
        // Initialize the emergency handler.
        $this->emergencyHandler = new Emergency($this);
        // Initialize the routing.
        $this->routing = new Routing($this);
        // Initialize the recursionHandler.
        $this->recursionHandler = new Recursion($this);
        // Initialize the code generation.
        $this->codegenHandler = new Codegen($this);
        // Initializes the messages.
        $this->messages = new Messages($this);
        // Initializes the chunks handler.
        $this->chunks = new Chunks($this);
        // Initializes the controller.
        $this->controller = new OutputActions($this);
        // Initializes the render class.
        $this->initRendrerer();
        // Check our environment.
        $this->checkEnvironmentAction($krexxDir);
    }

    /**
     * Yes, we do have an output here. We are generation messages to
     * inform the dev that the environment is not as it should be.
     *
     * @param string $krexxDir
     *   The directory where kreXX ist installed.
     */
    protected function checkEnvironmentAction($krexxDir)
    {
        // Check chunk folder is writable.
        // If not, give feedback!
        $chunkFolder = $krexxDir . 'chunks' . DIRECTORY_SEPARATOR;
        if (!is_writeable($chunkFolder)) {
            $this->messages->addMessage(
                'Chunksfolder ' . $chunkFolder . ' is not writable!' .
                'This will increase the memory usage of kreXX significantly!',
                'critical'
            );
            $this->messages->addKey('protected.folder.chunk', array($chunkFolder));
            // We can work without chunks, but this will require much more memory!
            $this->chunks->setUseChunks(false);
        }

        // Check if the log folder is writable.
        // If not, give feedback!
        $logFolder = $krexxDir . $this->config->getConfigValue('output', 'folder') . DIRECTORY_SEPARATOR;
        if (!is_writeable($logFolder)) {
            $this->messages->addMessage('Logfolder ' . $logFolder . ' is not writable !', 'critical');
            $this->messages->addKey('protected.folder.log', array($logFolder));
        }
        // At this point, we won't inform the dev right away. The error message
        // will pop up, when kreXX is actually displayed, no need to bother the
        // dev just now.
        // We might need to register our fatal error handler.
        if ($this->config->getConfigValue('backtraceAndError', 'registerAutomatically') === 'true') {
            $this->controller->registerFatalAction();
        }
    }

    /**
     * Re-new() the classes that need to be re-new()-ed.
     */
    public function reset()
    {
        // We need to reset our recursion handler, because
        // the content of classes might change with another run.
        $this->recursionHandler = new Recursion($this);
        // We also reset our emergency handler timer.
        $this->emergencyHandler->resetTimer();
    }

    /**
     * Loads the renderer from the skin.
     */
    protected function initRendrerer()
    {
        $skin = $this->config->getConfigValue('output', 'skin');
        $path = $this->config->krexxdir . 'resources/skins/' . $skin . '/Render.php';
        $classname = 'Brainworxx\Krexx\View\\' . ucfirst($skin) . '\\Render';
        include_once $path;
        $this->render = new $classname($this);
    }


    /**
     * Reads sourcecode from files, for the backtrace.
     *
     * @param string $file
     *   Path to the file you want to read.
     * @param int $highlight
     *   The line number you want to highlight
     * @param int $from
     *   The start line.
     * @param int $to
     *   The end line.
     *
     * @return string
     *   The source code.
     */
    public function readSourcecode($file, $highlight, $from, $to)
    {
        $result = '';
        if (is_readable($file)) {
            // Load content and add it to the backtrace.
            $contentArray = file($file);
            // Correct the value, in case we are exceeding the line numbers.
            if ($from < 0) {
                $from = 0;
            }
            if ($to > count($contentArray)) {
                $to = count($contentArray);
            }

            for ($currentLineNo = $from; $currentLineNo <= $to; $currentLineNo++) {
                if (isset($contentArray[$currentLineNo])) {
                    // Add it to the result.
                    $realLineNo = $currentLineNo + 1;

                    // Escape it.
                    $contentArray[$currentLineNo] = $this->encodeString($contentArray[$currentLineNo], true);

                    if ($currentLineNo === $highlight) {
                        $result .= $this->render->renderBacktraceSourceLine(
                            'highlight',
                            $realLineNo,
                            $contentArray[$currentLineNo]
                        );
                    } else {
                        $result .= $this->render->renderBacktraceSourceLine(
                            'source',
                            $realLineNo,
                            $contentArray[$currentLineNo]
                        );
                    }
                } else {
                    // End of the file.
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * Reads the content of a file.
     *
     * @param string $path
     *   The path to the file.
     *
     * @return string
     *   The content of the file, if readable.
     */
    public function getFileContents($path)
    {
        $result = '';
        // Is it readable and does it have any content?
        if (is_readable($path)) {
            $size = filesize($path);
            if ($size > 0) {
                $file = fopen($path, "r");
                $result = fread($file, $size);
                fclose($file);
            }
        }

        return $result;
    }

    /**
     * Sanitizes a string, by completely encoding it.
     *
     * Should work with mixed encoding.
     *
     * @param string $data
     *   The data which needs to be sanitized.
     * @param bool $code
     *   Do we need to format the string as code?
     *
     * @return string
     *   The encoded string.
     */
    public function encodeString($data, $code = false)
    {
        if (empty($data)) {
            return '';
        }

        // Try to encode it.
        set_error_handler(function () {
            /* do nothing. */
        });
        $result = @htmlentities($data, ENT_DISALLOWED);
        // We are also encoding @, because we need them for our chunks.
        $result = str_replace('@', '&#64;', $result);
        // We are also encoding the {, because we use it as markers for the skins.
        $result = str_replace('{', '&#123;', $result);
        restore_error_handler();

        // Check if encoding was successful.
        // 99.99% of the time, the encoding works.
        if (empty($result)) {
            // Something went wrong with the encoding, we need to
            // completely encode this one to be able to display it at all!
            $data = @mb_convert_encoding($data, 'UTF-32', mb_detect_encoding($data));

            if ($code) {
                // We are displaying sourcecode, so we need
                // to do some formatting.
                $sortingCallback = function ($n) {
                    if ($n === 9) {
                        // Replace TAB with two spaces, it's better readable that way.
                        $result = '&nbsp;&nbsp;';
                    } else {
                        $result = "&#$n;";
                    }
                    return $result;
                };
            } else {
                // No formatting.
                $sortingCallback = function ($n) {
                    return "&#$n;";
                };
            }

            // Here we have another SPOF. When the string is large enough
            // we will run out of memory!
            // @see https://sourceforge.net/p/krexx/bugs/21/
            // We will *NOT* return the unescaped string. So we must check if it
            // is small enough for the unpack().
            // 100 kb should be save enough.
            if (strlen($data) < 102400) {
                $result = implode("", array_map($sortingCallback, unpack("N*", $data)));
            } else {
                $result = $this->render->getHelp('stringTooLarge');
            }
        } else {
            if ($code) {
                // Replace all tabs with 2 spaces to make sourcecode better
                // readable.
                $result = str_replace(chr(9), '&nbsp;&nbsp;', $result);
            }
        }

        return $result;
    }
}
