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

namespace Brainworxx\Includekrexx\Plugins\Typo3\Scalar;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Scalar\String\AbstractScalarAnalysis;
use Brainworxx\Krexx\Service\Factory\Pool;
use Throwable;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * LLL string parser.
 */
class LllString extends AbstractScalarAnalysis
{
    /**
     * The name of the localisation utility.
     *
     * @var LocalizationUtility
     */
    protected LocalizationUtility $localisationUtility;

    /**
     * A list of all installed extensions in TYPO4 14.0
     *
     * @var array
     */
    protected array $extensionKeys = [];

    /**
     * Inject the pool.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(protected Pool $pool)
    {
        $this->localisationUtility = new LocalizationUtility();
        $typo3Version = new Typo3Version();
        if ($typo3Version->getMajorVersion() > 13) {
            try {
                $this->extensionKeys = ExtensionManagementUtility::getLoadedExtensionListArray();
            } catch (Throwable) {
                // Do nothing.
                // Unable to retrieve the extension keys. Maybe we are running
                // in a context where this is not possible. Or someone messed
                // with the installation. This is a debugger after all.
            }
        }
    }

    /**
     * Can we get translations, at all?
     *
     * @return bool
     */
    public static function isActive(): bool
    {
        // The translation service is always available.
        return true;
    }

    /**
     * Test the string for domain translation.
     *
     * It looks like this:
     * "some_extension.something:some_string"
     *
     * @param string $string
     * @return bool
     */
    protected function isDomainTranslation(string $string): bool
    {
        // Get a first impression.
        // We need a ":" and a "." in there somewhere.
        if (
            !str_contains(haystack: $string, needle: ':')
            && !str_contains(haystack: $string, needle: '.')
        ) {
            return false;
        }

        $parts = explode(separator: '.', string: $string);
        return (!empty($parts[0]) && in_array(needle: $parts[0], haystack: $this->extensionKeys));
    }

    /**
     * @param string $string
     *   The string we try to translate.
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model, so far.
     *
     * @return bool
     *   Can we translate it? Well, actually, we don't handle it in here.
     *   We directly add the translation to the model.
     */
    public function canHandle($string, Model $model): bool
    {
        if (
            !str_contains(haystack: $string, needle: 'LLL:')
            && !$this->isDomainTranslation(string: $string)
        ) {
            // Early return. Not much to do here.
            return false;
        }

        // Retrieve the EXT path from the framework.
        set_error_handler($this->pool->retrieveErrorCallback());

        try {
            // Add the string directly to the model
            $trans = $this->localisationUtility::translate(key: $string);
            if (!empty($trans)) {
                $model->addToJson(
                    key: $this->pool->messages->getHelp('TYPO3Trans'),
                    value: $trans
                );
            }

            $this->resolveExtPath(string: $string, model: $model);
        } catch (Throwable) {
            // Huh, someone messed with the translations.
        }

        restore_error_handler();
        // Always false.
        return false;
    }

    /**
     * Try to resolve the ext path.
     *
     * @param string $string
     * @param \Brainworxx\Krexx\Analyse\Model $model
     * @return void
     */
    protected function resolveExtPath(string $string, Model $model): void
    {
        $string = preg_replace(
            pattern: '/^LLL:(.+):[^:]+$/',
            replacement: "$1",
            subject: $string
        );

        if (str_starts_with(haystack: $string, needle: 'EXT:')) {
            $string = GeneralUtility::getFileAbsFileName(fileName: $string);
            $model->addToJson(
                key:$this->pool->messages->getHelp(key: 'TYPO3ResPath'),
                value: $string
            );
            if (!file_exists(filename: $string)) {
                $model->addToJson(
                    key: $this->pool->messages->getHelp(key: 'TYPO3ResPathError'),
                    value: $this->pool->messages->getHelp(key: 'TYPO3ResPathDoesNotExist')
                );
            }
        }
    }

    /**
     * Only used for unit tests.
     *
     * @codeCoverageIgnore
     *   Who tests the tests?
     *
     * @param \TYPO3\CMS\Extbase\Utility\LocalizationUtility $object
     *   The name of the localisation utility.
     */
    public function setLocalisationUtility(LocalizationUtility $object): void
    {
        $this->localisationUtility = $object;
    }
}
