<?php

/**
 * This file is part of the Smesg package.
 *
 * (c) Ulrik Nielsen <un@mrbase.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Smesg\Provider;

/**
 * @author Ulrik Nielsen <un@mrbase.dk>
 */
interface ProviderInterface
{
    /**
     * Sends the messages via the provided adapter.
     *
     * Note, this implementation must handle any limitations in the service it implements.
     *
     * @throws RuntimeException
     * @param boolean $dry_run defaults to false, if set to true, the request is returned, rather than send.
     * @return boolean
     */
    function send($dry_run = false);

    /**
     * Returns provider's name
     *
     * @return string
     */
    function getName();
}

