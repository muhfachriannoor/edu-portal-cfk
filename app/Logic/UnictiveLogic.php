<?php

namespace App\Logic;

use Nullix\CryptoJsAes\CryptoJsAes;

class UnictiveLogic
{

    private $password = '1324576890abcdef1324576890abcdef';
    private $iv = "abcdef1324576890abcdef1324576890";
    public function __construct()
    {
    }

    public function hashData($plaintext){
        $output = false;

        $encrypt_method = "AES-256-CBC";
        $secret_key = $this->password;
        $secret_iv = $this->iv;
        $key = hash('sha256', $secret_key);
        $iv = substr(hash('sha256', $secret_iv), 0, 16);
    
                $output = openssl_encrypt($plaintext, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
        return $output;
    }

    public function unHashData($encrypted_data)
    {
        $encrypt_method = "AES-256-CBC";
        $secret_key = $this->password;
        $secret_iv = $this->iv;
        $key = hash('sha256', $secret_key);
        $iv = substr(hash('sha256', $secret_iv), 0, 16);
        $output = openssl_decrypt(base64_decode($encrypted_data), $encrypt_method, $key, 0, $iv);
        return $output;
    }
    function generateRandomString($length = 10) {
        $characters = 'ACDEFHJKLMNPQRTWXVY34789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

}
