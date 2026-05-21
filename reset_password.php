<?php
require_once 'guolv.php';
include 'pdo_connect.php';
$x = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = test_input(isset($_POST['name']) ? $_POST['name'] : "");
    $email = test_input(isset($_POST['email']) ? $_POST['email'] : "");
    $new_pwd = test_input(isset($_POST['new_pwd']) ? $_POST['new_pwd'] : "");
    $re_pwd = test_input(isset($_POST['re_pwd']) ? $_POST['re_pwd'] : "");
    if (empty($name) || empty($email) || empty($new_pwd) || empty($re_pwd)) {
        $x="用户名，邮箱，密码都不能为空";
        goto END;
    }
    if (!preg_match('/^[A-Za-z0-9]{3,6}$/', $new_pwd)) {
        $x = "密码只能是字母或数字，长度3到6位";
        goto END;
    }
    if ($new_pwd != $re_pwd) {
        $x="两次密码不一致";
        goto END;
    }
    $sql = "SELECT * FROM registration_information WHERE name= :name AND email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['name' => $name, 'email' => $email]);
    if ($stmt->rowCount() == 0) {
        $x="用户名和邮箱不匹配！";
        goto END;
    }
    $hash_pwd = password_hash($new_pwd, PASSWORD_DEFAULT);
    $sql = "UPDATE registration_information SET password =:password WHERE name = :name AND email = :email";
    $stmt = $pdo->prepare($sql);
    $result=$stmt->execute(['password' => $hash_pwd, 'name' => $name, 'email' => $email]);
    if ($result) {
        $x="密码重置成功！2秒后返回登陆页面";
        header("refresh:2;url=login.php");
    }else{
        $x="重置失败，稍后请重试！";
    }
}
END:
?>
<link rel="stylesheet" href="222.css">
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <title>重置密码</title>
</head>
<body>
<h3>重置密码</h3>
<?php echo test_input($x); ?>
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
    <div style="text-align: center; margin-top: 25px;">
        <a href="login.php">返回登陆</a>
    </div>
</form>
</body>
</html>