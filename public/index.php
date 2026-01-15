<?php
// Start session
session_start();

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Autoload classes
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../src/Controllers/' . $class . '.php',
        __DIR__ . '/../src/Models/' . $class . '.php',
        __DIR__ . '/../src/' . $class . '.php',
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Initialize database
Database::getInstance();

// Create router
$router = new Router();

// Define routes
$router->get('/', [HomeController::class, 'index']);

// Upload routes
$router->get('/upload', [UploadController::class, 'index']);
$router->post('/upload/file', [UploadController::class, 'file']);
$router->post('/upload/folder', [UploadController::class, 'folder']);

// Pin routes
$router->get('/pins', [PinController::class, 'index']);
$router->post('/pins/add', [PinController::class, 'add']);
$router->post('/pins/remove', [PinController::class, 'remove']);
$router->get('/pins/sync', [PinController::class, 'sync']);

// Browse routes
$router->get('/browse', [BrowseController::class, 'view']);

// Settings routes
$router->get('/settings', [SettingsController::class, 'index']);
$router->post('/settings/update', [SettingsController::class, 'update']);

// Dispatch
$router->dispatch();
