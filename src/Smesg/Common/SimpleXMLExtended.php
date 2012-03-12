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

/**
 * Extend SimpleXMLElement so we can have CDATA sections
 *
 * @link http://coffeerings.posterous.com/php-simplexml-and-cdata
 */
class SimpleXMLExtended extends \SimpleXMLElement {

    /**
     * create CDATA section
     *
     * @param string $text
     */
    public function addCData($text) {
        $node = dom_import_simplexml($this);
        $no = $node->ownerDocument;
        $node->appendChild($no->createCDATASection($text));
    }
}
