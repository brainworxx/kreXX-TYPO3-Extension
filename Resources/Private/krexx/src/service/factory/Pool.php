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

namespace Brainworxx\Krexx\Service\Factory;

use Brainworxx\Krexx\Analyse\Caller\AbstractCaller;
use Brainworxx\Krexx\Analyse\Scope;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Service\Flow\Recursion;
use Brainworxx\Krexx\Service\Misc\Registry;
use Brainworxx\Krexx\View\Output\Chunks;
use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\View\Messages;
use Brainworxx\Krexx\View\Render;
use Brainworxx\Krexx\Analyse\Routing\Routing;

/**
 * Here we store all classes that we need.
 *
 * @package Brainworxx\Krexx\Service
 */
class Pool extends Factory
{

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
     * Finds the script caller.
     *
     * @var AbstractCaller
     */
    public $callerFinder;

    /**
     * Scope analysis class.
     *
     * @var Scope
     */
    public $scope;

    /**
     * Our registry. It will not be reset by the init().
     *
     * @var Registry
     */
    public $registry;

    /**
     * The routing of opur analysis.
     *
     * @var Routing
     */
    public $routing;

    /**
     * The directory where kreXX is installed.
     *
     * @var string
     */
    public $krexxDir;

    /**
     * Initializes all needed classes.
     *
     * @param $krexxDir
     *   The directory, where kreXX is stored.
     */
    public function __construct($krexxDir)
    {
        $this->registry = $this->createClass('Brainworxx\\Krexx\\Service\\Misc\\Registry');
        $this->init($krexxDir);
    }

    /**
     * (Re)initializes everything in the pool, in case in-runtime
     * factory overwrites.
     *
     * @param $krexxDir
     *   The dir where kreXX is stored.
     */
    public function init($krexxDir)
    {
        // Get the rewrites from the $GLOBALS.
        $this->flushRewrite();
        // Set the directory.
        $this->krexxDir = $krexxDir;
        // Initializes the messages.
        $this->messages = $this->createClass('Brainworxx\\Krexx\\View\\Messages');
        // Initializes the configuration
        $this->config = $this->createClass('Brainworxx\\Krexx\\Service\\Config\\Config');
        // Initialize the emergency handler.
        $this->emergencyHandler = $this->createClass('Brainworxx\\Krexx\\Service\\Flow\\Emergency');
        // Initialize the recursionHandler.
        $this->recursionHandler = $this->createClass('Brainworxx\\Krexx\\Service\\Flow\\Recursion');
        // Initialize the code generation.
        $this->codegenHandler = $this->createClass('Brainworxx\\Krexx\\Analyse\\Code\\Codegen');
        // Initializes the chunks handler.
        $this->chunks = $this->createClass('Brainworxx\\Krexx\\View\\Output\\Chunks');
        // Initializes the scope analysis
        $this->scope = $this->createClass('Brainworxx\\Krexx\\Analyse\\Scope');
        // Initializes the routing
        $this->routing = $this->createClass('Brainworxx\\Krexx\\Analyse\Routing\\Routing');
        // Initializes the render class.
        $this->initRenderer();
        // Check the environment and prepare the feedback, if necessary.
        $this->checkEnvironment();
    }

    /**
     * Check if the environment is as it should be.
     */
    protected function checkEnvironment()
    {
        // Check chunk folder is writable.
        // If not, give feedback!
        $chunkFolder = $this->config->getChunkDir();
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
        $logFolder = $this->config->getLogDir();
        if (!is_writeable($logFolder)) {
            $this->messages->addMessage('Logfolder ' . $logFolder . ' is not writable !', 'critical');
            $this->messages->addKey('protected.folder.log', array($logFolder));
        }
        // At this point, we won't inform the dev right away. The error message
        // will pop up, when kreXX is actually displayed, no need to bother the
        // dev just now.
    }

    /**
     * Re-new() the classes that need to be re-new()-ed.
     */
    public function reset()
    {
        // We need to reset our recursion handler, because
        // the content of classes might change with another run.
        $this->recursionHandler = $this->createClass('Brainworxx\\Krexx\\Service\\Flow\\Recursion');
        // Initialize the code generation.
        $this->codegenHandler = $this->createClass('Brainworxx\\Krexx\\Analyse\\Code\\Codegen');
        $this->scope = $this->createClass('Brainworxx\\Krexx\\Analyse\\Scope');
        // We also reset our emergency handler timer.
        $this->emergencyHandler->resetTimer();
    }

    /**
     * Reload the configuration.
     */
    public function resetConfig()
    {
        $this->config = $this->createClass('Brainworxx\\Krexx\\Service\\Config\\Config');
    }

    /**
     * Loads the renderer from the skin.
     */
    protected function initRenderer()
    {
        $skin = $this->config->getSetting('skin');
        $classname = '\\Brainworxx\\Krexx\\View\\' . ucfirst($skin) . '\\Render';
        include_once $this->krexxDir . 'resources/skins/' . $skin . '/Render.php';
        $this->render =  $this->createClass($classname);
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
        // Try to encode it.
        set_error_handler(function () {
            /* do nothing. */
        });

        $result = htmlentities($data);

        // We are also encoding @, because we need them for our chunks.
        $result = str_replace('@', '&#64;', $result);
        // We are also encoding the {, because we use it as markers for the skins.
        $result = str_replace('{', '&#123;', $result);

        // Check if encoding was successful.
        // 99.99% of the time, the encoding works.
        if (empty($result)) {
            // Something went wrong with the encoding, we need to
            // completely encode this one to be able to display it at all!
            $data = mb_convert_encoding($data, 'UTF-32', mb_detect_encoding($data));

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
                $result = $this->messages->getHelp('stringTooLarge');
            }
        } else {
            if ($code) {
                // Replace all tabs with 2 spaces to make sourcecode better
                // readable.
                $result = str_replace(chr(9), '&nbsp;&nbsp;', $result);
            }
        }

        // Reactivate whatever error handling we had previously.
        restore_error_handler();

        return $result;
    }
}
