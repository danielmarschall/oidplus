<?php

// Mastercopy of this file:
// http://www.viathinksoft.de/~daniel-marschall/code/php/marschallHash.phps

function MHA($password, $iteratedSalt='', $iterations=1987, $binary_output=false) {

	// --------------------------------------------------------------------------------
	// MarschallHash: Uncrackable hash with multiple SHA1 iterations in base64 encoding
	// This function is pretty slow because of the iterations, but therefore secure
	// against offline attacks or rainbowtables. Also, the slowlyness of this hash
	// makes the creation of rainbow tables much harder.
	//
	// (C)Copyright 2011 Daniel Marschall, ViaThinkSoft. All rights reserved.
	// www.daniel-marschall.de / www.viathinksoft.de
	//
	// Notation of the hash name:
	// - MHA-1987 (resp. MHA-xxxx where xxxx stands for $iterations)
	// - MHAb-1987 is used for the binary output variant
	// - MD5MHA-1987 is used if $password is a (unsalted!) md5-hash
	// - MD5MHAb-1987 is used for the binary output variant.
	//
	// Default parameters:
	// iteratedSalt  = ''       --> you should change this value to something
	//                              user-specific (e.g. username) or something random
	// iterations    = 1987     --> you should ONLY change this value to a lower
	//                              one if you have performance issues
	// binary_output = no       --> base64 encoding is chosen by default
	//
	// Format:
	// - MHA has the same length as SHA1.
	// - MHA in base64 format has a constant length of 27 bytes and is case sensitive.
	// - MHA in binary format has a constant length of 20 bytes.
	//
	// Comparison 1:
	// x         = ''
	// SHA1(x)   = 'da39a3ee5e6b4b0d3255bfef95601890afd80709'                         (Len = 40)
	// SHA256(x) = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855' (Len = 64)
	// MD5(x)    = 'd41d8cd98f00b204e9800998ecf8427e'                                 (Len = 32)
	// MHA(x)    = 'UOLv7DgK5/4S7994FeSWZkHDJoQ'                                      (Len = 27)
	//
	// Comparison 2:
	// x         = 'The quick brown fox jumps over the lazy dog'
	// SHA1(x)   = '2fd4e1c67a2d28fced849ee1bb76e7391b93eb12'                         (Len = 40)
	// SHA256(x) = 'd7a8fbb307d7809469ca9abcb0082e4f8d5651e46d3cdb762d02d0bf37c9e592' (Len = 64)
	// MD5(x)    = '9e107d9d372bb6826bd81d3542a419d6'                                 (Len = 32)
	// MHA(x)    = 'Bqdd38sigmurBt6kU/0q99GWSnE'                                      (Len = 27)
	//
	// Mechanism:
	// MHA(x, iteratedSalt, iterations) = optionalBase64(rec(iterations, x, iteratedSalt))
	// rec[n, x, iteratedSalt] = binarySHA1(iteratedSalt || rec[n-1, x, iteratedSalt] || iteratedSalt)
	// rec[0, x, iteratedSalt] = x
	//
	// Recommended usage:
	// - Use the username as iteratedSalt
	//      MHA($password, $username) == $database_hash
	// - If you want to upgrade your existing user-database, e.g. already hashed with md5():
	//   a) Update your database:
	//      $database_hash_new = MHA($database_hash_old, $username)
	//   b) Compare with this variant:
	//      MHA(md5($password), $username) == $database_hash_new
	//      or
	//      MD5_MHA($password, $username) == $database_hash_new
	//
	// Revision: 2011-07-21 (fixed typo in 2013-03-09)
	// --------------------------------------------------------------------------------

	if ($iterations < 1) {
		trigger_error('at function ' . __FUNCTION__ . ': $iterations has to be greater or equal 1', E_USER_ERROR);
		return false;
	}

	$m = $password;

	for ($i=1; $i<=$iterations; $i++) {
		$m = sha1($iteratedSalt.$m.$iteratedSalt, true); // SHA1 with binary output
	}

	if (!$binary_output) {
		$m = base64_encode($m);

		// Remove single "=" at the end
		# $m = str_replace('=', '', $m);
		$m = substr($m, 0, 27);
	}

	return $m;
}

// --- The following functions are useable for database migration ---

function MD5_TO_MD5_MHA($md5_hash, $iteratedSalt='', $iterations=1987, $binary_output=false) {
	// Use this function to migrate a (unsalted!) md5 hash into a MD5MHA hash
	//
	// Actually, this is just an alias of MHA()

	return marschallHash($md5_hash, $iteratedSalt, $iterations, $binary_output);
}

function MD5_MHA($password, $iteratedSalt='', $iterations=1987, $binary_output=false) {
	// Use this function if you have a MD5MHA hash instead of a MHA hash
	//
	// MD5MHA() is equal to MHA(MD5()) where MD5() is unsalted!

	return MHA(md5($password), $iteratedSalt, $iterations, $binary_output);
}

function MD5MHA($password, $iteratedSalt='', $iterations=1987, $binary_output=false) {
	// Alias of MD5_MHA()

	return MD5_MHA($password, $iteratedSalt, $iterations, $binary_output);
}

function MHA_AddIterations($mha, $iteratedSalt='', $additionalIterations, $binary_output=false) {
	// This function converts a MHA with x itertions into a MHA with
	// x+additionalIterations iterations, if the iteratedSalt is equal.
	// Use this function if you want to upgrade your database to a higher MHA strength.
	//
	// Example:
	// MHA_AddIterations(MHA('test', 'salt', 1987), 'salt', 13) == MHA('test', 'salt', 1987+13);
	//
	// Of course, you cannot lower the strength of a MHA, so additionalIterations has to be >= 0.

	// Is it Base64 input?
	# if (strlen($mha) == 28) $mha = base64_decode($mha);
	if (strlen($mha) == 27) $mha = base64_decode($mha);

	// Is it now binary input?
	// (Que) Will there be problems if the input string looks like multibyte?
	if (strlen($mha) != 20) {
		trigger_error('at function ' . __FUNCTION__ . ': does not seem to be a MHA', E_USER_ERROR);
		return false;
	}

	if ($additionalIterations == 0) return $mha;

	if ($additionalIterations < 0) {
		trigger_error('at function ' . __FUNCTION__ . ': additionalIterations has to be 0 or higher.', E_USER_ERROR);
		return false;
	}

	return marschallHash($mha, $iteratedSalt, $additionalIterations, $binary_output);
}

?>
