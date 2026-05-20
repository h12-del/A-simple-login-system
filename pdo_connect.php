<?php
$host = "localhost";
$dbname = "school";
$user = "root";
$password = "root";
$charset = "utf8mb4";
//数据配置
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
//错误提示
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,// 禁止模拟遇处理
];
//核心连接
//做调试   连接的用户密码错误的提示一些配置信息
try{
    $pdo = new PDO($dsn, $user, $password, $options);
    echo "";
}catch (PDOException $e){
    die ("Connected unsuccessfully".$e->getMessage());
}