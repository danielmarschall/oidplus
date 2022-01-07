<?php

/*
 * X.509 Utilities for PHP
 * Copyright 2011-2021 Daniel Marschall, ViaThinkSoft
 * Version 2021-12-29
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

# define('OPENSSL_EXEC', 'openssl');
# define('OPENSSL_EXEC', 'torify openssl');
define('OPENSSL_EXEC', 'vtor -cr 1 -- openssl');

# ToDo: For every function 2 modes: certFile, certPEM

function x_509_matching_issuer($cert, $issuer) {
	exec(OPENSSL_EXEC.' verify -purpose any -CApath /dev/null -CAfile '.escapeshellarg($issuer).' '.escapeshellarg($cert), $out, $code);
	$out = implode("\n", $out);
# Ab 1.0 wird hier ein Errorcode zurÃ¼ckgeliefert
#	if ($code != 0) return false;

# TODO
#                                  error 20 at 0 depth lookup:unable to get local issuer certificate
	$chain0_ok = strpos($out, "error 2 at 1 depth lookup:unable to get issuer certificate") !== false;
	$all_ok = substr($out, -2) == 'OK';

	$ok = $chain0_ok | $all_ok;

	return $ok;
}

function x_509_is_crl_file($infile) { # Only PEM files
	$cx = file($infile);
	return trim($cx[0]) == '-----BEGIN X509 CRL-----';
}

function x_509_chain($infile, $CApath) {
	$chain = array();
	$chain[] = $infile;

	while (true) {
		$out = array();
		exec(OPENSSL_EXEC.' x509 -issuer_hash -in '.escapeshellarg($infile).' -noout', $out, $code);
		if ($code != 0) return false;
		$hash = $out[0];
		unset($out);

#		$ary = glob($CApath . $hash . '.*');
#		$aryr = glob($CApath . $hash . '.r*');

		$ary = array();
		$aryr = array();
		$all_trusted = @glob($CApath . '*.pem');
		if ($all_trusted) foreach ($all_trusted as &$a) {
			if (x_509_is_crl_file($a)) {
				$out = array();
				exec(OPENSSL_EXEC.' crl -hash -noout -in '.escapeshellarg($a), $out, $code);
				if ($code != 0) return false;
				$this_hash = trim($out[0]);
				unset($out);
# echo "CRL $a : $this_hash == $hash<br>\n";
				if ($this_hash == $hash) {
					$aryr[] = $a;
				}
				if ($code != 0) return false;
			} else {
				$out = array();
				exec(OPENSSL_EXEC.' x509 -subject_hash -noout -in '.escapeshellarg($a), $out, $code);
				if ($code != 0) return false;
				$this_hash = trim($out[0]);
				unset($out);
# echo "CERT $a : $this_hash == $hash<br>\n";
				if ($this_hash == $hash) {
					$ary[] = $a;
				}
			}
		}

		$found = false;
# echo "Searching issuer for $infile... (Hash = $hash)<br>\n";
		foreach ($ary as &$a) {
			if (in_array($a, $aryr)) continue;

# echo "Check $a...<br>\n";
			if (x_509_matching_issuer($infile, $a)) {
# echo "Found! New file is $a<br>\n";
				$found = true;
				$infile = $a;

				if (in_array($a, $chain)) {
					# echo "Finished.\n";
					return $chain;
				}

				$chain[] = $a;
				break;
			}
		}
		if (!$found) {
			# echo "No issuer found!\n";
			return false;
		}
	}
}

function x_509_get_ocsp_uris($infile) {
	exec(OPENSSL_EXEC.' x509 -ocsp_uri -in '.escapeshellarg($infile).' -noout', $out, $code);
	if ($code != 0) return false;
	return $out;
}


// TODO: Needs caching, otherwise the page is too slow
function x_509_ocsp_check_chain($infile, $CApath) {
	$x = x_509_chain($infile, $CApath);

	if ($x === false) {
		return 'Error: Could not complete chain!';
	}

	# echo 'Chain: ';
	# print_r($x);

	$found_ocsp = false;
	$diag_nonce_err = false;
	$diag_verify_err = false;
	$diag_revoked = false;
	$diag_unknown = false;

	foreach ($x as $n => &$y) {
		if (isset($x[$n+1])) {
			$issuer = $x[$n+1];
		} else {
			$issuer = $y; // Root
		}

		$uris = x_509_get_ocsp_uris($y);

		foreach ($uris as &$uri) {
			$found_ocsp = true;

			$out = array();
			$xx = parse_url($uri);
			$host = $xx['host'];
#			$cmd = OPENSSL_EXEC." ocsp -issuer ".escapeshellarg($issuer)." -cert ".escapeshellarg($y)." -url ".escapeshellarg($uri)." -CApath ".escapeshellarg($CApath)." -VAfile ".escapeshellarg($issuer)." -nonce -header 'HOST' ".escapeshellarg($host)." -header 'User-Agent' 'Mozilla/5.0 (Windows NT 6.1; rv23.0) Gecko/20100101 Firefox/23.0' 2>&1" /* -text */;
# TODO: trusted.pem nicht hartcoden
			$cmd = OPENSSL_EXEC." ocsp -issuer ".escapeshellarg($issuer)." -cert ".escapeshellarg($y)." -url ".escapeshellarg($uri)." -CAfile ".escapeshellarg($CApath.'/../trusted.pem')." -VAfile ".escapeshellarg($issuer)." -nonce -header 'HOST' ".escapeshellarg($host)." -header 'User-Agent' 'Mozilla/5.0 (Windows NT 6.1; rv23.0) Gecko/20100101 Firefox/23.0' 2>&1" /* -text */;
#echo $cmd;
			exec($cmd, $out, $code);
			if ($code != 0) {
				if (($out[0] == 'Error querying OCSP responsder') ||
				    ($out[0] == 'Error querying OCSP responder')) {
					# TODO: openssl has a typo 'Error querying OCSP responsder'
					# TODO: why does this error occour for comodo CA?
					return "Error querying OCSP responder (Code $code)";
				}
				# print_r($out);
				return 'Error: OpenSSL-Exec failure ('.$code.')!';
			}

			$outc = implode("\n", $out);
			if (strpos($outc, "Response verify OK") === false) $diag_verify_err = true;
			if (strpos($outc, "WARNING: no nonce in response") !== false) $diag_nonce_err = true;
			# We are currently not watching for other warnings (ToDo)

			if (strpos($outc, "$y: unknown") !== false) {
				$diag_unknown = true;
			} else if (strpos($outc, "$y: revoked") !== false) {
				$diag_revoked = true;
			} else if (strpos($outc, "$y: good") === false) {
#echo "C = $outc<br>\n";
#Ã TODO:
# COMODO sagt
# C = Responder Error: unauthorized
# STARTCOM sagt
# C = Responder Error: malformedrequest
				return "Error: Unexpected OCSP state! ($outc)";
			}

			# print_r($out);
			unset($out);
		}
	}

	# echo "Found OCSP = ".($found_ocsp ? 1 : 0)."\n";
	# echo "Diag Nonce Error = ".($diag_nonce_err ? 1 : 0)."\n";
	# echo "Diag Verify Error = ".($diag_verify_err ? 1 : 0)."\n";
	# echo "Diag Revoked Error = ".($diag_revoked ? 1 : 0)."\n";
	# echo "Diag Unknown Error = ".($diag_unknown ? 1 : 0)."\n";

	if (!$found_ocsp) {
		return 'No OCSP responders found in chain.';
	}

	if ($diag_verify_err) {
		return 'Error: OCSP Verification failure!';
	}

	if ($diag_revoked) {
		return 'Error: Some certs are revoked!';
	}

	if ($diag_unknown) {
		return 'Warning: Some certs have unknown state!';
	}

	if ($diag_nonce_err) {
		return 'OK, but NONCE missing';
	}

	return 'OK';
}

function _opensslVerify($cert, $mode = 0, $crl_mode = 0) {
	# mode
        # 0 = cert is a file
        # 1 = cert is pem string

	# crl_mode
	# 0 = no crl check
	# 1 = 1 crl check
	# 2 = all crl check

	$params = '';
	if ($crl_mode == 0) {
		$params = '';
	} else if ($crl_mode == 1) {
		$params = '-crl_check ';
	} else if ($crl_mode == 2) {
		$params = '-crl_check_all ';
	} else {
		return false;
	}

        if ($mode == 0) {
#		$cmd = OPENSSL_EXEC.' verify '.$params.' -CApath '.escapeshellarg(__DIR__.'/../ca/trusted/').' '.escapeshellarg($cert);
		$cmd = OPENSSL_EXEC.' verify '.$params.' -CAfile '.escapeshellarg(__DIR__.'/../ca/trusted.pem').' '.escapeshellarg($cert);
        } else if ($mode == 1) {
#		$cmd = 'echo '.escapeshellarg($cert).' | '.OPENSSL_EXEC.' verify '.$params.' -CApath '.escapeshellarg(__DIR__.'/../ca/trusted/');
		$cmd = 'echo '.escapeshellarg($cert).' | '.OPENSSL_EXEC.' verify '.$params.' -CAfile '.escapeshellarg(__DIR__.'/../ca/trusted.pem');
        } else {
                return false;
        }
	$out = array();
	exec($cmd, $out, $code);

        if ($code != 0) return false;

	return $out;
}

function opensslVerify($cert, $mode = 0) {
	# 0 = cert is a file
	# 1 = cert is pem string

	$out = _opensslVerify($cert, $mode, 0);
	if ($out === false) return 'Internal error';
	$outtext = implode("\n", $out);

        $out_crl = _opensslVerify($cert, $mode, 2);
        if ($out_crl === false) return 'Internal error';
        $outtext_crl = implode("\n", $out_crl);

	if (strpos($outtext, "unable to get local issuer certificate") !== false) {
                return 'CA unknown';
	} else if (strpos($outtext, "certificate signature failure") !== false) {
                return 'Fraudulent!';
	}

	$stat_expired = (strpos($outtext, "certificate has expired") !== false);
	$stat_revoked = (strpos($outtext_crl, "certificate revoked") !== false);

	# (ToDo) We are currently not looking for warnings
	# $stat_crl_expired = (strpos($outtext_crl, "CRL has expired") !== false);

	if ($stat_expired && $stat_revoked) {
		return 'Expired & Revoked';
	} else if ($stat_revoked) {
		return 'Revoked';
	} else if ($stat_expired) {
		return 'Expired';
	}

	if (strpos($out[0], ': OK') !== false) {
		return 'Verified';
	}

	return 'Unknown error';
}

function getTextdump($cert, $mode = 0, $format = 0) {
	# mode
	# 0 = cert is a file
	# 1 = cert is pem string

	# format
	# 0 = normal
	# 1 = nameopt

	if ($format == 0) {
		$params = '';
	} else if ($format == 1) {
		$params = ' -nameopt "esc_ctrl, esc_msb, sep_multiline, space_eq, lname"';
	} else {
		return false;
	}

	if ($mode == 0) {
		exec(OPENSSL_EXEC.' x509 -noout -text'.$params.' -in '.escapeshellarg($cert), $out, $code);
	} else if ($mode == 1) {
		exec('echo '.escapeshellarg($cert).' | '.OPENSSL_EXEC.' x509 -noout -text'.$params, $out, $code);
	} else {
		return false;
	}

	if ($code != 0) return false;

	$text = implode("\n", $out);

	$text = str_replace("\n\n", "\n", $text); # TODO: repeat until no \n\n exist anymore

	return $text;
}

function getAttributes($cert, $mode = 0, $issuer = false, $longnames = false) {
	# mode
	# 0 = cert is a file
	# 1 = cert is pem string

	if ($longnames) {
		$params = ' -nameopt "esc_ctrl, esc_msb, sep_multiline, space_eq, lname"';
	} else {
		$params = ' -nameopt "esc_ctrl, esc_msb, sep_multiline, space_eq"';
	}

	if ($issuer) {
		$params .= ' -issuer';
	} else {
		$params .= ' -subject';
	}

	if ($mode == 0) {
		exec(OPENSSL_EXEC.' x509 -noout'.$params.' -in '.escapeshellarg($cert), $out, $code);
	} else if ($mode == 1) {
		exec('echo '.escapeshellarg($cert).' | '.OPENSSL_EXEC.' x509 -noout'.$params, $out, $code);
	} else {
		return false;
	}

	$attributes = array();
	foreach ($out as $n => &$o) {
		if ($n == 0) continue;
		preg_match("|    (.*) = (.*)$|ismU", $o, $m);
		if (!isset($attributes[$m[1]])) $attributes[$m[1]] = array();
		$attributes[$m[1]][] = $m[2];
	}

	return $attributes;
}

function openssl_get_sig_base64($cert, $mode = 0) {
	# mode
	# 0 = cert is a file
	# 1 = cert is pem string

	$params = '';

	$out = array();
	if ($mode == 0) {
		exec(OPENSSL_EXEC.' x509 -noout'.$params.' -in '.escapeshellarg($cert), $out, $code);
	} else if ($mode == 1) {
		exec('echo '.escapeshellarg($cert).' | '.OPENSSL_EXEC.' x509 -noout'.$params, $out, $code);
	} else {
		return false;
	}
	$dump = implode("\n", $out);

/*

    Signature Algorithm: sha1WithRSAEncryption
        65:f0:6f:f0:1d:66:a4:fe:d1:38:85:6f:5e:06:7b:f3:a7:08:
	...
	1a:13:37

*/

	$regex = "@\n {4}Signature Algorithm: (\S+)\n(( {8}([a-f0-9][a-f0-9]:){18}\n)* {8}([a-f0-9][a-f0-9](:[a-f0-9][a-f0-9]){0,17}\n))@sm";
	preg_match_all($regex, "$dump\n", $m);
	if (!isset($m[2][0])) return false;
	$x = preg_replace("@[^a-z0-9]@", "", $m[2][0]);
	$x = hex2bin($x);
	return base64_encode($x);
}
