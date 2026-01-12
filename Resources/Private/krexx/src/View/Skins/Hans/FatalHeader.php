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

namespace Brainworxx\Krexx\View\Skins\Hans;

/**
 * Renders the header of the fatal error handler.
 *
 * @deprecated
 *   Since 6.0.0
 *   Will be removed.
 *   Has anybody used this one since PHP 7.0 anyway?
 * @codeCoverageIgnore
 *   We will not test deprecated code.
 */
trait FatalHeader
{
    /**
     * @var string[]
     */
    private array $markerFatalHeader = [
        '{cssJs}',
        '{version}',
        '{search}',
        '{KrexxId}',
        '{type}',
        '{encoding}',
        '{noJavaScript}',
        '{searchHeadline}'
    ];

    /**
     * {@inheritdoc}
     */
    public function renderFatalHeader(string $cssJs, string $errorType): string
    {
        return str_replace(
            $this->markerFatalHeader,
            [
                $cssJs,
                $this->pool->config->version,
                $this->renderSearch(),
                $this->pool->recursionHandler->getMarker(),
                $errorType,
                $this->pool->chunks->getOfficialEncoding(),
                $this->pool->messages->getHelp('noJavaScript'),
                $this->pool->messages->getHelp('searchHeadline'),
            ],
            $this->fileCache[static::FILE_FATAL_HEADER]
        );
    }

    /**
     * Getter of the fatal header for unit tests.
     *
     * @codeCoverageIgnore
     *   We are not testing the unit tests.
     *
     * @return string[]
     *   The marker array.
     */
    public function getMarkerFatalHeader(): array
    {
        return $this->markerFatalHeader;
    }
}
