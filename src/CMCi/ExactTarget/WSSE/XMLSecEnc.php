<?php
/**
 * Created by PhpStorm.
 * User: zike
 * Date: 10/19/16
 * Time: 9:37 AM
 */

namespace CMCi\ExactTarget\WSSE;

use DOMDocument;
use Exception;
use DOMXPath;
use DOMNode;

/**
 * Class provided by Exact Target
 *
 * @codeCoverageIgnore
 * Class is a third-party library thus ignored
 */
class XMLSecEnc
{
    const CONTENT  = 'http://www.w3.org/2001/04/xmlenc#Content';
    const ELEMENT  = 'http://www.w3.org/2001/04/xmlenc#Element';
    const TEMPLATE = "<xenc:EncryptedData xmlns:xenc='http://www.w3.org/2001/04/xmlenc#'>
                             <xenc:CipherData>
                                   <xenc:CipherValue></xenc:CipherValue>
                             </xenc:CipherData>
                          </xenc:EncryptedData>";
    const URI      = 3;
    const XMLENCNS = 'http://www.w3.org/2001/04/xmlenc#';
    private $encdoc = null;
    private $rawNode = null;
    public $type = null;
    public $encKey = null;

    public function __construct()
    {
        $this->encdoc = new DOMDocument();
        $this->encdoc->loadXML(XMLSecEnc::TEMPLATE);
    }

    public function setNode($node)
    {
        $this->rawNode = $node;
    }

    public function encryptNode($objKey, $replace = true)
    {
        $data = '';
        if (empty($this->rawNode)) {
            throw new Exception('Node to encrypt has not been set');
        }
        if (!$objKey instanceof XMLSecurityKey) {
            throw new Exception('Invalid Key');
        }
        $doc         = $this->rawNode->ownerDocument;
        $xPath       = new DOMXPath($this->encdoc);
        $objList     = $xPath->query('/xenc:EncryptedData/xenc:CipherData/xenc:CipherValue');
        $cipherValue = $objList->item(0);
        if (is_null($cipherValue)) {
            throw new Exception('Error locating CipherValue element within template');
        }
        switch ($this->type) {
            case (XMLSecEnc::ELEMENT):
                $data = $doc->saveXML($this->rawNode);
                $this->encdoc->documentElement->setAttribute('Type', XMLSecEnc::ELEMENT);
                break;
            case (XMLSecEnc::CONTENT):
                $children = $this->rawNode->childNodes;
                foreach ($children as $child) {
                    $data .= $doc->saveXML($child);
                }
                $this->encdoc->documentElement->setAttribute('Type', XMLSecEnc::CONTENT);
                break;
            default:
                throw new Exception('Type is currently not supported');
        }
        $encMethod = $this->encdoc->documentElement->appendChild($this->encdoc->createElementNS(XMLSecEnc::XMLENCNS,
            'xenc:EncryptionMethod'));
        $encMethod->setAttribute('Algorithm', $objKey->getAlgorith());
        $cipherValue->parentNode->parentNode->insertBefore($encMethod, $cipherValue->parentNode);
        $strEncrypt = base64_encode($objKey->encryptData($data));
        $value      = $this->encdoc->createTextNode($strEncrypt);
        $cipherValue->appendChild($value);
        if ($replace) {
            switch ($this->type) {
                case (XMLSecEnc::ELEMENT):
                    if ($this->rawNode->nodeType == XML_DOCUMENT_NODE) {
                        return $this->encdoc;
                    }
                    $importEnc = $this->rawNode->ownerDocument->importNode($this->encdoc->documentElement, true);
                    $this->rawNode->parentNode->replaceChild($importEnc, $this->rawNode);
                    return $importEnc;
                    break;
                case (XMLSecEnc::CONTENT):
                    $importEnc = $this->rawNode->ownerDocument->importNode($this->encdoc->documentElement, true);
                    while ($this->rawNode->firstChild) {
                        $this->rawNode->removeChild($this->rawNode->firstChild);
                    }
                    $this->rawNode->appendChild($importEnc);
                    return $importEnc;
                    break;
            }
        }
    }

    public function decryptNode($objKey, $replace = true)
    {
        if (empty($this->rawNode)) {
            throw new Exception('Node to decrypt has not been set');
        }
        if (!$objKey instanceof XMLSecurityKey) {
            throw new Exception('Invalid Key');
        }
        $doc   = $this->rawNode->ownerDocument;
        $xPath = new DOMXPath($doc);
        $xPath->registerNamespace('xmlencr', XMLSecEnc::XMLENCNS);
        /* Only handles embedded content right now and not a reference */
        $query   = "./xmlencr:CipherData/xmlencr:CipherValue";
        $nodeset = $xPath->query($query, $this->rawNode);
        if ($node = $nodeset->item(0)) {
            $encryptedData = base64_decode($node->nodeValue);
            $decrypted     = $objKey->decryptData($encryptedData);
            if ($replace) {
                switch ($this->type) {
                    case (XMLSecEnc::ELEMENT):
                        $newdoc = new DOMDocument();
                        $newdoc->loadXML($decrypted);
                        if ($this->rawNode->nodeType == XML_DOCUMENT_NODE) {
                            return $newdoc;
                        }
                        $importEnc = $this->rawNode->ownerDocument->importNode($newdoc->documentElement, true);
                        $this->rawNode->parentNode->replaceChild($importEnc, $this->rawNode);
                        return $importEnc;
                        break;
                    case (XMLSecEnc::CONTENT):
                        if ($this->rawNode->nodeType == XML_DOCUMENT_NODE) {
                            $doc = $this->rawNode;
                        } else {
                            $doc = $this->rawNode->ownerDocument;
                        }
                        $newFrag = $doc->createDOMDocumentFragment();
                        $newFrag->appendXML($decrypted);
                        $this->rawNode->parentNode->replaceChild($newFrag, $this->rawNode);
                        return $this->rawNode->parentNode;
                        break;
                    default:
                        return $decrypted;
                }
            } else {
                return $decrypted;
            }
        } else {
            throw new Exception("Cannot locate encrypted data");
        }
    }

    public function encryptKey($srcKey, $rawKey, $append = true)
    {
        if ((!$srcKey instanceof XMLSecurityKey) || (!$rawKey instanceof XMLSecurityKey)) {
            throw new Exception('Invalid Key');
        }
        $strEncKey = base64_encode($srcKey->encryptData($rawKey->key));
        $root      = $this->encdoc->documentElement;
        $encKey    = $this->encdoc->createElementNS(XMLSecEnc::XMLENCNS, 'xenc:EncryptedKey');
        if ($append) {
            $keyInfo = $root->appendChild($this->encdoc->createElementNS('http://www.w3.org/2000/09/xmldsig#',
                'dsig:KeyInfo'));
            $keyInfo->appendChild($encKey);
        } else {
            $this->encKey = $encKey;
        }
        $encMethod = $encKey->appendChild($this->encdoc->createElementNS(XMLSecEnc::XMLENCNS, 'xenc:EncryptionMethod'));
        $encMethod->setAttribute('Algorithm', $srcKey->getAlgorith());
        if (!empty($srcKey->name)) {
            $keyInfo = $encKey->appendChild($this->encdoc->createElementNS('http://www.w3.org/2000/09/xmldsig#',
                'dsig:KeyInfo'));
            $keyInfo->appendChild($this->encdoc->createElementNS('http://www.w3.org/2000/09/xmldsig#',
                'dsig:KeyName',
                $srcKey->name));
        }
        $cipherData = $encKey->appendChild($this->encdoc->createElementNS(XMLSecEnc::XMLENCNS, 'xenc:CipherData'));
        $cipherData->appendChild($this->encdoc->createElementNS(XMLSecEnc::XMLENCNS, 'xenc:CipherValue', $strEncKey));
        return;
    }

    public function decryptKey($encKey)
    {
        if (!$encKey->isEncrypted) {
            throw new Exception("Key is not Encrypted");
        }
        if (empty($encKey->key)) {
            throw new Exception("Key is missing data to perform the decryption");
        }
        return $this->decryptNode($encKey, false);
    }

    public function locateEncryptedData($element)
    {
        if ($element instanceof DOMDocument) {
            $doc = $element;
        } else {
            $doc = $element->ownerDocument;
        }
        if ($doc) {
            $xpath   = new DOMXPath($doc);
            $query   = "//*[local-name()='EncryptedData' and namespace-uri()='" . XMLSecEnc::XMLENCNS . "']";
            $nodeset = $xpath->query($query);
            return $nodeset->item(0);
        }
        return;
    }

    public function locateKey($node = null)
    {
        if (empty($node)) {
            $node = $this->rawNode;
        }
        if (!$node instanceof DOMNode) {
            return;
        }
        if ($doc = $node->ownerDocument) {
            $xpath = new DOMXPath($doc);
            $xpath->registerNamespace('xmlsecenc', XMLSecEnc::XMLENCNS);
            $query   = ".//xmlsecenc:EncryptionMethod";
            $nodeset = $xpath->query($query, $node);
            if ($encmeth = $nodeset->item(0)) {
                $attrAlgorithm = $encmeth->getAttribute("Algorithm");
                try {
                    $objKey = new XMLSecurityKey($attrAlgorithm, array( 'type' => 'private' ));
                } catch (Exception $e) {
                    return;
                }
                return $objKey;
            }
        }
        return;
    }

    public static function staticLocateKeyInfo($objBaseKey = null, $node = null)
    {
        if (empty($node) || (!$node instanceof DOMNode)) {
            return;
        }
        if ($doc = $node->ownerDocument) {
            $xpath = new DOMXPath($doc);
            $xpath->registerNamespace('xmlsecenc', XMLSecEnc::XMLENCNS);
            $xpath->registerNamespace('xmlsecdsig', XMLSecurityDSig::XMLDSIGNS);
            $query   = "./xmlsecdsig:KeyInfo";
            $nodeset = $xpath->query($query, $node);
            if ($encmeth = $nodeset->item(0)) {
                foreach ($encmeth->childNodes as $child) {
                    switch ($child->localName) {
                        case 'KeyName':
                            if (!empty($objBaseKey)) {
                                $objBaseKey->name = $child->nodeValue;
                            }
                            break;
                        case 'KeyValue':
                            foreach ($child->childNodes as $keyval) {
                                switch ($keyval->localName) {
                                    case 'DSAKeyValue':
                                        throw new Exception("DSAKeyValue currently not supported");
                                        break;
                                    case 'RSAKeyValue':
                                        $modulus  = null;
                                        $exponent = null;
                                        if ($modulusNode = $keyval->getElementsByTagName('Modulus')->item(0)) {
                                            $modulus = base64_decode($modulusNode->nodeValue);
                                        }
                                        if ($exponentNode = $keyval->getElementsByTagName('Exponent')->item(0)) {
                                            $exponent = base64_decode($exponentNode->nodeValue);
                                        }
                                        if (empty($modulus) || empty($exponent)) {
                                            throw new Exception("Missing Modulus or Exponent");
                                        }
                                        $publicKey = XMLSecurityKey::convertRSA($modulus, $exponent);
                                        $objBaseKey->loadKey($publicKey);
                                        break;
                                }
                            }
                            break;
                        case 'RetrievalMethod':
                            /* Not currently supported */
                            break;
                        case 'EncryptedKey':
                            $objenc = new XMLSecEnc();
                            $objenc->setNode($child);
                            if (!$objKey = $objenc->locateKey()) {
                                throw new Exception("Unable to locate algorithm for this Encrypted Key");
                            }
                            $objKey->isEncrypted  = true;
                            $objKey->encryptedCtx = $objenc;
                            XMLSecEnc::staticLocateKeyInfo($objKey, $child);
                            return $objKey;
                            break;
                        case 'X509Data':
                            if ($x509certNodes = $child->getElementsByTagName('X509Certificate')) {
                                if ($x509certNodes->length > 0) {
                                    $x509cert = $x509certNodes->item(0)->textContent;
                                    $x509cert = str_replace(array( "\r", "\n" ), "", $x509cert);
                                    $x509cert = "-----BEGIN CERTIFICATE-----\n" . chunk_split($x509cert,
                                            64,
                                            "\n") . "-----END CERTIFICATE-----\n";
                                    $objBaseKey->loadKey($x509cert);
                                }
                            }
                            break;
                    }
                }
            }
            return $objBaseKey;
        }
        return;
    }

    public function locateKeyInfo($objBaseKey = null, $node = null)
    {
        if (empty($node)) {
            $node = $this->rawNode;
        }
        return XMLSecEnc::staticLocateKeyInfo($objBaseKey, $node);
    }
}