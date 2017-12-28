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

use Brainworxx\Krexx\Analyse\Code\Scope;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Service\Flow\Recursion;
use Brainworxx\Krexx\Service\Misc\Registry;
use Brainworxx\Krexx\View\Output\Chunks;
use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\View\Messages;
use Brainworxx\Krexx\View\Render;
use Brainworxx\Krexx\Analyse\Routing\Routing;
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Service\Misc\Encoding;

/**
 * Here we store all classes that we need.
 *
 * @package Brainworxx\Krexx\Service\Factory
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
     * The routing of our analysis.
     *
     * @var Routing
     */
    public $routing;

    /**
     * @var File
     */
    public $fileService;

    /**
     * @var Encoding
     */
    public $encodingService;

    /**
     * Initializes all needed classes.
     */
    public function __construct()
    {
        $this->init();
        $this->registry = $this->createClass('Brainworxx\\Krexx\\Service\\Misc\\Registry');
    }

    /**
     * (Re)initializes everything in the pool, in case in-runtime
     * factory overwrites.
     */
    public function init()
    {
        // Get the rewrites from the $GLOBALS.
        $this->flushRewrite();
        // Initialize the encoding service.
        $this->encodingService = $this->createClass('Brainworxx\\Krexx\\Service\\Misc\\Encoding');
        // Initializes the file service.
        $this->fileService = $this->createClass('Brainworxx\\Krexx\\Service\\Misc\\File');
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
        // Initializes the scope analysis.
        $this->scope = $this->createClass('Brainworxx\\Krexx\\Analyse\\Code\\Scope');
        // Initializes the routing.
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
        if (is_writeable($chunkFolder) === false) {
            $chunkFolder = $this->fileService->filterFilePath($chunkFolder);
            $this->messages->addMessage('chunksNotWritable', array($chunkFolder));
            // We can work without chunks, but this will require much more memory!
            $this->chunks->setUseChunks(false);
        }

        // Check if the log folder is writable.
        // If not, give feedback!
        $logFolder = $this->config->getLogDir();
        if (is_writeable($logFolder) === false) {
            $logFolder = $this->fileService->filterFilePath($logFolder);
            $this->messages->addMessage('logNotWritable', array($logFolder));
            // Tell the chunk output that we have no write access in the logging
            // folder.
            $this->chunks->setUseLogging(false);
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
        $this->scope = $this->createClass('Brainworxx\\Krexx\\Analyse\\Code\\Scope');
        // We also reset our emergency handler timer.
        $this->emergencyHandler->resetTimer();
    }

    /**
     * Loads the renderer from the skin.
     */
    protected function initRenderer()
    {
        $skin = $this->config->getSetting('skin');
        $classname = 'Brainworxx\\Krexx\\View\\' . ucfirst($skin) . '\\Render';
        include_once KREXX_DIR . 'resources/skins/' . $skin . '/Render.php';
        $this->render =  $this->createClass($classname);
    }
}
