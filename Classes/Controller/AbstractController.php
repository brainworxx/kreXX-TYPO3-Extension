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

namespace Brainworxx\Includekrexx\Controller;

use Brainworxx\Includekrexx\Bootstrap\Bootstrap;
use Brainworxx\Includekrexx\Collectors\AbstractCollector;
use Brainworxx\Includekrexx\Domain\Model\Settings;
use Brainworxx\Includekrexx\Service\LanguageTrait;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Pool;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use Brainworxx\Includekrexx\Collectors\Configuration;
use Brainworxx\Includekrexx\Collectors\FormConfiguration;
use TYPO3\CMS\Install\Configuration\Context\LivePreset;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\ResponseInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class Tx_Includekrexx_Controller_IndexController
 *
 * This is not a real controller. It hosts all those ugly workarounds to keep
 * this extension compatible back to 4.5. This  makes the other controllers
 * (hopefully) more readable.
 */
abstract class AbstractController extends ActionController
{
    use LanguageTrait;

    const MODULE_KEY = 'IncludekrexxKrexxConfiguration';

    /**
     * The kreXX framework.
     *
     * @var \Brainworxx\Krexx\Service\Factory\Pool
     */
    protected $pool;

     /**
     * @var \Brainworxx\Includekrexx\Collectors\Configuration
     */
    protected $configuration;

    /**
     * @var \Brainworxx\Includekrexx\Collectors\FormConfiguration
     */
    protected $formConfiguration;

    /**
     * @var \Brainworxx\Includekrexx\Domain\Model\Settings
     */
    protected $settingsModel;

    /**
     * @var LivePreset
     */
    protected $livePreset;

    /**
     * Set the pool and do the parent constructor.
     */
    public function __construct()
    {
        if (version_compare(TYPO3_version, '10.0.0', '<')) {
            // The constructor was removed with 10.0.0.
            parent::__construct();
        }
        Pool::createPool();
        $this->pool = Krexx::$pool;
    }

    /**
     * We check if we are running with a productive preset. If we do, we
     * will display a warning.
     */
    protected function checkProductiveSetting()
    {
        if ($this->livePreset->isActive()) {
            //Display a warning, if we are in Productive / Live settings.
            $this->addFlashMessage(
                static::translate('debugpreset.warning.message', Bootstrap::EXT_KEY),
                static::translate('debugpreset.warning.title', Bootstrap::EXT_KEY),
                FlashMessage::WARNING
            );
        }
    }

    /**
     * Inject the live preset.
     *
     * @param \TYPO3\CMS\Install\Configuration\Context\LivePreset $livePreset
     */
    public function injectLivePreset(LivePreset $livePreset)
    {
        $this->livePreset = $livePreset;
    }

    /**
     * Inject the configuration collector.
     *
     * @param \Brainworxx\Includekrexx\Collectors\Configuration $configuration
     */
    public function injectConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Inject the form configuration collector.
     *
     * @param \Brainworxx\Includekrexx\Collectors\FormConfiguration $formConfiguration
     */
    public function injectFormConfiguration(FormConfiguration $formConfiguration)
    {
        $this->formConfiguration = $formConfiguration;
    }

    /**
     * Inject the settings model.
     *
     * @param \Brainworxx\Includekrexx\Domain\Model\Settings $settings
     */
    public function injectSettings(Settings $settings)
    {
        $this->settingsModel = $settings;
    }

    /**
     * Move all messages from kreXX to the flash messages.
     */
    protected function retrieveKrexxMessages()
    {
        // Get the keys and the args.
        $keys = $this->pool->messages->getKeys();

        foreach ($keys as $message) {
            // And translate them.
            $this->addFlashMessage(
                static::translate($message['key'], Bootstrap::EXT_KEY, $message['params']),
                static::translate('general.error.title', Bootstrap::EXT_KEY),
                FlashMessage::ERROR
            );
        }
    }

    /**
     * Dispatches a file, using output buffering.
     *
     * @param string $path
     *   The path of the file we want to dispatch to the browser.
     */
    protected function dispatchFile($path)
    {
        header('Content-Type: text/html; charset=utf-8');
        header('Content-length: ' . filesize($path));

        $size = 1024 * 1024;
        $res = fopen($path, "rb");
        while (!feof($res)) {
            echo fread($res, $size);
            ob_flush();
            flush();
        }
        fclose($res);
    }

    /**
     * Additional check, if the current Backend user has access to the extension.
     *
     * @return bool
     *   The result of the check.
     */
    protected function hasAccess()
    {
        return isset($GLOBALS['BE_USER']) &&
            $GLOBALS['BE_USER']->check('modules', AbstractCollector::PLUGIN_NAME);
    }

    /**
     * Create the response, depending on the calling context.
     *
     * @return ResponseInterface|NullResponse
     */
    protected function createResponse()
    {
        if (empty($this->objectManager)) {
            // This is either 10.0 or 9.5 with frontend dispatching.
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            $response = $objectManager->get(NullResponse::class);
        } else {
            // 8.7 or 7.6 backend dispatching.
            // And yes, we do need the shutdown here.
            $response = $this->objectManager->get(ResponseInterface::class);
            $response->shutdown();
        }

        return $response;
    }

    /**
     * With 10.0, the backend container became somewhat unable to handle css and
     * js files. The path needs to be different, depending on the debug settings.
     *
     * @example
     *   /typo3_100/typo3conf/ext/includekrexx/Resources/Public/Css/Index.css
     *   --> with debug settings
     *   /typo3conf/ext/includekrexx/Resources/Public/Css/Index.css
     *   --> with productive settings.
     *
     * Imho the best way to deal with this is to (again) assign the css and js
     * inline.
     */
    protected function assignCssJs()
    {
        $jsPath = GeneralUtility::getFileAbsFileName('EXT:includekrexx/Resources/Public/JavaScript/Index.js');
        $cssPath = GeneralUtility::getFileAbsFileName('EXT:includekrexx/Resources/Public/Css/Index.css');
        $this->view->assign('js', file_get_contents($jsPath));
        $this->view->assign('css', file_get_contents($cssPath));
    }
}
