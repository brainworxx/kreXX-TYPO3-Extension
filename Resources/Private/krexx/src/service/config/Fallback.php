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

    const RENDER = 'render';
    const EVAL = 'eval';
    const VALUE = 'value';
    const SECTION = 'section';

    const EVALBOOL = 'evalBool';
    const EVALINT = 'evalInt';
    const EVALMAXRUNTIME = 'evalMaxRuntime';
    const DONOTEVAL = 'doNotEval';
    const EVALDESTINATION = 'evalDestination';
    const EVALSKIN = 'evalSkin';
    const EVALIPRANGE = 'evalIpRange';
    const EVALDEVHANDLE = 'evalDevHandle';

    const SECTIONOUTPUT = 'output';
    const SECTIONRUNTIME = 'runtime';
    const SECTIONPROPERTIES = 'properties';
    const SECTIONMETHODS = 'methods';
    const SECTIONPRUNEOUTPUT = 'pruneOutput';

    const VALUETRUE = 'true';
    const VALUEFALSE = 'false';

    const SETTINGDISABLED = 'disabled';
    const SETTINGIPRANGE = 'iprange';
    const SETTINGSKIN = 'skin';
    const SETTINGDESTINATION = 'destination';
    const SETTINGMAXFILES = 'maxfiles';
    const SETTINGDETECTAJAX = 'detectAjax';
    const SETTINGLEVEL = 'level';
    const SETTINGMAXCALL = 'maxCall';
    const SETTINGMAXRUNTIME = 'maxRuntime';
    const SETTINGMEMORYLEFT = 'memoryLeft';
    const SETTINGUSESCOPEANALYSIS = 'useScopeAnalysis';
    const SETTINGANALYSEPROTECTED = 'analyseProtected';
    const SETTINGANALYSEPRIVATE = 'analysePrivate';
    const SETTINGANALYSECONSTATS = 'analyseConstants';
    const SETTINGANALYSETRAVERSABLE = 'analyseTraversable';
    const SETTINGANALYSEPROTECTEDMETHODS = 'analyseProtectedMethods';
    const SETTINGANALYSEPRIVATEMETHODS = 'analysePrivateMethods';
    const SETTINGANALYSEGETTER = 'analyseGetter';
    const SETTINGDEBUGMETHODS = 'debugMethods';
    const SETTINGMAXSTEPNUMBER = 'maxStepNumber';
    const SETTINGARRAYCOUNTLIMIT = 'arrayCountLimit';

    /**
     * Defining the layout of the frontend editing form.
     *
     * @var array
     */
    public $configFallback;

    /**
     * Values, rendering settings and the actual fallback value.
     *
     * @var array
     */
    public $feConfigFallback;

    /**
     * Render settings for a editable select field.
     *
     * @var array
     */
    protected $editableSelect = array(
        'type' => 'Select',
        'editable' => Fallback::VALUETRUE,
    );

    /**
     * Render settings for a editable input field.
     *
     * @var array
     */
    protected $editableInput = array(
        'type' => 'Input',
        'editable' => Fallback::VALUETRUE,
    );

    /**
     * Render settings for a display only input field.
     *
     * @var array
     */
    protected $displayOnlyInput = array(
        'type' => 'Input',
        'editable' => Fallback::VALUEFALSE,
    );

    /**
     * Render settings for a display only select field.
     *
     * @var array
     */
    protected $displayOnlySelect = array(
        'type' => 'Select',
        'editable' => Fallback::VALUEFALSE,
    );

    /**
     * Render settings for a field which will not be displayed, or accept values.
     *
     * @var array
     */
    protected $displayNothing = array(
        'type' => 'None',
        'editable' => Fallback::VALUEFALSE,
    );

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

        $this->configFallback = array(
            Fallback::SECTIONOUTPUT => array(
                Fallback::SETTINGDISABLED,
                Fallback::SETTINGIPRANGE,
                Fallback::SETTINGSKIN,
                Fallback::SETTINGDESTINATION,
                Fallback::SETTINGMAXFILES,
            ),
            Fallback::SECTIONRUNTIME => array(
                Fallback::SETTINGDETECTAJAX,
                Fallback::SETTINGLEVEL,
                Fallback::SETTINGMAXCALL,
                Fallback::SETTINGMAXRUNTIME,
                Fallback::SETTINGMEMORYLEFT,
                Fallback::SETTINGUSESCOPEANALYSIS,
            ),
            Fallback::SECTIONPROPERTIES => array(
                Fallback::SETTINGANALYSEPROTECTED,
                Fallback::SETTINGANALYSEPRIVATE,
                Fallback::SETTINGANALYSECONSTATS,
                Fallback::SETTINGANALYSETRAVERSABLE,
            ),
            Fallback::SECTIONMETHODS => array(
                Fallback::SETTINGANALYSEPROTECTEDMETHODS,
                Fallback::SETTINGANALYSEPRIVATEMETHODS,
                Fallback::SETTINGANALYSEGETTER,
                Fallback::SETTINGDEBUGMETHODS,
            ),
            Fallback::SECTIONPRUNEOUTPUT => array(
                Fallback::SETTINGMAXSTEPNUMBER,
                Fallback::SETTINGARRAYCOUNTLIMIT,
            ),
        );

        $this->feConfigFallback = array(
            Fallback::SETTINGANALYSEPROTECTEDMETHODS => array(
                // Analyse protected class methods.
                Fallback::VALUE => Fallback::VALUEFALSE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVAL => Fallback::EVALBOOL,
                Fallback::SECTION => Fallback::SECTIONMETHODS,
            ),
            Fallback::SETTINGANALYSEPRIVATEMETHODS => array(
                // Analyse private class methods.
                Fallback::VALUE => Fallback::VALUEFALSE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVAL => Fallback::EVALBOOL,
                Fallback::SECTION => Fallback::SECTIONMETHODS,
            ),
            Fallback::SETTINGANALYSEPROTECTED => array(
                // Analyse protected class properties.
                Fallback::VALUE => Fallback::VALUEFALSE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVAL => Fallback::EVALBOOL,
                Fallback::SECTION => Fallback::SECTIONPROPERTIES,
            ),
            Fallback::SETTINGANALYSEPRIVATE => array(
                // Analyse private class properties.
                Fallback::VALUE => Fallback::VALUEFALSE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVAL => Fallback::EVALBOOL,
                Fallback::SECTION => Fallback::SECTIONPROPERTIES,
            ),
            Fallback::SETTINGANALYSECONSTATS => array(
                // Analyse class constants.
                Fallback::VALUE => Fallback::VALUETRUE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVAL => Fallback::EVALBOOL,
                Fallback::SECTION => Fallback::SECTIONPROPERTIES,
            ),
            Fallback::SETTINGANALYSETRAVERSABLE => array(
                // Analyse traversable part of classes.
                Fallback::VALUE => Fallback::VALUETRUE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVAL => Fallback::EVALBOOL,
                Fallback::SECTION => Fallback::SECTIONPROPERTIES,
            ),
            Fallback::SETTINGDEBUGMETHODS => array(
                // Debug methods that get called.
                // A debug method must be public and have no parameters.
                // Change these only if you know what you are doing.
                Fallback::VALUE => 'debug,__toArray,toArray,__toString,toString,_getProperties,__debugInfo,getProperties',
                Fallback::RENDER => $this->displayOnlyInput,
                Fallback::EVAL => Fallback::DONOTEVAL,
                Fallback::SECTION =>  Fallback::SECTIONMETHODS,
            ),
            Fallback::SETTINGLEVEL => array(
                // Maximum nesting level.
                Fallback::VALUE => 5,
                Fallback::RENDER => $this->editableInput,
                Fallback::EVAL => Fallback::EVALINT,
                Fallback::SECTION => Fallback::SECTIONRUNTIME,
            ),
            Fallback::SETTINGMAXCALL => array(
                // Maximum amount of kreXX calls.
                Fallback::VALUE => 10,
                Fallback::RENDER => $this->editableInput,
                Fallback::EVAL => Fallback::EVALINT,
                Fallback::SECTION => Fallback::SECTIONRUNTIME,
            ),
            Fallback::SETTINGDISABLED => array(
                // Disable kreXX.
                Fallback::VALUE => Fallback::VALUEFALSE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVAL => Fallback::EVALBOOL,
                Fallback::SECTION => Fallback::SECTIONOUTPUT,
            ),
            Fallback::SETTINGDESTINATION => array(
                // Output desination. Either 'file' or 'browser'.
                Fallback::VALUE => 'browser',
                Fallback::RENDER => $this->displayOnlySelect,
                Fallback::EVAL => Fallback::EVALDESTINATION,
                Fallback::SECTION => Fallback::SECTIONOUTPUT,
            ),
            Fallback::SETTINGMAXFILES => array(
                // Maximum files that are kept inside the logfolder.
                Fallback::VALUE => 10,
                Fallback::RENDER => $this->displayOnlyInput,
                Fallback::EVAL => Fallback::EVALINT,
                Fallback::SECTION => Fallback::SECTIONOUTPUT,
            ),
            Fallback::SETTINGSKIN => array(
                // Skin for kreXX. We have provided 'hans' and 'smokygrey'.
                Fallback::VALUE => 'smokygrey',
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVAL => Fallback::EVALSKIN,
                Fallback::SECTION => Fallback::SECTIONOUTPUT,
            ),
            Fallback::SETTINGDETECTAJAX => array(
                // Try to detect ajax requests.
                // If set to 'true', kreXX is disablked for them.
                Fallback::VALUE => Fallback::VALUETRUE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVAL => Fallback::EVALBOOL,
                Fallback::SECTION => Fallback::SECTIONRUNTIME,
            ),
            Fallback::SETTINGIPRANGE => array(
                // IP range for calling kreXX.
                // kreXX is disabled for everyone who dies not fit into this range.
                Fallback::VALUE => '*',
                Fallback::RENDER => $this->displayNothing,
                Fallback::EVAL => Fallback::EVALIPRANGE,
                Fallback::SECTION => Fallback::SECTIONOUTPUT,
            ),
            'devHandle' => array(
                Fallback::VALUE => '',
                Fallback::RENDER => $this->editableInput,
                Fallback::EVAL => Fallback::EVALDEVHANDLE,
                Fallback::SECTION => ''
            ),
            Fallback::SETTINGANALYSEGETTER => array(
                // Analyse the getter methods of a class and try to
                // get a possible return value without calling the method.
                Fallback::VALUE => Fallback::VALUETRUE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVAL => Fallback::EVALBOOL,
                Fallback::SECTION =>  Fallback::SECTIONMETHODS,
            ),
            Fallback::SETTINGMEMORYLEFT => array(
                // Maximum MB memory left, before triggering an emergency break.
                Fallback::VALUE => 64,
                Fallback::RENDER => $this->editableInput,
                Fallback::EVAL => Fallback::EVALINT,
                Fallback::SECTION => Fallback::SECTIONRUNTIME,
            ),
            Fallback::SETTINGMAXRUNTIME => array(
                // Maximum runtime in seconds, before triggering an emergancy break.
                Fallback::VALUE => 60,
                Fallback::RENDER => $this->editableInput,
                Fallback::EVAL => Fallback::EVALMAXRUNTIME,
                Fallback::SECTION => Fallback::SECTIONRUNTIME,
            ),
            Fallback::SETTINGUSESCOPEANALYSIS => array(
                // Use the scope analyis (aka autoconfiguration).
                Fallback::VALUE => Fallback::VALUETRUE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVAL => Fallback::EVALBOOL,
                Fallback::SECTION => Fallback::SECTIONRUNTIME,
            ),
            Fallback::SETTINGMAXSTEPNUMBER => array(
                // Maximum step numbers that get analysed from a backtrace.
                // All other steps be be omitted.
                Fallback::VALUE => 10,
                Fallback::RENDER => $this->editableInput,
                Fallback::EVAL => Fallback::EVALINT,
                Fallback::SECTION => Fallback::SECTIONPRUNEOUTPUT,
            ),
            Fallback::SETTINGARRAYCOUNTLIMIT => array(
                // Limit for the count in an array. If an array is larger that this,
                // we will use the ThroughLargeArray callback
                Fallback::VALUE => 300,
                Fallback::RENDER => $this->editableInput,
                Fallback::EVAL => Fallback::EVALINT,
                Fallback::SECTION => Fallback::SECTIONPRUNEOUTPUT,
            ),
        );
    }

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
        Fallback::SETTINGDESTINATION,
        Fallback::SETTINGMAXFILES,
        Fallback::SETTINGDEBUGMETHODS,
        Fallback::SETTINGIPRANGE,
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
        '\\ReflectionType',
        '\\ReflectionGenerator',
        '\\Reflector',
    );

    /**
     * The kreXX version.
     *
     * @var string
     */
    public $version = '2.4.1 dev';
}
