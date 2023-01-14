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
 *   kreXX Copyright (C) 2014-2023 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Collectors;

use Brainworxx\Includekrexx\Controller\ControllerConstInterface;
use Brainworxx\Includekrexx\Service\LanguageTrait;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Brainworxx\Krexx\Service\Factory\Pool;
use TYPO3\CMS\Fluid\View\AbstractTemplateView;

/**
 * General stuff for all data collectors.
 */
abstract class AbstractCollector implements ControllerConstInterface, ConfigConstInterface
{
    use LanguageTrait;

    /**
     * @var string
     */
    public const MODULE_DATA = 'moduleData';

    /**
     * @var string
     */
    public const PLUGIN_NAME = 'tools_IncludekrexxKrexxConfiguration';

    /**
     * @var string
     */
    protected const SETTINGS_NAME = 'name';

    /**
     * @var string
     */
    protected const SETTINGS_VALUE = 'value';

    /**
     * @var string
     */
    protected const SETTINGS_USE_FACTORY_SETTINGS = 'useFactorySettings';

    /**
     * @var string
     */
    protected const SETTINGS_FALLBACK = 'fallback';

    /**
     * @var string
     */
    protected const SETTINGS_MODE = 'mode';

    /**
     * @var string
     */
    protected const SETTINGS_OPTIONS = 'options';

    /**
     * The kreXX pool.
     *
     * @var \Brainworxx\Krexx\Service\Factory\Pool
     */
    protected $pool;

    /**
     * The current backend user
     *
     * @var array
     */
    protected $userUc = [];

    /**
     * List of options, that are 'expert' only.
     *
     * @var string[]
     */
    protected $expertOnly = [
        self::SETTING_DETECT_AJAX,
        self::SETTING_MAX_STEP_NUMBER,
        self::SETTING_ARRAY_COUNT_LIMIT,
        self::SETTING_DEBUG_METHODS,
        self::SETTING_MAX_RUNTIME,
        self::SETTING_MEMORY_LEFT,
        self::SETTING_MAX_FILES
    ];

    /**
     * Do we have access here?
     *
     * @var bool
     */
    protected $hasAccess = false;

    /**
     * Inject the pool.
     */
    public function __construct()
    {
        Pool::createPool();
        $this->pool = Krexx::$pool;
        if (isset($GLOBALS[static::BE_USER])) {
            $user = $GLOBALS[static::BE_USER];
            $this->hasAccess = $user
                ->check(static::BE_MODULES, static::PLUGIN_NAME);
        }
        if ($this->hasAccess && isset($user->uc[static::MODULE_DATA][static::MODULE_KEY])) {
            $this->userUc = $user->uc[static::MODULE_DATA][static::MODULE_KEY];
        }
    }

    /**
     * Assigning stuff to the view.
     *
     * @param AbstractTemplateView $view
     */
    abstract public function assignData(AbstractTemplateView $view): void;
}
