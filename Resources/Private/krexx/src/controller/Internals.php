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

use Brainworxx\Krexx\Config\Config;
use Brainworxx\Krexx\Framework\ShutdownHandler;
use Brainworxx\Krexx\Framework\Toolbox;
use Brainworxx\Krexx\Model\Output\IterateThroughConfig;
use Brainworxx\Krexx\View\Messages;
use Brainworxx\Krexx\View\Help;
use Brainworxx\Krexx\View\Render;

/**
 * Controller actions toolbox.
 *
 * @package Brainworxx\Krexx\Controller
 */
class Internals
{
    /**
     * Unix timestamp, used to determine if we need to do an emergency break.
     *
     * @var int
     */
    protected static $timer = 0;

    /**
     * Counts how often kreXX was called.
     *
     * @var int
     */
    public static $KrexxCount = 0;

    /**
     * Sends the output to the browser during shutdown phase.
     *
     * @var ShutdownHandler
     */
    public static $shutdownHandler;

    /**
     * The current nesting level we are in.
     *
     * @var int
     */
    public static $nestingLevel = 0;

    /**
     * Have we already send the CSS and JS?
     *
     * @var bool
     */
    protected static $headerSend = false;

    /**
     * An instance of the recursion handler.
     *
     * It gets reinstantiated with every new call.
     *
     * @var \Brainworxx\Krexx\Analysis\RecursionHandler
     */
    public static $recursionHandler;

    /**
     * The instance of the render class from the skin.
     *
     * Gets loaded in the output footer.
     *
     * @var Render
     */
    public static $render;

    /**
     * Loads the renderer from the skin.
     */
    public static function loadRendrerer()
    {
        $skin = Config::getConfigValue('output', 'skin');
        $path = Config::$krexxdir . 'resources/skins/' . $skin . '/Render.php';
        $classname = 'Brainworxx\Krexx\View\\' . ucfirst($skin) . '\\Render';
        include_once $path;
        self::$render = new $classname;
    }

    /**
     * Finds the place in the code from where krexx was called.
     *
     * @return array
     *   The code, from where krexx was called
     */
    protected static function findCaller()
    {
        $backtrace = debug_backtrace();
        while ($caller = array_pop($backtrace)) {
            if (isset($caller['function']) && strtolower($caller['function']) == 'krexx') {
                break;
            }
            if (isset($caller['class']) && strtolower($caller['class']) == 'krexx') {
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
            'varname' => self::getVarName($caller['file'], $caller['line']),
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
    protected static function getVarName($file, $line)
    {
        // Retrieve the call from the sourcecode file.
        $source = file($file);

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
        if (count($possibleCommands) > 1) {
            // Fallback to '...'.
            $varname = '...';
        } else {
            $sourceCall = reset($possibleCommands);

            // Now that we have our actual call, we must remove the krexx-part
            // from it.
            $possibleFunctionnames = array(
                'krexx',
                'krexx::open',
                'krexx::' . Config::getDevHandler(),
                'Krexx::open',
                'Krexx::' . Config::getDevHandler()
            );
            foreach ($possibleFunctionnames as $funcname) {
                preg_match('/' . $funcname . '\s*\((.*)\)\s*/u', $sourceCall, $name);
                if (isset($name[1])) {
                    $varname = $name[1];
                    break;
                }
            }
        }

        // Check if we have a value.
        if (!isset($varname) || strlen($varname) == 0) {
            $varname = '...';
        }

        return $varname;
    }

    /**
     * Finds out, if krexx was called too often, to prevent large output.
     *
     * @return bool
     *   Whether kreXX was called too often or not.
     */
    protected static function checkMaxCall()
    {
        $result = false;
        $maxCall = (int)Config::getConfigValue('runtime', 'maxCall');
        if (self::$KrexxCount >= $maxCall) {
            // Called too often, we might get into trouble here!
            $result = true;
        }
        // Give feedback if this is our last call.
        if (self::$KrexxCount == $maxCall - 1) {
            Messages::addMessage(Help::getHelp('maxCallReached'), 'critical');
        }
        self::$KrexxCount++;
        return $result;
    }

    /**
     * Checks if there is enough memory and time left on the Server.
     *
     * @param mixed $enable
     *   Enables and disables the check itself. When disabled, it will always
     *   return TRUE (all is OK).
     *
     * @return bool
     *   Boolean to show if we have enough left.
     *   TRUE = all is OK.
     *   FALSE = we have a problem.
     */
    public static function checkEmergencyBreak($enable = null)
    {
        static $result = true;
        static $isDisabled = false;

        // We are saving the value of being enabled / disabled.
        if ($enable === true) {
            $isDisabled = false;
        }
        if ($enable === false) {
            $isDisabled = true;
        }

        // Tell them everything is fine, when it is disabled.
        if ($isDisabled) {
            return true;
        }

        if ($result === false) {
            // This has failed before!
            // No need to check again!
            return false;
        }

        // Check Runtime.
        if (self::$timer + (int)Config::getConfigValue('runtime', 'maxRuntime') <= time()) {
            // This is taking longer than expected.
            $result = false;
        }

        if ($result) {
            // Commence with the memory check.
            // Check this only, if we have enough time left.
            $limit = strtoupper(ini_get('memory_limit'));
            $memoryLimit = 0;
            if (preg_match('/^(\d+)(.)$/', $limit, $matches)) {
                if ($matches[2] == 'M') {
                    // Megabyte.
                    $memoryLimit = $matches[1] * 1024 * 1024;
                } elseif ($matches[2] == 'K') {
                    // Kilobyte.
                    $memoryLimit = $matches[1] * 1024;
                }
            }

            // Were we able to determine a limit?
            if ($memoryLimit > 2) {
                $usage = memory_get_usage();
                $left = $memoryLimit - $usage;
                // Is more left than is configured?
                $result = $left >= (int)Config::getConfigValue('runtime', 'memoryLeft') * 1024 * 1024;
            }
        }

        if (!$result) {
            // No more memory or time, we disable kreXX!
            \Krexx::disable();
        }

        return $result;
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
    protected static function outputHeader($headline)
    {
        // Do we do an output as file?
        if (!self::$headerSend) {
            // Send doctype and css/js only once.
            self::$headerSend = true;
            return self::$render->renderHeader('<!DOCTYPE html>', $headline, self::outputCssAndJs());
        } else {
            return self::$render->renderHeader('', $headline, '');
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
    protected static function outputFooter($caller, $isExpanded = false)
    {
        // Now we need to stitch together the content of the ini file
        // as well as it's path.
        if (!is_readable(Config::getPathToIni())) {
            // Project settings are not accessible
            // tell the user, that we are using fallback settings.
            $path = 'Krexx.ini not found, using factory settings';
            // $config = array();
        } else {
            $path = 'Current configuration';
        }

        $wholeConfig = Config::getWholeConfiguration();
        $source = $wholeConfig[0];
        $config = $wholeConfig[1];

        $model = new IterateThroughConfig();
        $model->setName($path)
            ->setType(Config::getPathToIni())
            ->setHelpid('currentSettings')
            ->addParameter('config', $config)
            ->addParameter('source', $source);

        $configOutput = self::$render->renderExpandableChild($model, $isExpanded);
        return self::$render->renderFooter($caller, $configOutput, $isExpanded);
    }

    /**
     * Outputs the CSS and JS.
     *
     * @return string
     *   The generated markup.
     */
    protected static function outputCssAndJs()
    {
        // Get the css file.
        $css = Toolbox::getFileContents(
            Config::$krexxdir . 'resources/skins/' . Config::getConfigValue('output', 'skin') . '/skin.css'
        );
        // Remove whitespace.
        $css = preg_replace('/\s+/', ' ', $css);

        // Adding our DOM tools to the js.
        if (is_readable(Config::$krexxdir . 'resources/jsLibs/kdt.min.js')) {
            $jsFile = Config::$krexxdir . 'resources/jsLibs/kdt.min.js';
        } else {
            $jsFile = Config::$krexxdir . 'resources/jsLibs/kdt.js';
        }
        $js = Toolbox::getFileContents($jsFile);

        // Krexx.js is comes directly form the template.
        $path = Config::$krexxdir . 'resources/skins/' . Config::getConfigValue('output', 'skin');
        if (is_readable($path . '/krexx.min.js')) {
            $jsFile = $path . '/krexx.min.js';
        } else {
            $jsFile = $path . '/krexx.js';
        }
        $js .= Toolbox::getFileContents($jsFile);

        return self::$render->renderCssJs($css, $js);
    }

    /**
     * Resets the timer.
     *
     * When a certain time has passed, kreXX will use an emergency break to
     * prevent too large output (or no output at all (WSOD)).
     */
    protected static function resetTimer()
    {
        if (self::$timer == 0) {
            self::$timer = time();
        }
    }
}
