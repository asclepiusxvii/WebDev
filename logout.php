<?php
session_start();
$_SESSION = [];
session_destroy();
header("Location: /ramtech/login.php");
exit;
?>
