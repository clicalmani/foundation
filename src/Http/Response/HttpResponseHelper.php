<?php
namespace Clicalmani\Foundation\Http\Response;

/**
 * Class HttpResponseHelper
 * 
 * @package Clicalmani\Foundation
 * @author @Clicalmani\Foundation
 */
class HttpResponseHelper 
{
    use JsonResponse;
    use WebResponse;
    
    /**
     * Send a status code
     * 
     * @param int $status_code
     * @return int|bool
     */
    public function statusCode(int $status_code) : int|bool
    {
        return http_response_code($status_code);
    }
}
