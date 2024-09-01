<?php
namespace Clicalmani\Foundation\Auth;

abstract class Tokenizer extends Authenticate
{
	protected $db;

    public function __construct(protected mixed $user_id = null)
    {
        parent::__construct( $user_id );
		$this->db = \Clicalmani\Database\DB::getInstance();
    }

    public function getConnectedUserID(): mixed
    {
        if ($payload = with( new \Clicalmani\Foundation\Auth\AuthServiceProvider )->verifyToken( bearerToken() )) {
            return $payload->jti;
        }

        return null;
    }
}
