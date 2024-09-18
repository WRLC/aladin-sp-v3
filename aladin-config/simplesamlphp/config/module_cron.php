<?php
/*
 * Configuration for the Cron module.
 */

$dotenv = Dotenv\Dotenv::createImmutable( __DIR__ . '/../../../', '.env.local');
$dotenv->safeLoad();

$config = [

    'key' => $_ENV['SSP_CRON_KEY'],
    'allowed_tags' => array('daily', 'hourly', 'frequent'),
    'debug_message' => true,
    'sendemail' => false,

];