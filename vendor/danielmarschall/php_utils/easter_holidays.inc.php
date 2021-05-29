<?php

/*
 * Easter days (Germany)
 * Copyright 2010 Daniel Marschall, ViaThinkSoft
 * Version 2010-12-18
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


// Neujahr setzen (fester Feiertag am 1. Januar)
// Hl. Drei Kˆnige setzen (fester Feiertag am 6. Januar)
// Rosenmontag berechnen (beweglicher Feiertag; 48 Tage vor Ostern)
// Aschermittwoch berechnen (beweglicher Feiertag; 46 Tage vor Ostern)
// Karfreitag berechnen (beweglicher Feiertag; 2 Tage vor Ostern)
// Ostersonntag
// Ostermontag berechnen (beweglicher Feiertag; 1 Tag nach Ostern)
// Maifeiertag setzen (fester Feiertag am 1. Mai)
// Christi Himmelfahrt berechnen (beweglicher Feiertag; 39 Tage nach Ostern)
// Pfingstsonntag berechnen (beweglicher Feiertag; 49 Tage nach Ostern)
// Pfingstmontag berechnen (beweglicher Feiertag; 50 Tage nach Ostern)
// Fronleichnam berechnen (beweglicher Feiertag; 60 Tage nach Ostern)
// Mari‰ Himmelfahrt setzen (fester Feiertag am 15. August)
// Tag der deutschen Einheit setzen (fester Feiertag am 3. Oktober)
// Reformationstag setzen (fester Feiertag am 31. Oktober)
// Allerheiligen setzen (fester Feiertag am 1. November)
// Heiligabend setzen (fester 'Feiertag' am 24. Dezember)
// Erster Weihnachtstag setzen (fester 'Feiertag' am 25. Dezember)
// Zweiter Weihnachtstag setzen (fester 'Feiertag' am 26. Dezember)
// Sylvester setzen (fester 'Feiertag' am 31. Dezember)

function easter_sunday($year) {
	$J = date ("Y", mktime(0, 0, 0, 1, 1, $year));

	$a = $J % 19;
	$b = $J % 4;
	$c = $J % 7;
	$m = number_format (8 * number_format ($J / 100) + 13) / 25 - 2;
	$s = number_format ($J / 100 ) - number_format ($J / 400) - 2;
	$M = (15 + $s - $m) % 30;
	$N = (6 + $s) % 7;
	$d = ($M + 19 * $a) % 30;

	if ($d == 29) {
		$D = 28;
	} else if ($d == 28 and $a >= 11) {
		$D = 27;
	} else {
		$D = $d;
	}

	$e = (2 * $b + 4 * $c + 6 * $D + $N) % 7;

	$easter = mktime (0, 0, 0, 3, 21, $J) + (($D + $e + 1) * 86400);

	return $easter;
}


function get_easter_holidays($year) {
	$es = easter_sunday($year);
	$sd = 24 * 60 * 60;
	$days = array(
		'rose_monday' => $es - (48 * $sd),
		'shrove_tuesday' => $es - (47 * $sd),
		'ash_wednesday' => $es - (46 * $sd),
		'palm_sunday' => $es - (7 * $sd),
		'good_friday' => $es - (2 * $sd),
		'easter_sunday' => $es,
		'easter_monday' => $es + (1 * $sd),
		'low_sunday' => $es + (7 * $sd),
		'ascension_day' => $es + (39 * $sd),
		'whit_sunday' => $es + (49 * $sd),
		'whit_monday' => $es + (50 * $sd),
		'corpus_christi' => $es + (60 * $sd)
	);
	return($days);
}

function print_easter_days_german($year) {
	$days = get_easter_holidays($year);
	echo "Rosenmontag: ",
	     date ("d.m.Y", $days['rose_monday']), "<br>";
	echo "Fastnachtsdienstag: ",
	     date ("d.m.Y", $days['shrove_tuesday']), "<br>";
	echo "Aschermittwoch: ",
	     date ("d.m.Y", $days['ash_wednesday']), "<br>";
	echo "Palmsonntag: ",
	     date ("d.m.Y", $days['palm_sunday']), "<br>";
	echo "Karfreitag: ",
	     date ("d.m.Y", $days['good_friday']), "<br>";
	echo "Ostersonntag: ",
	     date ("d.m.Y", $days['easter_sunday']), "<br>";
	echo "Ostermontag: ",
	     date ("d.m.Y", $days['easter_monday']), "<br>";
	echo "Weiﬂer Sonntag: ",
	     date ("d.m.Y", $days['low_sunday']), "<br>";
	echo "Christi Himmelfahrt: ",
	     date ("d.m.Y", $days['ascension_day']), "<br>";
	echo "Pfingstsonntag: ",
	     date ("d.m.Y", $days['whit_sunday']), "<br>";
	echo "Pfingstmontag: ",
	     date ("d.m.Y", $days['whit_monday']), "<br>";
	echo "Fronleichnam: ",
	     date ("d.m.Y", $days['corpus_christi']), "<br>";
}

//print_easter_days_german(date("Y"));
