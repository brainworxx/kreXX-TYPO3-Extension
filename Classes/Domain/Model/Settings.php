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

namespace Brainworxx\Includekrexx\Domain\Model;

use Brainworxx\Includekrexx\Collectors\AbstractCollector;
use Brainworxx\Includekrexx\Controller\IndexController;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Pool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This is one BBW model ;-)
 *
 * @package Brainworxx\Includekrexx\Domain\Model
 */
class Settings
{
    /**
     * @var string
     */
    protected $disabled;

    /**
     * @var string
     */
    protected $iprange;

    /**
     * @var string
     */
    protected $detectAjax;

    /**
     * @var string
     */
    protected $skin;

    /**
     * @var string
     */
    protected $destination;

    /**
     * @var string
     */
    protected $maxfiles;

    /**
     * @var string
     */
    protected $useScopeAnalysis;

    /**
     * @var string
     */
    protected $maxStepNumber;

    /**
     * @var string
     */
    protected $arrayCountLimit;

    /**
     * @var string
     */
    protected $level;

    /**
     * @var string
     */
    protected $analyseProtected;

    /**
     * @var string
     */
    protected $analysePrivate;

    /**
     * @var string
     */
    protected $analyseTraversable;

    /**
     * @var string
     */
    protected $analyseProtectedMethods;

    /**
     * @var string
     */
    protected $analysePrivateMethods;

    /**
     * @var string
     */
    protected $analyseGetter;

    /**
     * @var string
     */
    protected $debugMethods;

    /**
     * @var string
     */
    protected $maxCall;

    /**
     * @var string
     */
    protected $maxRuntime;

    /**
     * @var string
     */
    protected $memoryLeft;

    /**
     * @var string
     */
    protected $formdisabled;

    /**
     * @var string
     */
    protected $formiprange;

    /**
     * @var string
     */
    protected $formdetectAjax;

    /**
     * @var string
     */
    protected $formskin;

    /**
     * @var string
     */
    protected $formdestination;

    /**
     * @var string
     */
    protected $formmaxfiles;

    /**
     * @var string
     */
    protected $formuseScopeAnalysis;

    /**
     * @var string
     */
    protected $formmaxStepNumber;

    /**
     * @var string
     */
    protected $formarrayCountLimit;

    /**
     * @var string
     */
    protected $formlevel;

    /**
     * @var string
     */
    protected $formanalyseProtected;

    /**
     * @var string
     */
    protected $formanalysePrivate;

    /**
     * @var string
     */
    protected $formanalyseTraversable;

    /**
     * @var string
     */
    protected $formanalyseProtectedMethods;

    /**
     * @var string
     */
    protected $formanalysePrivateMethods;

    /**
     * @var string
     */
    protected $formanalyseGetter;

    /**
     * @var string
     */
    protected $formdebugMethods;

    /**
     * @var string
     */
    protected $formmaxCall;

    /**
     * @var string
     */
    protected $formmaxRuntime;

    /**
     * @var string
     */
    protected $formmemoryLeft;

    /**
     * @var string
     */
    protected $factory;

    /**
     * The security validator used ba the setter.
     *
     * @var \Brainworxx\Krexx\Service\Config\Security
     */
    protected $security;

    /**
     * The system gegistry.
     *
     * @var \TYPO3\CMS\Core\Registry
     */
    protected $registry;

    /**
     * Initialize the pool, retrieve the security class.
     */
    public function __construct()
    {
        Pool::createPool();
        $this->security = Krexx::$pool->config->security;
        $this->registry = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
    }

    /**
     * @param string $analysePrivate
     */
    public function setAnalysePrivate($analysePrivate)
    {
        $this->analysePrivate = $analysePrivate;
    }

    /**
     * @param string $analyseGetter
     */
    public function setAnalyseGetter($analyseGetter)
    {
        $this->analyseGetter = $analyseGetter;
    }

    /**
     * @param string $analysePrivateMethods
     */
    public function setAnalysePrivateMethods($analysePrivateMethods)
    {
        $this->analysePrivateMethods = $analysePrivateMethods;
    }

    /**
     * @param string $analyseProtected
     */
    public function setAnalyseProtected($analyseProtected)
    {
        $this->analyseProtected = $analyseProtected;
    }

    /**
     * @param string $analyseProtectedMethods
     */
    public function setAnalyseProtectedMethods($analyseProtectedMethods)
    {
        $this->analyseProtectedMethods = $analyseProtectedMethods;
    }

    /**
     * @param string $analyseTraversable
     */
    public function setAnalyseTraversable($analyseTraversable)
    {
        $this->analyseTraversable = $analyseTraversable;
    }

    /**
     * @param string $arrayCountLimit
     */
    public function setArrayCountLimit($arrayCountLimit)
    {
        $this->arrayCountLimit = $arrayCountLimit;
    }

    /**
     * @param string $debugMethods
     */
    public function setDebugMethods($debugMethods)
    {
        $this->debugMethods = $debugMethods;
    }

    /**
     * @param string $destination
     */
    public function setDestination($destination)
    {
        $this->destination = $destination;
    }

    /**
     * @param string $detectAjax
     */
    public function setDetectAjax($detectAjax)
    {
        $this->detectAjax = $detectAjax;
    }

    /**
     * @param string $disabled
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;
    }

    /**
     * @param string $factory
     */
    public function setFactory($factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param string $formanalyseGetter
     */
    public function setFormanalyseGetter($formanalyseGetter)
    {
        $this->formanalyseGetter = $formanalyseGetter;
    }

    /**
     * @param string $formanalysePrivate
     */
    public function setFormanalysePrivate($formanalysePrivate)
    {
        $this->formanalysePrivate = $formanalysePrivate;
    }

    /**
     * @param string $formanalysePrivateMethods
     */
    public function setFormanalysePrivateMethods($formanalysePrivateMethods)
    {
        $this->formanalysePrivateMethods = $formanalysePrivateMethods;
    }

    /**
     * @param string $formanalyseProtected
     */
    public function setFormanalyseProtected($formanalyseProtected)
    {
        $this->formanalyseProtected = $formanalyseProtected;
    }

    /**
     * @param string $formanalyseProtectedMethods
     */
    public function setFormanalyseProtectedMethods($formanalyseProtectedMethods)
    {
        $this->formanalyseProtectedMethods = $formanalyseProtectedMethods;
    }

    /**
     * @param string $formanalyseTraversable
     */
    public function setFormanalyseTraversable($formanalyseTraversable)
    {
        $this->formanalyseTraversable = $formanalyseTraversable;
    }

    /**
     * @param string $formarrayCountLimit
     */
    public function setFormarrayCountLimit($formarrayCountLimit)
    {
        $this->formarrayCountLimit = $formarrayCountLimit;
    }

    /**
     * @param string $formdebugMethods
     */
    public function setFormdebugMethods($formdebugMethods)
    {
        $this->formdebugMethods = $formdebugMethods;
    }

    /**
     * @param string $formdestination
     */
    public function setFormdestination($formdestination)
    {
        $this->formdestination = $formdestination;
    }

    /**
     * @param string $formdetectAjax
     */
    public function setFormdetectAjax($formdetectAjax)
    {
        $this->formdetectAjax = $formdetectAjax;
    }

    /**
     * @param string $formdisabled
     */
    public function setFormdisabled($formdisabled)
    {
        $this->formdisabled = $formdisabled;
    }

    /**
     * @param string $formiprange
     */
    public function setFormiprange($formiprange)
    {
        $this->formiprange = $formiprange;
    }

    /**
     * @param string $formlevel
     */
    public function setFormlevel($formlevel)
    {
        $this->formlevel = $formlevel;
    }

    /**
     * @param string $formmaxCall
     */
    public function setFormmaxCall($formmaxCall)
    {
        $this->formmaxCall = $formmaxCall;
    }

    /**
     * @param string $formmaxfiles
     */
    public function setFormmaxfiles($formmaxfiles)
    {
        $this->formmaxfiles = $formmaxfiles;
    }

    /**
     * @param string $formmaxRuntime
     */
    public function setFormmaxRuntime($formmaxRuntime)
    {
        $this->formmaxRuntime = $formmaxRuntime;
    }

    /**
     * @param string $formmaxStepNumber
     */
    public function setFormmaxStepNumber($formmaxStepNumber)
    {
        $this->formmaxStepNumber = $formmaxStepNumber;
    }

    /**
     * @param string $formmemoryLeft
     */
    public function setFormmemoryLeft($formmemoryLeft)
    {
        $this->formmemoryLeft = $formmemoryLeft;
    }

    /**
     * @param string $formskin
     */
    public function setFormskin($formskin)
    {
        $this->formskin = $formskin;
    }

    /**
     * @param string $formuseScopeAnalysis
     */
    public function setFormuseScopeAnalysis($formuseScopeAnalysis)
    {
        $this->formuseScopeAnalysis = $formuseScopeAnalysis;
    }

    /**
     * @param string $iprange
     */
    public function setIprange($iprange)
    {
        $this->iprange = $iprange;
    }

    /**
     * @param string $level
     */
    public function setLevel($level)
    {
        $this->level = $level;
    }

    /**
     * @param string $maxCall
     */
    public function setMaxCall($maxCall)
    {
        $this->maxCall = $maxCall;
    }

    /**
     * @param string $maxfiles
     */
    public function setMaxfiles($maxfiles)
    {
        $this->maxfiles = $maxfiles;
    }

    /**
     * @param string $maxRuntime
     */
    public function setMaxRuntime($maxRuntime)
    {
        $this->maxRuntime = $maxRuntime;
    }

    /**
     * @param string $maxStepNumber
     */
    public function setMaxStepNumber($maxStepNumber)
    {
        $this->maxStepNumber = $maxStepNumber;
    }

    /**
     * @param string $memoryLeft
     */
    public function setMemoryLeft($memoryLeft)
    {
        $this->memoryLeft = $memoryLeft;
    }

    /**
     * @param string $skin
     */
    public function setSkin($skin)
    {
        $this->skin = $skin;
    }

    /**
     * @param string $useScopeAnalysis
     */
    public function setUseScopeAnalysis($useScopeAnalysis)
    {
        $this->useScopeAnalysis = $useScopeAnalysis;
    }

    /**
     * We iterate through the fallback array to generate the content of the
     * ini file.
     */
    public function generateIniContent()
    {
        $result = '';
        $moduleSettings = array();

        // Process the normal settings.
        foreach (Krexx::$pool->config->configFallback as $group => $settings) {
            $result .= '[' . $group . ']' . "\n";
            foreach ($settings as $settingName) {
                if (!is_null($this->$settingName) &&
                    $this->security->evaluateSetting($group, $settingName, $this->$settingName)) {
                    $result .= $settingName . ' = "' . $this->$settingName . '"'  . "\n";
                    $moduleSettings[$settingName] = $this->$settingName;
                }
            }
        }

        // Process the configuration for the settings editing.
        $result .= '[feEditing]' . "\n";
        $allowedValues = array('full', 'display', 'none');
        foreach (Krexx::$pool->config->feConfigFallback as $settingName => $settings) {
            $settingNameInModel = 'form' . $settingName;
            if ($settings['render']['Editable'] === 'true' &&
                in_array($this->$settingNameInModel, $allowedValues)
            ) {
                $result .= $settingName . ' = "' . $this->$settingNameInModel . '"'  . "\n";
                $moduleSettings[$settingNameInModel] = $this->$settingNameInModel;
            }
        }

        /** @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $user */
        $user = $GLOBALS['BE_USER'];
        // Save the last settings to the backend user, so we can retrieve it later.
        if (!isset($user->uc[AbstractCollector::MODULE_DATA][IndexController::MODULE_KEY])) {
            $user->uc[AbstractCollector::MODULE_DATA][IndexController::MODULE_KEY] = array();
        }
        $user->uc[AbstractCollector::MODULE_DATA][IndexController::MODULE_KEY] = array_merge(
            $user->uc[AbstractCollector::MODULE_DATA][IndexController::MODULE_KEY],
            $moduleSettings
        );
        $user->writeUC();

        return $result;
    }
}
