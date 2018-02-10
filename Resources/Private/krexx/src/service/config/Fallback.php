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
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Service\Config;

use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Configuration fallback settings.
 *
 * We have so much of them, they need an own class.
 *
 * @package Brainworxx\Krexx\Service\Config
 */
class Fallback
{

    /**
     * Here we store all relevant data.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * Injects the pool and initializes the security.
     *
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * Fallback settings, in case there is nothing in the config ini.
     *
     * @var array
     */
    public $configFallback = array(
        'output' => array(
            // Disable kreXX.
            'disabled' => 'false',
            // IP range for calling kreXX.
            // kreXX is disabled for everyone who dies not fit into this range.
            'iprange' => '*',
            // Skin for kreXX. We have provided 'hans' and 'smokygrey'.
            'skin' => 'smokygrey',
            // Output desination. Either 'file' or 'browser'.
            'destination' => 'browser',
            // Maximum files that are kept inside the logfolder.
            'maxfiles' => '10',
        ),
        'runtime' => array(
            // Try to detect ajax requests.
            // If set to 'true', kreXX is disablked for them.
            'detectAjax' => 'true',
            // Maximum nesting level.
            'level' => '5',
            // Maximum amount of kreXX calls.
            'maxCall' => '10',
            // Maximum runtime in seconds, before triggering an emergancy break.
            'maxRuntime' => '60',
            // Maximum MB memory left, before triggering an emergency break.
            'memoryLeft' => '64',
            // Use the scope analyis (aka autoconfiguration).
            'useScopeAnalysis' => 'true',
        ),
        'properties' => array(
            // Analyse protected class properties.
            'analyseProtected' => 'false',
            // Analyse private class properties.
            'analysePrivate' => 'false',
            // Analyse class constants.
            'analyseConstants' => 'true',
            // Analyse traversable part of classes.
            'analyseTraversable' => 'true',
        ),
        'methods' => array(
            // Analyse protected class methods.
            'analyseProtectedMethods' => 'false',
            // Analyse private class methods.
            'analysePrivateMethods' => 'false',
            // Analyse the getter methods of a class and try to
            // get a possible return value without calling the method.
            'analyseGetter' => 'true',
            // Debug methods that get called.
            // A debug method must be public and have no parameters.
            // Change these only if you know what you are doing.
            'debugMethods' => 'debug,__toArray,toArray,__toString,toString,_getProperties,__debugInfo,getProperties',
        ),
        'pruneOutput' => array(
            // Maximum step numbers that get analysed from a backtrace.
            // all other steps be be omitted.
            'maxStepNumber' => 10,
            // Limit for the count in an array. If an array is larger that this,
            // we will use the ThroughLargeArray callback
            'arrayCountLimit' => 300,
        ),
    );

    /**
     * Settings, what can be edited on the frontend, and what not.
     *
     * @var array
     */
    public $feConfigFallback = array(
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
            'type' => 'Input',
            'editable' => 'false',
        ),
        'skin' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'detectAjax' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'iprange' => array(
            'type' => 'None',
            'editable' => 'false',
        ),
        'devHandle' => array(
            'type' => 'Input',
            'editable' => 'true',
        ),
        'analyseGetter' => array(
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
        'useScopeAnalysis' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'maxStepNumber' => array(
            'type' => 'Input',
            'editable' => 'true',
        ),
        'arrayCountLimit' => array(
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
    protected $feConfigNoEdit = array(
        'destination',
        'maxfiles',
        'debugMethods',
        'iprange',
    );

    /**
     * Known Problems with debug functions, which will most likely cause a fatal.
     *
     * @see \Brainworxx\Krexx\Service\Config\Security::isAllowedDebugCall()
     * @see \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::pollAllConfiguredDebugMethods()
     *
     * @var array
     */
    protected $methodBlacklist = array(

        // TYPO3 viewhelpers dislike this function.
        // In the TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper the private
        // $viewHelperNode might not be an object, and trying to render it might
        // cause a fatal error!
        '\\TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\AbstractViewHelper' => array('__toString'),

        // Deleting all rows from the DB via typo3 repository is NOT a good
        // debug method!
        '\\TYPO3\\CMS\\Extbase\\Persistence\\RepositoryInterface' => array('removeAll'),
        'Tx_Extbase_Persistence_RepositoryInterface' => array('removeAll'),

        // The lazy loading proxy may not have loaded the object at this time.
        '\\TYPO3\\CMS\\Extbase\\Persistence\\Generic\\LazyLoadingProxy' => array('__toString'),
    );

    /**
     * These classes will never be polled by debug methods, because that would
     * most likely cause a fatal.
     *
     * @see \Brainworxx\Krexx\Service\Config\Security->isAllowedDebugCall()
     * @see \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects->pollAllConfiguredDebugMethods()
     *
     * @var array
     */
    protected $classBlacklist = array(
        // Fun with reflection classes. Not really.
        '\\ReflectionClass',
        '\\ReflectionFunction',
        '\\ReflectionMethod',
        '\\ReflectionParameter',
        '\\ReflectionZendExtension',
        '\\ReflectionExtension',
        '\\ReflectionFunctionAbstract',
        '\\ReflectionObject',
        '\\ReflectionType',
        '\\ReflectionGenerator',
        '\\Reflector',
    );

    /**
     * Here we store, how we are evaluation each setting.
     *
     * @see \Brainworxx\Krexx\Service\Config\Security::evaluateSetting
     *
     * @var array
     */
    protected $evalSettings = array(
        'analyseProtectedMethods' => 'evalBool',
        'analysePrivateMethods' => 'evalBool',
        'analyseProtected' => 'evalBool',
        'analysePrivate' => 'evalBool',
        'analyseConstants' => 'evalBool',
        'analyseTraversable' => 'evalBool',
        'debugMethods' => 'doNotEval',
        'level' => 'evalInt',
        'maxCall' => 'evalInt',
        'disabled' => 'evalBool',
        'detectAjax' => 'evalBool',
        'destination' => 'evalDestination',
        'maxfiles' => 'evalInt',
        'skin' => 'evalSkin',
        'devHandle' => 'evalDevHandle',
        'iprange' => 'evalIpRange',
        'analyseGetter' => 'evalBool',
        'memoryLeft' => 'evalInt',
        'maxRuntime' => 'evalMaxRuntime',
        'useScopeAnalysis' => 'evalBool',
        'maxStepNumber' => 'evalInt',
        'arrayCountLimit' => 'evalInt',
    );

    /**
     * The kreXX version.
     *
     * @var string
     */
    public $version = '2.4.1 dev';
}
