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
            'disabled' => 'false',
            'iprange' => '*',
            'skin' => 'smokygrey',
            'destination' => 'browser',
            'maxfiles' => '10',
        ),
        'runtime' => array(
            'detectAjax' => 'true',
            'level' => '10',
            'maxCall' => '20',
            'maxRuntime' => '60',
            'memoryLeft' => '64',
            'useScopeAnalysis' => 'true',
        ),
        'properties' => array(
            'analyseProtected' => 'false',
            'analysePrivate' => 'false',
            'analyseConstants' => 'true',
            'analyseTraversable' => 'true',
        ),
        'methods' => array(
            'analyseProtectedMethods' => 'false',
            'analysePrivateMethods' => 'false',
            'analyseGetter' => 'true',
            'debugMethods' => 'debug,__toArray,toArray,__toString,toString,_getProperties,__debugInfo,getProperties',
        ),
        'backtraceAndError' => array(
            'registerAutomatically' => 'false',
            'maxStepNumber' => 10,
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
            'type' => 'None',
            'editable' => 'false',
        ),
        'skin' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'registerAutomatically' => array(
            'type' => 'Select',
            'editable' => 'false',
        ),
        'detectAjax' => array(
            'type' => 'Select',
            'editable' => 'true',
        ),
        'iprange' => array(
            'type' => 'None',
            'editable' => 'false',
        ),
        'Local open function' => array(
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
     * @see \Brainworxx\Krexx\Service\Config\Security->isAllowedDebugCall()
     * @see \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects->pollAllConfiguredDebugMethods()
     *
     * @var array
     */
    protected $debugMethodsBlacklist = array(

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
    protected $debugClassBlacklist = array(
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
     * The kreXX version.
     *
     * @var string
     */
    public $version = '2.2.1 dev';
}
