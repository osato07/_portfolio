<?php
// assets/api/config.php

// SMTP Configuration
// Please replace the values below with your actual SMTP credentials.
// For Gmail, you often need an App Password if using 2FA.

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587); // or 465 for SSL
define('SMTP_USERNAME', 'nakamotosato4@gmail.com');
define('SMTP_PASSWORD', 'absxtpgoxomakbmc');

// Sender Info
define('SMTP_FROM_EMAIL', 'nakamotosato4@gmail.com'); // Usually same as SMTP_USERNAME for Gmail
define('SMTP_FROM_NAME', 'Portfolio Contact');

// Recipient (where the contact form sends emails to)
define('CONTACT_RECIPIENT_EMAIL', 'nakamotosato4@gmail.com');
