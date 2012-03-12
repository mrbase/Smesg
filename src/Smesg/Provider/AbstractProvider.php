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

use Smesg\Adapter\AdapterInterface;

/**
 * @author Ulrik Nielsen <un@mrbase.dk>
 */
abstract Class AbstractProvider
{
    /**
     * @var \Smesg\Adapter\AdapterInterface
     */
    private $adapter = null;

    /**
     * @var array
     */
    protected $messages = array();

    /**
     * @param \Smesg\Adapter\AdapterInterface $adapter An adapter.
     * @param array                           $config  An array containing the required config settings for the provider.
     */
    public function __construct(AdapterInterface $adapter, array $config)
    {
        $this->adapter = $adapter;
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Returns the adapter.
     *
     * @return \Smesg\Adapter\AdapterInterface
     */
    protected function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Add a message to the outgoing stack.
     *
     * @param  int    $to      A valid msisdn mobile number
     * @param  string $message The message to send
     * @param  mixed  $from    Can either be a mobile number or a textual name.
     * @params array  $options Array of alternative options to set on a message.
     * @return mixed           If $autoSend is set to true, the method returns the returnvalue of send(), otherwise void
     */
    public function addMessage($to, $message, array $options = array(), $autoSend = false)
    {
        $defaults = array(
            'from' => null,
            'price' => null,
            'smsc' => null,
        );

        $options = array_merge($defaults, $options);

        $msg = new \stdClass();
        $msg->to = $to;
        $msg->message = $message;
        $msg->options = $options;
        $this->messages[] = $msg;

        if ($autoSend) {
            return $this->send();
        }

        return $this;
    }
}
