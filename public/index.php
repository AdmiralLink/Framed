<?php

namespace Technicalpenguins\Framed;

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

if (!empty($_GET)) {
    if (isset($_GET['view']) && $_GET['view'] == 'conciliottos') {
        new View();
    } else if (isset($_GET['key']) && $_GET['key'] == 'update') {
        new Updater();
    }
}