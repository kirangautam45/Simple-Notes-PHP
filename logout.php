<?php
session_start();
require_once 'includes/functions.php';

logoutUser();
session_start(); // Restart session for flash message
setFlashMessage('success', 'You have been logged out successfully.');
redirect('login.php');
