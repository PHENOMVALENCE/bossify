<?php
/**
 * Response Handler Class
 * Standardized JSON responses for API endpoints
 */

class Response {
    public static function success($message, $data = null, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
        exit;
    }
    
    public static function error($message, $errors = null, $code = 400) {
        http_response_code($code);
        header('Content-Type: application/json');
        
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response);
        exit;
    }
    
    public static function validationError($errors) {
        self::error('Validation failed', $errors, 422);
    }
}
