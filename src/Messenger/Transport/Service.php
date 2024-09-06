<?php
namespace Clicalmani\Foundation\Messenger\Transport;

use App\Authenticate\Notify;
use App\Providers\RouteServiceProvider;
use Clicalmani\Foundation\Http\Requests\Request;
use Clicalmani\Foundation\Providers\RouteTPS;
use GuzzleHttp\Client;

class Service extends RouteTPS
{
    private $ls;

    /**
     * HTTP client
     * 
     * @var \GuzzleHttp\Client
     */
    private $http;

    private $retry = 3;

    /**
     * Constructor
     * 
     * @param \Clicalmani\Routing\Route $route
     */
    public function __construct(protected $route)
    {
        parent::__construct();
        $this->ls = new Notify;
        
        // $this->http = new Client(['base_uri' => 'http://localhost:8888', 'verify' => false]);
        $this->http = new Client(['base_uri' => 'https://xet.utc.bj', 'verify' => false]);
    }

    public function auth()
    {
        try {
            $response = $this->http->post('api/auth', [
                'form_params' => [
                    'key' => env('APP_KEY', '')
                ]
            ]);
            
            if (200 === $response->getStatusCode()) {
                $this->store(json_decode($response->getBody()->getContents())->data->token);
                return true;
            }

            return false;
        } catch (\Psr\Http\Client\ClientExceptionInterface $e) {
            return false;
        }

        return false;
    }

    public function connect()
    {
        $file = storage_path('/framework/sessions/.INDEX');
        
        if (FALSE === file_exists($file) && $this->retry) {
            $this->auth();
            $this->retry--;
        }

        try {
            $token = @file_get_contents($file) ?? '';
            $response = $this->http->post('api/connect', [
                'headers' => [
                    'Authorization' => "bearer $token"
                ]
            ]);
            
            if (200 === $response->getStatusCode()) {
                $response = json_decode($response->getBody()->getContents());
                
                $message = $response->data;
                if ( $message ) $this->ls->sendMessage($message, ( new Request )->user()->id_compte);
                
                if (TRUE === $response->success) return true;

                return false;
            }

            return false;
        } catch (\Psr\Http\Client\ClientExceptionInterface $e) {
            if ($this->retry) {
                $this->auth();
                $this->connect();
                $this->retry--;
            } else return false;
        }
    }

    public function store(string $content)
    {
        $file_path = storage_path('/framework/sessions');

        if (FALSE === is_dir($file_path)) {
            mkdir($file_path, 0700);
        }

        file_put_contents("$file_path/.INDEX", $content);
    }

    /**
     * Issue a redirect
     * 
     * @return void
     */
    public function redirect()
    {
        if ($this->route && @$this->route[0]->name === ( new RouteServiceProvider )->getApiPrefix()) {
            // if (FALSE === $this->connect()) $this->route->redirect = 303;
        }
    }
}
