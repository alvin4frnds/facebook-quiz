<?php

function add_action($coreAction = "", $functionName = null, $priority = 10, $varCount = 0) {
	if (is_null($functionName)) $functionName = "Not Specified";
	elseif (is_string($functionName)) $functionName .= "()";
	elseif (is_array($functionName)) $functionName = get_class($functionName[0]). "::".$functionName[1];
	else $functionName = "Couldn't decode";

	$info = "Adding action: to '{$coreAction}', with '{$functionName}', priority '{$priority}', vars '{$varCount}'";
	logMe($info);
}

function register_activation_hook($file, $array = []) {
	logMe("Registering file: '{$file}', on '".json_encode($array)."'");
}

function plugin_dir_path() {
	return base_path('app/Extras/wp-quiz-pro');
}

function logMe($string = null) {
	echo date('Y-m-d H:i:s', time()) . ": ". $string . "\n";

}