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
 * @author Ulrik Nielsen <un@mrbase.dk>
 */
class Response
{
    /**
     * var @array
     */
    protected $headers;

    /**
     * var @string
     */
    protected $body;

    /**
     * var @array
     */
    protected $request;

    /**
     * Response constructor
     *
     * @param mixed  $headers The response headers as string or array
     * @param string $body    The response body
     * @param mixed  $request The initiation request
     */
    public function __construct($headers, $body, $request)
    {
        $this->body = $body;
        $this->request = $request;

        if (!is_array($headers) || array_key_exists(0, $headers)) {
            if (is_array($headers)) {
                $lines = $headers;
            } else {
                $lines = explode("\n", str_replace("\r", '', $headers));
            }

            $headers = array();
            foreach ($lines as $line) {
                if (preg_match('~http\/1.(0|1) [0-9]{3} [.]?~i', $line)) {
                    list($key, $code, $text) = explode(' ', $line, 3);
                    $headers['status'] = (int) $code;
                    $headers['status_text'] = $text;
                    continue;
                }

                list ($key, $value) = explode(':', $line, 2);
                $headers[strtolower($key)] = trim($value);
            }
        }

        $this->headers = $headers;
    }

    /**
     * Get header value
     *
     * @param  string $key     name of the header
     * @param  string $default default value if empty
     * @return string
     */
    public function getHeader($key, $default = null)
    {
        $key = strtolower($key);
        return isset($this->headers[$key]) ? $this->headers[$key] : $default;
    }

    /**
     * Returns all headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Returns body of request
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Returns the request object
     *
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get the status code of the request
     *
     * @return int
     */
    public function getStatus()
    {
        $status = $this->getHeader('status');
        return $status ?: null;
    }
}
