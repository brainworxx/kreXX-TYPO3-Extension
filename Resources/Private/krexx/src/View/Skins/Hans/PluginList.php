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

use Brainworxx\Krexx\Service\Plugin\SettingsGetter;

/**
 * Renders the plugin list.
 */
trait PluginList
{
    /**
     * @var string[]
     */
    private array $markerSinglePlugin = [
        '{activeclass}',
        '{activetext}',
        '{plugintext}',
    ];

    /**
     * Render a list of all registered plugins.
     *
     * @return string
     *   The generated markup from the template files.
     */
    protected function renderPluginList(): string
    {
        $result = '';
        $messages = $this->pool->messages;
        foreach (SettingsGetter::getPlugins() as $plugin) {
            if ($plugin[static::IS_ACTIVE]) {
                $activeClass = 'kisactive';
                $activeText = $messages->getHelp('pluginActive');
            } else {
                $activeClass = 'kisinactive';
                $activeText = $messages->getHelp('pluginInactive');
            }

            $configClass = $plugin[static::CONFIG_CLASS];
            $result .= str_replace(
                $this->markerSinglePlugin,
                [
                    $activeClass,
                    $activeText,
                    $configClass->getName() . ' ' . $configClass->getVersion()
                ],
                $this->fileCache[static::FILE_SI_PLUGIN]
            );
        }
        return $result;
    }

    /**
     * Getter of the plugin list for unit tests.
     *
     * @codeCoverageIgnore
     *   We are not testing the unit tests.
     *
     * @return string[]
     *   The marker array.
     */
    public function getMarkerSinglePlugin(): array
    {
        return $this->markerSinglePlugin;
    }
}
