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

use Smesg\Common\IllegalEndpoindException;
use Smesg\Common\Response;

/**
 * @author Ulrik Nielsen <un@mrbase.dk>
 */
class PhpStreamAdapter implements AdapterInterface
{
    /**
     * @var array
     */
    protected $headers = array();

    /**
     * @var array
     */
    protected $request = array();

    /**
     * @var string
     */
    protected $response;

    /**
     * @var string
     */
    protected $endpoint;

    /**
     * @var string
     */
    protected $body;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'php_stream';
    }

    /**
     * {@inheritDoc}
     */
    public function get($url = null)
    {
        if ($url) {
            $this->setEndpoint($url);
        }

        if (isset($this->parameters) && is_array($this->parameters)) {
            $this->endpoint = $this->endpoint . '?' . http_build_query($this->parameters);
        }

        $content_type = 'application/x-www-form-urlencoded; charset=utf-8';
        $this->request = array(
            'http' => array(
                'header' => 'Content-type: ' . $content_type,
                'method' => 'GET',
                'max_redirects' => 0,
                'timeout' => 5,
            )
        );

        return $this->sendRequest();
    }

    /**
     * {@inheritDoc}
     */
    public function post()
    {
        return $this->build()->sendRequest();
    }

    /**
     * {@inheritDoc}
     */
    public function dryRun()
    {
        $this->build();
        return new Response($this->request['http']['header'], $this->request['http']['content'], null);
    }

    /**
     * {@inheritDoc}
     */
    public function getResponseBody()
    {
        if (!$this->response instanceof Response) {
            return null;
        }
        return $this->response->getBody();
    }

    /**
     * {@inheritDoc}
     */
    public function getResponseHeaders()
    {
        if (!$this->response instanceof Response) {
            return array();
        }
        return $this->response->getHeaders();
    }

    /**
     * {@inheritDoc}
     */
    public function setEndpoint($endpoint)
    {
        if (!filter_var($endpoint, FILTER_VALIDATE_URL)) {
            throw new IllegalEndpoindException("{$endpoint} is not a valid URL", 1);
        }
        $this->endpoint = $endpoint;
    }

    /**
     * {@inheritDoc}
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * {@inheritDoc}
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }


    /**
     * Ready the request for transport
     *
     * @return PhpStreamAdapter
     */
    protected function build()
    {
        $content = '';
        if (isset($this->parameters) && is_array($this->parameters)) {
            $content = http_build_query($this->parameters);
        }
        if (isset($this->body)) {
            $content = $this->body;
        }

        $content_type = 'application/x-www-form-urlencoded; charset=utf-8';
        if (substr($content, 0, 6) == '<?xml ') {
            $content_type = 'text/xml; charset=utf-8';
        }

        $this->request = array(
            'http' => array(
                'header' => 'Content-type: ' . $content_type,
                'method' => 'POST',
                'max_redirects' => 0,
                'timeout' => 5,
                'content' => $content,
            )
        );

        return $this;
    }


    /**
     * Sends the actual request and returns the response.
     *
     * @param array $request The stream_wraper options from wich to build the request
     * @return mixed
     */
    protected function sendRequest()
    {
        $context = stream_context_create($this->request);
        $response = trim(file_get_contents($this->endpoint, FALSE, $context));
        $this->response = new Response($http_response_header, $response, $this->request);

        return $this->response;
    }
}
