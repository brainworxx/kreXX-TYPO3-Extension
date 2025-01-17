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

namespace Brainworxx\Includekrexx\Plugins\Typo3\Scalar;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Scalar\String\AbstractScalarAnalysis;
use Brainworxx\Krexx\Service\Factory\Pool;
use Throwable;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Brainworxx\Includekrexx\Tests\Helpers\LocalizationUtility as UnitLocalizationUtility;
use Brainworxx\Includekrexx\Tests\Helpers\LocalizationUtility12 as UnitLocalizationUtility12;

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
     * Inject the pool.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->localisationUtility = new LocalizationUtility();
        parent::__construct($pool);
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
        if (strpos($string, 'LLL:') === false) {
            // Early return. Not much to do here.
            return false;
        }

        // Retrieve the EXT path from the framework.
        set_error_handler(function (): void {
            // Do nothing.
        });

        try {
            // Add the string directly to the model
            $trans = $this->localisationUtility::translate($string);
            if (!empty($trans)) {
                $model->addToJson($this->pool->messages->getHelp('TYPO3Trans'), $trans);
            }

            $this->resolveExtPath($string, $model);
        } catch (Throwable $e) {
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
        $string = preg_replace('/^LLL:(.+):[^:]+$/', "$1", $string);

        if (strpos($string, 'EXT:') === 0) {
            $string = GeneralUtility::getFileAbsFileName($string);
            $model->addToJson($this->pool->messages->getHelp('TYPO3ResPath'), $string);
            if (!file_exists($string)) {
                $model->addToJson(
                    $this->pool->messages->getHelp('TYPO3ResPathError'),
                    $this->pool->messages->getHelp('TYPO3ResPathDoesNotExist')
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
     * @param UnitLocalizationUtility|UnitLocalizationUtility12 $object
     *   The name of the localisation utility.
     */
    public function setLocalisationUtility($object): void
    {
        $this->localisationUtility = $object;
    }

    /**
     * Should not get called.
     *
     * We do not add another node to the output, just for a simple string.
     *
     * @return array
     */
    protected function handle(): array
    {
        // Do nothing.
        // Should not get called.
        return [];
    }
}
