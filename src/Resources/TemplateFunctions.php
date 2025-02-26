<?php
namespace Clicalmani\Foundation\Resources;

class TemplateFunctions 
{
    public static function csrf_token() {
        return csrf_token();
    }

    public static function csrf_field() : string
    {
        return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
    }

    public static function route(...$args) : string
    {
        return route(...$args);
    }

    public static function json($value = null, ?bool $associative = null, int $depth = 512, int $flags = 0) : Json
    {
        return new Json($value, $associative, $depth, $flags);
    }

    public static function assets(?string $path = '/') : string
    {
        return assets($path);
    }

    public static function session(string $name)
    {
        return $_SESSION[$name] ?? null;
    }

    public static function env(string $key, string $default = '')
    {
        return env($key, $default);
    }

    public static function app()
    {
        return app();
    }

    public static function strip_quotes(string $str)
    {
        return preg_replace('/[\'"]+/', '', $str);
    }

    public static function retrieveViewData() : string
    {
        return @$_SESSION['__componentData'] ?? '';
    }
}