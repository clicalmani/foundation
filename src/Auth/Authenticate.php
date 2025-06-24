<?php
namespace Clicalmani\Foundation\Auth;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Foundation\Http\RequestInterface;
use Clicalmani\Foundation\Providers\ServiceProvider;

abstract class Authenticate extends ServiceProvider implements \JsonSerializable
{
	/**
	 * User Model
	 * 
	 * @var string
	 */
	protected string $userModel;

	/**
	 * Serializer
	 * 
	 * @var callable
	 */
	protected static $serializer;

	/**
	 * Authenticated user
	 * 
	 * @var \Clicalmani\Database\Factory\Models\Elegant
	 */
	protected Elegant $user;
	 
	/**
	 * Constructor
	 *
	 * @param mixed $user_id 
	 */
	public function __construct(protected mixed $user_id = NULL)
	{
		$this->createUser($user_id);
	}

	/**
	 * User ID setter
	 * 
	 * @param mixed $user_id
	 * @return static
	 */
	public function createUser(mixed $user_id = NULL) : static
	{
		$this->user_id = $user_id ?: $this->user_id;
		$this->user = instance($this->userModel, fn(Elegant $instance) => $instance, $this->user_id);
		return $this;
	}

	/**
	 * Get connected user ID
	 * 
	 * @param ?\Clicalmani\Foundation\Http\RequestInterface $request
	 * @return mixed
	 */
	public function getConnectedUserID(?RequestInterface $request) : mixed
	{
		throw new \Exception(sprintf("%s::%s must be overriden. Thrown in %s at line %d", __CLASS__, __METHOD__, static::class, __LINE__));
	}

	/**
	 * User data serializer
	 * 
	 * @param callable $callback
	 * @return void
	 */
	protected function serialize(callable $callback) : void
	{
		static::$serializer = $callback;
	}

	public function jsonSerialize(): mixed
	{
		if (static::$serializer) return call(static::$serializer);

		return json_encode($this->user);
	}
	
	/**
	 * @override
	 * 
	 * @param string $attribute
	 * @return mixed
	 */
	public function __get(string $attribute)
	{
		return $this->user?->{$attribute};
	}
	
	public function __toString()
	{
		if ($this->serializer) return call($this->serializer);

		return null;
	}

	public function __call($name, $arguments)
	{
		return $this->user?->{$name}(...$arguments);
	}

	public function boot(): void
	{
		//
	}
}
