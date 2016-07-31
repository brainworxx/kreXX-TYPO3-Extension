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

namespace Brainworxx\Krexx\Config;

/**
 * Configuration fallback settings.
 *
 * We have so much of them, they need an own class.
 *
 * @package Brainworxx\Krexx\Framework
 */
class Fallback
{
    /**
     * Is the code generation allowed? We only allow it during a normal analysis.
     *
     * @var bool
     */
    public static $allowCodegen = false;

    /**
     * Stores if kreXX is actually enabled.
     *
     * @var bool
     */
    protected static $isEnabled = true;

    /**
     * Fallback settings, in case there is nothing in the config ini.
     *
     * @var array
     */
    public static $configFallback = array(
        'runtime' => array(
            'disabled' => 'false',
            'detectAjax' => 'true',
            'level' => '5',
            'maxCall' => '10',
            'memoryLeft' => '64',
            'maxRuntime' => '60',
        ),
        'output' => array(
            'skin' => 'smokygrey',
            'destination' => 'frontend',
            'folder' => 'log',
            'maxfiles' => '10',
        ),
        'properties' => array(
            'analyseProtected' => 'false',
            'analysePrivate' => 'false',
            'analyseConstants' => 'true',
            'analyseTraversable' => 'true',
        ),
        'methods' => array(
            'analyseMethodsAtall' => 'true',
            'analyseProtectedMethods' => 'false',
            'analysePrivateMethods' => 'false',
            'debugMethods' => 'debug,__toArray,toArray,__toString,toString,_getProperties,__debugInfo',
        ),
        'backtraceAndError' => array(
            'registerAutomatically' => 'false',
            'backtraceAnalysis' => 'deep',
        ),
    );

    /**
     * Settings, what can be edited on the frontend, and what not.
     *
     * @var array
     */
    public static $feConfigFallback = array(
        'analyseMethodsAtall' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'analyseProtectedMethods' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'analysePrivateMethods' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'analyseProtected' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'analysePrivate' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'analyseConstants' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'analyseTraversable' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'debugMethods' => array(
            'type' => 'Input',
            'editable' => 'false',
        ),
        'level' => array(
            'type' => 'Input',
            'editable' => 'true',
        ),
        'maxCall' => array(
            'type' => 'Input',
            'editable' => 'true',
        ),
        'disabled' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'destination' => array(
            'type' => 'Select',
            'editable' => 'false',
        ),
        'maxfiles' => array(
            'type' => 'None',
            'editable' => 'false',
        ),
        'folder' => array(
            'type' => 'None',
            'editable' => 'false',
        ),
        'skin' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'registerAutomatically' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'detectAjax' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'backtraceAnalysis' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'memoryLeft' => array(
            'type' => 'Input',
            'editable' => 'true',
        ),
        'maxRuntime' => array(
            'type' => 'Input',
            'editable' => 'true',
        ),
        'Local open function' => array(
            'type' => 'Input',
            'editable' => 'true',
        ),
    );

    /**
     * List of stuff who's fe-editing status can not be changed. Never.
     *
     * @see Tools::evaluateSetting
     *   Evaluating everything in here will fail, meaning that the
     *   setting will not be accepted.
     *
     * @var array
     */
    protected static $feConfigNoEdit = array(
        'destination',
        'folder',
        'maxfiles',
        'debugMethods',
    );

    /**
     * The directory where kreXX is stored.
     *
     * @var string
     */
    public static $krexxdir;

    /**
     * Known Problems with debug functions, which will most likely cause a fatal.
     *
     * Used by Objects::pollAllConfiguredDebugMethods() to determine
     * if we might expect problems.
     *
     * @var array
     */
    protected static $debugMethodsBlacklist = array(

        // TYPO3 viewhelpers dislike this function.
        // In the TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper the private
        // $viewHelperNode might not be an object, and trying to render it might
        // cause a fatal error!
        'TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper' => '__toString',

        // Will throw an error.
        'ReflectionClass' => '__toString',

        // Deleting all rows from the DB via typo3 reopsitory is NOT a good
        // debug method!
        'RepositoryInterface' => 'removeAll',
        'Tx_Extbase_Persistence_RepositoryInterface' => 'removeAll',
    );

    /**
     * Caching for the local settings.
     *
     * @var array
     */
    protected static $localConfig = array();

    /**
     * Path to the configuration file.
     *
     * @var string
     */
    protected static $pathToIni;

    /**
     * The kreXX version.
     *
     * @var string
     */
    public static $version = '1.4.3 dev';
}
