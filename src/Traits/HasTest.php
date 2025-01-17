<?php 
namespace Clicalmani\Foundation\Traits;

trait HasTest
{
    public static function test(string $action)
    {
        $controller = "Test\\Controllers\\" . substr(self::class, strrpos(self::class, "\\") + 1) . 'Test';
        return with( new $controller )->new($action);
    }
}
