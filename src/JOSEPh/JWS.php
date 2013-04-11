<?php

require_once dirname(__FILE__) . '/JWT.php';

class JOSEPh_JWS extends JOSEPh_JWT {
    function __construct($jwt) {
        $this->header = $jwt->header;
        $this->claims = $jwt->claims;
        $this->signature = $jwt->signature;
        $this->raw = $jwt->raw;
    }

    function sign($private_key_or_secret, $algorithm = 'HS256') {
        $this->header['alg'] = $algorithm;
        $this->signature = $this->_sign($private_key_or_secret);
        if (!$this->signature) {
            throw new JOSEPh_Exception('Signing failed because of unknown reason');
        }
        return $this;
    }

    function verify($public_key_or_secret) {
        if ($this->_verify($public_key_or_secret)) {
            return $this;
        } else {
            throw new JOSEPh_Exception_VerificationFailed('Signature verification failed');
        }
    }

    private function rsa($public_or_private_key) {
        $rsa = new Crypt_RSA();
        $rsa->loadKey($public_or_private_key);
        $rsa->setHash($this->digest());
        $rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
        return $rsa;
    }

    private function digest() {
        switch ($this->header['alg']) {
            case 'HS256':
            case 'RS256':
            case 'ES256':
                return 'sha256';
            case 'HS384':
            case 'RS384':
            case 'ES384':
                return 'sha384';
            case 'HS512':
            case 'RS512':
            case 'ES512':
                return 'sha512';
            default:
                throw new JOSEPh_Exception_UnexpectedAlgorithm('Unknown algorithm');
        }
    }

    private function _sign($private_key_or_secret) {
        $signature_base_string = implode('.', array(
            $this->compact((object) $this->header),
            $this->compact((object) $this->claims)
        ));
        switch ($this->header['alg']) {
            case 'HS256':
            case 'HS384':
            case 'HS512':
                return hash_hmac($this->digest(), $signature_base_string, $private_key_or_secret, true);
            case 'RS256':
            case 'RS384':
            case 'RS512':
                return $this->rsa($private_key_or_secret)->sign($signature_base_string);
            case 'ES256':
            case 'ES384':
            case 'ES512':
                throw new JOSEPh_Exception_UnexpectedAlgorithm('Algorithm not supported');
            default:
                throw new JOSEPh_Exception_UnexpectedAlgorithm('Unknown algorithm');
        }
    }

    private function _verify($public_key_or_secret) {
        $segments = explode('.', $this->raw);
        $signature_base_string = implode('.', array($segments[0], $segments[1]));
        switch ($this->header['alg']) {
            case 'HS256':
            case 'HS384':
            case 'HS512':
                return $this->signature === hash_hmac($this->digest(), $signature_base_string, $public_key_or_secret, true);
            case 'RS256':
            case 'RS384':
            case 'RS512':
                return $this->rsa($public_key_or_secret)->verify($signature_base_string, $this->signature);
            case 'ES256':
            case 'ES384':
            case 'ES512':
                throw new JOSEPh_Exception_UnexpectedAlgorithm('Algorithm not supported');
            default:
                throw new JOSEPh_Exception_UnexpectedAlgorithm('Unknown algorithm');
        }
    }
}
