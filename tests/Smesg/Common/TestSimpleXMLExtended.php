<?php

/**
 * This file is part of the Smesg package.
 *
 * (c) Ulrik Nielsen <un@mrbase.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Smesg\Common;

use Smesg\TestCase;
use Smesg\Common\SimpleXMLExtended;

class TestSimpleXMLExtended extends TestCase
{
    public function testInstanceOfSimpleXMLElement()
    {
        $xml = new SimpleXMLExtended('<?xml version="1.0" encoding="utf-8" ?><empty></empty>');
        $this->assertInstanceOf('SimpleXMLElement', $xml);
    }

    public function testSimpleXMLElements()
    {
        $xml = new SimpleXMLExtended('<?xml version="1.0" encoding="utf-8" ?><empty></empty>');
        $this->assertXmlStringEqualsXmlString('<?xml version="1.0" encoding="utf-8"?>'."\n".'<empty></empty>', $xml->saveXML());

        $cdata = $xml->addChild('cdata');
        $cdata->addCData('some ··· cdata string & <s>sdsd</s>');

        $this->assertXmlStringEqualsXmlString('<?xml version="1.0" encoding="utf-8"?>'."\n".'<empty><cdata><![CDATA[some ··· cdata string & <s>sdsd</s>]]></cdata></empty>', $xml->saveXML());
    }
}
