<?php
/**
 * SBrook\JWS\Jws
 */

namespace SBrook\JWS;

use SBrook\JWS\Exception\JwsException;

/**
 * Class Jws
 * @package SBrook\JWS
 * @throws JwsException:
 *  Encode:
 *   10. Header should be an array
 *   11. Payload should be a non empty string
 *   12. Unknown signature algorithm in header
 *  Decode:
 *   20. JWS should be a non empty string
 *   21. Invalid JWS header
 *   22. Error while decoding JWS header
 *   23. Error while decoding JWS payload
 */
abstract class Jws {
	/**
	 * Create JWS from payload and optional header and sign it.
	 * @param $payload - Payload.
	 * @param $header - Header data.
	 */
	abstract public function sign($payload, $header);

	/**
	 * Verify JWS signature.
	 * @param $jws - JWS.
	 */
	abstract public function verify($jws);

	/**
	 * Check validity of signature algorithm.
	 * @param string $algorithm - Algorithm name.
	 * @return bool - TRUE on valid algorithm, FALSE on invalid.
	 */
	abstract protected function isValidAlgorithm(string $algorithm): bool;

	/**
	 * Get JWS header.
	 * @param string $jws - JWS.
	 * @return array - Decoded JWS header.
	 * @throws JwsException
	 */
	public function getHeader($jws) {
		if (is_string($jws) && strlen($jws) > 0) {
			list($h, , ) = explode(".", $jws);
			$header = json_decode(base64_decode($h, true), true);
			if (is_null($header)) {
				throw new JwsException("Error while decoding JWS header", 22);
			} else {
				return $header;
			}
		} else {
			throw new JwsException("JWS should be a non empty string", 20);
		}
	}

	/**
	 * Get JWS payload.
	 * @param string $jws - JWS.
	 * @return string - Decoded JWS payload.
	 * @throws JwsException
	 */
	public function getPayload($jws) {
		if (is_string($jws) && strlen($jws) > 0) {
			list(, $p, ) = explode(".", $jws);
			$payload = base64_decode($p, true);
			if ($payload) {
				return $payload;
			} else {
				throw new JwsException("Error while decoding JWS payload", 23);
			}
		} else {
			throw new JwsException("JWS should be a non empty string", 20);
		}
	}

	/**
	 * Validate and prepare data to sign JWS.
	 * @param string $defaultAlgo - Default signature algorithm name.
	 * @param string $payload - Payload.
	 * @param array $header - Header data.
	 * @return array - Required data to sign JWS.
	 * @throws JwsException
	 */
	protected function prepareSign($defaultAlgo, $payload, $header): array {
		if (is_array($header)) {
			if (is_string($payload) && strlen($payload) > 0) {
				// Remove header parameters with empty string values:
				foreach ($header as $key => $value) {
					if (is_string($value) && strlen($value) == 0) {
						unset($header[$key]);
					}
				}

				// If not specified, set default signature algorithm:
				if (!array_key_exists("alg", $header)) {
					$header["alg"] = $defaultAlgo;
				}

				// Don't trust anyone:
				$header["alg"] = strtoupper($header["alg"]);

				if ($this->isValidAlgorithm($header["alg"])) {
					return [
						"alg" => $header["alg"],
						"h" => base64_encode(json_encode($header)),
						"p" => base64_encode($payload)
					];
				} else {
					throw new JwsException("Unknown signature algorithm in header", 12);
				}
			} else {
				throw new JwsException("Payload should be a non empty string", 11);
			}
		} else {
			throw new JwsException("Header should be an array", 10);
		}
	}

	/**
	 * Validate and prepare data to verify JWS.
	 * @param string $jws - JWS.
	 * @return array - Required data to verify JWS.
	 * @throws JwsException
	 */
	protected function prepareVerify($jws): array {
		if (is_string($jws) && strlen(trim($jws)) > 0) {
			list($h, $p, $s) = explode(".", $jws);
			$header = json_decode(base64_decode($h, true), true);

			if (is_array($header) && array_key_exists("alg", $header) && $this->isValidAlgorithm($header["alg"])) {
				return [
					"alg" => strtoupper($header["alg"]),
					"sig" => base64_decode($s),
					"h" => $h,
					"p" => $p
				];
			} else {
				throw new JwsException("Invalid JWS header", 21);
			}
		} else {
			throw new JwsException("JWS should be a non empty string", 20);
		}
	}
}
