<?php
/**
 * Test script để kiểm tra API get-courses.php
 */

// Simulate GET request
$_SERVER['REQUEST_METHOD'] = 'GET';

// Test với teacher_id = 1
$_GET['teacher_id'] = '1';

// Include API file
require_once __DIR__ . '/../api/get-courses.php';

