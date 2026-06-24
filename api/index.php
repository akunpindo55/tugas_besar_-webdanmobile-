<?php 
// Fix: Override SCRIPT_NAME agar Laravel tidak strip prefix /api dari REQUEST_URI
// Tanpa ini, request ke /api/login dianggap /login oleh Laravel (match web route)
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__. '/../public/index.php';