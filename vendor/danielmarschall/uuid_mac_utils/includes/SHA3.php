<?php /* -*- coding: utf-8; indent-tabs-mode: t; tab-width: 4 -*-
vim: ts=4 noet ai */

/**
	Streamable SHA-3 for PHP 5.2+, with no lib/ext dependencies!

	Copyright © 2018  Desktopd Developers

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU Lesser General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Lesser General Public License for more details.

	You should have received a copy of the GNU Lesser General Public License
	along with this program.  If not, see <https://www.gnu.org/licenses/>.

	@license LGPL-3+
	@file
*/

// Fixed small issues found by PHPstan by Daniel Marschall 02 August 2023


/**
	SHA-3 (FIPS-202) for PHP strings (byte arrays) (PHP 5.2.1+)
	PHP 7.0 computes SHA-3 about 4 times faster than PHP 5.2 - 5.6 (on x86_64)

	Based on the reference implementations, which are under CC-0
	Reference: http://keccak.noekeon.org/

	This uses PHP's native byte strings. Supports 32-bit as well as 64-bit
	systems. Also for LE vs. BE systems.
*/
class SHA3 {
	const SHA3_224 = 1;
	const SHA3_256 = 2;
	const SHA3_384 = 3;
	const SHA3_512 = 4;

	const SHAKE128 = 5;
	const SHAKE256 = 6;

	private $blockSize = null; // added DM 2 Aug 2023


	public static function init ($type = null) {
		switch ($type) {
			case self::SHA3_224: return new self (1152, 448, 0x06, 28);
			case self::SHA3_256: return new self (1088, 512, 0x06, 32);
			case self::SHA3_384: return new self (832, 768, 0x06, 48);
			case self::SHA3_512: return new self (576, 1024, 0x06, 64);
			case self::SHAKE128: return new self (1344, 256, 0x1f);
			case self::SHAKE256: return new self (1088, 512, 0x1f);
		}

		throw new Exception ('Invalid operation type');
	}


	/**
		Feed input to SHA-3 "sponge"
	*/
	public function absorb ($data) {
		if (self::PHASE_INPUT != $this->phase) {
			throw new Exception ('No more input accepted');
		}

		$rateInBytes = $this->rateInBytes;
		$this->inputBuffer .= $data;
		while (strlen ($this->inputBuffer) >= $rateInBytes) {
			list ($input, $this->inputBuffer) = array (
				substr ($this->inputBuffer, 0, $rateInBytes)
				, substr ($this->inputBuffer, $rateInBytes));

			$blockSize = $rateInBytes;
			for ($i = 0; $i < $blockSize; $i++) {
				$this->state[$i] = $this->state[$i] ^ $input[$i];
			}

			$this->state = self::keccakF1600Permute ($this->state);
			$this->blockSize = 0;
		}

		return $this;
	}

	/**
		Get hash output
	*/
	public function squeeze ($length = null) {
		$outputLength = $this->outputLength; // fixed length output
		if ($length && 0 < $outputLength && $outputLength != $length) {
			throw new Exception ('Invalid length');
		}

		if (self::PHASE_INPUT == $this->phase) {
			$this->finalizeInput ();
		}

		if (self::PHASE_OUTPUT != $this->phase) {
			throw new Exception ('No more output allowed');
		}
		if (0 < $outputLength) {
			$this->phase = self::PHASE_DONE;
			return $this->getOutputBytes ($outputLength);
		}

		$blockLength = $this->rateInBytes;
		list ($output, $this->outputBuffer) = array (
			substr ($this->outputBuffer, 0, $length)
			, substr ($this->outputBuffer, $length));
		$neededLength = $length - strlen ($output);
		$diff = $neededLength % $blockLength;
		if ($diff) {
			$readLength = (($neededLength - $diff) / $blockLength + 1)
				* $blockLength;
		} else {
			$readLength = $neededLength;
		}

		$read = $this->getOutputBytes ($readLength);
		$this->outputBuffer .= substr ($read, $neededLength);
		return $output . substr ($read, 0, $neededLength);
	}


	// internally used
	const PHASE_INIT = 1;
	const PHASE_INPUT = 2;
	const PHASE_OUTPUT = 3;
	const PHASE_DONE = 4;

	private $phase = self::PHASE_INIT;
	private $state; // byte array (string)
	private $rateInBytes; // positive integer
	private $suffix; // 8-bit unsigned integer
	private $inputBuffer = ''; // byte array (string): max length = rateInBytes
	private $outputLength = 0;
	private $outputBuffer = '';


	/**
	* @param int $rate
	* @param int $capacity
	* @param int $suffix
	* @param int $length
	*/
	public function __construct ($rate, $capacity, $suffix, $length = 0) {
		if (1600 != ($rate + $capacity)) {
			throw new Error ('Invalid parameters');
		}
		if (0 != ($rate % 8)) {
			throw new Error ('Invalid rate');
		}

		$this->suffix = $suffix;
		$this->state = str_repeat ("\0", 200);
		$this->blockSize = 0;

		$this->rateInBytes = $rate / 8;
		$this->outputLength = $length;
		$this->phase = self::PHASE_INPUT;
		return;
	}

	protected function finalizeInput () {
		$this->phase = self::PHASE_OUTPUT;

		$input = $this->inputBuffer;
		$inputLength = strlen ($input);
		if (0 < $inputLength) {
			$blockSize = $inputLength;
			for ($i = 0; $i < $blockSize; $i++) {
				$this->state[$i] = $this->state[$i] ^ $input[$i];
			}

			$this->blockSize = $blockSize;
		}

		// Padding
		$rateInBytes = $this->rateInBytes;
		$this->state[$this->blockSize] = $this->state[$this->blockSize]
			^ chr ($this->suffix);
		if (($this->suffix & 0x80) != 0
			&& $this->blockSize == ($rateInBytes - 1)) {
			$this->state = self::keccakF1600Permute ($this->state);
		}
		$this->state[$rateInBytes - 1] = $this->state[$rateInBytes - 1] ^ "\x80";
		$this->state = self::keccakF1600Permute ($this->state);
	}

	protected function getOutputBytes ($outputLength) {
		// Squeeze
		$output = '';
		while (0 < $outputLength) {
			$blockSize = min ($outputLength, $this->rateInBytes);
			$output .= substr ($this->state, 0, $blockSize);
			$outputLength -= $blockSize;
			if (0 < $outputLength) {
				$this->state = self::keccakF1600Permute ($this->state);
			}
		}

		return $output;
	}

	/**
		1600-bit state version of Keccak's permutation
	*/
	protected static function keccakF1600Permute ($state) {
		$lanes = str_split ($state, 8);
		$R = 1;
		$values = "\1\2\4\10\20\40\100\200";

		for ($round = 0; $round < 24; $round++) {
			// θ step
			$C = array ();
			for ($x = 0; $x < 5; $x++) {
				// (x, 0) (x, 1) (x, 2) (x, 3) (x, 4)
				$C[$x] = $lanes[$x] ^ $lanes[$x + 5] ^ $lanes[$x + 10]
					^ $lanes[$x + 15] ^ $lanes[$x + 20];
			}
			for ($x = 0; $x < 5; $x++) {
				//$D = $C[($x + 4) % 5] ^ self::rotL64 ($C[($x + 1) % 5], 1);
				$D = $C[($x + 4) % 5] ^ self::rotL64One ($C[($x + 1) % 5]);
				for ($y = 0; $y < 5; $y++) {
					$idx = $x + 5 * $y; // x, y
					$lanes[$idx] = $lanes[$idx] ^ $D;
				}
			}
			unset ($C, $D);

			// ρ and π steps
			$x = 1;
			$y = 0;
			$current = $lanes[1]; // x, y
			for ($t = 0; $t < 24; $t++) {
				list ($x, $y) = array ($y, (2 * $x + 3 * $y) % 5);
				$idx = $x + 5 * $y;
				list ($current, $lanes[$idx]) = array ($lanes[$idx]
					, self::rotL64 ($current
						, (($t + 1) * ($t + 2) / 2) % 64));
			}
			unset ($current);

			// χ step
			$temp = array ();
			for ($y = 0; $y < 5; $y++) {
				for ($x = 0; $x < 5; $x++) {
					$temp[$x] = $lanes[$x + 5 * $y];
				}
				for ($x = 0; $x < 5; $x++) {
					$lanes[$x + 5 * $y] = $temp[$x]
						^ ((~ (string)$temp[($x + 1) % 5]) & $temp[($x + 2) % 5]);

				}
			}
			unset ($temp);

			// ι step
			for ($j = 0; $j < 7; $j++) {
				$R = (($R << 1) ^ (($R >> 7) * 0x71)) & 0xff;
				if ($R & 2) {
					$offset = (1 << $j) - 1;
					$shift = $offset % 8;
					$octetShift = ($offset - $shift) / 8;
					$n = "\0\0\0\0\0\0\0\0";
					$n[$octetShift] = $values[$shift];

					$lanes[0] = $lanes[0]
						^ $n;
						//^ self::rotL64 ("\1\0\0\0\0\0\0\0", (1 << $j) - 1);
				}
			}
		}

		return implode ($lanes);
	}

	protected static function rotL64_64 ($n, $offset) {
		return ($n << $offset) & ($n >> (64 - $offset));
	}

	/**
		64-bit bitwise left rotation (Little endian)
	*/
	protected static function rotL64 ($n, $offset) {

		//$n = (binary) $n;
		//$offset = ((int) $offset) % 64;
		//if (8 != strlen ($n)) throw new Exception ('Invalid number');
		//if ($offset < 0) throw new Exception ('Invalid offset');

		$shift = $offset % 8;
		$octetShift = ($offset - $shift) / 8;
		$n = substr ($n, - $octetShift) . substr ($n, 0, - $octetShift);

		$overflow = 0x00;
		for ($i = 0; $i < 8; $i++) {
			$a = ord ($n[$i]) << $shift;
			$n[$i] = chr (0xff & $a | $overflow);
			$overflow = $a >> 8;
		}
		$n[0] = chr (ord ($n[0]) | $overflow);
		return $n;
	}

	/**
		64-bit bitwise left rotation (Little endian)
	*/
	protected static function rotL64One ($n) {
		list ($n[0], $n[1], $n[2], $n[3], $n[4], $n[5], $n[6], $n[7])
			= array (
				chr (((ord ($n[0]) << 1) & 0xff) ^ (ord ($n[7]) >> 7))
				,chr (((ord ($n[1]) << 1) & 0xff) ^ (ord ($n[0]) >> 7))
				,chr (((ord ($n[2]) << 1) & 0xff) ^ (ord ($n[1]) >> 7))
				,chr (((ord ($n[3]) << 1) & 0xff) ^ (ord ($n[2]) >> 7))
				,chr (((ord ($n[4]) << 1) & 0xff) ^ (ord ($n[3]) >> 7))
				,chr (((ord ($n[5]) << 1) & 0xff) ^ (ord ($n[4]) >> 7))
				,chr (((ord ($n[6]) << 1) & 0xff) ^ (ord ($n[5]) >> 7))
				,chr (((ord ($n[7]) << 1) & 0xff) ^ (ord ($n[6]) >> 7)));
		return $n;
	}
}


/*
$hexMsg = '7c815c384eee0f288ece27cced52a01603127b079c007378bc5d1e6c5e9e6d1c735723acbbd5801ac49854b2b569d4472d33f40bbb8882956245c366dc3582d71696a97a4e19557e41e54dee482a14229005f93afd2c4a7d8614d10a97a9dfa07f7cd946fa45263063ddd29db8f9e34db60daa32684f0072ea2a9426ecebfa5239fb67f29c18cbaa2af6ed4bf4283936823ac1790164fec5457a9cba7c767ca59392d94cab7448f50eb34e9a93a80027471ce59736f099c886dea1ab4cba4d89f5fc7ae2f21ccd27f611eca4626b2d08dc22382e92c1efb2f6afdc8fdc3d2172604f5035c46b8197d3';
$hexResult = 'dc2038c613a5f836bd3d7a4881b5b3bff3023da72d253e1b520bcad5162e181685662d40252bee982eb3214aa70ddf0a95c5d1031de9781266b1e0972fc9777d4a74164da68a5d4585f7a8e7438fe28d8af577306b8e2cbf6863c83431cc4c898dda50c94efd4925432fca36a6304790fbf4fefaeee279c01b8b6a8d1c275e3cb4e8bf17d880903fbaf27bfa65a2e3db8e285878a94955f6fc14f05a0fa2556994b8612bb7a494b4dd8b3cf1bc9e4bf833d4bfbf878c4d3bdc8fc70d26d7b7edaf0afe2f963dc6884c871c1475f4b92378b9824970e40da0a59780e84ac5138aa1efa46c1b50c3b045be59037c6a0c89e1d3cf246f1362794e8107b7cba74888f0bf4b905cfb9c33517f472bac16259809797f2fc883ffbdd7cede9518f891b9117de5ddc6d3e29fa56eb617f25e9eb1b66f7e46ed54c1d43ac07471d35c57b8c73bc68f5612ed042bff5e68634a4fb81e2ef0d92fff1e11e43fd6d9a935678d2fdd04e06061da3ba7de415b93c5a8db1653cf08de1866f5c3d33be32a3b8d2b7bb39e9745c6e88c782f220c367f945828b9b9250de71e8a14ec847bbeec2b1a486ce61731cef21b4a3a6353c2c705759fafa50ad33fb6abc23b45f28ee7736df6f59aaf38d59881547274cf9af2cfc8fc1ecadf81ab72e38abccd281df956f279bacc1796ad1f90d6930a5829bb95e94a8682a51a6743ae91b6c12c08e1465a';
$msg = pack ('H*', $hexMsg);
$result = pack ('H*', $hexResult);
$sponge = SHA3::init (SHA3::SHAKE128);
$sponge->absorb($msg);
assert($result == $sponge->squeeze($outputLength = strlen($result)));

$hexMsg = 'fc424eeb27c18a11c01f39c555d8b78a805b88dba1dc2a42ed5e2c0ec737ff68b2456d80eb85e11714fa3f8eabfb906d3c17964cb4f5e76b29c1765db03d91be37fc';
$hexResult = '66126e27da8c1600b68d0ed65e9f47c4165faa43dc4eb1b99ffeddc33e61e20b01b160c84740b0f9fe29fda1fb5eff2819d98c047cdd0cf8a0d396864e54a34657bd0c0355c75c77e5c3d9ad203e71fc2785a83d254b953277b262ee0a5bb7d0c24ed57faed4fdb96d5fd7820e6efeeb5a9e9df48c619c4872cf3b2516dbb28073273e2693544e271d6f0f64be8dc236ecd021c00039fd362a843dc3681b166cbc2407495e18903e469403807fe623f3648f799f18fbd60fff7705d07464e801e0aed4f2f0642b9a2c5cdd0c902b59b1da19a09375c1c13175b618091b8882a0e7205ee63a9219ecbcfa943a10d2d9a50c8c0b5d43b003f67ef0d52adbf9f659bb62fa6e00678bb8d4449648872a99eecdbb3dc381b5199fd500912afa93c63a6b23d00d0a416468fdab93aedd9115265be3a4440dd4029ff7f88d9755623e77f9430b934dae529be9a6b307b1b292ab5918eb24b14598554b4cc6269419c701494b7cba5b3d69f6cdcd5181fd03e0748d08e1e0aa5c4ec62c47877c1085873c016ef24e7e45da71d3db9db23b153cceda9a9ab5ccd8c5466cef29810098e976e4867075601f83a2d2cda1a476a1e990ce04c4567ffb99aac428922d9d8b25af68c36463d3aa4f689cd778f79e743e0bb5f935e6d45f978dcb2aed12dfcdca469556556e19f25d4c959c98785fb471d4bd1675d3b84742766d5ba4bff2a3f912';
$msg = pack ('H*', $hexMsg);
$result = pack ('H*', $hexResult);
$sponge = SHA3::init (SHA3::SHAKE256);
$sponge->absorb($msg);
assert($result == $sponge->squeeze($outputLength = strlen($result)));
*/

