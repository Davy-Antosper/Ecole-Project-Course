<?php
require_once 'config.php';
checkAuth();

if (!isset($_GET['student_id'])) {
    die(json_encode([]));
}

