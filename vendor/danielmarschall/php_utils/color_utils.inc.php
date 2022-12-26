<?php

/*
 * Color Utils for PHP
 * Copyright 2019 - 2022 Daniel Marschall, ViaThinkSoft
 * Revision 2022-12-27
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

// Some of these functions were taken from other sources.

function RGB_TO_HSV($R, $G, $B) { // RGB Values:Number 0-255
                                  // HSV Results:Number 0-1
	$HSL = array();

	$var_R = ($R / 255);
	$var_G = ($G / 255);
	$var_B = ($B / 255);

	$var_Min = min($var_R, $var_G, $var_B);
	$var_Max = max($var_R, $var_G, $var_B);
	$del_Max = $var_Max - $var_Min;

	$V = $var_Max;

	if ($del_Max == 0) {
		$H = 0;
		$S = 0;
	} else {
		$S = $del_Max / $var_Max;

		$del_R = ((($var_Max - $var_R) / 6) + ($del_Max / 2)) / $del_Max;
		$del_G = ((($var_Max - $var_G) / 6) + ($del_Max / 2)) / $del_Max;
		$del_B = ((($var_Max - $var_B) / 6) + ($del_Max / 2)) / $del_Max;

		if      ($var_R == $var_Max) $H = $del_B - $del_G;
		else if ($var_G == $var_Max) $H = (1/3) + $del_R - $del_B;
		else if ($var_B == $var_Max) $H = (2/3) + $del_G - $del_R;
		else $H = 0;

		if ($H<0) $H++;
		if ($H>1) $H--;
	}

	return array($H, $S, $V);
}

function HSV_TO_RGB($H, $S, $V) { // HSV Values:Number 0-1
                                  // RGB Results:Number 0-255
	$RGB = array();

	if($S == 0) {
		$R = $G = $B = $V * 255;
	} else {
		$var_H = $H * 6;
		$var_i = floor( $var_H );
		$var_1 = $V * ( 1 - $S );
		$var_2 = $V * ( 1 - $S * (     $var_H - $var_i ));
		$var_3 = $V * ( 1 - $S * (1 - ($var_H - $var_i )));

		if       ($var_i == 0) { $var_R = $V     ; $var_G = $var_3  ; $var_B = $var_1 ; }
		else if  ($var_i == 1) { $var_R = $var_2 ; $var_G = $V      ; $var_B = $var_1 ; }
		else if  ($var_i == 2) { $var_R = $var_1 ; $var_G = $V      ; $var_B = $var_3 ; }
		else if  ($var_i == 3) { $var_R = $var_1 ; $var_G = $var_2  ; $var_B = $V     ; }
		else if  ($var_i == 4) { $var_R = $var_3 ; $var_G = $var_1  ; $var_B = $V     ; }
		else                   { $var_R = $V     ; $var_G = $var_1  ; $var_B = $var_2 ; }

		$R = $var_R * 255;
		$G = $var_G * 255;
		$B = $var_B * 255;
	}

	return array($R, $G, $B);
}

function rgb2html($r, $g=-1, $b=-1) {
	if (is_array($r) && sizeof($r) == 3) {
		list($r, $g, $b) = $r;
	}

	$r = intval($r);
	$g = intval($g);
	$b = intval($b);

	$r = dechex($r<0 ? 0 : ($r>255 ? 255 : $r));
	$g = dechex($g<0 ? 0 : ($g>255 ? 255 : $g));
	$b = dechex($b<0 ? 0 : ($b>255 ? 255 : $b));

	$color  = (strlen($r) < 2 ? '0' : '').$r;
	$color .= (strlen($g) < 2 ? '0' : '').$g;
	$color .= (strlen($b) < 2 ? '0' : '').$b;
	return '#'.$color;
}

function changeHueOfCSS($css_content, $h_shift=0, $s_shift=0, $v_shift=0) {
	// TODO: also support rgb() and rgba() color references (and maybe also hsl/hsla and color names?)
	// TODO: Bootstrap uses "--bs-link-color-rgb: 13,110,253;" which we must also accept
	$css_content = preg_replace_callback('@(:|,)\\s*#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})@ismU',
		function ($x) use ($h_shift, $s_shift, $v_shift) {
			if (strlen($x[2]) == 3) {
				$r = hexdec($x[2][0].$x[2][0]);
				$g = hexdec($x[2][1].$x[2][1]);
				$b = hexdec($x[2][2].$x[2][2]);
			} else {
				$r = hexdec($x[2][0].$x[2][1]);
				$g = hexdec($x[2][2].$x[2][3]);
				$b = hexdec($x[2][4].$x[2][5]);
			}
			list ($h,$s,$v) = RGB_TO_HSV($r, $g, $b);
			$h = (float)$h;
			$s = (float)$s;
			$v = (float)$v;
			$h = ($h + $h_shift); while ($h > 1) $h -= 1; while ($h < 0) $h += 1;
			$s = ($s + $s_shift); while ($s > 1) $s  = 1; while ($s < 0) $s  = 0;
			$v = ($v + $v_shift); while ($v > 1) $v  = 1; while ($v < 0) $v  = 0;
			list ($r,$g,$b) = HSV_TO_RGB($h, $s, $v);
			return ':'.rgb2html($r,$g,$b);
		}, $css_content);
        return $css_content;
}

function invertColorsOfCSS($css_content) {
	// TODO: also support rgb() and rgba() color references (and maybe also hsl/hsla and color names?)
	// TODO: Bootstrap uses "--bs-link-color-rgb: 13,110,253;" which we must also accept
	$css_content = preg_replace_callback('@(:|,)\\s*#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})@ismU',
		function ($x) {
			if (strlen($x[2]) == 3) {
				$r = hexdec($x[2][0].$x[2][0]);
				$g = hexdec($x[2][1].$x[2][1]);
				$b = hexdec($x[2][2].$x[2][2]);
			} else {
				$r = hexdec($x[2][0].$x[2][1]);
				$g = hexdec($x[2][2].$x[2][3]);
				$b = hexdec($x[2][4].$x[2][5]);
			}
			$r = 255 - $r;
			$g = 255 - $g;
			$b = 255 - $b;
			return ':'.rgb2html($r,$g,$b);
		}, $css_content);
        return $css_content;
}
