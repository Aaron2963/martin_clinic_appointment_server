<?php

namespace App\Model;

trait DataTransferObject
{
    static function fromJson(string $json)
    {
        $obj = json_decode($json, true);
        $class = get_called_class();
        $instance = new $class($obj);
        return $instance;
    }

    static function fromArray(array $array)
    {
        $class = get_called_class();
        $data = [];
        foreach ($array as $key => $value) {
            $key = lcfirst($key);
            $key = str_replace('ID', 'Id', $key);
            $data[$key] = $value;
        }
        $instance = new $class($data);
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
