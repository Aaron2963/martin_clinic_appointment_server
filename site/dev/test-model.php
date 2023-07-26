<?php

namespace App\Model;

// require all php file in __DIR__ . "/../lib/model"
require_once __DIR__ . "/../lib/model/DataTransferObject.php";
foreach (glob(__DIR__ . "/../lib/model/*.php") as $filename) {
    require_once $filename;
}

$json = json_encode([
    'userId' => '123',
    'loginName' => 'test',
    'fullName' => 'Foo Bar'
]);

$user = User::fromJson($json);
echo $user->toJson();
exit(0);
