<?php
require_once 'guolv.php';
include 'pdo_connect.php';
$x = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = test_input(isset($_POST['name']) ? $_POST['name'] : '');
    $password = test_input(isset($_POST['password']) ? $_POST['password'] : '');
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    if (empty($name) || empty($password)) {
        $x = "密码和用户名不能为空";
        goto END;
    }
    if ($email === false) {
        $x = "邮箱格式不正确";
        goto END;
    }
    if (!preg_match('/^[A-Za-z0-9]{3,6}$/', $password)) {
        $x = "密码只能是字母或数字，长度3到6位";
        goto END;
    }
    $sql="SELECT * FROM registration_information WHERE name = :name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['name' => $name]);
    if ($stmt->rowCount() > 0) {
        $x = "用户名已被注册!";
        goto END;
    }
    $sql = "SELECT * FROM registration_information WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    if ($stmt->rowCount() > 0) {
        $x = "邮箱已被注册!";
        goto END;
    }
    if (strpos($name, 'admin' )=== 0) {
        $is_admin = 1;
    } else {
        $is_admin = 0;
    }
    $hash_password = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO registration_information (name, password, email, is_admin) VALUES (:name, :password, :email, :is_admin)";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([':name' => $name, ':password' => $hash_password, ':email' => $email, ':is_admin' => $is_admin]);
    if ($result) {
        $x = "注册成功!新建id为：" . $pdo->lastInsertId();
        header("refresh:2;url=login.php");
    } else {
        $error = $stmt->errorInfo();
        $x = "注册失败" . $error[2];
    }
}
END:
?>
<link rel="stylesheet" href="222.css">
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <title>注册页面</title>
</head>
<body>
<h2>账号注册</h2>

<?php if ($x): ?>
<div class="msg"><?php echo test_input($x); ?></div>
<?php endif; ?>

<form method="post">
    <label for="name">用户名:</label>
    <input id="name" type="text" name="name" placeholder="请输入用户名"><br><br>
    <label for="email">邮箱:</label>
    <input id="email" type="email" name="email" placeholder="请输入邮箱"><br><br>
    <label for="password">密码:</label>
    <input id="password" type="password" name="password" placeholder="请输入密码"><br><br>
    <button type="submit">提交保存</button><br>
    <div style="text-align: center; margin-top: 25px;">
        <a href="login.php">已有账号？返回登录</a>
    </div>
</form>
</body>
</html>

