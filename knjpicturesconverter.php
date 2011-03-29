#!/usr/bin/php53gtk
<?php
	chdir(dirname(__FILE__));
	
	require_once("knjphpframework/functions_knj_extensions.php");
	knj_dl(array("gd", "gtk2"));
	
	require_once("knjphpframework/functions_knj_locales.php");
	knjlocales_setmodule("locales", "locales");
	
	require_once("windows/win_main.php");
	$win_main = new WinMain();
	Gtk::main();
?>
