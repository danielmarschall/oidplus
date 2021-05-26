<?php

if ($cookie_state == 1)
{
  if (isset($_COOKIE['TestCookie']))
  {
    setcookie('TestCookie', 'test', time());
    echo 'Cookies werden unterstützt!';
  }
  else
  {
    echo 'Es werden keine Cookies unterstützt!';
  }
}
else
{
  setcookie('TestCookie', 'test');
  header('Location: '.$_SERVER['PHP_SELF'].'?cookie_state=1');
}

?>