<?php
/**
 * File: config/google_oauth.example.php
 * Mục đích: Cấu hình Google OAuth
 * 
 * Hướng dẫn:
 * 1. Copy file này thành google_oauth.php
 * 2. Điền thông tin Google OAuth Client ID và Secret
 * 3. Cấu hình Redirect URI trong Google Console:
 *    - http://localhost:3000/auth/google/callback (development)
 *    - https://yourdomain.com/auth/google/callback (production)
 */

return [
    'client_id' => 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com',
    'client_secret' => 'YOUR_GOOGLE_CLIENT_SECRET',
    'redirect_uri' => 'http://localhost:3000/auth/google/callback',
    'scopes' => [
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/userinfo.profile'
    ]
];

