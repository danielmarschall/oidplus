<?php

include __DIR__.'/../includes/oidplus.inc.php';

header('Content-Type:text/plain');

echo "localpath(null,false): ".OIDplus::localpath(null,false)."\n";
echo "localpath(null,true): ".OIDplus::localpath(null,true)."\n";
echo "localpath(__FILE__,false): ".OIDplus::localpath(__FILE__,false)."\n";
echo "localpath(__FILE__,true): ".OIDplus::localpath(__FILE__,true)."\n";
echo "localpath(__DIR__,false): ".OIDplus::localpath(__DIR__,false)."\n";
echo "localpath(__DIR__,true): ".OIDplus::localpath(__DIR__,true)."\n";

echo "webpath(null,false): ".OIDplus::webpath(null,false)."\n";
echo "webpath(null,true): ".OIDplus::webpath(null,true)."\n";
echo "webpath(__FILE__,false): ".OIDplus::webpath(__FILE__,false)."\n";
echo "webpath(__FILE__,true): ".OIDplus::webpath(__FILE__,true)."\n";
echo "webpath(__DIR__,false): ".OIDplus::webpath(__DIR__,false)."\n";
echo "webpath(__DIR__,true): ".OIDplus::webpath(__DIR__,true)."\n";
