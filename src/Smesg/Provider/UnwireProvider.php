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

use Smesg\Common\SimpleXMLExtended;
use Smesg\Common\MaxMessagesReachedException;
use Smesg\Adapter\AdapterInterface;
use Smesg\Provider\ProviderInterface;

use \InvalidArgumentException;
use \RuntimeException;

/**
 * @author Ulrik Nielsen <un@mrbase.dk>
 */
class UnwireProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * @var string
     */
    CONST BATCH_ENDPOINT = 'https://gw.unwire.com/service/smspushxml/1.3';

    /**
     * Max number of messages pr batch call.
     * @var int
     */
    CONST BATCH_MAX_QUANTITY = 1024;

    /**
     * @var string
     */
    //
    CONST SINGLE_ENDPOINT = 'https://gw.unwire.com/service/smspush';

    /**
     * @var string
     */
    CONST SMSC_ENDPOINT = 'https://mobile.unwire.dk/java/servlet/smsclookup';

    /**
     * @var boolean
     */
    protected $error = false;

    /**
     * @var array
     */
    protected $config = array(
        'user' => '',
        'password' => '',
        'appnr' => '',
        'mediacode' => '',
        'price' => '',
        'get_smsc' => false
    );

    /**
     * @var array
     */
    protected $defaults = array(
        'operator' => null,
        'shortcode' => null,
        'mediacode' => null,
        'price' => array(
            'amount' => null,
            'currency' => null,
            'vat' => null,
        ),
        'content' => null,
    );

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     */
    public function __construct(AdapterInterface $adapter, array $config)
    {
        parent::__construct($adapter, $config);

        $missing = array();
        $required = array('user', 'password', 'appnr', 'mediacode');

        foreach($required as $key) {
            if (empty($this->config[$key])) {
                $missing[] = $key;
            }
        }

        if (count($missing)) {
            throw new \InvalidArgumentException('The following config arguments cannot be empty: ' . implode(', ', $missing) . '.');
        }
    }


    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'unwire';
    }


    /**
     * Setting defaults
     *
     * @param array $defaults
     */
    public function setDefaultValues(array $defaults = array())
    {
        $this->defaults = array_merge($this->defaults, $defaults);
    }


    /**
     * Return default values.
     *
     * @return array
     */
    public function getDefaultValues()
    {
        preg_match('/([0-9.]+)([A-Z]{3})/', $this->config['price'], $matches);

        $this->defaults['price'] = array(
            'currency' => (!empty($this->defaults['price']['currency']) ? $this->defaults['price']['currency'] : $matches[2]),
            'amount' => (!empty($this->defaults['price']['amount']) ? $this->defaults['price']['amount'] : $matches[1]),
            'vat' => (!empty($this->defaults['price']['vat']) ? $this->defaults['price']['vat'] : null),
        );

        return $this->defaults;
    }


    /**
     * {@inheritDoc}
     *
     * @throws MaxMessagesReachedException
     * @throws InvalidArgumentException
     */
    public function addMessage($to, $message, array $options = array(), $autoSend = false)
    {
        if (count($this->messages) == self::BATCH_MAX_QUANTITY) {
            throw new MaxMessagesReachedException('Unwire batch mode does not allow more than '.self::BATCH_MAX_QUANTITY.' pr. batch.');
        }

        if ($this->config['get_smsc']) {
            $options['smsc'] = $this->getSmsc($to);
        }

        if (isset($options['price']) && !preg_match('/[0-9]+\.[0-9]{2}[A-Z]{3}/', $options['price'])) {
            throw new InvalidArgumentException('The price must be in the format "00.00XXX" where XXX is the correct currency code for the destination country.');
        }

        return parent::addMessage($to, $message, $options, $autoSend);
    }


    /**
     * {@inheritDoc}
     *
     * @see UnwireProvider::sendOne()
     */
    public function send($dry_run = false)
    {
        switch (count($this->messages)) {
            case 0:
                throw new RuntimeException("No messages to send.");
            case 1:
                return $this->sendOne($dry_run);
        }

        $xml = new SimpleXMLExtended('<?xml version="1.0" encoding="utf-8" ?><messages></messages>');
        $xml->addAttribute('xmlns', 'http://www.unwire.com/UM/core/v1.3');
        $xml->addAttribute('xsi:schemaLocation', 'http://www.unwire.com/UM/core/v1.3 https://gw.unwire.com/service/xmlinterface/core-1.3.xsd', 'http://www.w3.org/2001/XMLSchema-instance');
        $xml->addAttribute('count', count($this->messages));
        $xml->addAttribute('service', 'mt');
        $xml->addAttribute('sessionid', (session_id() ?: time()));

        $default = $xml->addChild('defaultvalues');
        $default->addAttribute('type', 'text');

        // set default values
        foreach ($this->getDefaultValues() as $key => $value) {
            if ($key == 'content') {
                $content = $default->addChild($key);
                $content->addChild('text', $value);
            } else if ($key == 'price') {
                $price = $default->addChild('price', $value['amount']);
                $price->addAttribute('currency', $value['currency']);
                if (!is_null($value['vat'])) {
                    $price->addAttribute('vat', $value['vat']);
                }
            } else {
                if ($value) {
                    $default->addChild($key, $value);
                }
            }
        }

        $i=1;

        // add messages
        foreach ($this->messages as $message) {
            $msg = $xml->addChild('sms');
            $msg->addAttribute('id', $i);
            $msg->addAttribute('type', 'text');
            $msg->addChild('msisdn', $message->to);

            if (empty($this->defaults['content'])) {
                $content = $msg->addChild('content');
                $content->addChild('text', $message->message);
            }

            if (empty($this->defaults['operator']) && isset($message->options['smsc'])) {
                $msg->addChild('operator', $message->options['smsc']);
            }

            if (isset($message->options['shortcode'])) {
                $msg->addChild('mediacode', $message->options['shortcode']);
            } else if (empty($this->defaults['shortcode'])) {
                $msg->addChild('shortcode', $this->config['appnr']);
            }

            if (isset($message->options['mediacode'])) {
                $msg->addChild('mediacode', $message->options['mediacode']);
            } else if (empty($this->defaults['mediacode'])) {
                $msg->addChild('mediacode', $this->config['mediacode']);
            }

            if (isset($message->options['price'])) {
                preg_match('/([0-9.]+)([A-Z]{3})/', $message->options['price'], $matches);

                $price = $msg->addChild('price', $matches[1]);
                $price->addAttribute('currency', $matches[2]);
                if (!is_null($this->defaults['price']['vat'])) {
                    $price->addAttribute('vat', $this->defaults['price']['vat']);
                }
            }

            $i++;
        }

        $endpoint = str_replace('://', '://'.$this->config['user'].':'.$this->config['password'].'@', self::BATCH_ENDPOINT);

        $adapter = $this->getAdapter();
        $adapter->setEndpoint($endpoint);
        $adapter->setBody($xml->asXML());

        if ($dry_run) {
            $response = $adapter->dryRun();
        } else {
            $response = $adapter->post();
        }

        // empty query
        $this->messages = array();

        return $response;
    }

    /**
     * We use smspush for sending single messages.
     *
     * @param  boolean         $dry_run
     * @return Common\Response $response;
     */
    protected function sendOne($dry_run = false)
    {
        $message = $this->messages[0];
        $adapter = $this->getAdapter();

        $adapter->setEndpoint(self::SINGLE_ENDPOINT);
        $adapter->setParameters(array(
            'user'      => $this->config['user'],
            'password'  => $this->config['password'],
            'smsc'      => $message->options['smsc'],
            'price'     => ($message->options['price'] ?: $this->config['price']),
            'appnr'     => $this->config['appnr'],
            'mediacode' => $this->config['mediacode'],
            'to'        => $message->to,
            'text'      => $message->message,
            'sessionid' => $message->to . ':' . date('Ymdhis'),
        ));

        if ($dry_run) {
            return $adapter->dryRun();
        }

        return $adapter->post();
    }

    /**
     * Get smsc for a given mobile number.
     *
     * TODO: implement some sort of caching here.
     *
     * @param  int    $number the mobilenumber with countrycode prefix, not zero-padded tho
     * @return string
     */
    protected function getSmsc($number)
    {
        $adapter = $this->getAdapter();
        $adapter->setEndpoint(self::SMSC_ENDPOINT);
        $adapter->setParameters(array(
            'user'     => $this->config['user'],
            'password' => $this->config['password'],
            'msisdn'   => $number
        ));
        $response = $adapter->get();

        return $response->getBody();
    }
}
