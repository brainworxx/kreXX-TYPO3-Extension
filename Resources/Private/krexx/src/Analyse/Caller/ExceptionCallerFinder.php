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
 *   kreXX Copyright (C) 2014-2021 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Caller;

use Throwable;

/**
 * The caller finder for exceptions
 *
 * @package Brainworxx\Krexx\Analyse\Caller
 */
class ExceptionCallerFinder extends AbstractCaller implements BacktraceConstInterface
{
    /**
     * It simply deducts everything from the exception.
     *
     * @param string $headline
     *   An empty string. Not used here.
     * @param \Throwable|\Brainworxx\Krexx\Logging\Model $data
     *   The exception that was thrown
     *
     * @return array
     *   The exception, that was thrown.
     */
    public function findCaller(string $headline, $data): array
    {
        if ($data instanceof Throwable) {
            $headline = get_class($data);
        }
        return [
            static::TRACE_FILE => $data->getFile(),
            static::TRACE_LINE => $data->getLine() + 1,
            static::TRACE_VARNAME => ' ' . get_class($data),
            static::TRACE_LEVEL => 'error',
            static::TRACE_TYPE => $headline,
            static::TRACE_DATE => date('d-m-Y H:i:s', time()),
            static::TRACE_URL => $this->getCurrentUrl(),
        ];
    }
}
