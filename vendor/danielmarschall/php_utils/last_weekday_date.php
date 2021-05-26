<?php

function get_last_weekday_date($dow) {
	for ($i=0; $i<=6; $i++) {
		$d = ftime()-$i*86400;
		$e = date('N', $d);
		if ($e == $dow) {
			return date('d.m.Y', $d);
		}
	}
}
