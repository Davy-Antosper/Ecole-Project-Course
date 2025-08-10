<?php
require_once 'config.php';
checkAuth();

$user = getCurrentUser();
$db = getDB();

$stmt = $db->prepare("
