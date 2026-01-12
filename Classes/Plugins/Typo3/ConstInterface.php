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

namespace Brainworxx\Includekrexx\Plugins\Typo3;

interface ConstInterface
{
    /**
     * Our extension key.
     *
     * @var string
     */
    public const EXT_KEY = 'includekrexx';

    /**
     * @var string
     */
    public const TYPO3_CONF_VARS = 'TYPO3_CONF_VARS';

    /**
     * @var string
     */
    public const EXTCONF = 'EXTCONF';

    /**
     * @var string
     */
    public const WRITER_CONFIGURATION = 'writerConfiguration';

    /**
     * @var string
     */
    public const LOG = 'LOG';

    /**
     * @var string
     */
    public const ADMIN_PANEL = 'adminpanel';

    /**
     * @var string
     */
    public const MODULES = 'modules';

    /**
     * @var string
     */
    public const DEBUG = 'debug';

    /**
     * @var string
     */
    public const SUBMODULES = 'submodules';

    /**
     * @var string
     */
    public const SYS = 'SYS';

    /**
     * @var string
     */
    public const FLUID = 'fluid';

    /**
     * @var string
     */
    public const FLUID_NAMESPACE = 'namespaces';

    /**
     * @var string
     */
    public const KREXX = 'krexx';

    /**
     * @var string
     */
    public const TX_INCLUDEKREXX = 'tx_includekrexx';

    /**
     * The activation setting name of our TYPO3 file writer.
     *
     * @var string
     */
    public const ACTIVATE_T3_FILE_WRITER = 'activateT3FileWriter';

    /**
     * The configuration name of our TYPO3 file writer.
     *
     * @var string
     */
    public const LOG_LEVEL_T3_FILE_WRITER = 'loglevelT3FileWriter';
}
