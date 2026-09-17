<?php
namespace Clicalmani\Core\Http\Requests;

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
            return new \Clicalmani\Core\Http\Requests\UploadedFile($name);
        }

        return null;
    }
}