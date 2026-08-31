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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Controller;

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughConfig;
use Brainworxx\Krexx\Analyse\Caller\AbstractCaller;
use Brainworxx\Krexx\Analyse\Caller\CallerFinder;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\View\Output\AbstractOutput;
use Brainworxx\Krexx\View\Output\Browser;
use Brainworxx\Krexx\View\Output\BrowserImmediately;
use Brainworxx\Krexx\View\Output\File;

/**
 * Methods for the "controller" that are not directly "actions".
 */
abstract class AbstractController implements ConfigConstInterface
{
    /**
     * Here we remember, if we are currently running an analysis.
     * The debug methods may trigger another run, and we may get into
     * trouble, memory or runtime wise.
     *
     * @var bool
     */
    public static bool $analysisInProgress = false;

    /**
     * Sends the output to the browser during shutdown phase.
     *
     * @var AbstractOutput
     */
    protected AbstractOutput $outputService;

    /**
     * Have we already send the CSS and JS, depending on the destination?
     *
     * The only reason for this to be a class variable is, that we need it for
     * the unit tests.
     *
     * @var bool[]
     */
    protected static array $jsCssSend = [];

    /**
     * Finds our caller.
     *
     * @var AbstractCaller
     */
    protected AbstractCaller $callerFinder;

    /**
     * The configured output destination.
     *
     * @var string
     */
    protected string $destination;

    /**
     * Injects the pool.
     *
     * @param Pool $pool
     *   The pool, where we store the classes we need.
     */
    public function __construct(protected Pool $pool)
    {
        $this->callerFinder = $pool->createClass(classname: CallerFinder::class);

        // Register our output service.
        // Depending on the setting, we use another class here.
        // We get a new output service for every krexx call, because the hosting
        // cms may do their stuff in the shutdown functions as well.
        $this->destination = $pool->config->getSetting(name: static::SETTING_DESTINATION);
        $this->outputService = match ($this->destination) {
            static::VALUE_BROWSER => $pool->createClass(
                classname: Browser::class
            ),
            static::VALUE_FILE => $pool->createClass(classname: File::class),
            default => $pool->createClass(classname: BrowserImmediately::class),
        };

        $this->pool->reset();
    }

    /**
     * Simply renders the footer and output current settings.
     *
     * @param string[] $caller
     *   Where was kreXX initially invoked from.
     * @param bool $isExpanded
     *   Are we rendering an expanded footer?
     *   TRUE when we render the settings menu only.
     *
     * @return string
     *   The generated markup.
     */
    protected function outputFooter(array $caller, bool $isExpanded = false): string
    {
        // Now we need to stitch together the content of the configuration file
        // as well as its path.
        $pathToConfig = $this->pool->config->getPathToConfigFile();
        if ($this->pool->fileService->fileIsReadable(filePath: $pathToConfig)) {
            $path = $this->pool->messages->getHelp(key: 'currentConfig');
        } else {
            // Project settings are not accessible
            // tell the user, that we are using fallback settings.
            $path = $this->pool->messages->getHelp(key: 'configFileNotFound');
        }

        return $this->pool->render->renderFooter(
            caller: $caller,
            model: $this->pool->createClass(classname: Model::class)
                ->setName(name: $path)
                ->setType(type: $pathToConfig)
                ->setHelpid(helpId: 'currentSettings')
                ->injectCallback(
                    object: $this->pool->createClass(classname: ThroughConfig::class)
                ),
            configOnly: $isExpanded
        );
    }

    /**
     * Outputs the CSS and JS.
     *
     * @return string
     *   The generated markup.
     */
    protected function outputCssAndJs(): string
    {
        // We only do this once per output type.
        if (isset(static::$jsCssSend[$this->destination . getmypid()])) {
            // Been here, done that.
            return '';
        }
        static::$jsCssSend[$this->destination . getmypid()] = true;

        // Adding the js to the output.
        $skinDirectory = $this->pool->config->getSkinDirectory();
        if ($this->pool->fileService->fileIsReadable(filePath: KREXX_DIR . 'resources/jsLibs/kdt.min.js')) {
            // The js works only if everything is minified.
            $jsCode = $this->pool->fileService->getFileContents(filePath: KREXX_DIR . 'resources/jsLibs/kdt.min.js') .
                $this->pool->fileService->getFileContents(filePath: $skinDirectory . 'krexx.min.js');
        } else {
            $jsCode = $this->pool->fileService->getFileContents(filePath: KREXX_DIR . 'resources/jsLibs/kdt.js') .
                $this->pool->fileService->getFileContents(filePath: $skinDirectory . 'krexx.js');
        }

        // Get the css file.
        if ($this->pool->fileService->fileIsReadable(filePath: $skinDirectory . 'skin.min.css')) {
            $css = $this->pool->fileService->getFileContents(filePath: $skinDirectory . 'skin.min.css');
        } else {
            $css = $this->pool->fileService->getFileContents(filePath: $skinDirectory . 'skin.css');
        }

        /** @var Model $model */
        $model = $this->pool->createClass(classname: Model::class);
        $model->setData(data: $jsCode)->setNormal(normal: $css);
        $this->pool->eventService->dispatch(name: static::class . '::outputCssAndJs', model: $model);
        return $this->pool->render->renderCssJs($model->getNormal(), $model->getData());
    }
}
