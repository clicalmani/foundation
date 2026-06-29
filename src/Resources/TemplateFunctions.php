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

    /**
     * Remplace les expressions de type {{ VARIABLE }} par les valeurs fournies.
     *
     * @param string $content Le texte brut contenant les balises
     * @param array $data Le tableau associatif [ 'CLE' => 'Valeur' ]
     * @return string
     */
    public static function parse_template(string $content, array $data): string
    {
        $builtPlaceholders = [];
        
        foreach ($data as $key => $value) {
            // On s'assure que la clé se transforme en {{ TOUT_EN_MAJUSCULE }} ou respecte la casse
            $builtPlaceholders['{{ ' . trim($key) . ' }}'] = $value;
        }
        
        // strtr est extrêmement rapide et gère toutes les substitutions en une seule passe
        return strtr($content, $builtPlaceholders);
    }

    public static function config(string $key, mixed $default = null)
    {
        return config($key, $default);
    }

    public static function date(string $date)
    {
        return date($date);
    }
}