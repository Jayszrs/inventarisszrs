<?php

declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';

if (is_logged_in()) {
    redirect_to('/dashboard.php');
}

redirect_to('/login.php');
