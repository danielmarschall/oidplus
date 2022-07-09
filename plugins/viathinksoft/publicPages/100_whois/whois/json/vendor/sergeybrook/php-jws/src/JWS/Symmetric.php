<?php
/**
 * SBrook\JWS\Symmetric
 */

namespace SBrook\JWS;

/**
 * Interface Symmetric
 * @package SBrook\JWS
 */
interface Symmetric {
	/**
	 * Set secret key.
	 * @param $key - Secret key.
	 * @param $pass - Secret key password.
	 */
	public function setSecretKey($key, $pass);
}
