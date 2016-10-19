<?php
/**
 * Created by PhpStorm.
 * User: zike
 * Date: 10/19/16
 * Time: 9:42 AM
 */

namespace CMCi\ExactTarget\WSSE;

use Exception;
/**
 * Class provided by Exact Target
 *
 * @codeCoverageIgnore
 * Class is a third-party library thus ignored
 */
class XMLSecurityKey
{
    const TRIPLEDES_CBC = 'http://www.w3.org/2001/04/xmlenc#tripledes-cbc';
    const AES128_CBC = 'http://www.w3.org/2001/04/xmlenc#aes128-cbc';
    const AES192_CBC = 'http://www.w3.org/2001/04/xmlenc#aes192-cbc';
    const AES256_CBC = 'http://www.w3.org/2001/04/xmlenc#aes256-cbc';
    const RSA_1_5 = 'http://www.w3.org/2001/04/xmlenc#rsa-1_5';
    const RSA_OAEP_MGF1P = 'http://www.w3.org/2001/04/xmlenc#rsa-oaep-mgf1p';
    const RSA_SHA1 = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';
    const DSA_SHA1 = 'http://www.w3.org/2000/09/xmldsig#dsa-sha1';
    private $cryptParams = array();
    public $type = 0;
    public $key = null;
    public $passphrase = "";
    public $iv = null;
    public $name = null;
    public $keyChain = null;
    public $isEncrypted = false;
    public $encryptedCtx = null;
    public $guid = null;
    public function __construct($type, $params = null)
    {
        switch ($type) {
            case (XMLSecurityKey::TRIPLEDES_CBC):
                $this->cryptParams['library'] = 'mcrypt';
                $this->cryptParams['cipher']  = MCRYPT_TRIPLEDES;
                $this->cryptParams['mode']    = MCRYPT_MODE_CBC;
                $this->cryptParams['method']  = 'http://www.w3.org/2001/04/xmlenc#tripledes-cbc';
                break;
            case (XMLSecurityKey::AES128_CBC):
                $this->cryptParams['library'] = 'mcrypt';
                $this->cryptParams['cipher']  = MCRYPT_RIJNDAEL_128;
                $this->cryptParams['mode']    = MCRYPT_MODE_CBC;
                $this->cryptParams['method']  = 'http://www.w3.org/2001/04/xmlenc#aes128-cbc';
                break;
            case (XMLSecurityKey::AES192_CBC):
                $this->cryptParams['library'] = 'mcrypt';
                $this->cryptParams['cipher']  = MCRYPT_RIJNDAEL_128;
                $this->cryptParams['mode']    = MCRYPT_MODE_CBC;
                $this->cryptParams['method']  = 'http://www.w3.org/2001/04/xmlenc#aes192-cbc';
                break;
            case (XMLSecurityKey::AES256_CBC):
                $this->cryptParams['library'] = 'mcrypt';
                $this->cryptParams['cipher']  = MCRYPT_RIJNDAEL_128;
                $this->cryptParams['mode']    = MCRYPT_MODE_CBC;
                $this->cryptParams['method']  = 'http://www.w3.org/2001/04/xmlenc#aes256-cbc';
                break;
            case (XMLSecurityKey::RSA_1_5):
                $this->cryptParams['library'] = 'openssl';
                $this->cryptParams['padding'] = OPENSSL_PKCS1_PADDING;
                $this->cryptParams['method']  = 'http://www.w3.org/2001/04/xmlenc#rsa-1_5';
                if (is_array($params) && !empty($params['type'])) {
                    if ($params['type'] == 'public' || $params['type'] == 'private') {
                        $this->cryptParams['type'] = $params['type'];
                        break;
                    }
                }
                throw new Exception('Certificate "type" (private/public) must be passed via parameters');
            case (XMLSecurityKey::RSA_OAEP_MGF1P):
                $this->cryptParams['library'] = 'openssl';
                $this->cryptParams['padding'] = OPENSSL_PKCS1_OAEP_PADDING;
                $this->cryptParams['method']  = 'http://www.w3.org/2001/04/xmlenc#rsa-oaep-mgf1p';
                $this->cryptParams['hash']    = null;
                if (is_array($params) && !empty($params['type'])) {
                    if ($params['type'] == 'public' || $params['type'] == 'private') {
                        $this->cryptParams['type'] = $params['type'];
                        break;
                    }
                }
                throw new Exception('Certificate "type" (private/public) must be passed via parameters');
            case (XMLSecurityKey::RSA_SHA1):
                $this->cryptParams['library'] = 'openssl';
                $this->cryptParams['method']  = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';
                if (is_array($params) && !empty($params['type'])) {
                    if ($params['type'] == 'public' || $params['type'] == 'private') {
                        $this->cryptParams['type'] = $params['type'];
                        break;
                    }
                }
                throw new Exception('Certificate "type" (private/public) must be passed via parameters');
                break;
            default:
                throw new Exception('Invalid Key Type');
        }
        $this->type = $type;
    }

    public function generateSessionKey()
    {
        $key = '';
        if (!empty($this->cryptParams['cipher']) && !empty($this->cryptParams['mode'])) {
            $keysize = mcrypt_module_get_algo_key_size($this->cryptParams['cipher']);
            /* Generating random key using iv generation routines */
            if (($keysize > 0) && ($td = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', $this->cryptParams['mode'], ''))) {
                if ($this->cryptParams['cipher'] == MCRYPT_RIJNDAEL_128) {
                    $keysize = 16;
                    if ($this->type == XMLSecurityKey::AES256_CBC) {
                        $keysize = 32;
                    } elseif ($this->type == XMLSecurityKey::AES192_CBC) {
                        $keysize = 24;
                    }
                }
                while (strlen($key) < $keysize) {
                    $key .= mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
                }
                mcrypt_module_close($td);
                $key       = substr($key, 0, $keysize);
                $this->key = $key;
            }
        }
        return $key;
    }

    public function loadKey($key, $isFile = false, $isCert = false)
    {
        if ($isFile) {
            $this->key = file_get_contents($key);
        } else {
            $this->key = $key;
        }
        if ($isCert) {
            $this->key = openssl_x509_read($this->key);
            openssl_x509_export($this->key, $str_cert);
            $this->key = $str_cert;
        }
        if ($this->cryptParams['library'] == 'openssl') {
            if ($this->cryptParams['type'] == 'public') {
                $this->key = openssl_get_publickey($this->key);
            } else {
                $this->key = openssl_get_privatekey($this->key, $this->passphrase);
            }
        } elseif ($this->cryptParams['cipher'] == MCRYPT_RIJNDAEL_128) {
            /* Check key length */
            switch ($this->type) {
                case (XMLSecurityKey::AES256_CBC):
                    if (strlen($this->key) < 25) {
                        throw new Exception('Key must contain at least 25 characters for this cipher');
                    }
                    break;
                case (XMLSecurityKey::AES192_CBC):
                    if (strlen($this->key) < 17) {
                        throw new Exception('Key must contain at least 17 characters for this cipher');
                    }
                    break;
            }
        }
    }

    private function encryptMcrypt($data)
    {
        $td       = mcrypt_module_open($this->cryptParams['cipher'], '', $this->cryptParams['mode'], '');
        $this->iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $this->key, $this->iv);
        $encrypted_data = $this->iv . mcrypt_generic($td, $data);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $encrypted_data;
    }

    private function decryptMcrypt($data)
    {
        $td        = mcrypt_module_open($this->cryptParams['cipher'], '', $this->cryptParams['mode'], '');
        $iv_length = mcrypt_enc_get_iv_size($td);
        $this->iv = substr($data, 0, $iv_length);
        $data     = substr($data, $iv_length);
        mcrypt_generic_init($td, $this->key, $this->iv);
        $decrypted_data = mdecrypt_generic($td, $data);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        if ($this->cryptParams['mode'] == MCRYPT_MODE_CBC) {
            $dataLen        = strlen($decrypted_data);
            $paddingLength  = substr($decrypted_data, $dataLen - 1, 1);
            $decrypted_data = substr($decrypted_data, 0, $dataLen - ord($paddingLength));
        }
        return $decrypted_data;
    }

    private function encryptOpenSSL($data)
    {
        if ($this->cryptParams['type'] == 'public') {
            if (!openssl_public_encrypt($data, $encrypted_data, $this->key, $this->cryptParams['padding'])) {
                throw new Exception('Failure encrypting Data');
            }
        } else {
            if (!openssl_private_encrypt($data, $encrypted_data, $this->key, $this->cryptParams['padding'])) {
                throw new Exception('Failure encrypting Data');
            }
        }
        return $encrypted_data;
    }

    private function decryptOpenSSL($data)
    {
        if ($this->cryptParams['type'] == 'public') {
            if (!openssl_public_decrypt($data, $decrypted, $this->key, $this->cryptParams['padding'])) {
                throw new Exception('Failure decrypting Data');
            }
        } else {
            if (!openssl_private_decrypt($data, $decrypted, $this->key, $this->cryptParams['padding'])) {
                throw new Exception('Failure decrypting Data');
            }
        }
        return $decrypted;
    }

    private function signOpenSSL($data)
    {
        if (!openssl_sign($data, $signature, $this->key)) {
            throw new Exception('Failure Signing Data');
        }
        return $signature;
    }

    private function verifyOpenSSL($data, $signature)
    {
        return openssl_verify($data, $signature, $this->key);
    }

    public function encryptData($data)
    {
        switch ($this->cryptParams['library']) {
            case 'mcrypt':
                return $this->encryptMcrypt($data);
                break;
            case 'openssl':
                return $this->encryptOpenSSL($data);
                break;
        }
    }

    public function decryptData($data)
    {
        switch ($this->cryptParams['library']) {
            case 'mcrypt':
                return $this->decryptMcrypt($data);
                break;
            case 'openssl':
                return $this->decryptOpenSSL($data);
                break;
        }
    }

    public function signData($data)
    {
        switch ($this->cryptParams['library']) {
            case 'openssl':
                return $this->signOpenSSL($data);
                break;
        }
    }

    public function verifySignature($data, $signature)
    {
        switch ($this->cryptParams['library']) {
            case 'openssl':
                return $this->verifyOpenSSL($data, $signature);
                break;
        }
    }

    public function getAlgorith()
    {
        return $this->cryptParams['method'];
    }

    public static function makeAsnSegment($type, $string)
    {
        switch ($type) {
            case 0x02:
                if (ord($string) > 0x7f) {
                    $string = chr(0) . $string;
                }
                break;
            case 0x03:
                $string = chr(0) . $string;
                break;
        }
        $length = strlen($string);
        if ($length < 128) {
            $output = sprintf("%c%c%s", $type, $length, $string);
        } elseif ($length < 0x0100) {
            $output = sprintf("%c%c%c%s", $type, 0x81, $length, $string);
        } elseif ($length < 0x010000) {
            $output = sprintf("%c%c%c%c%s", $type, 0x82, $length / 0x0100, $length % 0x0100, $string);
        } else {
            $output = null;
        }
        return ($output);
    }
    /* Modulus and Exponent must already be base64 decoded */
    public static function convertRSA($modulus, $exponent)
    {
        /* make an ASN publicKeyInfo */
        $exponentEncoding       = XMLSecurityKey::makeAsnSegment(0x02, $exponent);
        $modulusEncoding        = XMLSecurityKey::makeAsnSegment(0x02, $modulus);
        $sequenceEncoding       = XMLSecurityKey:: makeAsnSegment(0x30, $modulusEncoding . $exponentEncoding);
        $bitstringEncoding      = XMLSecurityKey::makeAsnSegment(0x03, $sequenceEncoding);
        $rsaAlgorithmIdentifier = pack("H*", "300D06092A864886F70D0101010500");
        $publicKeyInfo          = XMLSecurityKey::makeAsnSegment(0x30, $rsaAlgorithmIdentifier . $bitstringEncoding);
        /* encode the publicKeyInfo in base64 and add PEM brackets */
        $publicKeyInfoBase64 = base64_encode($publicKeyInfo);
        $encoding            = "-----BEGIN PUBLIC KEY-----\n";
        $offset              = 0;
        while ($segment = substr($publicKeyInfoBase64, $offset, 64)) {
            $encoding = $encoding . $segment . "\n";
            $offset += 64;
        }
        return $encoding . "-----END PUBLIC KEY-----\n";
    }

    public function serializeKey($parent)
    {
    }
}