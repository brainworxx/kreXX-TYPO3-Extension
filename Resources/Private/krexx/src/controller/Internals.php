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

namespace Brainworxx\Krexx\Controller;

use Brainworxx\Krexx\Service\Misc\Shutdown;
use Brainworxx\Krexx\Service\Storage;
use Brainworxx\Krexx\Model\Simple;

/**
 * Methods for the "controller" that are not directly "actions".
 *
 * @package Brainworxx\Krexx\Controller
 */
class Internals
{

    /**
     * Sends the output to the browser during shutdown phase.
     *
     * @var Shutdown
     */
    protected $shutdownHandler;

    /**
     * Have we already send the CSS and JS?
     *
     * @var bool
     */
    protected $headerSend = false;

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
     * @var string array
     */
    protected $timekeeping = array();
    protected $counterCache = array();

    /**
     * Our storage wher we keep al relevant classes.
     *
     * @var Storage
     */
    protected $storage;

    /**
     * Injects the storage.
     *
     * @param Storage $storage
     *   The storage, where we store the classes we need.
     */
    public function __construct(Storage $storage)
    {
        $this->storage = $storage;

        // Register our shutdown handler. He will handle the display
        // of kreXX after the hosting CMS is finished.
        $this->shutdownHandler = new Shutdown($this->storage);
        register_shutdown_function(array(
            $this->shutdownHandler,
            'shutdownCallback'
        ));
    }

    /**
     * Finds the place in the code from where krexx was called.
     *
     * @return array
     *   The code, from where krexx was called
     */
    protected function findCaller()
    {
        $backtrace = debug_backtrace();
        while ($caller = array_pop($backtrace)) {
            if (isset($caller['function']) && strtolower($caller['function']) === 'krexx') {
                break;
            }
            if (isset($caller['class']) && strtolower($caller['class']) === 'krexx') {
                break;
            }
        }

        // We will not keep the whole backtrace im memory. We only return what we
        // actually need.
        return array(
            'file' => htmlspecialchars($caller['file']),
            'line' => (int)$caller['line'],
            // We don't need to escape the varname, this will be done in
            // the model.
            'varname' => $this->getVarName($caller['file'], $caller['line']),
        );
    }

    /**
     * Tries to extract the name of the variable which we try to analyse.
     *
     * @param string $file
     *   Path to the sourcecode file.
     * @param string $line
     *   The line from where kreXX was called.
     *
     * @return string
     *   The name of the variable.
     */
    protected function getVarName($file, $line)
    {
        // Retrieve the call from the sourcecode file.
        $source = file($file);
        // Fallback to '. . .'.
        $varname = '. . .';

        // Now that we have the line where it was called, we must check if
        // we have several commands in there.
        $possibleCommands = explode(';', $source[$line - 1]);
        // Now we must weed out the none krexx commands.
        foreach ($possibleCommands as $key => $command) {
            if (strpos(strtolower($command), 'krexx') === false) {
                unset($possibleCommands[$key]);
            }
        }
        // I have no idea how to determine the actual call of krexx if we
        // are dealing with several calls per line.
        if (count($possibleCommands) === 1) {
            $sourceCall = reset($possibleCommands);

            // Now that we have our actual call, we must remove the krexx-part
            // from it.
            $possibleFunctionnames = array(
                'krexx',
                'krexx::open',
                'krexx::' . $this->storage->config->getDevHandler(),
                'Krexx::open',
                'Krexx::' . $this->storage->config->getDevHandler()
            );
            foreach ($possibleFunctionnames as $funcname) {
                // This little baby tries to resolve everything inside the
                // brackets of the kreXX call.
                preg_match('/' . $funcname . '\s*\((.*)\)\s*/u', $sourceCall, $name);
                if (isset($name[1])) {
                    $varname = $name[1];
                    break;
                }
            }
        }

        $varname = $this->storage->encodeString(trim($varname, " \t\n\r\0\x0B'\""));

        // Check if we have a value.
        if (empty($varname)) {
            $varname = '. . .';
        }

        return $varname;
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
        if (!$this->headerSend) {
            // Send doctype and css/js only once.
            $this->headerSend = true;
            return $this->storage->render->renderHeader('<!DOCTYPE html>', $headline, $this->outputCssAndJs());
        } else {
            return $this->storage->render->renderHeader('', $headline, '');
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
        if (!is_readable($this->storage->config->getPathToIni())) {
            // Project settings are not accessible
            // tell the user, that we are using fallback settings.
            $path = 'Krexx.ini not found, using factory settings';
            // $config = array();
        } else {
            $path = 'Current configuration';
        }

        $wholeConfig = $this->storage->config->getWholeConfiguration();
        $source = $wholeConfig[0];
        $config = $wholeConfig[1];

        $model = new Simple($this->storage);
        $model->setName($path)
            ->setType($this->storage->config->getPathToIni())
            ->setHelpid('currentSettings')
            ->addParameter('data', $config)
            ->addParameter('source', $source)
            ->initCallback('Iterate\ThroughConfig');

        $configOutput = $this->storage->render->renderExpandableChild($model, $isExpanded);
        return $this->storage->render->renderFooter($caller, $configOutput, $isExpanded);
    }

    /**
     * Outputs the CSS and JS.
     *
     * @return string
     *   The generated markup.
     */
    protected function outputCssAndJs()
    {
        $krexxDir = $this->storage->config->krexxdir;
        // Get the css file.
        $css = $this->storage->getFileContents(
            $krexxDir .
            'resources/skins/' .
            $this->storage->config->getConfigValue('output', 'skin') .
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
        $js = $this->storage->getFileContents($jsFile);

        // Krexx.js is comes directly form the template.
        $path = $krexxDir . 'resources/skins/' . $this->storage->config->getConfigValue('output', 'skin');
        if (is_readable($path . '/krexx.min.js')) {
            $jsFile = $path . '/krexx.min.js';
        } else {
            $jsFile = $path . '/krexx.js';
        }
        $js .= $this->storage->getFileContents($jsFile);

        return $this->storage->render->renderCssJs($css, $js);
    }

    /**
     * Disables the fatal handler and the tick callback.
     *
     * We disable the tick callback and the error handler during
     * a analysis, to generate faster output.
     */
    public function noFatalForKrexx()
    {
        if ($this->fatalShouldActive) {
            $this->krexxFatal->setIsActive(false);
            unregister_tick_function(array($this->krexxFatal, 'tickCallback'));
        }
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
            if ($moment != $start) {
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

            if ((!$ssl && $port == '80') || ($ssl && $port == '443')) {
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
}
