<?php
namespace Clicalmani\Core\Http\Request;

trait HttpInputStream
{
    /**
     * Request file
     * 
     * @param string $name File name
     * @return \Clicalmani\Core\Http\UploadedFile|null
     */
    public function file(string $name) : UploadedFile|null
    {
        if ( $this->hasFile($name) ) {
            return new \Clicalmani\Core\Http\Request\UploadedFile($name);
        }

        return null;
    }
}