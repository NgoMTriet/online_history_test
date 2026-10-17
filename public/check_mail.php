<?php

$config = require __DIR__ . '/../config/mail.php';

echo 'Username: '
    . ($config['username'] !== '' ? 'DA CAU HINH' : 'DANG TRONG')
    . '<br>';

echo 'Password: '
    . ($config['password'] !== '' ? 'DA CAU HINH' : 'DANG TRONG')
    . '<br>';