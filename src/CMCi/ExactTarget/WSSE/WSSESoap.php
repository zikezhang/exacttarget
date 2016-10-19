<?php
/**
 * Created by PhpStorm.
 * User: zike
 * Date: 10/19/16
 * Time: 9:33 AM
 */

namespace CMCi\ExactTarget\WSSE;

use DOMElement;
use DOMXPath;
use Exception;

/**
 * Class provided by Exact Target
 *
 * @codeCoverageIgnore
 * Class is a third-party library thus ignored
 */
class WSSESoap
{
    const WSSENS  = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    const WSUNS   = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd';
    const WSUNAME = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0';
    const WSSEPFX = 'wsse';
    const WSUPFX  = 'wsu';
    private $soapNS, $soapPFX;
    private $soapDoc = null;
    private $envelope = null;
    private $SOAPXPath = null;
    private $secNode = null;
    public $signAllHeaders = false;

    private function locateSecurityHeader($bMustUnderstand = true, $setActor = null)
    {
        if (is_null($this->secNode)) {
            $headers = $this->SOAPXPath->query('//wssoap:Envelope/wssoap:Header');
            $header  = $headers->item(0);
            if (!$header) {
                $header = $this->soapDoc->createElementNS($this->soapNS, $this->soapPFX . ':Header');
                $this->envelope->insertBefore($header, $this->envelope->firstChild);
            }
            $secnodes = $this->SOAPXPath->query('./wswsse:Security', $header);
            $secnode  = null;
            foreach ($secnodes as $node) {
                $actor = $node->getAttributeNS($this->soapNS, 'actor');
                if ($actor == $setActor) {
                    $secnode = $node;
                    break;
                }
            }
            if (!$secnode) {
                $secnode = $this->soapDoc->createElementNS(WSSESoap::WSSENS, WSSESoap::WSSEPFX . ':Security');
                $header->appendChild($secnode);
                if ($bMustUnderstand) {
                    $secnode->setAttributeNS($this->soapNS, $this->soapPFX . ':mustUnderstand', '1');
                }
                if (!empty($setActor)) {
                    $ename = 'actor';
                    if ($this->soapNS == 'http://www.w3.org/2003/05/soap-envelope') {
                        $ename = 'role';
                    }
                    $secnode->setAttributeNS($this->soapNS, $this->soapPFX . ':' . $ename, $setActor);
                }
            }
            $this->secNode = $secnode;
        }
        return $this->secNode;
    }

    /**
     * @param \DOMDocument $doc
     * @param bool|true    $bMustUnderstand
     * @param null         $setActor
     */
    public function __construct(\DOMDocument $doc, $bMustUnderstand = true, $setActor = null)
    {
        $this->soapDoc   = $doc;
        $this->envelope  = $doc->documentElement;
        $this->soapNS    = $this->envelope->namespaceURI;
        $this->soapPFX   = $this->envelope->prefix;
        $this->SOAPXPath = new DOMXPath($doc);
        $this->SOAPXPath->registerNamespace('wssoap', $this->soapNS);
        $this->SOAPXPath->registerNamespace('wswsse', WSSESoap::WSSENS);
        $this->locateSecurityHeader($bMustUnderstand, $setActor);
    }

    public function addTimestamp($secondsToExpire = 3600)
    {
        /* Add the WSU timestamps */
        $security = $this->locateSecurityHeader();
        $timestamp = $this->soapDoc->createElementNS(WSSESoap::WSUNS, WSSESoap::WSUPFX . ':Timestamp');
        $security->insertBefore($timestamp, $security->firstChild);
        $currentTime = time();
        $created     = $this->soapDoc->createElementNS(WSSESoap::WSUNS,
            WSSESoap::WSUPFX . ':Created',
            gmdate("Y-m-d\TH:i:s", $currentTime) . 'Z');
        $timestamp->appendChild($created);
        if (!is_null($secondsToExpire)) {
            $expire = $this->soapDoc->createElementNS(WSSESoap::WSUNS,
                WSSESoap::WSUPFX . ':Expires',
                gmdate("Y-m-d\TH:i:s", $currentTime + $secondsToExpire) . 'Z');
            $timestamp->appendChild($expire);
        }
    }

    public function addUserToken($userName, $password = null, $passwordDigest = false)
    {
        if ($passwordDigest && empty($password)) {
            throw new Exception("Cannot calculate the digest without a password");
        }
        $security = $this->locateSecurityHeader();
        $token = $this->soapDoc->createElementNS(WSSESoap::WSSENS, WSSESoap::WSSEPFX . ':UsernameToken');
        $security->insertBefore($token, $security->firstChild);
        $username = $this->soapDoc->createElementNS(WSSESoap::WSSENS, WSSESoap::WSSEPFX . ':Username', $userName);
        $token->appendChild($username);
        /* Generate nonce - create a 256 bit session key to be used */
        $objKey = new XMLSecurityKey(XMLSecurityKey::AES256_CBC);
        $nonce  = $objKey->generateSessionKey();
        unset($objKey);
        $createdate = gmdate("Y-m-d\TH:i:s") . 'Z';
        if ($password) {
            $passType = WSSESoap::WSUNAME . '#PasswordText';
            if ($passwordDigest) {
                $password = base64_encode(sha1($nonce . $createdate . $password, true));
                $passType = WSSESoap::WSUNAME . '#PasswordDigest';
            }
            $passwordNode = $this->soapDoc->createElementNS(WSSESoap::WSSENS,
                WSSESoap::WSSEPFX . ':Password',
                $password);
            $token->appendChild($passwordNode);
            $passwordNode->setAttribute('Type', $passType);
        }
        $nonceNode = $this->soapDoc->createElementNS(WSSESoap::WSSENS,
            WSSESoap::WSSEPFX . ':Nonce',
            base64_encode($nonce));
        $token->appendChild($nonceNode);
        $created = $this->soapDoc->createElementNS(WSSESoap::WSUNS, WSSESoap::WSUPFX . ':Created', $createdate);
        $token->appendChild($created);
    }

    public function addBinaryToken($cert, $isPEMFormat = true)
    {
        $security = $this->locateSecurityHeader();
        $data     = XMLSecurityDSig::get509XCert($cert, $isPEMFormat);
        $token = $this->soapDoc->createElementNS(WSSESoap::WSSENS, WSSESoap::WSSEPFX . ':BinarySecurityToken', $data);
        $security->insertBefore($token, $security->firstChild);
        $token->setAttribute('EncodingType',
            'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary');
        $token->setAttributeNS(WSSESoap::WSUNS, WSSESoap::WSUPFX . ':Id', XMLSecurityDSig::generateGUID());
        $token->setAttribute('ValueType',
            'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3');
        return $token;
    }

    public function attachTokentoSig($token)
    {
        if (!($token instanceof DOMElement)) {
            throw new Exception('Invalid parameter: BinarySecurityToken element expected');
        }
        $objXMLSecDSig = new XMLSecurityDSig();
        if ($objDSig = $objXMLSecDSig->locateSignature($this->soapDoc)) {
            $tokenURI = '#' . $token->getAttributeNS(WSSESoap::WSUNS, "Id");
            $this->SOAPXPath->registerNamespace('secdsig', XMLSecurityDSig::XMLDSIGNS);
            $query   = "./secdsig:KeyInfo";
            $nodeset = $this->SOAPXPath->query($query, $objDSig);
            $keyInfo = $nodeset->item(0);
            if (!$keyInfo) {
                $keyInfo = $objXMLSecDSig->createNewSignNode('KeyInfo');
                $objDSig->appendChild($keyInfo);
            }
            $tokenRef = $this->soapDoc->createElementNS(WSSESoap::WSSENS,
                WSSESoap::WSSEPFX . ':SecurityTokenReference');
            $keyInfo->appendChild($tokenRef);
            $reference = $this->soapDoc->createElementNS(WSSESoap::WSSENS, WSSESoap::WSSEPFX . ':Reference');
            $reference->setAttribute("URI", $tokenURI);
            $tokenRef->appendChild($reference);
        } else {
            throw new Exception('Unable to locate digital signature');
        }
    }

    public function signSoapDoc($objKey)
    {
        $objDSig = new XMLSecurityDSig();
        $objDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
        $arNodes = array();
        foreach ($this->secNode->childNodes as $node) {
            if ($node->nodeType == XML_ELEMENT_NODE) {
                $arNodes[] = $node;
            }
        }
        if ($this->signAllHeaders) {
            foreach ($this->secNode->parentNode->childNodes as $node) {
                if (($node->nodeType == XML_ELEMENT_NODE) &&
                    ($node->namespaceURI != WSSESoap::WSSENS)
                ) {
                    $arNodes[] = $node;
                }
            }
        }
        foreach ($this->envelope->childNodes as $node) {
            if ($node->namespaceURI == $this->soapNS && $node->localName == 'Body') {
                $arNodes[] = $node;
                break;
            }
        }
        $arOptions = array( 'prefix' => WSSESoap::WSUPFX, 'prefix_ns' => WSSESoap::WSUNS );
        $objDSig->addReferenceList($arNodes, XMLSecurityDSig::SHA1, null, $arOptions);
        $objDSig->sign($objKey);
        $objDSig->appendSignature($this->secNode, true);
    }

    /**
     * Add Encrypted Key
     *
     * @param XMLSecEnc   $key
     * @param \DOMElement $token
     *
     * @return bool
     */
    public function addEncryptedKey(XMLSecEnc $key, \DOMElement $token)
    {
        if (!$key->encKey) {
            return false;
        }
        $encKey   = $key->encKey;
        $security = $this->locateSecurityHeader();
        $doc      = $security->ownerDocument;
        if (!$doc->isSameNode($encKey->ownerDocument)) {
            $key->encKey = $security->ownerDocument->importNode($encKey, true);
            $encKey      = $key->encKey;
        }
        if (!empty($key->guid)) {
            return true;
        }
        $lastToken  = null;
        $findTokens = $security->firstChild;
        while ($findTokens) {
            if ($findTokens->localName == 'BinarySecurityToken') {
                $lastToken = $findTokens;
            }
            $findTokens = $findTokens->nextSibling;
        }
        if ($lastToken) {
            $lastToken = $lastToken->nextSibling;
        }
        $security->insertBefore($encKey, $lastToken);
        $key->guid = XMLSecurityDSig::generateGUID();
        $encKey->setAttribute('Id', $key->guid);
        $encMethod = $encKey->firstChild;
        while ($encMethod && $encMethod->localName != 'EncryptionMethod') {
            $encMethod = $encMethod->nextChild;
        }
        if ($encMethod) {
            $encMethod = $encMethod->nextSibling;
        }
        $objDoc  = $encKey->ownerDocument;
        $keyInfo = $objDoc->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'dsig:KeyInfo');
        $encKey->insertBefore($keyInfo, $encMethod);
        $tokenURI = '#' . $token->getAttributeNS(WSSESoap::WSUNS, "Id");
        $tokenRef = $objDoc->createElementNS(WSSESoap::WSSENS, WSSESoap::WSSEPFX . ':SecurityTokenReference');
        $keyInfo->appendChild($tokenRef);
        $reference = $objDoc->createElementNS(WSSESoap::WSSENS, WSSESoap::WSSEPFX . ':Reference');
        $reference->setAttribute("URI", $tokenURI);
        $tokenRef->appendChild($reference);
        return true;
    }

    public function addReference(\DOMNode $baseNode, $guid)
    {
        $refList = null;
        $child   = $baseNode->firstChild;
        while ($child) {
            if (($child->namespaceURI == XMLSecEnc::XMLENCNS) && ($child->localName == 'ReferenceList')) {
                $refList = $child;
                break;
            }
            $child = $child->nextSibling;
        }
        $doc = $baseNode->ownerDocument;
        if (is_null($refList)) {
            $refList = $doc->createElementNS(XMLSecEnc::XMLENCNS, 'xenc:ReferenceList');
            $baseNode->appendChild($refList);
        }
        $dataref = $doc->createElementNS(XMLSecEnc::XMLENCNS, 'xenc:DataReference');
        $refList->appendChild($dataref);
        $dataref->setAttribute('URI', '#' . $guid);
    }

    public function encryptBody($siteKey, $objKey, $token)
    {
        $enc = new XMLSecEnc();
        foreach ($this->envelope->childNodes as $node) {
            if ($node->namespaceURI == $this->soapNS && $node->localName == 'Body') {
                break;
            }
        }
        $enc->setNode($node);
        /* encrypt the symmetric key */
        $enc->encryptKey($siteKey, $objKey, false);
        $enc->type = XMLSecEnc::CONTENT;
        /* Using the symmetric key to actually encrypt the data */
        $encNode = $enc->encryptNode($objKey);
        $guid = XMLSecurityDSig::generateGUID();
        $encNode->setAttribute('Id', $guid);
        $refNode = $encNode->firstChild;
        while ($refNode && $refNode->nodeType != XML_ELEMENT_NODE) {
            $refNode = $refNode->nextSibling;
        }
        if ($refNode) {
            $refNode = $refNode->nextSibling;
        }
        if ($this->addEncryptedKey($enc, $token)) {
            $this->addReference($enc->encKey, $guid);
        }
    }

    public function saveXML()
    {
        return $this->soapDoc->saveXML();
    }

    public function save($file)
    {
        return $this->soapDoc->save($file);
    }
}