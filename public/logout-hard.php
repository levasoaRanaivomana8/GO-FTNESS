<?php
session_start();
$_SESSION = [];
session_destroy();
header('Location: /go-fitness/public/login');
exit;
