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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\View\Skins;

use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\View\Skins\RenderSmokyGrey;

abstract class AbstractRenderSmokyGrey extends AbstractTest
{
    const PATH_TO_SKIN = '/some path/';
    const GET_NAME = 'getName';
    const GET_DOMID = 'getDomid';
    const GET_NORMAL = 'getNormal';
    const GET_CONNECTOR_LEFT = 'getConnectorLeft';
    const GET_CONNECTOR_RIGHT = 'getConnectorRight';
    const GET_JSON = 'getJson';
    const GET_HAS_EXTRAS = 'getHasExtra';
    const GET_DATA = 'getData';
    const GET_IS_CALLBACK = 'getIsCallback';
    const GET_TYPE = 'getType';
    const RENDER_ME = 'renderMe';
    const GET_CONNECTOR_LANGUAGE = 'getConnectorLanguage';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $fileServiceMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $modelMock;

    /**
     * @var \Brainworxx\Krexx\View\Skins\RenderSmokyGrey
     */
    protected $renderSmokyGrey;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->renderSmokyGrey = new RenderSmokyGrey(Krexx::$pool);
        $this->setValueByReflection('skinPath', static::PATH_TO_SKIN, $this->renderSmokyGrey);
        $this->mockTemplate();
    }

     /**
     * Short circuiting the existence of a specific template file.
     * We only simulate the differences in the smoky grey skin.
     *
     * @see \Brainworxx\Krexx\View\AbstractRender::getTemplateFileContent
     */
    protected function mockTemplate()
    {
        $fileSuffix = '.html';
        $this->fileServiceMock = $this->createMock(File::class);
        $this->fileServiceMock->expects($this->any())
            ->method('getFileContents')
            ->will($this->returnValueMap([
                // sourceButton.html
                [
                    static::PATH_TO_SKIN . $this->renderSmokyGrey::FILE_SOURCE_BUTTON . $fileSuffix,
                    true,
                    $this->renderSmokyGrey::MARKER_LANGUAGE
                ],
                // singleChild.html
                [
                    static::PATH_TO_SKIN . $this->renderSmokyGrey::FILE_SI_CHILD . $fileSuffix,
                    true,
                    $this->renderSmokyGrey::MARKER_SOURCE_BUTTON .
                    $this->renderSmokyGrey::MARKER_ADDITIONAL_JSON
                ],
                // nest.html
                [
                    static::PATH_TO_SKIN . $this->renderSmokyGrey::FILE_NEST . $fileSuffix,
                    true,
                    $this->renderSmokyGrey::MARKER_STYLE .
                    $this->renderSmokyGrey::MARKER_MAIN_FUNCTION .
                    $this->renderSmokyGrey::MARKER_DOM_ID
                ],
                // expandableChildNormal.html
                [
                    static::PATH_TO_SKIN . $this->renderSmokyGrey::FILE_EX_CHILD_NORMAL . $fileSuffix,
                    true,
                    $this->renderSmokyGrey::MARKER_GEN_SOURCE .
                    $this->renderSmokyGrey::MARKER_CODE_WRAPPER_LEFT .
                    $this->renderSmokyGrey::MARKER_CODE_WRAPPER_RIGHT .
                    $this->renderSmokyGrey::MARKER_IS_EXPANDED .
                    $this->renderSmokyGrey::MARKER_K_TYPE .
                    $this->renderSmokyGrey::MARKER_CONNECTOR_LEFT .
                    $this->renderSmokyGrey::MARKER_CONNECTOR_RIGHT .
                    $this->renderSmokyGrey::MARKER_NAME .
                    $this->renderSmokyGrey::MARKER_NORMAL .
                    $this->renderSmokyGrey::MARKER_TYPE .
                    $this->renderSmokyGrey::MARKER_SOURCE_BUTTON .
                    $this->renderSmokyGrey::MARKER_HELP .
                    $this->renderSmokyGrey::MARKER_NEST .
                    $this->renderSmokyGrey::MARKER_ADDITIONAL_JSON
                ],
                // connectorRight.html
                [
                    static::PATH_TO_SKIN . $this->renderSmokyGrey::FILE_CONNECTOR_RIGHT . $fileSuffix,
                    true,
                    $this->renderSmokyGrey::MARKER_CONNECTOR
                ],
                // recursion.html
                [
                    static::PATH_TO_SKIN . $this->renderSmokyGrey::FILE_RECURSION . $fileSuffix,
                    true,
                    $this->renderSmokyGrey::MARKER_ADDITIONAL_JSON
                ],
                // singleEditableChild.html
                [
                    static::PATH_TO_SKIN . $this->renderSmokyGrey::FILE_SI_EDIT_CHILD . $fileSuffix,
                    true,
                    $this->renderSmokyGrey::MARKER_ADDITIONAL_JSON
                ],
                // singleButton.html
                [
                    static::PATH_TO_SKIN . $this->renderSmokyGrey::FILE_SI_BUTTON . $fileSuffix,
                    true,
                    $this->renderSmokyGrey::MARKER_CLASS .
                    $this->renderSmokyGrey::MARKER_TEXT .
                    $this->renderSmokyGrey::MARKER_ADDITIONAL_JSON
                ],
                // header.html
                [
                    static::PATH_TO_SKIN . $this->renderSmokyGrey::FILE_HEADER . $fileSuffix,
                    true,
                    $this->renderSmokyGrey::MARKER_K_DEBUG_CLASSES .
                    $this->renderSmokyGrey::MARKER_K_CONFIG_CLASSES
                ],
                // footer.html
                [
                    static::PATH_TO_SKIN . $this->renderSmokyGrey::FILE_FOOTER . $fileSuffix,
                    true,
                    $this->renderSmokyGrey::MARKER_K_CONFIG_CLASSES
                ],
                // fatalMain.html
                [
                    static::PATH_TO_SKIN . $this->renderSmokyGrey::FILE_FATAL_MAIN . $fileSuffix,
                    true,
                    $this->renderSmokyGrey::MARKER_SEARCH .
                    $this->renderSmokyGrey::MARKER_KREXX_ID .
                    $this->renderSmokyGrey::MARKER_PLUGINS
                ],

            ]));

        Krexx::$pool->fileService = $this->fileServiceMock;
    }

    /**
     * The great Moddelmock is not a wizard from Harry Potter.
     *
     * @param $methodName
     * @param $returnValue
     */
    protected function mockModel($methodName, $returnValue)
    {
        if (empty($this->modelMock)) {
            $this->modelMock = $this->createMock(Model::class);
        }
        $this->modelMock->expects($this->once())
            ->method($methodName)
            ->will($this->returnValue($returnValue));
    }
}
