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

/**
 * @author Ulrik Nielsen <un@mrbase.dk>
 */
interface AdapterInterface
{
    /**
     * Send get request.
     *
     * @param string $url If set this will override the endpoint.
     * @return mixed
     */
    function get($url = null);

    /**
     * Send post request.
     *
     * @return mixed
     */
    function post();

    /**
     * Returns a Response object representation of the request you wanted to send.
     * Here for testing purposes.
     *
     * @return Common\Response
     */
    function dryRun();

    /**
     * Returns the response body of a request.
     *
     * @return mixed
     */
    function getResponseBody();

    /**
     * Returns the response headers of a request.
     *
     * @return array
     */
    function getResponseHeaders();

    /**
     * Returns adapters's name
     *
     * @return string
     */
    function getName();

    /**
     * Set the endpoint for the request.
     *
     * @param string $endpoint The URL endpoint for the request.
     * @throws IllegalEndpoingException
     */
    function setEndpoint($endpoint);

    /**
     * Set the body part of a request.
     *
     * @param string $body
     */
    function setBody($body);

    /**
     * Set parameters of a request.
     *
     * @param array $parameters
     */
    function setParameters(array $parameters);
}

