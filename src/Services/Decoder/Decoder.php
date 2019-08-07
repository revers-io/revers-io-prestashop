<?php
namespace ReversIO\Services\Decoder;

class Decoder
{
    public function base64Decoder($value)
    {
        return base64_decode($value);
    }
}
