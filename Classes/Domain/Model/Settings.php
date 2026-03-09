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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Domain\Model;

use Brainworxx\Includekrexx\Collectors\AbstractCollector;
use Brainworxx\Includekrexx\Controller\ControllerConstInterface;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Abusing the TYPO3 attribute mapper, to save our settings.
 */
class Settings implements ControllerConstInterface, ConfigConstInterface
{
    /**
     * @var null|string
     */
    protected ?string $analyseGetter = null;

    /**
     * @var null|string
     */
    protected ?string $formanalyseGetter = null;

    /**
     * @var null|string
     */
    protected ?string $analysePrivate = null;

    /**
     * @var null|string
     */
    protected ?string $formanalysePrivate = null;

     /**
     * @var null|string
     */
    protected ?string $analysePrivateMethods = null;

    /**
     * @var null|string
     */
    protected ?string $formanalysePrivateMethods = null;

    /**
     * @var null|string
     */
    protected ?string $analyseProtected = null;

    /**
     * @var null|string
     */
    protected ?string $formanalyseProtected = null;

    /**
     * @var null|string
     */
    protected ?string $analyseProtectedMethods = null;

    /**
     * @var null|string
     */
    protected ?string $formanalyseProtectedMethods = null;

    /**
     * @var null|string
     */
    protected ?string $analyseScalar = null;

    /**
     * @var null|string
     */
    protected ?string $formanalyseScalar = null;

    /**
     * @var null|string
     */
    protected ?string $analyseTraversable = null;

    /**
     * @var null|string
     */
    protected ?string $formanalyseTraversable = null;

    /**
     * @var null|string
     */
    protected ?string $arrayCountLimit = null;

    /**
     * @var null|string
     */
    protected ?string $formarrayCountLimit = null;

    /**
     * @var null|string
     */
    protected ?string $debugMethods = null;

    /**
     * @var null|string
     */
    protected ?string $formdebugMethods = null;

    /**
     * @var null|string
     */
    protected ?string $destination = null;

    /**
     * @var null|string
     */
    protected ?string $formdestination = null;

    /**
     * @var null|string
     */
    protected ?string $detectAjax = null;

    /**
     * @var null|string
     */
    protected ?string $formdetectAjax = null;

    /**
     * @var null|string
     */
    protected ?string $disabled = null;

    /**
     * @var null|string
     */
    protected ?string $formdisabled = null;

    /**
     * @var null|string
     */
    protected ?string $iprange = null;

    /**
     * @var null|string
     */
    protected ?string $formiprange = null;

    /**
     * @var null|string
     */
    protected ?string $languageKey = null;

    /**
     * @var null|string
     */
    protected ?string $formlanguageKey = null;

    /**
     * @var null|string
     */
    protected ?string $level = null;

    /**
     * @var null|string
     */
    protected ?string $formlevel = null;

    /**
     * @var null|string
     */
    protected ?string $activateT3FileWriter = null;

    /**
     * @var null|string
     */
    protected ?string $loglevelT3FileWriter = null;

    /**
     * @var null|string
     */
    protected ?string $maxCall = null;

    /**
     * @var null|string
     */
    protected ?string $formmaxCall = null;

    /**
     * @var null|string
     */
    protected ?string $maxfiles = null;

    /**
     * @var null|string
     */
    protected ?string $formmaxfiles = null;

    /**
     * @var null|string
     */
    protected ?string $maxRuntime = null;

    /**
     * @var null|string
     */
    protected ?string $formmaxRuntime = null;

    /**
     * @var null|string
     */
    protected ?string $maxStepNumber = null;

    /**
     * @var null|string
     */
    protected ?string $formmaxStepNumber = null;

    /**
     * @var null|string
     */
    protected ?string $memoryLeft = null;

    /**
     * @var null|string
     */
    protected ?string $formmemoryLeft = null;

    /**
     * @var null|string
     */
    protected ?string $skin = null;

    /**
     * @var null|string
     */
    protected ?string $formskin = null;

    /**
     * @var string
     */
    protected string $factory;

    /**
     * I really would like to drop PHP 7.4 support now. Pretty please?
     *
     * @param string|null $analyseGetter
     * @param string|null $formanalyseGetter
     * @param string|null $analysePrivate
     * @param string|null $formanalysePrivate
     * @param string|null $analysePrivateMethods
     * @param string|null $formanalysePrivateMethods
     * @param string|null $analyseProtected
     * @param string|null $formanalyseProtected
     * @param string|null $analyseProtectedMethods
     * @param string|null $formanalyseProtectedMethods
     * @param string|null $analyseScalar
     * @param string|null $formanalyseScalar
     * @param string|null $analyseTraversable
     * @param string|null $formanalyseTraversable
     * @param string|null $arrayCountLimit
     * @param string|null $formarrayCountLimit
     * @param string|null $debugMethods
     * @param string|null $formdebugMethods
     * @param string|null $destination
     * @param string|null $formdestination
     * @param string|null $detectAjax
     * @param string|null $formdetectAjax
     * @param string|null $disabled
     * @param string|null $formdisabled
     * @param string|null $iprange
     * @param string|null $formiprange
     * @param string|null $languageKey
     * @param string|null $formlanguageKey
     * @param string|null $level
     * @param string|null $formlevel
     * @param string|null $activateT3FileWriter
     * @param string|null $loglevelT3FileWriter
     * @param string|null $maxCall
     * @param string|null $formmaxCall
     * @param string|null $maxfiles
     * @param string|null $formmaxfiles
     * @param string|null $maxRuntime
     * @param string|null $formmaxRuntime
     * @param string|null $maxStepNumber
     * @param string|null $formmaxStepNumber
     * @param string|null $memoryLeft
     * @param string|null $formmemoryLeft
     * @param string|null $skin
     * @param string|null $formskin
     */
    public function __construct(
        ?string $analyseGetter = null,
        ?string $formanalyseGetter = null,
        ?string $analysePrivate = null,
        ?string $formanalysePrivate = null,
        ?string $analysePrivateMethods = null,
        ?string $formanalysePrivateMethods = null,
        ?string $analyseProtected = null,
        ?string $formanalyseProtected = null,
        ?string $analyseProtectedMethods = null,
        ?string $formanalyseProtectedMethods = null,
        ?string $analyseScalar = null,
        ?string $formanalyseScalar = null,
        ?string $analyseTraversable = null,
        ?string $formanalyseTraversable = null,
        ?string $arrayCountLimit = null,
        ?string $formarrayCountLimit = null,
        ?string $debugMethods = null,
        ?string $formdebugMethods = null,
        ?string $destination = null,
        ?string $formdestination = null,
        ?string $detectAjax = null,
        ?string $formdetectAjax = null,
        ?string $disabled = null,
        ?string $formdisabled = null,
        ?string $iprange = null,
        ?string $formiprange = null,
        ?string $languageKey = null,
        ?string $formlanguageKey = null,
        ?string $level = null,
        ?string $formlevel = null,
        ?string $activateT3FileWriter = null,
        ?string $loglevelT3FileWriter = null,
        ?string $maxCall = null,
        ?string $formmaxCall = null,
        ?string $maxfiles = null,
        ?string $formmaxfiles = null,
        ?string $maxRuntime = null,
        ?string $formmaxRuntime = null,
        ?string $maxStepNumber = null,
        ?string $formmaxStepNumber = null,
        ?string $memoryLeft = null,
        ?string $formmemoryLeft = null,
        ?string $skin = null,
        ?string $formskin = null
    ) {
        $this->analyseGetter = $analyseGetter;
        $this->formanalyseGetter = $formanalyseGetter;
        $this->analysePrivate = $analysePrivate;
        $this->formanalysePrivate = $formanalysePrivate;
        $this->analysePrivateMethods = $analysePrivateMethods;
        $this->formanalysePrivateMethods = $formanalysePrivateMethods;
        $this->analyseProtected = $analyseProtected;
        $this->formanalyseProtected = $formanalyseProtected;
        $this->analyseProtectedMethods = $analyseProtectedMethods;
        $this->formanalyseProtectedMethods = $formanalyseProtectedMethods;
        $this->analyseScalar = $analyseScalar;
        $this->formanalyseScalar = $formanalyseScalar;
        $this->analyseTraversable = $analyseTraversable;
        $this->formanalyseTraversable = $formanalyseTraversable;
        $this->arrayCountLimit = $arrayCountLimit;
        $this->formarrayCountLimit = $formarrayCountLimit;
        $this->debugMethods = $debugMethods;
        $this->formdebugMethods = $formdebugMethods;
        $this->destination = $destination;
        $this->formdestination = $formdestination;
        $this->detectAjax = $detectAjax;
        $this->formdetectAjax = $formdetectAjax;
        $this->disabled = $disabled;
        $this->formdisabled = $formdisabled;
        $this->iprange = $iprange;
        $this->formiprange = $formiprange;
        $this->languageKey = $languageKey;
        $this->formlanguageKey = $formlanguageKey;
        $this->level = $level;
        $this->formlevel = $formlevel;
        $this->activateT3FileWriter = $activateT3FileWriter;
        $this->loglevelT3FileWriter = $loglevelT3FileWriter;
        $this->maxCall = $maxCall;
        $this->formmaxCall = $formmaxCall;
        $this->maxfiles = $maxfiles;
        $this->formmaxfiles = $formmaxfiles;
        $this->maxRuntime = $maxRuntime;
        $this->formmaxRuntime = $formmaxRuntime;
        $this->maxStepNumber = $maxStepNumber;
        $this->formmaxStepNumber = $formmaxStepNumber;
        $this->memoryLeft = $memoryLeft;
        $this->formmemoryLeft = $formmemoryLeft;
        $this->skin = $skin;
        $this->formskin = $formskin;
    }

    /**
     * @param string $factory
     */
    public function setFactory(string $factory): void
    {
        $this->factory = $factory;
    }

    /**
     * We iterate through the fallback array to generate the content of the
     * ini file.
     *
     * @return string
     *   The generated contend of the ini file.
     */
    public function generateContent(): string
    {
        Pool::createPool();

        $moduleSettings = [];
        // Process the settings.
        $settings = $this->processGroups($moduleSettings);
        $feEditing =  $this->processFeEditing($moduleSettings);
        $result = $settings + $feEditing;

        /** @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $user */
        $user = $GLOBALS[static::BE_USER];
        // Save the last settings to the backend user, so we can retrieve it later.
        if (!isset($user->uc[AbstractCollector::MODULE_DATA][static::MODULE_KEY])) {
            $user->uc[AbstractCollector::MODULE_DATA][static::MODULE_KEY] = [];
        }
        $user->uc[AbstractCollector::MODULE_DATA][static::MODULE_KEY] = array_merge(
            $user->uc[AbstractCollector::MODULE_DATA][static::MODULE_KEY],
            $moduleSettings
        );
        $user->writeUC();

        return json_encode($result);
    }

    /**
     * Process the normal groups of the ini.
     *
     * @param array $moduleSettings
     *   The module settings. We store these in the user data.
     *
     * @return array
     *   The generated array result.
     */
    protected function processGroups(array &$moduleSettings): array
    {
        $result = [];
        $validation = Krexx::$pool->config->validation;

        foreach (Krexx::$pool->config->configFallback as $group => $settings) {
            $result[$group] = [];
            foreach ($settings as $settingName) {
                if (
                    $this->$settingName !== null
                    && $validation->evaluateSetting($group, $settingName, $this->$settingName)
                ) {
                    $moduleSettings[$settingName] = $result[$group][$settingName] = $this->$settingName;
                }
            }
        }

        return $result;
    }

    /**
     * Generate the frontend editing part.
     *
     * @param array $moduleSettings
     *   The module settings. We store these in the user data.
     *
     * @return array
     *   The generated ini content.
     */
    protected function processFeEditing(array &$moduleSettings): array
    {
        $result = [static::SECTION_FE_EDITING => []];

        $allowedValues = [
            static::RENDER_TYPE_CONFIG_FULL,
            static::RENDER_TYPE_CONFIG_DISPLAY,
            static::RENDER_TYPE_CONFIG_NONE
        ];
        foreach (Krexx::$pool->config->feConfigFallback as $settingName => $settings) {
            $settingNameInModel = 'form' . $settingName;
            if (
                $settings[static::RENDER][static::RENDER_TYPE] !== static::RENDER_TYPE_NONE &&
                in_array($this->$settingNameInModel, $allowedValues, true)
            ) {
                $moduleSettings[$settingNameInModel] = $result[static::SECTION_FE_EDITING][$settingName]
                    = $this->$settingNameInModel;
            }
        }

        return $result;
    }

    /**
     * Prepare the filepath. We do the migration from ini to json here.
     *
     * @param string $filepath
     *   The path to the current configuration file.
     *
     * @return string
     *   The path to the new configuration file.
     */
    public function prepareFileName(string $filepath): string
    {
        // Make sure to switch to json.
        $pathParts = pathinfo($filepath);
        $rootPath = $pathParts[static::PATHINFO_DIRNAME] . DIRECTORY_SEPARATOR .
            $pathParts[static::PATHINFO_FILENAME] . '.';
        $iniPath = $rootPath . 'ini';
        if (file_exists($iniPath)) {
            unlink($iniPath);
        }

        return $rootPath . 'json';
    }
}
