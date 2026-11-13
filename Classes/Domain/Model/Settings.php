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
     * @var string
     */
    protected string $factory;

    /**
     * Setting the values on construction.
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
     * @param string|null $triggerLazy
     * @param string|null $formtriggerLazy
     * @param string|null $analyseTraversable
     * @param string|null $formanalyseTraversable
     * @param string|null $arrayCountLimit
     * @param string|null $formarrayCountLimit
     * @param string|null $debugMethods
     * @param string|null $formdebugMethods
     * @param string|null $destination
     * @param string|null $formdestination
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
     * @param string|null $maxStepNumber
     * @param string|null $formmaxStepNumber
     * @param string|null $skin
     * @param string|null $formskin
     */
    public function __construct(
        protected ?string $analyseGetter = null,
        protected ?string $formanalyseGetter = null,
        protected ?string $analysePrivate = null,
        protected ?string $formanalysePrivate = null,
        protected ?string $analysePrivateMethods = null,
        protected ?string $formanalysePrivateMethods = null,
        protected ?string $analyseProtected = null,
        protected ?string $formanalyseProtected = null,
        protected ?string $analyseProtectedMethods = null,
        protected ?string $formanalyseProtectedMethods = null,
        protected ?string $triggerLazy = null,
        protected ?string $formtriggerLazy = null,
        protected ?string $analyseTraversable = null,
        protected ?string $formanalyseTraversable = null,
        protected ?string $arrayCountLimit = null,
        protected ?string $formarrayCountLimit = null,
        protected ?string $debugMethods = null,
        protected ?string $formdebugMethods = null,
        protected ?string $destination = null,
        protected ?string $formdestination = null,
        protected ?string $disabled = null,
        protected ?string $formdisabled = null,
        protected ?string $iprange = null,
        protected ?string $formiprange = null,
        protected ?string $languageKey = null,
        protected ?string $formlanguageKey = null,
        protected ?string $level = null,
        protected ?string $formlevel = null,
        protected ?string $activateT3FileWriter = null,
        protected ?string $loglevelT3FileWriter = null,
        protected ?string $maxCall = null,
        protected ?string $formmaxCall = null,
        protected ?string $maxfiles = null,
        protected ?string $formmaxfiles = null,
        protected ?string $maxStepNumber = null,
        protected ?string $formmaxStepNumber = null,
        protected ?string $skin = null,
        protected ?string $formskin = null
    ) {
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
        $settings = $this->processGroups(moduleSettings: $moduleSettings);
        $feEditing = $this->processFeEditing(moduleSettings:$moduleSettings);
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
                    $this->$settingName !== null &&
                    $validation->evaluateSetting(group: $group, name: $settingName, value: $this->$settingName)
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
                $settings[static::RENDER][static::RENDER_TYPE] !== static::RENDER_TYPE_NONE
                && in_array(needle: $this->$settingNameInModel, haystack: $allowedValues, strict: true)
            ) {
                $moduleSettings[$settingNameInModel] = $result[static::SECTION_FE_EDITING][$settingName] =
                    $this->$settingNameInModel;
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
