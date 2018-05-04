<?php

class TSEP_Manifest
{
  /**
   * Transaction Key
   * Generated Manifest
   */
  public $transKey = "";      // Transaction Key
  public $manifest = "";      // Manifest

  /**
   * Merchant ID
   * Device ID
   * API Server Address
   */
  protected $_mid;           // Merchant ID
  protected $_deviceID;      // Device ID
  protected $_tsepApiServer; // API server

  /**
   * TSYS User ID
   * Password
   * Developer ID
   */
  private $_userID;           // User ID
  private $_password;         // Password
  private $_devID;            // Developer ID
  
  /**
   * Sets up the necessary properties for generating transaction keys
   * and manifests.  Edit the values below to reflect whatever you've
   * been given by TSYS during registration.
   */
  public function __construct () {
    // Merchant ID
    $this->_mid = "";

    // User ID
    $this->_userID = "";

    // Password
    $this->_password = "";

    // Developer ID
    $this->_devID = "";

    // Device ID
    $this->_deviceID = "";

    // API server
    $this->_tsepApiServer = "https://stagegw.transnox.com";
  }

  public function get_transaction_key () {
    $defaults = array (
      CURLOPT_URL => $this->_tsepApiServer . "/servlets/TransNox_API_Server",
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => $this->generate_key_request(),
      CURLOPT_RETURNTRANSFER => true
    );

    $ch = curl_init();
    curl_setopt_array($ch, $defaults);

    $resp = json_decode(curl_exec($ch));
    curl_close($ch);

    if ($resp && $resp->GenerateKeyResponse && $resp->GenerateKeyResponse->status === "PASS") {
      $this->transKey = $resp->GenerateKeyResponse->transactionKey;
    }

    return $this->transKey;
  }

  public function generate_manifest ($transKey = null) {
    if (!$transKey) {
      $transKey = $this->transKey;
    }

    $manstr = "";
    $manstr .= str_pad($this->_mid, 20);
    $manstr .= str_pad($this->_deviceID, 24);
    $manstr .= str_pad(0, 12, "0", STR_PAD_LEFT);
    $manstr .= str_pad(date("mdY"), 8);

    $cipher = "aes-128-cbc";
    $key = substr($transKey, 0, 16);

    $aesManifestStr = openssl_encrypt($manstr, $cipher, $key, OPENSSL_NO_PADDING, $key);
    $hexManifestStr = bin2hex($aesManifestStr);
    
    $hashTxnKey = hash_hmac("md5", $transKey, $transKey);
    $hashPre = substr($hashTxnKey, 0, 4);
    $hashSuf = substr($hashTxnKey, -4);

    $this->manifest = $hashPre . $hexManifestStr . $hashSuf;
  }

  public function get_js_view_url () {
    return $this->_tsepApiServer . 
      "/transit-tsep-web/jsView/" . $this->_deviceID . "?" . $this->manifest;
  }

  private function generate_key_request () {
    $req = array (
      "GenerateKey" => array (
        "mid" => $this->_mid,
        "userID" => $this->_userID,
        "password" => $this->_password,
        "developerID" => $this->_devID
      )
    );

    return json_encode($req);
  }
}