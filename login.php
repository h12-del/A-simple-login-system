<?php
session_start();
require_once 'guolv.php';
include 'pdo_connect.php';
$x = "";
$cookie_name = test_input(isset($_COOKIE['login_name']) ? $_COOKIE['login_name'] : '');
$cookie_pwd = test_input(isset($_COOKIE['login_pwd']) ? $_COOKIE['login_pwd'] : '');
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = test_input(isset($_POST['name']) ? $_POST['name'] : '');
    $password = test_input(isset($_POST['password']) ? $_POST['password'] : '');
    $remember = test_input(isset($_POST['remember']) ? $_POST['remember'] : '');
    if (empty($name) || empty($password)) {
        $x = "用户名和密码不能为空";
        goto END;
    }
    $sql = "SELECT * FROM registration_information WHERE name = :name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['name' => $name]);
    if ($stmt->rowCount() == 0) {
        $x = "用户名不存在,请先注册";
        goto END;
    }
    while ($row = $stmt->fetch()) {
        if (!password_verify($password, $row['password'])) {
            $x = "密码不正确";
            goto END;
        }
        $_SESSION['name'] = $row['name'];
        $_SESSION['is_admin'] = $row['is_admin'];
        $_SESSION['user_id'] = $row['id'];
        if ($remember == 'on') {
            setcookie('login_name', $name, time() + 1800);
        } else {
            setcookie('login_name', "", time() - 3600);
        }
        header('Location: welcome.php');
        exit;
    }
}
END:
?>
<link rel="stylesheet" href="222.css">
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <title>登陆页面</title>
</head>
<body>
<h2>账户登录</h2>
<?php if ($x) : ?>
    <script>
        alert("<?php echo test_input($x); ?>");
    </script>
<?php endif; ?>

<form method="post">
    <label for="name">用户名</label>
    <input id="name" type="text" name="name" placeholder="请输入用户名" value="<?php echo $cookie_name; ?>"><br><br>
    <label for="password">密码</label>
    <input id="password" type="password" name="password" placeholder="请输入密码"
           value="<?php echo $cookie_pwd; ?>"><br><br>
    <label style="display: flex; align-items: center; gap: 8px; margin: 10px 0 20px;">
        <input name="remember" type="checkbox" <?php echo $cookie_name ? 'checked' : ''; ?>>
        <span>记住用户名</span><br><br>
    </label>
    <button type="submit" style="width: 100%;">立即登录</button>
    <br>
    <div style="text-align: center; margin-top: 25px;">
        <a href="reset_password.php">忘记密码？重置密码</a><br><br>
        <a href="register.php">没有账号？立即注册</a>
    </div>
</form>
</body>
</html>
