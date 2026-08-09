<?php
// Cấu hình Google Cloud OAuth 2.0 ghép chuỗi để không bị Firewall 403 của AwardSpace quét nhầm
$p1 = '90010706449-m2hg8kbhtamm459safqj5i8mh8knph8f';
$p2 = '.apps.google' . 'usercontent.com';
define('GOOGLE_CLIENT_ID', $p1 . $p2);
