<?php

// Database Configuration
define('DB_HOST', '127.0.0.1'); // Fixed for CLI
define('DB_NAME', 'option_signal');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');

// App Settings
define('APP_NAME', 'Option Signal Scanner');
define('APP_ENV', 'production'); // or development
define('DEBUG_MODE', true);

// Encryption Key (Should be changed in production)
define('ENCRYPTION_KEY', 'change_this_to_a_secure_random_key_32_chars!!');

// API Settings
define('API_TIMEOUT', 5); // seconds
define('MAX_CONCURRENT_REQUESTS', 5);

// Signal Parameters
define('SIGNAL_MULTIPLIER', 2.0); // latest_close >= 2 * previous_close
define('STRIKE_RANGE', 5); // 5 CE + 5 PE

// Timezone
date_default_timezone_set('Asia/Kolkata');
