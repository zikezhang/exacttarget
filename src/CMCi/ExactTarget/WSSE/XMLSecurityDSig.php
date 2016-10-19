<?php
/**
 * Created by PhpStorm.
 * User: zike
 * Date: 10/19/16
 * Time: 9:39 AM
 */

namespace CMCi\ExactTarget\WSSE;

use DOMElement;
use DOMDocument;
use DOMXPath;
use DOMNode;
use Exception;

/**
 *
 * Class provided by Exact Target
 *
 * @codeCoverageIgnore
 * Class is a third-party library thus ignored
 */
class XMLSecurityDSig
{
    const C14N              = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
    const C14N_COMMENTS     = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315#WithComments';
    const EXC_C14N          = 'http://www.w3.org/2001/10/xml-exc-c14n#';
    const EXC_C14N_COMMENTS = 'http://www.w3.org/2001/10/xml-exc-c14n#WithComments';
    const RIPEMD160         = 'http://www.w3.org/2001/04/xmlenc#ripemd160';
    const SHA1              = 'http://www.w3.org/2000/09/xmldsig#sha1';
    const SHA256            = 'http://www.w3.org/2001/04/xmlenc#sha256';
    const SHA512            = 'http://www.w3.org/2001/04/xmlenc#sha512';
    const TEMPLATE          = '<ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
                                    <ds:SignedInfo>
                                      <ds:SignatureMethod />
                                    </ds:SignedInfo>
                                  </ds:Signature>';
    const XMLDSIGNS         = 'http://www.w3.org/2000/09/xmldsig#';
    public $idKeys = array();
    public $idNS = array();
    public $sigNode = null;
    private $canonicalMethod = null;
    private $prefix = 'ds';
    private $signedInfo = null;
    private $searchpfx = 'secdsig';
    private $xPathCtx = null;

    public function __construct()
    {
        $sigdoc = new DOMDocument();
        $sigdoc->loadXML(XMLSecurityDSig::TEMPLATE);
        $this->sigNode = $sigdoc->documentElement;
    }

    private function getXPathObj()
    {
        if (empty($this->xPathCtx) && !empty($this->sigNode)) {
            $xpath = new DOMXPath($this->sigNode->ownerDocument);
            $xpath->registerNamespace('secdsig', XMLSecurityDSig::XMLDSIGNS);
            $this->xPathCtx = $xpath;
        }
        return $this->xPathCtx;
    }

    public static function generateGUID($prefix = null)
    {
        $uuid = md5(uniqid(rand(), true));
        $guid = $prefix . substr($uuid, 0, 8) . "-" .
            substr($uuid, 8, 4) . "-" .
            substr($uuid, 12, 4) . "-" .
            substr($uuid, 16, 4) . "-" .
            substr($uuid, 20, 12);
        return $guid;
    }

    public function locateSignature($objDoc)
    {
        if ($objDoc instanceof DOMDocument) {
            $doc = $objDoc;
        } else {
            $doc = $objDoc->ownerDocument;
        }
        if ($doc) {
            $xpath = new DOMXPath($doc);
            $xpath->registerNamespace('secdsig', XMLSecurityDSig::XMLDSIGNS);
            $query         = ".//secdsig:Signature";
            $nodeset       = $xpath->query($query, $objDoc);
            $this->sigNode = $nodeset->item(0);
            return $this->sigNode;
        }
        return;
    }

    public function createNewSignNode($name, $value = null)
    {
        $doc = $this->sigNode->ownerDocument;
        if (!is_null($value)) {
            $node = $doc->createElementNS(XMLSecurityDSig::XMLDSIGNS, $this->prefix . ':' . $name, $value);
        } else {
            $node = $doc->createElementNS(XMLSecurityDSig::XMLDSIGNS, $this->prefix . ':' . $name);
        }
        return $node;
    }

    public function setCanonicalMethod($method)
    {
        switch ($method) {
            case 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315':
            case 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315#WithComments':
            case 'http://www.w3.org/2001/10/xml-exc-c14n#':
            case 'http://www.w3.org/2001/10/xml-exc-c14n#WithComments':
                $this->canonicalMethod = $method;
                break;
            default:
                throw new Exception('Invalid Canonical Method');
        }
        if ($xpath = $this->getXPathObj()) {
            $query   = './' . $this->searchpfx . ':SignedInfo';
            $nodeset = $xpath->query($query, $this->sigNode);
            if ($sinfo = $nodeset->item(0)) {
                $query   = './' . $this->searchpfx . 'CanonicalizationMethod';
                $nodeset = $xpath->query($query, $sinfo);
                if (!($canonNode = $nodeset->item(0))) {
                    $canonNode = $this->createNewSignNode('CanonicalizationMethod');
                    $sinfo->insertBefore($canonNode, $sinfo->firstChild);
                }
                $canonNode->setAttribute('Algorithm', $this->canonicalMethod);
            }
        }
    }

    private function canonicalizeData($node, $canonicalmethod)
    {
        $exclusive    = false;
        $withComments = false;
        switch ($canonicalmethod) {
            case 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315':
                $exclusive    = false;
                $withComments = false;
                break;
            case 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315#WithComments':
                $withComments = true;
                break;
            case 'http://www.w3.org/2001/10/xml-exc-c14n#':
                $exclusive = true;
                break;
            case 'http://www.w3.org/2001/10/xml-exc-c14n#WithComments':
                $exclusive    = true;
                $withComments = true;
                break;
        }
        /* Support PHP versions < 5.2 not containing C14N methods in DOM extension */
        $php_version = explode('.', PHP_VERSION);
        if (($php_version[0] < 5) || ($php_version[0] == 5 && $php_version[1] < 2)) {
            return $this->c14NGeneral($node, $exclusive, $withComments);
        }
        return $node->C14N($exclusive, $withComments);
    }

    public function canonicalizeSignedInfo()
    {
        $doc             = $this->sigNode->ownerDocument;
        $canonicalmethod = null;
        if ($doc) {
            $xpath   = $this->getXPathObj();
            $query   = "./secdsig:SignedInfo";
            $nodeset = $xpath->query($query, $this->sigNode);
            if ($signInfoNode = $nodeset->item(0)) {
                $query   = "./secdsig:CanonicalizationMethod";
                $nodeset = $xpath->query($query, $signInfoNode);
                if ($canonNode = $nodeset->item(0)) {
                    $canonicalmethod = $canonNode->getAttribute('Algorithm');
                }
                $this->signedInfo = $this->canonicalizeData($signInfoNode, $canonicalmethod);
                return $this->signedInfo;
            }
        }
        return;
    }

    public function calculateDigest($digestAlgorithm, $data)
    {
        switch ($digestAlgorithm) {
            case XMLSecurityDSig::SHA1:
                $alg = 'sha1';
                break;
            case XMLSecurityDSig::SHA256:
                $alg = 'sha256';
                break;
            case XMLSecurityDSig::SHA512:
                $alg = 'sha512';
                break;
            case XMLSecurityDSig::RIPEMD160:
                $alg = 'ripemd160';
                break;
            default:
                throw new Exception("Cannot validate digest: Unsupported Algorith <$digestAlgorithm>");
        }
        return base64_encode(hash($alg, $data, true));
    }

    public function validateDigest($refNode, $data)
    {
        $xpath = new DOMXPath($refNode->ownerDocument);
        $xpath->registerNamespace('secdsig', XMLSecurityDSig::XMLDSIGNS);
        $query           = 'string(./secdsig:DigestMethod/@Algorithm)';
        $digestAlgorithm = $xpath->evaluate($query, $refNode);
        $digValue        = $this->calculateDigest($digestAlgorithm, $data);
        $query           = 'string(./secdsig:DigestValue)';
        $digestValue     = $xpath->evaluate($query, $refNode);
        return ($digValue == $digestValue);
    }

    public function processTransforms($refNode, $objData)
    {
        $data  = $objData;
        $xpath = new DOMXPath($refNode->ownerDocument);
        $xpath->registerNamespace('secdsig', XMLSecurityDSig::XMLDSIGNS);
        $query           = './secdsig:Transforms/secdsig:Transform';
        $nodelist        = $xpath->query($query, $refNode);
        $canonicalMethod = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
        foreach ($nodelist as $transform) {
            $algorithm = $transform->getAttribute("Algorithm");
            switch ($algorithm) {
                case 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315':
                case 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315#WithComments':
                case 'http://www.w3.org/2001/10/xml-exc-c14n#':
                case 'http://www.w3.org/2001/10/xml-exc-c14n#WithComments':
                    $canonicalMethod = $algorithm;
                    break;
            }
        }
        if ($data instanceof DOMNode) {
            $data = $this->canonicalizeData($objData, $canonicalMethod);
        }
        return $data;
    }

    public function processRefNode($refNode)
    {
        $dataObject = null;
        if ($uri = $refNode->getAttribute("URI")) {
            $arUrl = parse_url($uri);
            if (empty($arUrl['path'])) {
                if ($identifier = $arUrl['fragment']) {
                    $xPath = new DOMXPath($refNode->ownerDocument);
                    if ($this->idNS && is_array($this->idNS)) {
                        foreach ($this->idNS as $nspf => $ns) {
                            $xPath->registerNamespace($nspf, $ns);
                        }
                    }
                    $iDlist = '@Id="' . $identifier . '"';
                    if (is_array($this->idKeys)) {
                        foreach ($this->idKeys as $idKey) {
                            $iDlist .= " or @$idKey='$identifier'";
                        }
                    }
                    $query      = '//*[' . $iDlist . ']';
                    $dataObject = $xPath->query($query)->item(0);
                } else {
                    $dataObject = $refNode->ownerDocument;
                }
            } else {
                $dataObject = file_get_contents($arUrl);
            }
        } else {
            $dataObject = $refNode->ownerDocument;
        }
        $data = $this->processTransforms($refNode, $dataObject);
        return $this->validateDigest($refNode, $data);
    }

    public function validateReference()
    {
        $doc = $this->sigNode->ownerDocument;
        if (!$doc->isSameNode($this->sigNode)) {
            $this->sigNode->parentNode->removeChild($this->sigNode);
        }
        $xpath   = $this->getXPathObj();
        $query   = "./secdsig:SignedInfo/secdsig:Reference";
        $nodeset = $xpath->query($query, $this->sigNode);
        if ($nodeset->length == 0) {
            throw new Exception("Reference nodes not found");
        }
        foreach ($nodeset as $refNode) {
            if (!$this->processRefNode($refNode)) {
                throw new Exception("Reference validation failed");
            }
        }
        return true;
    }

    private function addRefInternal($sinfoNode, $node, $algorithm, $arTransforms = null, $options = null)
    {
        $prefix    = null;
        $prefix_ns = null;
        if (is_array($options)) {
            $prefix    = empty($options['prefix']) ? null : $options['prefix'];
            $prefix_ns = empty($options['prefix_ns']) ? null : $options['prefix_ns'];
            $id_name   = empty($options['id_name']) ? 'Id' : $options['id_name'];
        }
        $refNode = $this->createNewSignNode('Reference');
        $sinfoNode->appendChild($refNode);
        if ($node instanceof DOMDocument) {
            $uri = null;
        } else {
            /* Do wer really need to set a prefix? */
            $uri = XMLSecurityDSig::generateGUID();
            $refNode->setAttribute("URI", '#' . $uri);
        }
        $transNodes = $this->createNewSignNode('Transforms');
        $refNode->appendChild($transNodes);
        if (is_array($arTransforms)) {
            foreach ($arTransforms as $transform) {
                $transNode = $this->createNewSignNode('Transform');
                $transNodes->appendChild($transNode);
                $transNode->setAttribute('Algorithm', $transform);
            }
        } elseif (!empty($this->canonicalMethod)) {
            $transNode = $this->createNewSignNode('Transform');
            $transNodes->appendChild($transNode);
            $transNode->setAttribute('Algorithm', $this->canonicalMethod);
        }
        if (!empty($uri)) {
            $attname = $id_name;
            if (!empty($prefix)) {
                $attname = $prefix . ':' . $attname;
            }
            $node->setAttributeNS($prefix_ns, $attname, $uri);
        }
        $canonicalData = $this->processTransforms($refNode, $node);
        $digValue      = $this->calculateDigest($algorithm, $canonicalData);
        $digestMethod = $this->createNewSignNode('DigestMethod');
        $refNode->appendChild($digestMethod);
        $digestMethod->setAttribute('Algorithm', $algorithm);
        $digestValue = $this->createNewSignNode('DigestValue', $digValue);
        $refNode->appendChild($digestValue);
    }

    public function addReference($node, $algorithm, $arTransforms = null, $options = null)
    {
        if ($xpath = $this->getXPathObj()) {
            $query   = "./secdsig:SignedInfo";
            $nodeset = $xpath->query($query, $this->sigNode);
            if ($sInfo = $nodeset->item(0)) {
                $this->addRefInternal($sInfo, $node, $algorithm, $arTransforms, $options);
            }
        }
    }

    public function addReferenceList($arNodes, $algorithm, $arTransforms = null, $options = null)
    {
        if ($xpath = $this->getXPathObj()) {
            $query   = "./secdsig:SignedInfo";
            $nodeset = $xpath->query($query, $this->sigNode);
            if ($sInfo = $nodeset->item(0)) {
                foreach ($arNodes as $node) {
                    $this->addRefInternal($sInfo, $node, $algorithm, $arTransforms, $options);
                }
            }
        }
    }

    public function locateKey($node = null)
    {
        if (empty($node)) {
            $node = $this->sigNode;
        }
        if (!$node instanceof DOMNode) {
            return;
        }
        if ($doc = $node->ownerDocument) {
            $xpath = new DOMXPath($doc);
            $xpath->registerNamespace('secdsig', XMLSecurityDSig::XMLDSIGNS);
            $query     = "string(./secdsig:SignedInfo/secdsig:SignatureMethod/@Algorithm)";
            $algorithm = $xpath->evaluate($query, $node);
            if ($algorithm) {
                try {
                    $objKey = new XMLSecurityKey($algorithm, array( 'type' => 'public' ));
                } catch (Exception $e) {
                    return;
                }
                return $objKey;
            }
        }
        return;
    }

    public function verify($objKey)
    {
        $doc   = $this->sigNode->ownerDocument;
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('secdsig', XMLSecurityDSig::XMLDSIGNS);
        $query    = "string(./secdsig:SignatureValue)";
        $sigValue = $xpath->evaluate($query, $this->sigNode);
        if (empty($sigValue)) {
            throw new Exception("Unable to locate SignatureValue");
        }
        return $objKey->verifySignature($this->signedInfo, base64_decode($sigValue));
    }

    public function signData($objKey, $data)
    {
        return $objKey->signData($data);
    }

    public function sign($objKey)
    {
        if ($xpath = $this->getXPathObj()) {
            $query   = "./secdsig:SignedInfo";
            $nodeset = $xpath->query($query, $this->sigNode);
            if ($sInfo = $nodeset->item(0)) {
                $query   = "./secdsig:SignatureMethod";
                $nodeset = $xpath->query($query, $sInfo);
                $sMethod = $nodeset->item(0);
                $sMethod->setAttribute('Algorithm', $objKey->type);
                $data         = $this->canonicalizeData($sInfo, $this->canonicalMethod);
                $sigValue     = base64_encode($this->signData($objKey, $data));
                $sigValueNode = $this->createNewSignNode('SignatureValue', $sigValue);
                if ($infoSibling = $sInfo->nextSibling) {
                    $infoSibling->parentNode->insertBefore($sigValueNode, $infoSibling);
                } else {
                    $this->sigNode->appendChild($sigValueNode);
                }
            }
        }
    }

    public function appendCert()
    {
    }

    public function appendKey($objKey, $parent = null)
    {
        $objKey->serializeKey($parent);
    }

    public function appendSignature($parentNode, $insertBefore = false)
    {
        $baseDoc = ($parentNode instanceof DOMDocument) ? $parentNode : $parentNode->ownerDocument;
        $newSig  = $baseDoc->importNode($this->sigNode, true);
        if ($insertBefore) {
            $parentNode->insertBefore($newSig, $parentNode->firstChild);
        } else {
            $parentNode->appendChild($newSig);
        }
    }

    public static function get509XCert($cert, $isPEMFormat = true)
    {
        if ($isPEMFormat) {
            $data   = '';
            $arCert = explode("\n", $cert);
            $inData = false;
            foreach ($arCert as $curData) {
                if (!$inData) {
                    if (strncmp($curData, '-----BEGIN CERTIFICATE', 22) == 0) {
                        $inData = true;
                    }
                } else {
                    if (strncmp($curData, '-----END CERTIFICATE', 20) == 0) {
                        break;
                    }
                    $data .= trim($curData);
                }
            }
        } else {
            $data = $cert;
        }
        return $data;
    }

    public function add509Cert($cert, $isPEMFormat = true)
    {
        $data = XMLSecurityDSig::get509XCert($cert, $isPEMFormat);
        if ($xpath = $this->getXPathObj()) {
            $query   = "./secdsig:KeyInfo";
            $nodeset = $xpath->query($query, $this->sigNode);
            $keyInfo = $nodeset->item(0);
            if (!$keyInfo) {
                $inserted = false;
                $keyInfo  = $this->createNewSignNode('KeyInfo');
                if ($xpath = $this->getXPathObj()) {
                    $query   = "./secdsig:Object";
                    $nodeset = $xpath->query($query, $this->sigNode);
                    if ($sObject = $nodeset->item(0)) {
                        $sObject->parentNode->insertBefore($keyInfo, $sObject);
                        $inserted = true;
                    }
                }
                if (!$inserted) {
                    $this->sigNode->appendChild($keyInfo);
                }
            }
            $x509DataNode = $this->createNewSignNode('X509Data');
            $keyInfo->appendChild($x509DataNode);
            $x509CertNode = $this->createNewSignNode('X509Certificate', $data);
            $x509DataNode->appendChild($x509CertNode);
        }
    }
    /*
      Functions to generate simple cases of Exclusive Canonical XML - Callable function is c14NGeneral()
      i.e.: $canonical = c14NGeneral($domelement, TRUE);
     */
    /* helper function */

    public function sortAndAddAttrs($element, $arAtts)
    {
        $newAtts = array();
        foreach ($arAtts as $attnode) {
            $newAtts[$attnode->nodeName] = $attnode;
        }
        ksort($newAtts);
        foreach ($newAtts as $attnode) {
            $element->setAttribute($attnode->nodeName, $attnode->nodeValue);
        }
    }

    /* helper function */
    public function canonical($tree, $element, $withcomments)
    {
        if ($tree->nodeType != XML_DOCUMENT_NODE) {
            $dom = $tree->ownerDocument;
        } else {
            $dom = $tree;
        }
        if ($element->nodeType != XML_ELEMENT_NODE) {
            if ($element->nodeType == XML_COMMENT_NODE && !$withcomments) {
                return;
            }
            $tree->appendChild($dom->importNode($element, true));
            return;
        }
        $arNS = array();
        if ($element->namespaceURI != "") {
            if ($element->prefix == "") {
                $elCopy = $dom->createElementNS($element->namespaceURI, $element->nodeName);
            } else {
                $prefix = $tree->lookupPrefix($element->namespaceURI);
                if ($prefix == $element->prefix) {
                    $elCopy = $dom->createElementNS($element->namespaceURI, $element->nodeName);
                } else {
                    $elCopy                       = $dom->createElement($element->nodeName);
                    $arNS[$element->namespaceURI] = $element->prefix;
                }
            }
        } else {
            $elCopy = $dom->createElement($element->nodeName);
        }
        $tree->appendChild($elCopy);
        /* Create DOMXPath based on original document */
        $xPath = new DOMXPath($element->ownerDocument);
        /* Get namespaced attributes */
        $arAtts = $xPath->query('attribute::*[namespace-uri(.) != ""]', $element);
        /* Create an array with namespace URIs as keys, and sort them */
        foreach ($arAtts as $attnode) {
            if (array_key_exists($attnode->namespaceURI, $arNS) &&
                ($arNS[$attnode->namespaceURI] == $attnode->prefix)
            ) {
                continue;
            }
            $prefix = $tree->lookupPrefix($attnode->namespaceURI);
            if ($prefix != $attnode->prefix) {
                $arNS[$attnode->namespaceURI] = $attnode->prefix;
            } else {
                $arNS[$attnode->namespaceURI] = null;
            }
        }
        if (count($arNS) > 0) {
            asort($arNS);
        }
        /* Add namespace nodes */
        foreach ($arNS as $namespaceURI => $prefix) {
            if (!is_null($prefix)) {
                $elCopy->setAttributeNS("http://www.w3.org/2000/xmlns/", "xmlns:" . $prefix, $namespaceURI);
            }
        }
        if (count($arNS) > 0) {
            ksort($arNS);
        }
        /* Get attributes not in a namespace, and then sort and add them */
        $arAtts = $xPath->query('attribute::*[namespace-uri(.) = ""]', $element);
        $this->sortAndAddAttrs($elCopy, $arAtts);
        /* Loop through the URIs, and then sort and add attributes within that namespace */
        foreach ($arNS as $nsURI => $prefix) {
            $arAtts = $xPath->query('attribute::*[namespace-uri(.) = "' . $nsURI . '"]', $element);
            $this->sortAndAddAttrs($elCopy, $arAtts);
        }
        foreach ($element->childNodes as $node) {
            $this->canonical($elCopy, $node, $withcomments);
        }
    }

    /*
      $element - DOMElement for which to produce the canonical version of
      $exclusive - boolean to indicate exclusive canonicalization (must pass TRUE)
      $withcomments - boolean indicating wether or not to include comments in canonicalized form
     */
    public function c14NGeneral($element, $exclusive = false, $withcomments = false)
    {
        /* IF PHP 5.2+ then use built in canonical functionality */
        $php_version = explode('.', PHP_VERSION);
        if (($php_version[0] > 5) || ($php_version[0] == 5 && $php_version[1] >= 2)) {
            return $element->C14N($exclusive, $withcomments);
        }
        /* Must be element */
        if (!$element instanceof DOMElement) {
            return;
        }
        /* Currently only exclusive XML is supported */
        if ($exclusive === false) {
            throw new Exception("Only exclusive canonicalization is supported in this version of PHP");
        }
        $copyDoc = new DOMDocument();
        $this->canonical($copyDoc, $element, $withcomments);
        return $copyDoc->saveXML($copyDoc->documentElement, LIBXML_NOEMPTYTAG);
    }
}