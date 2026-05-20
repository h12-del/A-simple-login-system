<link rel="stylesheet" href="style.css">
<?php
require_once "guolv.php";
include 'pdo_connect.php';
$x = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = test_input(isset($_POST["name"]) ? ($_POST["name"]) : "");
    $email = test_input(isset($_POST["email"]) ? ($_POST["email"]) : "");
    $new_pwd = test_input(isset($_POST["new_pwd"]) ? ($_POST["new_pwd"]) : "");
    $re_pwd = test_input(isset($_POST["re_pwd"]) ? $_POST["re_pwd"] : "");

    //不能为为空
    if (empty($name) || empty($email) || empty($new_pwd) || empty($re_pwd)) {
        $x = "用户名、邮箱、密码都不能为空！";
        goto END;
    }

    //验证两次密码一致
    if($new_pwd != $re_pwd) {
      $x = "两次密码不一致！";
      goto END;
    }

    //用户名和邮箱是否存在
    $sql = "SELECT * FROM registration_information WHERE name = :name AND email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":name" => $name, ":email" => $email]);

    if ($stmt->rowCount() == 0) {
        $x = "用户名和邮箱不匹配！";
        goto END;
    }

    //密码加密
    $hash_pwd = password_hash($new_pwd, PASSWORD_DEFAULT);

    //更新数据库里的新密码
    $sql = "UPDATE registration_information SET password = :password WHERE name = :name AND email = :email";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([':name' => $name, ':password' => $hash_pwd, ':email' => $email]);
    if ($result) {
        $x = "密码重置成功！2秒后将跳转到登录页";
        // 重置成功后自动跳转
        header("refresh:2;url=login.php");
    } else {
        $x = "重置失败，请稍后再试";
    }
END:
}

?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <title>重置密码</title>
</head>
<body>
<h3>重置密码</h3>
<p ><?php echo $x; ?></p>
<form method="post" action="" onsubmit="return confirm('确定要重置密码吗？')">
    <label for="name">用户名:</label>
    <input id="name" type="text" name="name" placeholder="请输入用户名"><br><br>
    <label for="email">邮箱:</label>
    <input id="email" type="email" name="email" placeholder="请输入邮箱"><br><br>
    <label for="new_pwd">新密码:</label>
    <input id="new_pwd" type="password" name="new_pwd" placeholder="请输入新密码"><br><br>
    <label for="re_pwd">确认新密码:</label>
    <input id="re_pwd" type="password" name="re_pwd" placeholder="再次输入新密码"><br><br>
    <button type="submit">确认重置</button><br><br>
    <a href="login1.php"></a>
</form>
</body>
</html>
