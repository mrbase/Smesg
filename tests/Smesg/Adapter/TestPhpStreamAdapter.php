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
use Smesg\Adapter\PhpStreamAdapter;

class TestPhpStreamAdapter extends TestCase
{
    public function testGetNullResponse()
    {
        $adapter = new PhpStreamAdapter();
        $this->assertNull($adapter->getResponseBody());
    }

    public function testGetEmptyResponseHeaders()
    {
        $adapter = new PhpStreamAdapter();
        $this->assertEquals(array(), $adapter->getResponseHeaders());
    }
}
