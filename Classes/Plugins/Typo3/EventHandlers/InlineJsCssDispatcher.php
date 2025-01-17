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
 *   kreXX Copyright (C) 2014-2025 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Brainworxx\Krexx\Service\Factory\EventHandlerInterface;
use Brainworxx\Krexx\Service\Factory\Pool;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class InlineJsCssDispatcher implements EventHandlerInterface, ConfigConstInterface
{
    /**
     * The pool.
     *
     * @var \Brainworxx\Krexx\Service\Factory\Pool
     */
    protected Pool $pool;

    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * We add the inline js to the asset collector, to avoid CSP problems.
     *
     * @param \Brainworxx\Krexx\Analyse\Callback\AbstractCallback|null $callback
     * @param \Brainworxx\Krexx\Analyse\Model|null $model
     * @return string
     */
    public function handle(
        ?AbstractCallback $callback = null,
        ?Model $model = null
    ): string {
        // We do this only when we are not logging.
        if ($this->pool->config->getSetting(static::SETTING_DESTINATION) !== static::VALUE_FILE) {
            $jsSources = $model->getData();
            if (!empty($jsSources)) {
                /** @var AssetCollector $collector */
                $collector = GeneralUtility::makeInstance(AssetCollector::class);
                // We enforce a nonce and add it to the bottom.
                // Otherwise, it may not get rendered.
                $collector->addInlineJavaScript(
                    'krexxDomTools',
                    '(function(){' . $jsSources . '})();',
                    [],
                    ['priority' => false, 'useNonce' => true]
                );
                $collector->addInlineStyleSheet(
                    'krexxInlineCss',
                    $model->getNormal(),
                    [],
                    ['priority' => false, 'useNonce' => true]
                );
            }
        }

        return '';
    }
}
