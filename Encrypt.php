<?php

namespace Main;

class Encrypt
{
  private const encryptionKey = 'c^X%&xhmUcES+rct5DAL3=b7th&AC3^3A%yG7HN9xzRL+A-YXD_6TsSefX?sYezVe$@u&zpLT-p5?F*MTv=!wcuSHSQ2g^XvJPZYv?t9rA=Qq-zs2*32%^G!BJrYrs8+';

# http://php.net/manual/en/function.openssl-get-cipher-methods.php
  private const encryptionMethod = 'AES-256-OFB';

  public static function encrypt($data){
    $ivlen = openssl_cipher_iv_length(self::encryptionMethod);
    $iv = openssl_random_pseudo_bytes($ivlen);
    $ciphertext_raw = openssl_encrypt($data, self::encryptionMethod, self::encryptionKey, $options=OPENSSL_RAW_DATA, $iv);
    $hmac = hash_hmac('sha256', $ciphertext_raw, self::encryptionKey, $as_binary=true);
    $ciphertext = base64_encode( $iv.$hmac.$ciphertext_raw );
    return $ciphertext;
  }

  public static function decrypt($data){
    $c = base64_decode($data);
    $ivlen = openssl_cipher_iv_length(self::encryptionMethod);
    $iv = substr($c, 0, $ivlen);
    $hmac = substr($c, $ivlen, $sha2len=32);
    $ciphertext_raw = substr($c, $ivlen+$sha2len);
    $plaintext = openssl_decrypt($ciphertext_raw, self::encryptionMethod, self::encryptionKey, $options=OPENSSL_RAW_DATA, $iv);
    $calcmac = hash_hmac('sha256', $ciphertext_raw, self::encryptionKey, $as_binary=true);
    if (hash_equals($hmac, $calcmac)){
      return $plaintext;
    }
  }
}