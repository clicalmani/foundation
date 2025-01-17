<?php
namespace Clicalmani\Foundation\Http\Response;

use Clicalmani\Foundation\Routing\Route;

/**
 * Trait JsonResponse
 * 
 * @package Clicalmani\Foundation
 * @author @Clicalmani\Foundation
 */
Trait JsonResponse
{
    /**
     * Send status code
     * 
     * @param int $status_code
     * @return int|bool
     */
    public function sendStatus(int $status_code) : int|bool
    {
        return http_response_code($status_code);
    }

    /**
     * Send json
     * 
     * @param mixed $data
     * @return string|false
     */
    public function json(mixed $data = null) : string|false
    {
        return json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK |
            JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR /** Enable strict mode */,
            512
        );
    }

    /**
     * Send success status
     * 
     * @param mixed $data
     * @return string|false
     */
    public function success(mixed $data = null) : string|false
    {
        return $this->json(['success' => true, 'data' => $data]);
    }

    /**
     * Send error status
     * 
     * @param mixed $data
     * @return string|false
     */
    public function error(mixed $data = null)
    {
        return $this->json(['success' => false, 'data' => $data]);
    }

    /**
     * Send an error status message
     * 
     * @param string $status_code
     * @param ?string $code
     * @param ?string $message
     * @return void
     */
    public function status(string $status_code, ?string $status = null, ?string $message = null) : void
    {
        $this->sendStatus($status_code);
        
        if (Route::isApi()) echo $this->json(['success' => false, 'status_code' => $status_code, 'error_code' => $status, 'error_message' => $message]);

        else {
            echo view($status_code, ['code' => $status, 'message' => $message]);
        }

        EXIT;
    }
}
