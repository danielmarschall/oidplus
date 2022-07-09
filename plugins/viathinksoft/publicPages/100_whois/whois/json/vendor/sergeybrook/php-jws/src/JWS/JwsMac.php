<?php
/**
 * SBrook\JWS\JwsMac
 */

namespace SBrook\JWS;

use SBrook\JWS\Exception\JwsException;

/**
 * Class JwsMac
 * @package SBrook\JWS
 * @throws JwsException:
 *  30. Secret key should be a non empty string
 */
class JwsMac extends Jws implements Symmetric {
	/**
	 * JWS signature secret key.
	 * @var string
	 */
	protected $secretKey = "";

	/**
	 * Default signature algorithm.
	 * @var string
	 */
	protected $defaultAlgo = "HS256";

	/**
	 * Signature algorithms map JWS => hash_hmac().
	 *
	 * JWS signature algorithms (RFC 7518, Section 3.2) - "alg":
	 *  HS256: HMAC using SHA-256 - Min recommended key length: 32 bytes
	 *  HS384: HMAC using SHA-384 - Min recommended key length: 48 bytes
	 *  HS512: HMAC using SHA-512 - Min recommended key length: 64 bytes
	 *
	 * @var array
	 */
	protected $algos = [
		"HS256" => "SHA256",
		"HS384" => "SHA384",
		"HS512" => "SHA512"
	];

	/**
	 * JwsMac constructor.
	 * @param string $key - JWS signature secret key.
	 * @throws JwsException
	 */
	public function __construct($key) {
		if (is_string($key) && strlen($key) > 0) {
			$this->secretKey = $key;
		} else {
			throw new JwsException("Secret key should be a non empty string", 30);
		}
	}

	/**
	 * JwsMac destructor.
	 */
	public function __destruct() {
		unset(
			$this->secretKey,
			$this->defaultAlgo,
			$this->algos
		);
	}

	/**
	 * Set JWS signature secret key - overwrites previously set key.
	 * @param string $key - JWS signature secret key.
	 * @param $pass - (Optional) Not in use.
	 * @throws JwsException
	 */
	public function setSecretKey($key, $pass = "") {
		if (is_string($key) && strlen($key) > 0) {
			$this->secretKey = $key;
		} else {
			throw new JwsException("Secret key should be a non empty string", 30);
		}
	}

	/**
	 * Create JWS from payload and optional header and sign it.
	 * @param string $payload - Payload.
	 * @param array $header - (Optional) Header data.
	 * @return string - JWS.
	 * @throws JwsException
	 */
	public function sign($payload, $header = []) {
		$d = $this->prepareSign($this->defaultAlgo, $payload, $header);
		return $d["h"] . "." . $d["p"] . "." . base64_encode(hash_hmac($this->algos[$d["alg"]], $d["h"] . "." . $d["p"], $this->secretKey, true));
	}

	/**
	 * Verify JWS signature.
	 * @param string $jws - JWS.
	 * @return bool - TRUE on valid signature, FALSE on invalid.
	 * @throws JwsException
	 */
	public function verify($jws) {
		$d = $this->prepareVerify($jws);
		return hash_equals($d["sig"], hash_hmac($this->algos[$d["alg"]], $d["h"] . "." . $d["p"], $this->secretKey, true));
	}

	/**
	 * Check validity of signature algorithm.
	 * @param string $algorithm - Algorithm name.
	 * @return bool - TRUE on valid algorithm, FALSE on invalid.
	 */
	protected function isValidAlgorithm(string $algorithm): bool {
		return array_key_exists(strtoupper($algorithm), $this->algos);
	}
}
