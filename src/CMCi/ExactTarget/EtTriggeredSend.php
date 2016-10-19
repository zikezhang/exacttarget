<?php

namespace CMCi\ExactTarget;


/**
 * EtTriggeredSend (Active Class)
 *
 * Active Classes accept an instance of CMCi\ExactTarget\EtClient
 * to communicate with the Exact Target server.
 *
 * @package ExactTarget
 * @author  Zike ZHANG <support@cmcigroup.com>
 * @version 1.0
 */
class EtTriggeredSend extends EtBaseClass
{
    protected $client;

    public $Client; // EtClientID
    public $TriggeredSendDefinition; // EtTriggeredSendDefinition
    public $Subscribers; // EtSubscriber
    public $Attributes; // EtAttribute
    public $CustomerKey; // String

    /**
     * allow for passing optional client class to [some] Et-classes
     * so they can take advantage of client specific functions.
     * e.g. send() and save()
     *
     * @param CMCi\ExactTarget\EtClient $EtClient
     */
    public function __construct($EtClient = null)
    {
        $this->client = $EtClient;
    }

    /**
     * Used for setting client after class instantiation
     *
     * @param CMCi\ExactTarget\EtClient $EtClient
     */
    public function setClient($EtClient)
    {
        $this->client = $EtClient;
    }

    /**
     * Get active client instance.
     *
     * @return CMCi\ExactTarget\EtClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Use customer key ($custKey) as the triggered send key definition.
     *
     * @param type $custKey
     * @param type $options
     */
    public function useKey($custKey, $options = array())
    {
        $tsd = new EtTriggeredSendDefinition();
        $tsd->setCustomerKey($custKey);
        if (!empty($options)) {
            foreach ($options as $key => $value) {
                $tsd->set($key, $value);
            }
        }

        $this->TriggeredSendDefinition = new \SoapVar($tsd, SOAP_ENC_OBJECT, "TriggeredSendDefinition", EtClient::SOAPWSDL);
    }

    /**
     * Shortcut function for getTriggeredSendDefinition()
     * Gets TriggeredSendDefinition on ($this) triggered-send.
     *
     * @return \CMCi\ExactTarget\EtTriggeredSendDefinition
     */
    public function getTSD()
    {
        return $this->TriggeredSendDefinition;
    }

    /**
     * Shortcut function for setTriggeredSendDefinition()
     * Sets triggered-send definition for ($this) triggered-send.
     *
     * @param \CMCi\ExactTarget\EtTriggeredSendDefinition $triggeredSendDefinition
     */
    public function setTSD($triggeredSendDefinition)
    {
        $this->TriggeredSendDefinition = $triggeredSendDefinition;
    }

    /**
     * send() - uses client to send email
     *
     * @return boolean
     */
    public function send()
    {
        return $this->client->sendEmail($this, "TriggeredSend");
    }
}
