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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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
use Brainworxx\Krexx\Analyse\ConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\View\Output\AbstractOutput;
use Brainworxx\Krexx\View\Output\Browser;
use Brainworxx\Krexx\View\Output\File;

/**
 * Methods for the "controller" that are not directly "actions".
 *
 * @package Brainworxx\Krexx\Controller
 */
abstract class AbstractController implements ConstInterface
{
    /**
     * Here we remember, if we are currently running a analysis.
     * The debug methods may trigger another run, and we may get into
     * trouble, memory or runtime wise.
     *
     * @var bool
     */
    public static $analysisInProgress = false;

    /**
     * Sends the output to the browser during shutdown phase.
     *
     * @var AbstractOutput
     */
    protected $outputService;

    /**
     * Have we already send the CSS and JS, depending on the destination?
     *
     * @var array
     */
    protected static $jsCssSend = [];

    /**
     * Our pool where we keep all relevant classes.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * Finds our caller.
     *
     * @var AbstractCaller
     */
    protected $callerFinder;

    /**
     * The configured output destination.
     *
     * @var string
     */
    protected $destination;

    /**
     * Injects the pool.
     *
     * @param Pool $pool
     *   The pool, where we store the classes we need.
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
        $this->callerFinder = $pool->createClass(CallerFinder::class);

        // Register our output service.
        // Depending on the setting, we use another class here.
        // We get a new output service for every krexx call, because the hosting
        // cms may do their stuff in the shutdown functions as well.
        $this->destination = $pool->config->getSetting(Fallback::SETTING_DESTINATION);
        if ($this->destination === Fallback::VALUE_BROWSER) {
            $this->outputService = $pool->createClass(Browser::class);
        } elseif ($this->destination === Fallback::VALUE_FILE) {
            $this->outputService = $pool->createClass(File::class);
        }
    }

    /**
     * Simply renders the footer and output current settings.
     *
     * @param array $caller
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
        // Now we need to stitch together the content of the ini file
        // as well as it's path.
        $pathToIni = $this->pool->config->getPathToIniFile();
        if ($this->pool->fileService->fileIsReadable($pathToIni) === true) {
            $path = $this->pool->messages->getHelp('currentConfig');
        } else {
            // Project settings are not accessible
            // tell the user, that we are using fallback settings.
            $path = $this->pool->messages->getHelp('iniNotFound');
        }

        $model = $this->pool->createClass(Model::class)
            ->setName($path)
            ->setType($this->pool->fileService->filterFilePath($pathToIni))
            ->setHelpid('currentSettings')
            ->injectCallback(
                $this->pool->createClass(ThroughConfig::class)
            );

        return $this->pool->render->renderFooter(
            $caller,
            $model,
            $isExpanded
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
        $result = isset(static::$jsCssSend[$this->destination]);
        static::$jsCssSend[$this->destination] = true;
        if ($result === true) {
            // Been here, done that.
            return '';
        }

        // Adding our DOM tools to the js.
        if ($this->pool->fileService->fileIsReadable(KREXX_DIR . 'resources/jsLibs/kdt.min.js') === true) {
            $kdtPath = KREXX_DIR . 'resources/jsLibs/kdt.min.js';
        } else {
            $kdtPath = KREXX_DIR . 'resources/jsLibs/kdt.js';
        }
        $jsCode = $this->pool->fileService->getFileContents($kdtPath);

        // Adding the skin css and js.
        $skinDirectory = $this->pool->config->getSkinDirectory();
        // Get the css file.
        $css = $this->pool->fileService->getFileContents($skinDirectory . 'skin.css');
        // Remove whitespace.
        $css = preg_replace('/\s+/', ' ', $css);
        // Krexx.js is comes directly form the template.
        if ($this->pool->fileService->fileIsReadable($skinDirectory . 'krexx.min.js') === true) {
            $skinJsPath = $skinDirectory . 'krexx.min.js';
        } else {
            $skinJsPath = $skinDirectory . 'krexx.js';
        }
        $jsCode .= $this->pool->fileService->getFileContents($skinJsPath);

        return $this->pool->render->renderCssJs($css, $jsCode);
    }
}
