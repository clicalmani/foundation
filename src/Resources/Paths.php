<?php
namespace Clicalmani\Foundation\Resources;

trait Paths
{
    public function root_path(string $path = '')
    {
        return root_path($path);
    }

    public function resources_path(string $path = '')
    {
        return resources_path($path);
    }
}