<?php
// Database instellingen
define('SERVER_NAME', 'localhost');
define('USER_NAME', 'User');
define('PASSWORD', 'User');
define('DB_NAME', 'vereniging');

// gebruikersinstellingen
define('GEBRUIKER', 'gebruiker');
define('WACHTWOORD', password_hash('mysqlphp', PASSWORD_DEFAULT));

// er wordt automatisch uitgelogd na 10 minuten
define('UITLOGTIJD', 10 * 60);