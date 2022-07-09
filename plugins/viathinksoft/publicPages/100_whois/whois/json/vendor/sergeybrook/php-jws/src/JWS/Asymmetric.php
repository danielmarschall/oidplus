<?php
/**
 * SBrook\JWS\Asymmetric
 */

namespace SBrook\JWS;

/**
 * Interface Asymmetric
 * @package SBrook\JWS
 */
interface Asymmetric {
	/**
	 * Set private key.
	 * @param $key - Private key.
	 * @param $pass - Private key password.
	 */
	public function setPrivateKey($key, $pass);

	/**
	 * Set public key.
	 * @param $key - Public key.
	 */
	public function setPublicKey($key);
}
