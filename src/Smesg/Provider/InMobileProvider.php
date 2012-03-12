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

use Smesg\Common\Response;
use Smesg\Common\SimpleXMLExtended;
use Smesg\Adapter\AdapterInterface;
use Smesg\Provider\ProviderInterface;

/**
 * @author Ulrik Nielsen <un@mrbase.dk>
 */
class InMobileProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * @var string
     */
    CONST BATCH_ENDPOINT = 'https://login.inmobile.dk/publicsms/sendxml';

    /**
     * @var string
     */
    CONST SINGLE_ENDPOINT = 'https://login.inmobile.dk/publicsms/send';

    /**
     * @var boolean
     */
    protected $error = false;

    /**
     * @var array
     *
     * TODO: translate error codes into english
     */
    protected $error_codes = array(
          2 => 'The message is delivered to the mobile number and the valuation is completed (if this parameter is specified)',
          1 => 'Message sent to the mobile number - awaiting delivery status',
         -1 => 'Premium Rate could not be carried out and the message is not delivered',
         -2 => 'Mobile number has been blacklisted by the operator',
         -3 => 'Mobile number was not recognized by the operator. Premium Rate has not been completed (if this parameter is specified)',
         -4 => 'Unknown error by the operator (contact support@inmobile.dk) - the message was not delivered to the mobile number',
         -5 => 'Message Body validity period has expired and the message has not been delivered (usually happens after 48 hours)',
         -6 => 'The message was removed from the gateway and has not been delivered',
         -7 => 'Communication error between Inmobile and the SMSC - message has not been delivered to the mobile number',
         -8 => 'Error in username or password',
         -9 => 'Errors in the parameters',
        -10 => 'Errors in the service ID',
        -11 => 'No indication of the sender ID',
        -12 => 'No input errors in the text box',
        -13 => 'Missing indication of the MSISDN (mobile number)',
        -14 => 'No indication of country codes',
        -15 => 'No access to that country (contact salg@inmobile.dk)',
        -16 => 'No coverage on the account (book more balance through the administration or on +45 88336699)',
        -17 => 'The length of the message is more than the allowed 589 characters (4 SMS messages)',
        -18 => 'The length of the message is more than the allowed 160 characters (1 SMS message)',
    );


    /**
     * @var array
     */
    protected $config = array(
        'user' => '',
        'password' => '',
        'serviceid' => '',
        'from' => '',
        'price' => 0,
        'overcharge' => 0,
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
        $required = array('user', 'password', 'serviceid');

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
        return 'inmobile';
    }


    /**
     * TODO: implement defaults
     */
    public function setMessageDefaults(){}

    /**
     * {@inheritDoc}
     */
    public function send($dry_run = false)
    {
        switch (count($this->messages)) {
            case 0:
                throw new \RuntimeException("No messages to send.");
            case 1:
                return $this->sendOne($dry_run);
        }

        // create xml document
        $xml = new SimpleXMLExtended('<?xml version="1.0" encoding="utf-8" ?><push></push>');
        $xml->addChild('user', $this->config['user']);
        $xml->addChild('password', $this->config['password']);
        $xml->addChild('serviceid', $this->config['serviceid']);

        $ts = date('d-m-Y H:i:00');

        // attach messages
        foreach ($this->messages as $message) {
            $batch = $xml->addChild('smsbatch');
            $batch->addChild('sendtime', $ts);
            if ($message->options['from']) {
                $batch->addChild('sender', $message->options['from']);
            } else {
                $batch->addChild('sender', $this->config['from']);
            }
            $batch->addChild('price', $this->config['price']);
            $cdata = $batch->addChild('message');
            $cdata->addCData($message->message);
            $recipient = $batch->addChild('recipients');
            $recipient->addChild('msisdn', $message->to);
        }

        $adapter = $this->getAdapter();
        $adapter->setEndpoint(self::BATCH_ENDPOINT);
        $adapter->setBody($xml->asXML());

        if ($dry_run) {
            return $adapter->dryRun();
        } else {
            $response = $adapter->post();
        }

        /**
         * inmobile returns 2 kinds of response
         *
         * 1: integer 2 to -18 error results
         * 2: xml containing the result of your success request
         *
         * we have asked inmobile to change this in new versions to always send back xml
         */
        if (strlen($response->getBody()) <= 3) {
            $xml = new SimpleXMLExtended('<?xml version="1.0" encoding="utf-8" ?><response></response>');
            $error = $xml->addChild('error');
            $error->addChild('code', $response->getBody());
            $error->addChild('message', $this->error_codes[$response->getBody()]);

            $response = new Response($response->getHeaders(), $xml->asXML(), $response->getRequest());
        }

        return $response;
    }

    /**
     * We use smspush for sending single messages.
     *
     * @return string $response;
     */
    protected function sendOne($dry_run)
    {
        $message = $this->messages[0];
        $adapter = $this->getAdapter();

        $adapter->setEndpoint(self::SINGLE_ENDPOINT);
        $adapter->setParameters(array(
            'user' => $this->config['user'],
            'password' => $this->config['password'],
            'serviceid' => $this->config['serviceid'],
            'sender' => ($message->options['from'] ?: $this->config['from']),
            'message' => $message->message,
            'msisdn' => $message->to,
            'price' => $this->config['price'],
            'overcharge' => $this->config['overcharge'],
        ));

        if ($dry_run) {
            return $adapter->dryRun();
        }

        return $adapter->post();
    }
}
