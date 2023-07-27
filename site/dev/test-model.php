<?php

namespace App\Model;

// require all php file in __DIR__ . "/../lib/model"
require_once __DIR__ . "/../lib/model/DataTransferObject.php";
foreach (glob(__DIR__ . "/../lib/model/*.php") as $filename) {
    require_once $filename;
}

$arr = [
    'clinicId' => '123',
    'name' => 'test',
    'departments' => [
        [
            'departmentId' => '456',
            'name' => 'test2',
        ],
        [
            'departmentId' => '789',
            'name' => 'test3',
        ],
    ],
];
$json = json_encode($arr);

$user = Clinic::fromJson($json);
var_dump($user->toJson());
exit(0);
