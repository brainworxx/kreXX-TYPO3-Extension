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

$EM_CONF[$_EXTKEY] = [
    'title' => 'kreXX Debugger',
    'description' => 'Fluid and PHP debugger with backend access to logfiles, code generation to reach the displayed values and much more. We added some special stuff for Aimeos.',
    'category' => 'misc',
    'version' => '6.0.0',
    'state' => 'stable',
    'uploadfolder' => false,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author_email' => 'info@brainworxx.de',
    'author' => 'BRAINWORXX GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-13.4.99',
            'php' => '7.4.0-8.4.99',
        ],
        'conflicts' => [],
        'suggests' => [
            'adminpanel' => '10.4.0-13.4.99',
            'aimeos' => '20.10.5-23.99.99',
            'vhs' => '6.0.0-7.99.99'
        ],
    ],
];
