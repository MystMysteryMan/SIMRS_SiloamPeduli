<?php
// core/App.php - Front Controller & Router Dispatcher

class App {
    public static function run(): void {
        // Cek parameter controller & action dari query string
        $controllerParam = $_GET['c'] ?? '';
        $actionParam = $_GET['a'] ?? 'index';

        // Auto routing gerbang awal
        if (empty($controllerParam)) {
            if (!empty($_SESSION['user_id'])) {
                $controllerName = 'DashboardController';
                $action = 'index';
            } else {
                $controllerName = 'AuthController';
                $action = 'login';
            }
        } else {
            // Ubah format misal: 'patient' -> 'PatientController', 'user_management' -> 'UserController'
            $cClean = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $controllerParam)));
            $controllerName = $cClean . 'Controller';
            $action = $actionParam;
        }

        $controllerFile = __DIR__ . '/../app/controllers/' . $controllerName . '.php';

        if (!file_exists($controllerFile)) {
            http_response_code(404);
            die("Controller tidak ditemukan: {$controllerName}");
        }

        require_once $controllerFile;

        if (!class_exists($controllerName)) {
            http_response_code(404);
            die("Class {$controllerName} tidak ditemukan.");
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $action)) {
            http_response_code(404);
            die("Action method {$action}() tidak ditemukan di controller {$controllerName}.");
        }

        // Panggil controller action
        $controller->$action();
    }
}

