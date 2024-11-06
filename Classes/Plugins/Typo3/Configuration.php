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
 *   kreXX Copyright (C) 2014-2024 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Plugins\Typo3;

use Brainworxx\Includekrexx\Log\FileWriter as KrexxFileWriter;
use Brainworxx\Includekrexx\Modules\Log;
use Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\DirtyModels;
use Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\FlexFormParser;
use Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\InlineJsCssDispatcher;
use Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\QueryDebugger;
use Brainworxx\Includekrexx\Plugins\Typo3\Rewrites\CheckOutput as T3CheckOutput;
use Brainworxx\Includekrexx\Plugins\Typo3\Scalar\ExtFilePath;
use Brainworxx\Includekrexx\Plugins\Typo3\Scalar\LllString;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessObject;
use Brainworxx\Krexx\Analyse\Scalar\String\Xml;
use Brainworxx\Krexx\Controller\BacktraceController;
use Brainworxx\Krexx\Controller\DumpController;
use Brainworxx\Krexx\Controller\EditSettingsController;
use Brainworxx\Krexx\Controller\ExceptionController;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Plugin\NewSetting;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\View\Output\CheckOutput;
use Closure;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Query\QueryBuilder as DbQueryBuilder;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Persistence\RepositoryInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Configuration file for the TYPO3 kreXX plugin.
 *
 * Not to be confused with a TYPO3 frontend plugin.
 */
class Configuration implements PluginConfigInterface, ConstInterface, ConfigConstInterface
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName(): string
    {
        return 'TYPO3';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \TYPO3\CMS\Core\Package\Exception
     */
    public function getVersion(): string
    {
        return ExtensionManagementUtility::getExtensionVersion(static::EXT_KEY);
    }

    /**
     * TYPO3 specific stuff, like:
     *
     * - Register the overwrites for the configuration.
     * - Point the directories to the temp folder.
     * - Protect the temp folder, if necessary.
     */
    public function exec(): void
    {
        // We are using the TYPO3 ip security, instead of the kreXX implementation.
        Registration::addRewrite(CheckOutput::class, T3CheckOutput::class);

        // Registering some special stuff for the model analysis.
        Registration::registerEvent(ProcessObject::class . static::START_PROCESS, DirtyModels::class);

        // Registering the flexform parser.
        Registration::registerEvent(Xml::class . static::END_EVENT, FlexFormParser::class);

        // Register the JS dispatcher.
        Registration::registerEvent(EditSettingsController::class . '::outputCssAndJs', InlineJsCssDispatcher::class);
        Registration::registerEvent(ExceptionController::class . '::outputCssAndJs', InlineJsCssDispatcher::class);
        Registration::registerEvent(BacktraceController::class . '::outputCssAndJs', InlineJsCssDispatcher::class);
        Registration::registerEvent(DumpController::class . '::outputCssAndJs', InlineJsCssDispatcher::class);

        // See if we must create a temp directory for kreXX.
        $tempPaths = $this->generateTempPaths();
        // Register it!
        Registration::setConfigFile($tempPaths[ConfigConstInterface::CONFIG_FOLDER] . DIRECTORY_SEPARATOR . 'Krexx.');
        Registration::setChunksFolder($tempPaths[ConfigConstInterface::CHUNKS_FOLDER] . DIRECTORY_SEPARATOR);
        Registration::setLogFolder($tempPaths[ConfigConstInterface::LOG_FOLDER] . DIRECTORY_SEPARATOR);
        $this->createWorkingDirectories($tempPaths);

        if (!class_exists(ObjectManager::class)) {
            // Register the direct output as pre-configuration in TYPO3 12.
            Registration::addNewFallbackValue(static::SETTING_DESTINATION, static::VALUE_BROWSER_IMMEDIATELY);
        }

        $this->registerBlacklisting();

        // Add additional texts to the help.
        Registration::registerAdditionalHelpFile(
            ExtensionManagementUtility::extPath(static::EXT_KEY) . 'Resources/Private/Language/t3.kreXX.ini'
        );

        // Register the scalar analysis classes.
        Registration::addScalarStringAnalyser(ExtFilePath::class);
        Registration::addScalarStringAnalyser(LllString::class);

        $this->registerFileWriterSettings();
        $this->registerVersionDependantStuff();
        $this->registerFileWriter();
    }

    /**
     * Adding our debugging blacklist.
     */
    protected function registerBlacklisting(): void
    {
        // TYPO3 viewhelpers dislike this function.
        // In the TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper the private
        // $viewHelperNode might not be an object, and trying to render it might
        // cause a fatal error!
        $toString = '__toString';
        Registration::addMethodToDebugBlacklist(AbstractViewHelper::class, $toString);

        // Deleting all rows from the DB via typo3 repository is NOT a good
        // debug method!
        Registration::addMethodToDebugBlacklist(RepositoryInterface::class, 'removeAll');

        // The lazy loading proxy may not have loaded the object at this time.
        Registration::addMethodToDebugBlacklist(LazyLoadingProxy::class, $toString);

        // We now have a better variant for the QueryBuilder analysis.
        Registration::addMethodToDebugBlacklist(DbQueryBuilder::class, $toString);
    }

    /**
     * Generate the temp paths.
     *
     * @return string[]
     *   The temp paths.
     */
    protected function generateTempPaths(): array
    {
        $pathSite = Environment::getVarPath() . DIRECTORY_SEPARATOR . static::TX_INCLUDEKREXX;

        // See if we must create a temp directory for kreXX.
        return [
            'main' => $pathSite,
            ConfigConstInterface::LOG_FOLDER =>
                $pathSite . DIRECTORY_SEPARATOR . ConfigConstInterface::LOG_FOLDER,
            ConfigConstInterface::CHUNKS_FOLDER =>
                $pathSite . DIRECTORY_SEPARATOR . ConfigConstInterface::CHUNKS_FOLDER,
            ConfigConstInterface::CONFIG_FOLDER =>
                $pathSite . DIRECTORY_SEPARATOR . ConfigConstInterface::CONFIG_FOLDER,
        ];
    }

    /**
     * Register or file writer, if needed.
     */
    protected function registerFileWriter(): void
    {
        // Create the pool, it is not present and reload the configuration
        // with our new settings.
        Pool::createPool();
        Krexx::$pool->createClass(Config::class);
        if (Krexx::$pool->config->getSetting(static::ACTIVATE_T3_FILE_WRITER) === false) {
            // Do nothing.
            return;
        }

        // The things you have to do when you don't know if anybody has messed
        // with globals.
        if (!isset($GLOBALS[static::TYPO3_CONF_VARS][static::LOG])) {
            $GLOBALS[static::TYPO3_CONF_VARS][static::LOG] = [];
        }
        if (!isset($GLOBALS[static::TYPO3_CONF_VARS][static::LOG][static::WRITER_CONFIGURATION])) {
            $GLOBALS[static::TYPO3_CONF_VARS][static::LOG][static::WRITER_CONFIGURATION] = [];
        }
        $level = Krexx::$pool->config->getSetting(static::LOG_LEVEL_T3_FILE_WRITER);
        if (!isset($GLOBALS[static::TYPO3_CONF_VARS][static::LOG][static::WRITER_CONFIGURATION][$level])) {
            $GLOBALS[static::TYPO3_CONF_VARS][static::LOG][static::WRITER_CONFIGURATION][$level] = [];
        }
        // Using the configured log level.
        $GLOBALS[static::TYPO3_CONF_VARS][static::LOG][static::WRITER_CONFIGURATION]
        [$level][KrexxFileWriter::class] = [];
    }

    /**
     * Register the new setting for the TYPO3 file writer.
     */
    protected function registerFileWriterSettings(): void
    {
        // Register the two new settings for the TYPO3 log writer integration.
        Registration::addNewSettings(GeneralUtility::makeInstance(NewSetting::class)
            ->setSection($this->getName())
            ->setIsFeProtected(true)
            ->setDefaultValue(static::VALUE_FALSE)
            ->setIsEditable(false)
            ->setRenderType(static::RENDER_TYPE_NONE)
            ->setValidation('evalBool')
            ->setName(static::ACTIVATE_T3_FILE_WRITER));

        Registration::addNewSettings(GeneralUtility::makeInstance(NewSetting::class)
            ->setSection($this->getName())
            ->setIsFeProtected(true)
            ->setDefaultValue(LogLevel::ERROR)
            ->setIsEditable(false)
            ->setRenderType(static::RENDER_TYPE_NONE)
            ->setValidation($this->createFileWriterValidator())
            ->setName(static::LOG_LEVEL_T3_FILE_WRITER));
    }

    /**
     * Create the validation callback for the file writer.
     *
     * @return \Closure
     */
    protected function createFileWriterValidator(): Closure
    {
        return function ($value, Pool $pool): bool {
            $levels = [
                LogLevel::EMERGENCY,
                LogLevel::ALERT,
                LogLevel::CRITICAL,
                LogLevel::ERROR,
                LogLevel::WARNING,
                LogLevel::NOTICE,
                LogLevel::INFO,
                LogLevel::DEBUG,
            ];
            if (in_array($value, $levels, true)) {
                return true;
            }

            $pool->messages->addMessage('configErrorLoglevelT3FileWriter', [$value]);
            return false;
        };
    }

    /**
     * Register the admin panel integration and the query debugger.
     */
    protected function registerVersionDependantStuff(): void
    {
        // The QueryBuilder special analysis.
        Registration::registerEvent(Objects::class . static::START_EVENT, QueryDebugger::class);

        // Register our modules for the admin panel.
        if (
            isset($GLOBALS[static::TYPO3_CONF_VARS][static::EXTCONF][static::ADMIN_PANEL]
                [static::MODULES][static::DEBUG])
        ) {
            $GLOBALS[static::TYPO3_CONF_VARS][static::EXTCONF][static::ADMIN_PANEL]
            [static::MODULES][static::DEBUG][static::SUBMODULES] = array_replace_recursive(
                $GLOBALS[static::TYPO3_CONF_VARS][static::EXTCONF][static::ADMIN_PANEL]
                [static::MODULES][static::DEBUG][static::SUBMODULES],
                [static::KREXX => ['module' => Log::class, 'before' => ['log']]]
            );
        }
    }

    /**
     * Create and protect the working directories.
     *
     * @param string[] $tempPaths
     */
    protected function createWorkingDirectories(array $tempPaths): void
    {
        // htAccess to prevent a listing
        $htAccess = '# Apache 2.2' . chr(10);
        $htAccess .= '<IfModule !authz_core_module>' . chr(10);
        $htAccess .= '	Order Deny,Allow' . chr(10);
        $htAccess .= '	Deny from all' . chr(10);
        $htAccess .= '</IfModule>' . chr(10);
        $htAccess .= '# Apache 2.4+' . chr(10);
        $htAccess .= '<IfModule authz_core_module>' . chr(10);
        $htAccess .= '	<RequireAll>' . chr(10);
        $htAccess .= '		Require all denied' . chr(10);
        $htAccess .= '	</RequireAll>' . chr(10);
        $htAccess .= '</IfModule>' . chr(10);

        // Create and protect the temporal folders.
        foreach ($tempPaths as $tempPath) {
            if (!is_dir($tempPath)) {
                // Create it!
                GeneralUtility::mkdir($tempPath);
                // Protect it!
                GeneralUtility::writeFileToTypo3tempDir($tempPath . DIRECTORY_SEPARATOR . '.htaccess', $htAccess);
                // Empty index.html in case the htaccess is not enough.
                GeneralUtility::writeFileToTypo3tempDir($tempPath . DIRECTORY_SEPARATOR . 'index.html', '');
            }
        }
    }
}
