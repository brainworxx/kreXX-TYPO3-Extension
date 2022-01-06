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

namespace Brainworxx\Includekrexx\Service;

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Wrapper around the LocalizationUtility for better unit testing.
 *
 * @codeCoverageIgnore
 *   The only reason for this trait is unit testing. I will not write a specific
 *   test for it.
 */
trait LanguageTrait
{
    /**
     * Returns the localized label of the LOCAL_LANG key, $key.
     *
     * @param string $key
     *   The key from the LOCAL_LANG array for which to return the value.
     * @param string|null $extensionName
     *   The name of the extension
     * @param array|null $arguments
     *   The arguments of the extension, being passed over to vsprintf
     *
     * @return string|null
     *   The value from LOCAL_LANG or null if no translation was found.
     */
    public static function translate(string $key, string $extensionName = null, array $arguments = null): ?string
    {
        if (defined('KREXX_TEST_IN_PROGRESS')) {
            return $key;
        }

        return LocalizationUtility::translate($key, $extensionName, $arguments);
    }
}
