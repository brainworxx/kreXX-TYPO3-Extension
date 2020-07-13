<?php


namespace Brainworxx\Includekrexx\Plugins\Typo3\Scalar;

use Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\AbstractScalarAnalysis;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\View\ViewConstInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * LLL string parser.
 *
 * @package Brainworxx\Includekrexx\Plugins\Typo3\Scalar
 */
class LllString extends AbstractScalarAnalysis implements ViewConstInterface
{
    /**
     * Can we get translations, at all?
     *
     * @return bool
     */
    public static function isActive(): bool
    {
        // Test if language service is available.
        return is_callable([LocalizationUtility::class, 'translate']);
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
        // Retrieve the EXT path from the framework.
        set_error_handler(function () {
            // Do nothing.
        });

        try {
            // Add the string directly to the model
            if (strpos($string, 'LLL:') !== 0) {
                $trans = LocalizationUtility::translate($string);
                if (empty($trans) === false) {
                    $model->addToJson('Translation', $trans);
                }
            }
        } catch (\Throwable $e) {
            // Huh, someone messed with the GeneralUtility.
            restore_error_handler();
            return false;
        }

        restore_error_handler();

        // Always false.
        return false;
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