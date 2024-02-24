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
 *   kreXX Copyright (C) 2014-2024 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Plugins\Typo3\Scalar;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Scalar\String\FilePath;
use Throwable;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Resolving the actual file path from strings that start with EXT:
 */
class ExtFilePath extends FilePath
{
    /**
     * Retrieve the absolute path and then pass it into the original FilePath.
     *
     * @param string $string
     * @param \Brainworxx\Krexx\Analyse\Model $model
     * @return bool
     */
    public function canHandle($string, Model $model): bool
    {
        if (strpos($string, 'EXT:') !== 0) {
            // Does not start with EXT:
            // Nothing to do here.
            return false;
        }

        // Retrieve the EXT path from the framework.
        set_error_handler($this->pool->retrieveErrorCallback());
        try {
            $string = GeneralUtility::getFileAbsFileName($string);
        } catch (Throwable $e) {
            // Huh, someone messed with the GeneralUtility.
            restore_error_handler();
            return false;
        }

        restore_error_handler();

        if (empty($string)) {
            return false;
        }

        // Preserve the result from the getFileAbsFileName.
        $messages = $this->pool->messages;
        $model->addToJson($messages->getHelp('TYPO3ResPath'), $this->pool->fileService->filterFilePath($string));

        if (!file_exists($string)) {
            $model->addToJson($messages->getHelp('TYPO3ResPathError'), $messages->getHelp('TYPO3ResPathDoesNotExist'));
        }

        return parent::canHandle($string, $model);
    }
}
