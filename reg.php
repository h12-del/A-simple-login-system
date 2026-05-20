<link rel="stylesheet" href="style.css">
<?php
require_once "guolv.php";
include 'pdo_connect.php';

$x = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = test_input(isset($_POST["name"]) ? $_POST["name"] : "");
    $password = test_input(isset($_POST["password"]) ? $_POST["password"] : "");
    $email = filter_input(INPUT_POST, "email", FILTER_VALIDATE_EMAIL);

    //用户名和密码非空
    if (empty($name) || empty($password)) {
        $x = "用户名和密码都不能为空";
        goto END;
    }

    //邮箱格式判断
    if ($email === false) {
        $x = "邮箱格式不正确！";
        goto END;
    }

    //检查用户名是否已被注册
    $sql = "SELECT * FROM registration_information WHERE name = :name";
    $stmt = $pdo->prepare($sql);
    $stmt -> execute([':name'=>$name]);
    if ($stmt->rowCount() > 0) {
        $x = "用户名已被注册！";
        goto END;
    }

    //检查邮箱是否已被注册
    $sql = "SELECT *FROM registration_information WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt -> execute([':email'=>$email]);
    if ($stmt->rowCount() > 0) {
        $x = "邮箱已被注册！";
        goto END;
    }
    // 规则：strpos($name, 'admin') === 0 表示以 admin 开头
    if (strpos($name, 'admin') === 0) {
        $is_admin = 1;    // 管理员
    } else {
        $is_admin = 0;    // 普通用户
    }
    //密码加密
    $hash_passwd = password_hash($password, PASSWORD_DEFAULT);

    // 插入数据库，带上自动判断的 is_admin
    $sql = "INSERT INTO registration_information (name, password, email, is_admin) 
            VALUES (:name, :password, :email, :is_admin)";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
            ':name' => $name,
            ':password' => $hash_passwd,
            ':email' => $email,
            ':is_admin' => $is_admin  // 自动插入管理员/普通用户标记
    ]);
    if ($result) {
        $x = "注册成功！新建ID为：" . $pdo->lastInsertId();
        header("refresh:2;url=login.php");
        exit;
    } else {
        $error = $stmt->errorInfo();
        $x = "注册失败" . $error[2];
    }
END:
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>注册</title>

</head>
<body>
<h2>添加注册信息</h2>

<!-- 提示信息 -->
<?php if ($x): ?>
    <div class="msg"><?php echo $x; ?></div>
<?php endif; ?>

<!-- 表单 -->
<form method="post" >
    <label for="name">姓名：</label>
    <input id="name" type="text" name="name" placeholder="请输入用户名"><br><br>
    <label for="email">邮箱：</label>
    <input id="email" type="email" name="email"  placeholder="请输入邮箱" required><br><br>
    <label for="password">密码：</label>
    <input id="password" type="password" name="password" placeholder="请输入密码"><br><br>
    <button type="submit" >提交保存</button>

</form>
</body>
</html>
