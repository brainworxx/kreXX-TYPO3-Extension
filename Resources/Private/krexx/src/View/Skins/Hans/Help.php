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

namespace Brainworxx\Krexx\View\Skins\Hans;

use Brainworxx\Krexx\Analyse\Model;

/**
 * Renders the help info.
 */
trait Help
{
    /**
     * @var string[]
     */
    private $markerHelpRow = [
        '{helptitle}',
        '{helptext}'
    ];

    /**
     * @var string
     */
    private $markerHelp = '{help}';

     /**
     * Renders the helptext.
     *
     * @param Model $model
     *   The ID of the helptext.
     *
     * @return string
     *   The generated markup from the template files.
     */
    protected function renderHelp(Model $model): string
    {
        $data = $model->getJson();

        // Test if we have anything to display at all.
        if (empty($data)) {
            return '';
        }

        // We have at least something to display here.
        $helpContent = '';

        // Add the stuff from the json after the help text, if any.
        foreach ($data as $title => $text) {
            $helpContent .= str_replace(
                $this->markerHelpRow,
                [$title, $text],
                $this->fileCache[static::FILE_HELPROW]
            );
        }

        // Add it into the wrapper.
        return str_replace($this->markerHelp, $helpContent, $this->fileCache[static::FILE_HELP]);
    }

    /**
     * Getter of the help row for unit tests.
     *
     * @codeCoverageIgnore
     *   We are not testing the unit tests.
     *
     * @return string[]
     *   The marker array.
     */
    public function getMarkerHelpRow(): array
    {
        return $this->markerHelpRow;
    }

    /**
     * Getter of the help for unit tests.
     *
     * @codeCoverageIgnore
     *   We are not testing the unit tests.
     *
     * @return string[]
     *   The marker array.
     */
    public function getMarkerHelp(): array
    {
        return [$this->markerHelp];
    }
}
