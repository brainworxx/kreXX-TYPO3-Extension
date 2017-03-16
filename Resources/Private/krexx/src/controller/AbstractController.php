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

namespace Brainworxx\Krexx\Controller;

use Brainworxx\Krexx\Analyse\Caller\AbstractCaller;
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Output\AbstractOutput;

/**
 * Methods for the "controller" that are not directly "actions".
 *
 * @package Brainworxx\Krexx\Controller
 */
abstract class AbstractController
{
    /**
     * Config for the 'deep' backtrace analysis.
     *
     * @var array
     */
    protected $configFatal = array(
        'analyseProtected' => 'true',
        'analysePrivate' => 'true',
        'analyseTraversable' => 'true',
        'analyseConstants' => 'true',
        'analyseProtectedMethods' => 'true',
        'analysePrivateMethods' => 'true',
    );

    /**
     * The fileservice, used to read and write files.
     *
     * @var File
     */
    protected $fileService;

    /**
     * Sends the output to the browser during shutdown phase.
     *
     * @var AbstractOutput
     */
    protected $outputService;

    /**
     * Have we already send the CSS and JS?
     *
     * @var bool
     */
    protected static $headerSend = false;

    /**
     * Here we store the fatal error handler.
     *
     * @var \Brainworxx\Krexx\Errorhandler\Fatal
     */
    protected $krexxFatal;

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
     * Here we save all timekeeping stuff.
     *
     * @var array
     */
    protected static $timekeeping = array();

    /**
     * More timekeeping stuff.
     *
     * @var array
     */
    protected static $counterCache = array();

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
        $this->fileService = $pool->createClass('Brainworxx\\Krexx\\Service\\Misc\\File');

        // Register our output service.
        // Depending on the setting, we use another class here.
        $outputSetting = $pool->config->getSetting('destination');
        if ($outputSetting === 'shutdown') {
            $this->outputService = $pool->createClass('Brainworxx\\Krexx\\Service\\Output\\Shutdown');
        }
        if ($outputSetting === 'file') {
            $this->outputService = $pool->createClass('Brainworxx\\Krexx\\Service\\Output\\File');
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
        // Do we do an output as file?
        if (!self::$headerSend) {
            // Send doctype and css/js only once.
            self::$headerSend = true;
            return $this->pool->render->renderHeader('<!DOCTYPE html>', $headline, $this->outputCssAndJs());
        } else {
            return $this->pool->render->renderHeader('', $headline, '');
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
    protected function outputFooter($caller, $isExpanded = false)
    {
        // Now we need to stitch together the content of the ini file
        // as well as it's path.
        if (!is_readable($this->pool->config->getPathToIniFile())) {
            // Project settings are not accessible
            // tell the user, that we are using fallback settings.
            $path = 'Krexx.ini not found, using factory settings';
            // $config = array();
        } else {
            $path = 'Current configuration';
        }

        $model = $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
            ->setName($path)
            ->setType($this->pool->config->getPathToIniFile())
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
        $krexxDir = $this->pool->krexxDir;
        // Get the css file.
        $css = $this->fileService->getFileContents(
            $krexxDir .
            'resources/skins/' .
            $this->pool->config->getSetting('skin') .
            '/skin.css'
        );
        // Remove whitespace.
        $css = preg_replace('/\s+/', ' ', $css);

        // Adding our DOM tools to the js.
        if (is_readable($krexxDir . 'resources/jsLibs/kdt.min.js')) {
            $jsFile = $krexxDir . 'resources/jsLibs/kdt.min.js';
        } else {
            $jsFile = $krexxDir . 'resources/jsLibs/kdt.js';
        }
        $js = $this->fileService->getFileContents($jsFile);

        // Krexx.js is comes directly form the template.
        $path = $krexxDir . 'resources/skins/' . $this->pool->config->getSetting('skin');
        if (is_readable($path . '/krexx.min.js')) {
            $jsFile = $path . '/krexx.min.js';
        } else {
            $jsFile = $path . '/krexx.js';
        }
        $js .= $this->fileService->getFileContents($jsFile);

        return $this->pool->render->renderCssJs($css, $js);
    }

    /**
     * Disables the fatal handler and the tick callback.
     *
     * We disable the tick callback and the error handler during
     * a analysis, to generate faster output.
     *
     * @return $this
     *   Return $this for chaining.
     */
    public function noFatalForKrexx()
    {
        if ($this->fatalShouldActive) {
            $this->krexxFatal->setIsActive(false);
            unregister_tick_function(array($this->krexxFatal, 'tickCallback'));
        }

        return $this;
    }

    /**
     * Re-enable the fatal handler and the tick callback.
     *
     * We disable the tick callback and the error handler during
     * a analysis, to generate faster output.
     */
    public function reFatalAfterKrexx()
    {
        if ($this->fatalShouldActive) {
            $this->krexxFatal->setIsActive(true);
            register_tick_function(array($this->krexxFatal, 'tickCallback'));
        }
    }

    /**
     * The benchmark main function.
     *
     * @param array $timeKeeping
     *   The timekeeping array.
     *
     * @return array
     *   The benchmark array.
     *
     * @see http://php.net/manual/de/function.microtime.php
     * @author gomodo at free dot fr
     */
    protected function miniBenchTo(array $timeKeeping)
    {
        // Get the very first key.
        $start = key($timeKeeping);
        $totalTime = round((end($timeKeeping) - $timeKeeping[$start]) * 1000, 4);
        $result['url'] = $this->getCurrentUrl();
        $result['total_time'] = $totalTime;
        $prevMomentName = $start;
        $prevMomentStart = $timeKeeping[$start];

        foreach ($timeKeeping as $moment => $time) {
            if ($moment !== $start) {
                // Calculate the time.
                $percentageTime = round(((round(($time - $prevMomentStart) * 1000, 4) / $totalTime) * 100), 1);
                $result[$prevMomentName . '->' . $moment] = $percentageTime . '%';
                $prevMomentStart = $time;
                $prevMomentName = $moment;
            }
        }
        return $result;
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
        static $result;

        // Check if someone has been messing with the $_SERVER, to prevent
        // warnings and notices.
        if (empty($_SERVER) ||
            empty($_SERVER['SERVER_PROTOCOL']) ||
            empty($_SERVER['SERVER_PORT']) ||
            empty($_SERVER['SERVER_NAME'])) {
            $result = 'n/a';
        }

        if (!isset($result)) {
            $s = $_SERVER;

            // SSL or no SSL.
            if (!empty($s['HTTPS']) && $s['HTTPS'] === 'on') {
                $ssl = true;
            } else {
                $ssl = false;
            }
            $sp = strtolower($s['SERVER_PROTOCOL']);
            $protocol = substr($sp, 0, strpos($sp, '/'));
            if ($ssl) {
                $protocol .= 's';
            }

            $port = $s['SERVER_PORT'];

            if ((!$ssl && $port === '80') || ($ssl && $port === '443')) {
                // Normal combo with port and protocol.
                $port = '';
            } else {
                // We have a special port here.
                $port = ':' . $port;
            }

            if (isset($s['HTTP_HOST'])) {
                $host = $s['HTTP_HOST'];
            } else {
                $host = $s['SERVER_NAME'] . $port;
            }

            $result = htmlspecialchars($protocol . '://' . $host . $s['REQUEST_URI'], ENT_QUOTES, 'UTF-8');
        }
        return $result;
    }

    /**
     * Simply outputs a formatted var_dump.
     *
     * This is an internal debugging function, because it is
     * rather difficult to debug a debugger, when your tool of
     * choice is the debugger itself.
     *
     * @param mixed $data
     *   The data for the var_dump.
     */
    public static function formattedVarDump($data)
    {
        echo '<pre>';
        var_dump($data);
        echo('</pre>');
    }
}
