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
use Brainworxx\Includekrexx\Service\LanguageTrait;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Pool;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use Brainworxx\Includekrexx\Collectors\Configuration;
use Brainworxx\Includekrexx\Collectors\FormConfiguration;
use TYPO3\CMS\Install\Configuration\Context\LivePreset;

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
        if ($this->objectManager->get(LivePreset::class)->isActive()) {
            //Display a warning, if we are in Productive / Live settings.
            $this->addFlashMessage(
                static::translate('debugpreset.warning.message', Bootstrap::EXT_KEY),
                static::translate('debugpreset.warning.title', Bootstrap::EXT_KEY),
                FlashMessage::WARNING
            );
        }
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
     * Move all messages from kreXX to the flash messages.
     *
     * @return array
     *   The translated messages.
     */
    protected function retrieveKrexxMessages()
    {
        $result = [];
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

        return $result;
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
            $GLOBALS['BE_USER']->check('modules', 'tools_IncludekrexxKrexxConfiguration');
    }
}
