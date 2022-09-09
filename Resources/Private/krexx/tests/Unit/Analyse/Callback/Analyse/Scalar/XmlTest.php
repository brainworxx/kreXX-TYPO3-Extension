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
 *   kreXX Copyright (C) 2014-2022 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Callback\Analyse\Scalar;

use Brainworxx\Krexx\Analyse\Scalar\String\Xml;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use Krexx;

class XmlTest extends AbstractTest
{
    const SCALAR_NAMESPACE = '\\Brainworxx\\Krexx\\Analyse\\Scalar\\String\\';
    const TEXT_XML = 'text/xml;';
    const ATTRIBUTES = 'attributes';
    const CHILDREN = 'children';

    /**
     * Test the disabling of the XML analysis.
     *
     * @covers \Brainworxx\Krexx\Analyse\Scalar\String\Xml::isActive
     */
    public function testIsActiveNot()
    {
        $functionExistsMock = $this->getFunctionMock(
            static::SCALAR_NAMESPACE,
            'function_exists'
        );
        // The first false should prevent thge other tests from getting called.
        $functionExistsMock->expects($this->once())
            ->will($this->returnValue(false));

        $classExistsMock = $this->getFunctionMock(
            static::SCALAR_NAMESPACE,
            'class_exists'
        );
        $classExistsMock->expects($this->never());

        $this->assertFalse(Xml::isActive());
    }

    /**
     * Test the enabling of the XML analysis.
     *
     * @covers \Brainworxx\Krexx\Analyse\Scalar\String\Xml::isActive
     */
    public function testIsActive()
    {
        $functionExistsMock = $this->getFunctionMock(
            static::SCALAR_NAMESPACE,
            'function_exists'
        );
        // The first false should prevent thge other tests from getting called.
        $functionExistsMock->expects($this->exactly(2))
            ->will($this->returnValue(true));

        $classExistsMock = $this->getFunctionMock(
            static::SCALAR_NAMESPACE,
            'class_exists'
        );
        $classExistsMock->expects($this->exactly(2))
            ->will($this->returnValue(true));

        $this->assertTrue(Xml::isActive());
    }

    /**
     * Test the handling of strings.
     *
     * @covers \Brainworxx\Krexx\Analyse\Scalar\String\Xml::canHandle
     */
    public function testcanHandle()
    {
        $string = 'lacking the xml finfo info';
        $model = new Model(Krexx::$pool);
        $xml = new Xml(Krexx::$pool);
        $this->assertFalse($xml->canHandle($string, $model), $string);

        $string = 'Now with the XML finfo info,but still not XML.';
        $model = new Model(Krexx::$pool);
        $model->addToJson('Mimetype string', static::TEXT_XML);
        $xml = new Xml(Krexx::$pool);
        $this->assertTrue($xml->canHandle($string, $model), $string);

        $string = '<?xml version="1.0" encoding="utf-8"?><node><yxcv qwer="asdf" /></node>';
        $model = new Model(Krexx::$pool);
        $model->addToJson('Mimetype string', static::TEXT_XML);
        $xml = new Xml(Krexx::$pool);
        $this->assertTrue($xml->canHandle($string, $model), $string);
        $this->assertEquals($string, $this->retrieveValueByReflection('handledValue', $xml));
    }

    /**
     * Test the actual handling of a XML string.
     *
     * @covers \Brainworxx\Krexx\Analyse\Scalar\String\Xml::handle
     * @covers \Brainworxx\Krexx\Analyse\Scalar\String\Xml::parseXml
     * @covers \Brainworxx\Krexx\Analyse\Scalar\String\Xml::tagOpen
     * @covers \Brainworxx\Krexx\Analyse\Scalar\String\Xml::tagClosed
     * @covers \Brainworxx\Krexx\Analyse\Scalar\String\Xml::tagData
     */
    public function testHandle()
    {
        Krexx::$pool->rewrite = [
            ThroughMeta::class => CallbackCounter::class
        ];

        $string = '<?xml version="1.0" encoding="utf-8"?><root><node>rogue text<yxcv qwer="asdf"><![CDATA[content]]></yxcv><yxcv qwer="yxcv" /></node></root>';
        $model = new Model(Krexx::$pool);
        $model->addToJson('Mimetype string', static::TEXT_XML)->setHasExtra(true);
        $xml = new Xml(Krexx::$pool);
        $xml->canHandle($string, $model);
        $xml->callMe();
        $prettyPrint = '&lt;?xml version=&quot;1.0&quot; encoding=&quot;utf-8&quot;?&gt;
&lt;root&gt;
&nbsp;&nbsp;&lt;node&gt;rogue text&lt;yxcv qwer=&quot;asdf&quot;&gt;&lt;![CDATA[content]]&gt;&lt;/yxcv&gt;&lt;yxcv qwer=&quot;yxcv&quot;/&gt;&lt;/node&gt;
&lt;/root&gt;
';

        $this->assertEquals(1, CallbackCounter::$counter);
        $result = CallbackCounter::$staticParameters[0][XML::PARAM_DATA];
        $this->assertEquals($prettyPrint, $result['Pretty print']);
    }
}
