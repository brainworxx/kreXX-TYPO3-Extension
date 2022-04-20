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

namespace Brainworxx\Includekrexx\Collectors;

use Brainworxx\Includekrexx\Controller\ControllerConstInterface;
use Brainworxx\Includekrexx\Service\LanguageTrait;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Pool;
use TYPO3\CMS\Fluid\View\AbstractTemplateView;

/**
 * General stuff for all data collectors.
 */
abstract class AbstractCollector implements ControllerConstInterface
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
     * @var array
     */
    protected $expertOnly = [
        'detectAjax',
        'useScopeAnalysis',
        'maxStepNumber',
        'arrayCountLimit',
        'debugMethods',
        'maxRuntime',
        'memoryLeft',
        'maxfiles'
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
        if (isset($GLOBALS['BE_USER'])) {
            $user = $GLOBALS['BE_USER'];
            $this->hasAccess = $user
                ->check('modules', static::PLUGIN_NAME);
        }
        if ($this->hasAccess && isset($user->uc[static::MODULE_DATA][static::MODULE_KEY])) {
            $this->userUc = $user->uc[static::MODULE_DATA][static::MODULE_KEY];
        }
    }

    /**
     * Assigning stuff to the view.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view
     */
    abstract public function assignData(AbstractTemplateView $view): void;
}
