<?php
namespace Clicalmani\Foundation\Sandbox;

class Sandbox
{
    private static $tmp_name = '__.php';

    /**
     * Evaluate a PHP expression
     * 
     * @param string $expression
     * @param ?array $args
     * @param ?bool $exec
     * 
     * @return mixed
     */
    public static function eval(string $expression, ?array $args = [], ?bool $exec = false) : mixed
    {
        $args     = serialize($args);

        $content = <<<EVAL
        <?php
        \$serialized = <<<ARGS
        $args
        ARGS;
        extract(unserialize(\$serialized));
        \n\n
        EVAL;

        if (TRUE === $exec) $content .= "return $expression;";
        else $content .= <<<EVAL
        return <<<DELIMITER
            $expression
        DELIMITER;
        EVAL;
        
        return self::getResult($content);
    }

    private static function getResult(string $content)
    {
        file_put_contents(sys_get_temp_dir() . '/' . static::$tmp_name, $content);
        return include sys_get_temp_dir() . '/' . static::$tmp_name;
    }
}
