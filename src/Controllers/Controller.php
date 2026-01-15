<?php

class Controller {
    protected function render($template, $data = []) {
        extract($data);
        
        ob_start();
        include __DIR__ . '/../../templates/pages/' . $template . '.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../../templates/layouts/main.php';
    }
    
    protected function renderPartial($template, $data = []) {
        extract($data);
        include __DIR__ . '/../../templates/pages/' . $template . '.php';
    }
    
    protected function json($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
    
    protected function redirect($url) {
        header("Location: $url");
        exit;
    }
    
    protected function getPost($key, $default = null) {
        return $_POST[$key] ?? $default;
    }
    
    protected function getGet($key, $default = null) {
        return $_GET[$key] ?? $default;
    }
    
    protected function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    protected function isHtmx() {
        return isset($_SERVER['HTTP_HX_REQUEST']);
    }
}
