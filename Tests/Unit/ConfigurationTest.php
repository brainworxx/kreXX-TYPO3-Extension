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

namespace Brainworxx\Includekrexx\Tests\Unit;

use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;

class ConfigurationTest extends AbstractHelper
{
    /**
     * We have several configuration files in here with redundant constrains and
     * version numbers, namely:
     *   - ext_emconf.php
     *   - changelog.rst
     *   - settings.cfg
     *
     * All of them contain the same fu**ing data!
     */
    public function testConfiguration()
    {
        $dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR .
            '..' . DIRECTORY_SEPARATOR;
        $composerJsonPath = $dir . 'composer.json';
        $extEmConfPath = $dir . 'ext_emconf.php';
        $changelogPath = $dir . 'Documentation/Changelog/Index.rst';
        $docSettingsPath = $dir . 'Documentation/Settings.cfg';
        $extLocalConfPath = $dir . 'ext_localconf.php';

        $this->assertFileExists($composerJsonPath);
        $this->assertFileExists($extEmConfPath);
        $this->assertFileExists($changelogPath);
        $this->assertFileExists($docSettingsPath);
        $this->assertFileExists($extLocalConfPath);

        // The stuff from the configuration files.
        $composer = json_decode(file_get_contents($composerJsonPath));
        $_EXTKEY = 'includekrexx';
        include $extEmConfPath;
        $docSettings = parse_ini_file($docSettingsPath);
        $changelogContent = file($changelogPath)[14];

        // Our expectations.
        $versionNumber = '5.0.5';
        $t3EmConstraint = '10.4.0-13.3.99';
        $phpEmConstraint = '7.2.0-8.3.99';
        $t3ComposerConstraint = '^10.4 || ^11 || ^12 || ^13.3';
        $phpComposerConstraint = '^7.2 || ^7.3 || ^7.4 || ^8.0 || ^8.1 || ^8.2 || ^8.3';

        // Test the EM configuration.
        $this->assertEquals($versionNumber, $EM_CONF[$_EXTKEY]['version']);
        $this->assertEquals($t3EmConstraint, $EM_CONF[$_EXTKEY]['constraints']['depends']['typo3']);
        $this->assertEquals($phpEmConstraint, $EM_CONF[$_EXTKEY]['constraints']['depends']['php']);

        // Test the composer stuff.
        if (PHP_OS_FAMILY  === 'Windows') {
            // We run this one locally, and not on a ci system.
            $this->assertEquals($t3ComposerConstraint, $composer->require->{'typo3/cms-core'});
            $this->assertEquals($phpComposerConstraint, $composer->require->php);
        }

        // Test the doc settings.
        $this->assertEquals($versionNumber, $docSettings['release']);
        $this->assertEquals($versionNumber, $docSettings['version']);

        // Test the changelog.
        $this->assertStringContainsString($versionNumber, $changelogContent);
    }
}
