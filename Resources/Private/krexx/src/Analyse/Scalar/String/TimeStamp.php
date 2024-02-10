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

namespace Brainworxx\Krexx\Analyse\Scalar\String;

use Brainworxx\Krexx\Analyse\Model;
use DateTime;
use Throwable;

/**
 * "Analysing" a timestamp in a string.
 *
 * @see \Brainworxx\Krexx\Analyse\Routing\Process\ProcessInteger
 *   Here happens the integer version.
 */
class TimeStamp extends AbstractScalarAnalysis
{
    /**
     * {@inheritDoc}
     */
    public static function isActive(): bool
    {
        return true;
    }

    /**
     * We add the short info straight to the model.
     *
     * {@inheritDoc}
     *
     * @throws \Exception
     */
    public function canHandle($string, Model $model): bool
    {
        // Get a first impression.
        $int  = (int) $string;
        if ($int < 946681200) {
            // We'll not treat it like a timestamp.
            return false;
        }

        // Might be a regular time stamp, get a second impression.
        $metaTimestamp = $this->pool->messages->getHelp('metaTimestamp');
        if ((string)$int === $string) {
            $model->addToJson(
                $metaTimestamp,
                (new DateTime('@' . $int))->format('d.M Y H:i:s')
            );
            return false;
        }

        // Check for a microtime string.
        try {
            $model->addToJson(
                $metaTimestamp,
                (DateTime::createFromFormat('U.u', $string)->format('d.M Y H:i:s.u'))
            );
        } catch (Throwable $exception) {
            // Do nothing
        }

        // The last part to check for would be a string return from the
        // microtime. In over 10 years of PHP development, I've never seen one
        // of these, ever. So no, as of, right now, we will not check these.

        // Make sure that the handle part is not called, to save time.
        return false;
    }
}
