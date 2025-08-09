<?php
session_start();

if (isset($_SESSION['user_id'])) {
    // Mettre à jour le timestamp de la session
