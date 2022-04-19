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
 *   kreXX Copyright (C) 2014-2022 Brainworxx GmbH
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

use Brainworxx\Includekrexx\Modules\Log;
use Brainworxx\Includekrexx\Bootstrap\Bootstrap;
use Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\DirtyModels;
use Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\QueryDebugger;
use Brainworxx\Includekrexx\Plugins\Typo3\Scalar\ExtFilePath;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessObject;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Plugin\NewSetting;
use Brainworxx\Krexx\View\Output\CheckOutput;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Service\Plugin\Registration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Brainworxx\Includekrexx\Plugins\Typo3\Rewrites\CheckOutput as T3CheckOutput;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Persistence\RepositoryInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper as NewAbstractViewHelper;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;
use TYPO3\CMS\Core\Database\Query\QueryBuilder as DbQueryBuilder;
use TYPO3\CMS\Core\Log\LogLevel;
use Brainworxx\Includekrexx\Log\FileWriter as KrexxFileWriter;
use Closure;

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
    public function exec()
    {
        // We are using the TYPO3 ip security, instead of the kreXX implementation.
        Registration::addRewrite(CheckOutput::class, T3CheckOutput::class);

        // Registering some special stuff for the model analysis.
        Registration::registerEvent(ProcessObject::class . static::START_PROCESS, DirtyModels::class);

        // See if we must create a temp directory for kreXX.
        $tempPaths = $this->generateTempPaths();

        // Register it!
        Registration::setConfigFile($tempPaths[static::CONFIG_FOLDER] . DIRECTORY_SEPARATOR . 'Krexx.ini');
        Registration::setChunksFolder($tempPaths[static::CHUNKS_FOLDER] . DIRECTORY_SEPARATOR);
        Registration::setLogFolder($tempPaths[static::LOG_FOLDER] . DIRECTORY_SEPARATOR);
        $this->createWorkingDirectories($tempPaths);

        // Adding our debugging blacklist.
        // TYPO3 viewhelpers dislike this function.
        // In the TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper the private
        // $viewHelperNode might not be an object, and trying to render it might
        // cause a fatal error!
        $toString = '__toString';
        $removeAll = 'removeAll';
        Registration::addMethodToDebugBlacklist(AbstractViewHelper::class, $toString);
        Registration::addMethodToDebugBlacklist(NewAbstractViewHelper::class, $toString);

        // Deleting all rows from the DB via typo3 repository is NOT a good
        // debug method!
        Registration::addMethodToDebugBlacklist(RepositoryInterface::class, $removeAll);

        // The lazy loading proxy may not have loaded the object at this time.
        Registration::addMethodToDebugBlacklist(LazyLoadingProxy::class, $toString);

        // We now have a better variant for the QueryBuilder analysis.
        Registration::addMethodToDebugBlacklist(DbQueryBuilder::class, $toString);

        // Add additional texts to the help.
        $extPath = ExtensionManagementUtility::extPath(static::EXT_KEY);
        Registration::registerAdditionalHelpFile($extPath . 'Resources/Private/Language/t3.kreXX.ini');

        // Register the scalar analysis classes.
        Registration::addScalarStringAnalyser(ExtFilePath::class);

        $this->registerFileWriterSettings();
        $this->registerVersionDependantStuff();
        $this->registerFileWriter();
    }

    /**
     * Generate the temp paths.
     *
     * @return string[]
     *   The temp paths.
     */
    protected function generateTempPaths(): array
    {
        // Get the absolute site path. The constant PATH_site is deprecated
        // since 9.2.
        $pathSite = class_exists(Environment::class) ? Environment::getPublicPath() . '/' : PATH_site;
        $pathSite .= 'typo3temp';

        // See if we must create a temp directory for kreXX.
        return [
            'main' => $pathSite . DIRECTORY_SEPARATOR . static::TX_INCLUDEKREXX,
            static::LOG_FOLDER => $pathSite . DIRECTORY_SEPARATOR . static::TX_INCLUDEKREXX .
                DIRECTORY_SEPARATOR . static::LOG_FOLDER,
            static::CHUNKS_FOLDER => $pathSite . DIRECTORY_SEPARATOR . static::TX_INCLUDEKREXX .
                DIRECTORY_SEPARATOR . static::CHUNKS_FOLDER,
            static::CONFIG_FOLDER => $pathSite . DIRECTORY_SEPARATOR . static::TX_INCLUDEKREXX .
                DIRECTORY_SEPARATOR . static::CONFIG_FOLDER,
        ];
    }

    /**
     * Register or file writer, if needed.
     */
    protected function registerFileWriter()
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
        if (isset($GLOBALS[static::TYPO3_CONF_VARS][static::LOG]) === false) {
            $GLOBALS[static::TYPO3_CONF_VARS][static::LOG] = [];
        }
        if (isset($GLOBALS[static::TYPO3_CONF_VARS][static::LOG][static::WRITER_CONFIGURATION]) === false) {
            $GLOBALS[static::TYPO3_CONF_VARS][static::LOG][static::WRITER_CONFIGURATION] = [];
        }
        $level = Krexx::$pool->config->getSetting(static::LOG_LEVEL_T3_FILE_WRITER);
        if (isset($GLOBALS[static::TYPO3_CONF_VARS][static::LOG][static::WRITER_CONFIGURATION][$level]) === false) {
            $GLOBALS[static::TYPO3_CONF_VARS][static::LOG][static::WRITER_CONFIGURATION][$level] = [];
        }
        // Using the configured log level.
        $GLOBALS[static::TYPO3_CONF_VARS][static::LOG][static::WRITER_CONFIGURATION]
        [$level][KrexxFileWriter::class] = [];
    }

    /**
     * Register the new setting for the TYPO3 file writer.
     */
    protected function registerFileWriterSettings()
    {
        // Register the two new settings for the TYPO3 log writer integration.
        $activeT3FileWriter = GeneralUtility::makeInstance(NewSetting::class);
        $activeT3FileWriter->setSection($this->getName())
            ->setIsFeProtected(true)
            ->setDefaultValue(static::VALUE_FALSE)
            ->setIsEditable(false)
            ->setRenderType(static::RENDER_TYPE_NONE)
            ->setValidation(static::EVAL_BOOL)
            ->setName(static::ACTIVATE_T3_FILE_WRITER);
        Registration::addNewSettings($activeT3FileWriter);

        $loglevelT3FileWriter = GeneralUtility::makeInstance(NewSetting::class);
        $loglevelT3FileWriter->setSection($this->getName())
            ->setIsFeProtected(true)
            ->setDefaultValue((string)LogLevel::ERROR)
            ->setIsEditable(false)
            ->setRenderType(static::RENDER_TYPE_NONE)
            ->setValidation($this->createFileWriterValidator())
            ->setName(static::LOG_LEVEL_T3_FILE_WRITER);
        Registration::addNewSettings($loglevelT3FileWriter);
    }

    /**
     * Create the validation callback for the file writer.
     *
     * @return \Closure
     */
    protected function createFileWriterValidator(): Closure
    {
        return function ($value, Pool $pool) {
            // All values from the ini should be strings.
            // The emergency log level in TYPO3 9 and older is the integer 0.
            // And when I ask 'some value' == 0, I will always get a true.
            if (is_numeric($value)) {
                $value = (int) $value;
            }
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
            foreach ($levels as $level) {
                if ($value === $level) {
                    return true;
                }
            }

            $pool->messages->addMessage('configErrorLoglevelT3FileWriter', [$value]);
            return false;
        };
    }

    /**
     * Register the admin panel integration and the query debugger.
     */
    protected function registerVersionDependantStuff()
    {
        // The QueryBuilder special analysis.
        // Only for Doctrine stuff.
        if (version_compare(Bootstrap::getTypo3Version(), '8.3', '>')) {
            Registration::registerEvent(Objects::class . static::START_EVENT, QueryDebugger::class);
        }

        // Register our modules for the admin panel.
        if (
            version_compare(Bootstrap::getTypo3Version(), '9.5', '>=') &&
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
     * @param array $tempPaths
     */
    protected function createWorkingDirectories(array $tempPaths)
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

        // Empty index.html in case the htaccess is not enough.
        $indexHtml = '';
        // Create and protect the temporal folders.
        foreach ($tempPaths as $tempPath) {
            if (!is_dir($tempPath)) {
                // Create it!
                GeneralUtility::mkdir($tempPath);
                // Protect it!
                GeneralUtility::writeFileToTypo3tempDir($tempPath . '/' . '.htaccess', $htAccess);
                GeneralUtility::writeFileToTypo3tempDir($tempPath . '/' . 'index.html', $indexHtml);
            }
        }
    }
}
