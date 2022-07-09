<?php
/**
 * Class files autoloader.
 */

spl_autoload_register(function ($className) {
	$search = ["\\", "SBrook"];
	$replace = [DIRECTORY_SEPARATOR, __DIR__];

	/** @noinspection PhpIncludeInspection */
	include(str_replace($search, $replace, $className).".php");
});
