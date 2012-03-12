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
use Smesg\Adapter\PhpStreamAdapter;
use Smesg\Provider\UnwireProvider;

class TestUnwireProvider extends TestCase
{
    protected $config = array(
        'user' => '1',
        'password' => '1',
        'appnr' => '1212',
        'mediacode' => 'test',
        'price' => '0.00DKK',
        'get_smsc' => false
    );

    public function testValidConfig()
    {
        $adapter = new PhpStreamAdapter();
        $provider = new UnwireProvider($adapter, $this->config);

        $this->assertInstanceOf('Smesg\Provider\UnwireProvider', $provider);
        $this->assertInstanceOf('Smesg\Provider\AbstractProvider', $provider);
        $this->assertInstanceOf('Smesg\Provider\ProviderInterface', $provider);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidConfig()
    {
        $adapter = new PhpStreamAdapter();
        $provider = new UnwireProvider($adapter, array());
    }

    public function testOneMessage()
    {
        $adapter = new PhpStreamAdapter();
        $provider = new UnwireProvider($adapter, $this->config);

        $provider->addMessage(12345678, 'test message');
        $response = $provider->send(true);

        $body = substr($response->getBody(), 0, -14);
        $this->assertEquals(array('content-type' => 'application/x-www-form-urlencoded; charset=utf-8'), $response->getHeaders());
        $this->assertEquals('user=1&password=1&price=0.00DKK&appnr=1212&mediacode=test&to=12345678&text=test+message&sessionid=12345678%3A', $body);
    }

    public function testBuildBulkMessageNoDefaults()
    {
        $adapter = new PhpStreamAdapter();
        $provider = new UnwireProvider($adapter, $this->config);

        $provider->addMessage(12345678, 'test 1.2.3');
        $provider->addMessage(87654321, 'test 1.2.3');
        $response = $provider->send(true);
        $xml = preg_replace('/sessionid="[0-9a-z]+"/i', 'sessionid="test"', $response->getBody());
        $this->assertXmlStringEqualsXmlString('<?xml version="1.0" encoding="utf-8"?>
<messages xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.unwire.com/UM/core/v1.3" xsi:schemaLocation="http://www.unwire.com/UM/core/v1.3 https://gw.unwire.com/service/xmlinterface/core-1.3.xsd" count="2" service="mt" sessionid="test"><defaultvalues type="text"><price currency="DKK">0.00</price><content><text/></content></defaultvalues><sms id="1" type="text"><msisdn>12345678</msisdn><content><text>test 1.2.3</text></content><shortcode>1212</shortcode><mediacode>test</mediacode></sms><sms id="2" type="text"><msisdn>87654321</msisdn><content><text>test 1.2.3</text></content><shortcode>1212</shortcode><mediacode>test</mediacode></sms></messages>', $xml);
    }

    public function testBuildBulkMessageDefaults()
    {
        $adapter = new PhpStreamAdapter();
        $provider = new UnwireProvider($adapter, $this->config);

        $provider->setDefaultValues(array(
            'content' => '4.3.2.1 test',
            'shortcode' => 1212,
            'mediacode' => 'test',
            'price' => array('vat' => 0.25)
        ));

        $provider->addMessage(12345678, '');
        $provider->addMessage(87654321, '');
        $response = $provider->send(true);

        $xml = preg_replace('/sessionid="[0-9a-z]+"/i', 'sessionid="test"', $response->getBody());
        $this->assertXmlStringEqualsXmlString('<?xml version="1.0" encoding="utf-8"?>
<messages xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.unwire.com/UM/core/v1.3" xsi:schemaLocation="http://www.unwire.com/UM/core/v1.3 https://gw.unwire.com/service/xmlinterface/core-1.3.xsd" count="2" service="mt" sessionid="test"><defaultvalues type="text"><shortcode>1212</shortcode><mediacode>test</mediacode><price currency="DKK" vat="0.25">0.00</price><content><text>4.3.2.1 test</text></content></defaultvalues><sms id="1" type="text"><msisdn>12345678</msisdn></sms><sms id="2" type="text"><msisdn>87654321</msisdn></sms></messages>', $xml);
    }
}
