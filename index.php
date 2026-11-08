<?php
// index.php - Front Controller Entry Point SIMRS Siloam Peduli (Native MVC)

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Model.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/core/App.php';

// Dispatch request to controller & action
App::run();
