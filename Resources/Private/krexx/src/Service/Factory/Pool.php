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

namespace Brainworxx\Krexx\Service\Factory;

use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\Analyse\Code\Scope;
use Brainworxx\Krexx\Analyse\Routing\Routing;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Service\Flow\Recursion;
use Brainworxx\Krexx\Service\Misc\Encoding;
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Service\Misc\Registry;
use Brainworxx\Krexx\View\Messages;
use Brainworxx\Krexx\View\Output\Chunks;
use Brainworxx\Krexx\View\Render;

/**
 * Here we store all classes that we need.
 *
 * @package Brainworxx\Krexx\Service\Factory
 */
class Pool extends AbstractFactory
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
     * Our registry.
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
     * Our file handling is done in the file service.
     *
     * @var File
     */
    public $fileService;

    /**
     * Sting encoding happens here.
     *
     * @var Encoding
     */
    public $encodingService;

    /**
     * The event handler handles events.
     *
     * @var Event
     */
    public $eventService;

    /**
     * Initializes all needed classes.
     *
     * @param array $rewrite
     *   The rewrites we are using for the classes.
     */
    public function __construct(array $rewrite = [])
    {
        $this->rewrite = $rewrite;

        // Initializes the file service.
        $this->createClass(File::class);
        // Initializes the messages.
        $this->createClass(Messages::class);
        // Initialize the encoding service.
        $this->createClass(Encoding::class);
        // Initializes the configuration.
        $this->createClass(Config::class);
        // Initialize the emergency handler.
        $this->createClass(Emergency::class);
        // Initialize the recursionHandler.
        $this->createClass(Recursion::class);
        // Initialize the code generation.
        $this->createClass(Codegen::class);
        // Initializes the chunks handler.
        $this->createClass(Chunks::class);
        // Initializes the scope analysis.
        $this->createClass(Scope::class);
        // Initializes the routing.
        $this->createClass(Routing::class);
        // Initialize the event handler.
        $this->createClass(Event::class);
        // Initializes the render class.
        $this->createClass($this->config->getSkinClass());
        // Create the registry
        $this->createClass(Registry::class);
        // Check the environment and prepare the feedback, if necessary.
        $this->checkEnvironment();
    }

    /**
     * (Re)initializes everything in the pool, in case in-runtime
     * factory overwrites.
     *
     * @deprecated
     *   Since 3.1.0. Will be removed.
     */
    public function init()
    {
        $this->__construct();
    }

    /**
     * Check if the environment is as it should be.
     */
    protected function checkEnvironment()
    {
        // Check chunk folder is writable.
        // If not, give feedback!
        $chunkFolder = $this->config->getChunkDir();
        if (is_writable($chunkFolder) === false) {
            $this->messages->addMessage(
                'chunksNotWritable',
                [$this->fileService->filterFilePath($chunkFolder)]
            );
            // We can work without chunks, but this will require much more memory!
            $this->chunks->setUseChunks(false);
        }

        // Check if the log folder is writable.
        // If not, give feedback!
        $logFolder = $this->config->getLogDir();
        if (is_writable($logFolder) === false) {
            $this->messages->addMessage(
                'logNotWritable',
                [$this->fileService->filterFilePath($logFolder)]
            );
            // Tell the chunk output that we have no write access in the logging
            // folder.
            $this->chunks->setUseLogging(false);
        }

        // At this point, we won't inform the dev right away. The error message
        // will pop up, when kreXX is actually displayed, no need to bother the
        // dev just now.
    }

    /**
     * Renew the "semi-singletons" after an analysis.
     */
    public function reset()
    {
        // We need to reset our recursion handler, because
        // the content of classes might change with another run.
        $this->createClass(Recursion::class);
        // Initialize the code generation.
        $this->createClass(Codegen::class);
        $this->createClass(Scope::class);
        // We also initialize emergency handler timer.
        $this->emergencyHandler->initTimer();
    }
}
