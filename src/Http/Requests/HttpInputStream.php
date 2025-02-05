<?php
namespace Clicalmani\Foundation\Http\Requests;

trait HttpInputStream
{
    /**
     * Request file
     * 
     * @param string $name File name
     * @return \Clicalmani\Foundation\Http\UploadedFile|null
     */
    public function file(string $name) : UploadedFile|null
    {
        if ( $this->hasFile($name) ) {
            return new \Clicalmani\Foundation\Http\Requests\UploadedFile($name);
        }

        return null;
    }
}