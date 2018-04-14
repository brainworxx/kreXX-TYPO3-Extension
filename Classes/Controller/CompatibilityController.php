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

namespace Brainworxx\Includekrexx\Controller;

use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Config\Fallback;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;

/**
 * Class Tx_Includekrexx_Controller_IndexController
 *
 * This is not a real controller. It hosts all those ugly workarounds to keep
 * this extension compatible back to 4.5. This  makes the other controllers
 * (hopefully) more readable.
 */
class CompatibilityController extends ActionController
{
    const EXT_KEY = 'includekrexx';

    /**
     * List of all setting-names for which we are accepting values.
     *
     * @var array
     */
    protected $allowedSettingsNames = array(
        Fallback::SETTING_SKIN,
        Fallback::SETTING_MAX_FILES,
        Fallback::SETTING_DESTINATION,
        Fallback::SETTING_MAX_CALL,
        Fallback::SETTING_DISABLED,
        Fallback::SETTING_DETECT_AJAX,
        Fallback::SETTING_ANALYSE_PROTECTED,
        Fallback::SETTING_ANALYSE_PRIVATE,
        Fallback::SETTING_ANALYSE_TRAVERSABLE,
        Fallback::SETTING_DEBUG_METHODS,
        Fallback::SETTING_NESTING_LEVEL,
        Fallback::SETTING_ANALYSE_PROTECTED_METHODS,
        Fallback::SETTING_ANALYSE_PRIVATE_METHODS,
        Fallback::SETTING_ANALYSE_CONSTANTS,
        Fallback::SETTING_IP_RANGE,
        Fallback::SETTING_ANALYSE_GETTER,
        Fallback::SETTING_MAX_RUNTIME,
        Fallback::SETTING_MEMORY_LEFT,
        Fallback::SETTING_USE_SCOPE_ANALYSIS,
        Fallback::SETTING_MAX_STEP_NUMBER,
        Fallback::SETTING_ARRAY_COUNT_LIMIT,
    );

    /**
     * List of all sections for which we are accepting values
     *
     * @var array
     */
    protected $allowedSections = array(
        Fallback::SECTION_RUNTIME,
        Fallback::SECTION_OUTPUT,
        Fallback::SECTION_PROPERTIES,
        Fallback::SECTION_METHODS,
        Fallback::SECTION_PRUNE_OUTPUT,
    );

    /**
     * The kreXX framework.
     *
     * @var \Brainworxx\Krexx\Service\Factory\Pool
     */
    protected $pool;

    /**
     * Set the pool and do the parent constructor.
     */
    public function __construct()
    {
        parent::__construct();
        Pool::createPool();
        $this->pool = \Krexx::$pool;
    }

    /**
     * We check if we are running with a productive preset. If we do, we
     * will display a warning.
     */
    protected function checkProductiveSetting()
    {
        $isProductive = false;

        // Check the 'Live' preset (7.6 and above)
        if (class_exists('TYPO3\\CMS\\Install\\Configuration\\Context\\LivePreset')) {
            /** @var \TYPO3\CMS\Install\Configuration\Context\LivePreset $debugPreset */
            $productionPreset = $this->objectManager
                ->get('TYPO3\\CMS\\Install\\Configuration\\Context\\LivePreset');
            $isProductive = $productionPreset->isActive();
        }

        // Check the 'Production' preset (6.2)
        if (class_exists('TYPO3\\CMS\\Install\\Configuration\\Context\\ProductionPreset')) {
            /** @var \TYPO3\CMS\Install\Configuration\Context\ProductionPreset $debugPreset */
            $productionPreset = $this->objectManager
                ->get('TYPO3\\CMS\\Install\\Configuration\\Context\\ProductionPreset');
            $isProductive = $productionPreset->isActive();
        }

        if ($isProductive) {
            //Display a warning, if we are in Productive / Live settings.
            $this->addFlashMessage(
                LocalizationUtility::translate('debugpreset.warning.message', static::EXT_KEY),
                LocalizationUtility::translate('debugpreset.warning.title', static::EXT_KEY),
                FlashMessage::WARNING
            );
        }
    }

    /**
     * Injects the arguments
     *
     * @param Arguments $arguments
     *   The arguments from the call to the controller.
     */
    public function injectArguments(Arguments $arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * Gets all messages from kreXX and translates them.
     *
     * @return array
     *   The translated messages.
     */
    protected function getTranslatedMessages()
    {
        $result = array();
        // Get the keys and the args.
        $keys = $this->pool->messages->getKeys();

        foreach ($keys as $message) {
            // And translate them.
            $result[] = LocalizationUtility::translate($message['key'], static::EXT_KEY, $message['params']);
        }

        return $result;
    }

    /**
     * Due to a change in the attributes of the flashmessage viewhelper,
     * we are using special partials for it, depending on the TYPO3 version.
     */
    protected function assignFlashInfo()
    {
        if (version_compare(TYPO3_version, '7.3', '>=')) {
            $this->view->assign('specialflash', true);
        } else {
            $this->view->assign('specialflash', false);
        }
    }
}
