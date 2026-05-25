<?php
session_start();

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: log-in.html');
    exit();
}

readfile(__DIR__ . '/cafe-admin.html');
