<?php

/**
 * This file is part of the Smesg package.
 *
 * (c) Ulrik Nielsen <un@mrbase.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Smesg\Adapter;

use Smesg\TestCase;
use Smesg\Common\Response;

class TestResponse extends TestCase
{
    protected $headers = array(
        'HTTP/1.0 200 OK',
        'Vary: Accept-Encoding',
        'Content-Type: text/html',
        'Accept-Ranges: bytes',
        'ETag: "125703609"',
        'Last-Modified: Thu, 21 Aug 2008 13:10:08 GMT',
        'Content-Length: 770',
        'Connection: close',
        'Date: Sat, 03 Mar 2012 21:03:16 GMT',
        'Server: lighttpd/1.4.28',
    );

    public function testGetStatus()
    {
        $response = new Response($this->headers, '', '');
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals(200, $response->getHeader('status'));
    }

    public function testGetEmptyBody()
    {
        $response = new Response($this->headers, '', '');
        $this->assertEquals('', $response->getBody());
    }

    public function testGetEtagHeader()
    {
        $response = new Response($this->headers, '', '');
        $this->assertEquals('"125703609"', $response->getHeader('etag'));
        $this->assertEquals('"125703609"', $response->getHeader('ETAG'));
        $this->assertEquals('"125703609"', $response->getHeader('EtaG'));
    }
}
