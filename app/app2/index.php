<?php 
require 'store.php';
require 'session.php';
$handler = new sysSession(store::getInstance());
session_set_save_handler($handler, true);
register_shutdown_function('session_write_close');
session_start();
echo "app2--应用2";
var_dump($_COOKIE, $_SESSION);