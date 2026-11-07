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

namespace Brainworxx\Includekrexx\Collectors;

use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CodeExamples extends AbstractCollector
{
    /**
     * The path to the code examples.
     *
     * @var string
     */
    protected const PATH = 'EXT:includekrexx/Resources/Private/Examples/';

    /**
     * The configuration for the code examples.
     *
     * @var array
     */
    protected array $configuration = [
        ['type' => 'html', 'rows' => 6,  'content' => 'FluidDebug.txt'],
        ['type' => 'html', 'rows' => 6,  'content' => 'FluidDebugInline.txt'],
        ['type' => 'html', 'rows' => 10, 'content' => 'FluidTimer.txt'],
        ['type' => 'php',  'rows' => 3,  'content' => 'PhpBacktrace.txt'],
        ['type' => 'php',  'rows' => 8,  'content' => 'PhpDebug.txt'],
        ['type' => 'php',  'rows' => 3,  'content' => 'PhpTimerEnd.txt'],
        ['type' => 'php',  'rows' => 1,  'content' => 'PhpTimerMoment.txt'],
    ];

    /**
     * Assign the code examples for the view.
     */
    public function assignData($view): void
    {
        $codeExamples = [];
        foreach ($this->configuration as $example) {
            $codeExamples[pathinfo($example['content'], PATHINFO_FILENAME)] = [
                'modeInstruction' => $this->createModeInstructions($example['type']),
                'content' => $this->retrieveFileContent($example['content']),
                'rows' => $example['rows']
            ];
        }

        $view->assign('codeExamples', $codeExamples);
    }

    protected function createModeInstructions(string $type): string
    {
        return json_encode(
            value: JavaScriptModuleInstruction::create(
                name: '@codemirror/lang-' . $type,
                exportName: $type
            )->invoke()
        );
    }

    /**
     * Retrieve the content of a file.
     *
     * @param string $fileName
     *   The name of the file to retrieve.
     *
     * @return string
     *   The content of the file.
     */
    protected function retrieveFileContent(string $fileName): string
    {
        return trim(file_get_contents(
            filename: GeneralUtility::getFileAbsFileName(fileName: self::PATH . $fileName)
        ));
    }
}
