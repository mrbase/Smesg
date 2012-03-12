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
use Smesg\Provider\InMobileProvider;

class TestInMobileProvider extends TestCase
{
    protected $config = array(
        'user' => '1',
        'password' => '1',
        'serviceid' => '1',
        'from' => '',
        'price' => 0,
        'overcharge' => 0,
    );

    public function testValidConfig()
    {
        $adapter = new PhpStreamAdapter();
        $provider = new InMobileProvider($adapter, $this->config);

        $this->assertInstanceOf('Smesg\Provider\InMobileProvider', $provider);
        $this->assertInstanceOf('Smesg\Provider\AbstractProvider', $provider);
        $this->assertInstanceOf('Smesg\Provider\ProviderInterface', $provider);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidConfig()
    {
        $adapter = new PhpStreamAdapter();
        $provider = new InMobileProvider($adapter, array());
    }

    public function testOneMessage()
    {
        $adapter = new PhpStreamAdapter();
        $provider = new InMobileProvider($adapter, $this->config);

        $provider->addMessage(12345678, 'test message');
        $response = $provider->send(true);

        $this->assertEquals($response->getBody(), 'user=1&password=1&serviceid=1&sender=&message=test+message&msisdn=12345678&price=0&overcharge=0');
        $this->assertEquals($response->getHeaders(), array('content-type' => 'application/x-www-form-urlencoded; charset=utf-8'));
    }

    public function testMultipleMessages()
    {
        $adapter = new PhpStreamAdapter();
        $provider = new InMobileProvider($adapter, $this->config);

        $provider->addMessage(12345678, 'test message');
        $provider->addMessage(12345678, 'test message');
        $response = $provider->send(true);

        $xml = preg_replace('/([0-9]{2}-[0-9]{2}-[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2})/', '00-00-0000 00:00:00', $response->getBody());

        $this->assertEquals($response->getHeaders(), array('content-type' => 'text/xml; charset=utf-8'));
        $this->assertXmlStringEqualsXmlString($xml, '<?xml version="1.0" encoding="utf-8"?>
<push><user>1</user><password>1</password><serviceid>1</serviceid><smsbatch><sendtime>00-00-0000 00:00:00</sendtime><price>0</price><message><![CDATA[test message]]></message><recipients><msisdn>12345678</msisdn></recipients></smsbatch><smsbatch><sendtime>00-00-0000 00:00:00</sendtime><price>0</price><message><![CDATA[test message]]></message><recipients><msisdn>12345678</msisdn></recipients></smsbatch></push>');

    }
}
