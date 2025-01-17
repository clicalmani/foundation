<?php
namespace Clicalmani\Foundation\Http\Requests;

trait HttpOutputStream
{
    /**
     * Provide a download attachment response header
     * 
     * @param string $filename Download file name
     * @param string $filepath Download file path
     * @return mixed
     */
    public function download($filename, $filepath)  : mixed
    {
        header("Content-Disposition: attachment; filename=$filename");

        if ( file_exists($filepath) ) {
            header('Content-Type: ' . mime_content_type($filepath));
            return readfile($filepath);
        }

        return null;
    }

    /**
     * File streaming
     * 
     * @param string $filename
     * @param ?array $events
     */
    public function stream($filename, ?array $events = [])
    {
        header('Cache-Control: no-cache');
        header('Content-Type: text/event-stream');

        foreach ($events as $event) {
            echo "event: $event\n\n";
        }

        if (file_exists($filename) && is_readable($filename)) {
            readfile($filename);
        }

        ob_end_flush();
        flush();
        sleep(5);
    }
}