<?php

namespace App\Model;

trait DataTransferObject
{
    static function fromJson(string $json): static
    {
        $obj = json_decode($json, true);
        $class = get_called_class();
        $instance = new $class($obj);
        return $instance;
    }

    public function toJson(): string
    {
        return json_encode($this);
    }
}