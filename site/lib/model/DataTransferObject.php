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

    static function fromArray(array $array): static
    {
        $class = get_called_class();
        $instance = new $class($array);
        return $instance;
    }

    public function toJson(): string
    {
        return json_encode($this);
    }

    public function toArray(): array
    {
        return (array) $this;
    }
}
