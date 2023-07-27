<?php

$FileDIR = dirname(__DIR__);
require_once $FileDIR . "/vendor/autoload.php";

use MISA\DBSMOD as M;
use Dotenv\Dotenv;

//宣告當前運作環境是否為本機測試環境
define('IS_LOCALHOST', getenv('APP_IS_LOCALHOST') === '1');

//載入環境變數
$Dotenv = IS_LOCALHOST 
    ? Dotenv::createImmutable(__DIR__, '.env.local') 
    : Dotenv::createImmutable(__DIR__);
$Dotenv->load();

//啟動SESSION、宣告常用變數、設定錯誤回報等級、設定時區、設定回應編碼
$ConfigInit = M\ConfigInit::FromENV();
$ConfigInit->Deploy();

//宣告路徑|網域公用變數
$ConfigPath = M\ConfigPath::FromENV();
$ConfigPath->Deploy();

//SQL連線
$ConfigDatabase = M\ConfigDatabase::FromENV();
$ConfigDatabase->Deploy();

//SMTP連線
$ConfigSMTP = M\ConfigSMTP::FromENV(false);
$ConfigSMTP->Deploy();

//交易設定
$ConfigTrade = M\ConfigTrade::FromENV();

//宣告資料表名稱全域變數
$ConfigTableName = new M\ConfigTableName(
    $ConfigPath->JSONPath . '/db_table.json',
    $ConfigDatabase
);
$ConfigTableName->Deploy();

//語言處理
$LangCode = "zhTW";
if (strpos($_SERVER['PHP_SELF'], '/enUS/') !== false) {
    $LangCode = "enUS";
}
$ConfigLanguage = new M\ConfigLanguage(
    $LangCode,
    "{$ConfigPath->ProjectDIR}/site/lang/$LangCode.lang"
);
$ConfigLanguage->Deploy();

//專案全域變數
$RoleID_Array = [
    "teacher" => "teacher",
    "student" => "student",
    "assistant" => "assistant"
];

//抵禦XSS及Injection攻擊
M\Utility::HandleRequestData();

//JWT簽署金鑰位置
$JWT_SECRET = $ConfigPath->JSONPath . '/jwtRS256.key';
$JWT_PUBLIC = $ConfigPath->JSONPath . '/jwtRS256.key.pub';
