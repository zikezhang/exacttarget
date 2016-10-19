<?php

namespace CMCi\ExactTarget;

use CMCi\ExactTarget\WSSE\WSSESoap;

/**
 * EtSoapClient
 *
 * The SOAP request handler.
 *
 * @package ExactTarget
 * @author  Zike ZHANG <support@cmcigroup.com>
 * @version 1.0
 */

class EtSoapClient extends \SoapClient
{
    public $username = null;
    public $password = null;

    public function __doRequest($request, $location, $saction, $version, $one_way = 0)
    {
        $doc = new \DOMDocument();
        $doc->loadXML($request);

        $objWSSE = new WSSESoap($doc);

        $objWSSE->addUserToken($this->username, $this->password, false);

        return parent::__doRequest($objWSSE->saveXML(), $location, $saction, $version, $one_way);
    }
}
