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

namespace Brainworxx\Krexx\View\Skins\SmokyGrey;

use Brainworxx\Krexx\Analyse\Model;

/**
 * Renders the footer with the configuration options.
 */
trait Footer
{
    /**
     * @var string[]
     */
    private array $markerFooter = [
        '{kconfiguration-classes}',
        '{additionalData}',
        '{noDataAvailable}'
    ];

    /**
     * {@inheritDoc}
     */
    public function renderFooter(array $caller, Model $model, bool $configOnly = false): string
    {
        // Doing special stuff for smokygrey:
        // We hide the debug-tab when we are displaying the config-only and switch
        // to the config as the current payload.
        if ($configOnly) {
            return str_replace(
                $this->markerFooter,
                [
                    '',
                    $this->pool->messages->getHelp('additionalData'),
                    $this->pool->messages->getHelp('noDataAvailable'),
                ],
                parent::renderFooter($caller, $model, true)
            );
        }

        return str_replace(
            $this->markerFooter,
            [
                static::STYLE_HIDDEN,
                $this->pool->messages->getHelp('additionalData'),
                $this->pool->messages->getHelp('noDataAvailable'),
            ],
            parent::renderFooter($caller, $model, $configOnly)
        );
    }

    /**
     * Getter of the footer for unit tests.
     *
     * @codeCoverageIgnore
     *   We are not testing the unit tests.
     *
     * @return string[]
     *   The marker array.
     */
    public function getMarkerFooter(): array
    {
        return $this->markerFooter;
    }
}
