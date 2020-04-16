<?php

require_once __DIR__ . '/../includes/oidplus.inc.php';

?><!DOCTYPE html>
<html lang="en">

<head>
	<title>OIDplus Setup</title>
	<meta name="robots" content="noindex">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="setup.css">
	<script src="../3p/sha3_js/sha3.js"></script><!-- https://github.com/emn178/js-sha3 -->
	<script src="setup.js"></script>
</head>

<body onload="rebuild()">

<h1>OIDplus Setup - Configuration File Generator</h1>

<p>Thank you very much for choosing OIDplus! This setup assistant will help you creating or updating the file <b>includes/config.inc.php</b>.
Setup does not automatically write to this file. Instead, you need to copy-paste the contents into the file.
Once OIDplus setup is finished, you can change the config file by hand, or run this setup assistant again.</p>

<div id="step1">
<h2>Step 1: Enter setup information</h2>

<h3>Administrator password</h3>

<form id="step1_form">
<p>Which admin password do you want?<br><input id="admin_password" type="password" autocomplete="new-password" onkeypress="rebuild()" onkeyup="rebuild()"> <span id="password_warn"></span></p>
<p>Please repeat the password input:<br><input id="admin_password2" type="password" autocomplete="new-password" onkeypress="rebuild()" onkeyup="rebuild()"> <span id="password_warn2"></span></p>

<h3>Database connectivity</h3>

<p><a href="../plugins/database/database_connectivity_diagram.png" target="_blank"><img src="../plugins/database/database_connectivity_diagram.png" width="20%" alt="Database connectivity diagram" title="Database connectivity diagram"></a></p>

Database plugin: <select name="db_plugin" onChange="dbplugin_changed()" id="db_plugin">

<?php

$ary = glob(__DIR__ . '/../plugins/database/'.'*'.'/plugin.inc.php');
foreach ($ary as $a) include $a;

foreach (get_declared_classes() as $c) {
	if (is_subclass_of($c, 'OIDplusDatabasePlugin')) {
		$selected = $c::name() == 'MySQL' ? ' selected="true"' : '';
		echo '<option value="'.htmlentities($c::name()).'"'.$selected.'>'.htmlentities($c::name()).'</option>';
	}
}

?>
</select>

<script>

setupdir = '<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI']; ?>';
rebuild_callbacks = [];
rebuild_config_callbacks = [];
plugin_combobox_change_callbacks = [];

function dbplugin_changed() {
	var e = document.getElementById("db_plugin");
	var strPlugin = e.options[e.selectedIndex].value;

	for (var i = 0; i < plugin_combobox_change_callbacks.length; i++) {
		var f = plugin_combobox_change_callbacks[i];
		f(strPlugin);
	}

	rebuild();
}

</script>

<div style="margin-left:50px">

<?php

$files = glob(__DIR__.'/../plugins/database/*/setup.part.html');
foreach ($files as $file) {
	echo file_get_contents($file);
}
if (count($files) == 0) {
	echo '<p><font color="red">ERROR: No database plugins were found! You CANNOT use OIDplus without database connection.</font></p>';
}

?>

</div>

<script>
dbplugin_changed();
</script>

<p>Tablename prefix (e.g. <b>oidplus_</b>):<br><input id="tablename_prefix" type="text" value="oidplus_" onkeypress="rebuild()" onkeyup="rebuild()"></p>

<h3>ReCAPTCHA</h3>

<p><input id="recaptcha_enabled" type="checkbox" onclick="rebuild()"> <label for="recaptcha_enabled">reCAPTCHA enabled</label> (<a href="https://developers.google.com/recaptcha/intro" target="_blank">more information and obtain key</a>)</p>
<p>reCAPTCHA Public key<br><input id="recaptcha_public" type="text" onkeypress="rebuild()" onkeyup="rebuild()"></p>
<p>reCAPTCHA Private key<br><input id="recaptcha_private" type="text" onkeypress="rebuild()" onkeyup="rebuild()"></p>

<h3>TLS</h3>

<p>SSL enforcement<br><select name="enforce_ssl" id="enforce_ssl" onclick="rebuild()">
<option value="0">No SSL available (don't redirect)</option>
<option value="1">Enforce SSL (always redirect)</option>
<option value="2" selected>Intelligent SSL detection (redirect if port 443 is open)</option>
</select></p>
</form>
</div>

<div id="step2">
<h2>Step 2: Initialize database</h2>
<p><font color="red"><b>If you already have an OIDplus database and just want to rebuild the config file, please skip this step.</b></font></p>
<p>Otherwise, import one of the following MySQL dumps in your database:</p>
<p><ul>
	<li><a href="struct_empty.sql.php" id="struct_1" target="_blank">Empty OIDplus database without example data</a><span id="struct_cli_1"></span><br><br></li>
	<li><a href="struct_with_examples.sql.php" id="struct_2" target="_blank">OIDplus database with example data</a><span id="struct_cli_2"></span><br><br></li>

</ul></p>
<p><font color="red">Warning: All data from the previous OIDplus instance will be deleted during the import.<br>If you already have an OIDplus database, skip to Step 3.</font></p>

</div>

<div id="step3">
<h2>Step 3: Save config.inc.php file</h2>
<p>Save following contents into the file <b>includes/config.inc.php</b>:</p>
<code><font color="darkblue"><div id="config"></div></font></code>
</div>

<div id="step4">
<h2>Step 4: Continue to next step</h2>
<p><input type="button" onclick="window.location.href='../'" value="Continue"></p>
<!-- <p><a href="../">Run the OIDplus system</a></p> -->
</div>

</body>
</html>
