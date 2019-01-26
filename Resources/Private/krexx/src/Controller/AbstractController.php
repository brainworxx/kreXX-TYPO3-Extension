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

namespace Brainworxx\Krexx\Controller;

use Brainworxx\Krexx\Analyse\Caller\AbstractCaller;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\View\Output\AbstractOutput;

/**
 * Methods for the "controller" that are not directly "actions".
 *
 * @package Brainworxx\Krexx\Controller
 */
abstract class AbstractController
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
    protected static $jsCssSend = array();

    /**
     * Here we store the fatal error handler.
     *
     * @var \Brainworxx\Krexx\Errorhandler\Fatal
     */
    protected static $krexxFatal;

    /**
     * Stores whether out fatal error handler should be active.
     *
     * During a kreXX analysis, we deactivate it to improve performance.
     * Here we save, whether we should reactivate it.
     *
     * @var boolean
     */
    protected $fatalShouldActive = false;

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
     * Injects the pool.
     *
     * @param Pool $pool
     *   The pool, where we store the classes we need.
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
        $this->callerFinder = $pool->createClass('Brainworxx\\Krexx\\Analyse\\Caller\\CallerFinder');

        // Register our output service.
        // Depending on the setting, we use another class here.
        // We get a new output service for every krexx call, because the hosting
        // cms may do their stuff in the shutdown functions as well.
        $outputSetting = $pool->config->getSetting(Fallback::SETTING_DESTINATION);
        if ($outputSetting === Fallback::VALUE_BROWSER) {
            $this->outputService = $pool->createClass('Brainworxx\\Krexx\\View\\Output\\Browser');
        } elseif ($outputSetting === Fallback::VALUE_FILE) {
            $this->outputService = $pool->createClass('Brainworxx\\Krexx\\View\\Output\\File');
        }
    }

    /**
     * Simply outputs the Header of kreXX.
     *
     * @param string $headline
     *   The headline, displayed in the header.
     *
     * @return string
     *   The generated markup
     */
    protected function outputHeader($headline)
    {
        return $this->pool->render->renderHeader('<!DOCTYPE html>', $headline, $this->outputCssAndJs());
    }

    /**
     * Simply renders the footer and output current settings.
     *
     * @param array $caller
     *   Where was kreXX initially invoked from.
     * @param boolean $isExpanded
     *   Are we rendering an expanded footer?
     *   TRUE when we render the settings menu only.
     *
     * @return string
     *   The generated markup.
     */
    protected function outputFooter(array $caller, $isExpanded = false)
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

        $model = $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
            ->setName($path)
            ->setType($this->pool->fileService->filterFilePath($pathToIni))
            ->setHelpid('currentSettings')
            ->injectCallback(
                $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughConfig')
            );

        $configOutput = $this->pool->render->renderExpandableChild($model, $isExpanded);
        return $this->pool->render->renderFooter($caller, $configOutput, $isExpanded);
    }

    /**
     * Outputs the CSS and JS.
     *
     * @return string
     *   The generated markup.
     */
    protected function outputCssAndJs()
    {
        // We only do this once per output type.
        $destination = $this->pool->config->getSetting('destination');
        $result = isset(static::$jsCssSend[$destination]);
        static::$jsCssSend[$destination] = true;
        if ($result === true) {
            // Been here, done that.
            return '';
        }

        // Get the css file.
        $css = $this->pool->fileService->getFileContents(
            KREXX_DIR .
            'resources/skins/' .
            $this->pool->config->getSetting(Fallback::SETTING_SKIN) .
            '/skin.css'
        );
        // Remove whitespace.
        $css = preg_replace('/\s+/', ' ', $css);

        // Adding our DOM tools to the js.
        if ($this->pool->fileService->fileIsReadable(KREXX_DIR . 'resources/jsLibs/kdt.min.js') === true) {
            $jsFile = KREXX_DIR . 'resources/jsLibs/kdt.min.js';
        } else {
            $jsFile = KREXX_DIR . 'resources/jsLibs/kdt.js';
        }

        $jsCode = $this->pool->fileService->getFileContents($jsFile);

        // Krexx.js is comes directly form the template.
        $path = KREXX_DIR . 'resources/skins/' . $this->pool->config->getSetting(Fallback::SETTING_SKIN);
        if ($this->pool->fileService->fileIsReadable($path . '/krexx.min.js') === true) {
            $jsFile = $path . '/krexx.min.js';
        } else {
            $jsFile = $path . '/krexx.js';
        }

        $jsCode .= $this->pool->fileService->getFileContents($jsFile);

        return $this->pool->render->renderCssJs($css, $jsCode);
    }

    /**
     * Disables the fatal handler and the tick callback.
     *
     * We disable the tick callback and the error handler during
     * a analysis, to generate faster output. We also disable
     * other kreXX calls, which may be caused by the debug callbacks
     * to prevent kreXX from starting other kreXX calls.
     *
     * @return $this
     *   Return $this for chaining.
     */
    public function noFatalForKrexx()
    {
        if ($this->fatalShouldActive === true) {
            $this::$krexxFatal->setIsActive(false);
            unregister_tick_function(array($this::$krexxFatal, 'tickCallback'));
        }

        return $this;
    }

    /**
     * Re-enable the fatal handler and the tick callback.
     *
     * We disable the tick callback and the error handler during
     * a analysis, to generate faster output. We re-enable kreXX
     * afterwards, so the dev can use it again.
     *
     * @return $this
     *   Return $this for chaining.
     */
    public function reFatalAfterKrexx()
    {
        if ($this->fatalShouldActive === true) {
            $this::$krexxFatal->setIsActive(true);
            register_tick_function(array($this::$krexxFatal, 'tickCallback'));
        }

        return $this;
    }

    /**
     * Return the current URL.
     *
     * @see http://stackoverflow.com/questions/6768793/get-the-full-url-in-php
     * @author Timo Huovinen
     *
     * @return string
     *   The current URL.
     */
    protected function getCurrentUrl()
    {
        $server = $this->pool->getServer();

        // Check if someone has been messing with the $_SERVER, to prevent
        // warnings and notices.
        if (empty($server) === true ||
            empty($server['SERVER_PROTOCOL']) === true ||
            empty($server['SERVER_PORT']) === true ||
            empty($server['SERVER_NAME'])=== true) {
            return 'n/a';
        }

        // SSL or no SSL.
        $ssl = (!empty($server['HTTPS']) && $server['HTTPS'] === 'on');

        $protocol = strtolower($server['SERVER_PROTOCOL']);
        $protocol = substr($protocol, 0, strpos($protocol, '/'));
        if ($ssl === true) {
            $protocol .= 's';
        }

        $port = $server['SERVER_PORT'];

        if (($ssl === false && $port === '80') || ($ssl === true && $port === '443')) {
            // Normal combo with port and protocol.
            $port = '';
        } else {
            // We have a special port here.
            $port = ':' . $port;
        }

        if (isset($server['HTTP_HOST']) === true) {
            $host = $server['HTTP_HOST'];
        } else {
            $host = $server['SERVER_NAME'] . $port;
        }

        return $this->pool->encodingService->encodeString($protocol . '://' . $host . $server['REQUEST_URI']);
    }
}
