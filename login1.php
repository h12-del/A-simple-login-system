<link rel="stylesheet" href="style.css">
<?php
// 开启会话
session_start();

// 引入过滤文件
require_once "guolv.php";

// 引入数据库连接
include 'pdo_connect.php';

// 错误提示变量
$x = "";

// 读取并过滤 Cookie 记住的账号密码
// 这里严格按照你的要求：使用 test_input() 过滤，和截图格式完全一致
// 作用：如果浏览器有保存的Cookie，就读取并过滤后赋值给变量
$cookie_name = test_input(isset($_COOKIE['login_name']) ? $_COOKIE['login_name'] : "");
$cookie_pwd = test_input(isset($_COOKIE['login_pwd']) ? $_COOKIE['login_pwd'] : "");


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = test_input(isset($_POST["name"]) ? $_POST["name"] : "");
    $password = test_input(isset($_POST["password"]) ? $_POST["password"] : "");
    // 9. 获取“记住密码”复选框是否勾选（勾选为on，未勾选为空）
    $remember = isset($_POST["remember"]) ? $_POST["remember"] : "";

    // 10. 验证：用户名和密码不能为空
    if (empty($name) || empty($password)) {
        $x = "用户名和密码不能为空！";
        goto END;
    }


    // 11. 数据库查询：根据用户名查找用户信息
    $sql = "SELECT * FROM registration_information WHERE name = :name";
    $stmt = $pdo->prepare($sql);       // PDO预处理，防SQL注入
    $stmt->execute([':name' => $name]);// 执行查询

    // 12. 判断：用户是否存在
    if ($stmt->rowCount() == 0) {
        $x = "用户不存在请先注册!";
        goto END;
    }


    // 13. 循环读取查询到的用户数据
    while ($row = $stmt->fetch()) {
        // 14. 验证密码：用户输入的密码 vs 数据库加密密码
        if(!password_verify($password, $row["password"])) {
            $x = "密码不正确";
            goto END;
        }

        // 15. 登录成功！把用户信息存入 SESSION 会话
        $_SESSION['name'] = $row["name"];
        $_SESSION['is_admin'] = $row["is_admin"];
        $_SESSION['user_id'] = $row["id"];//存储用户ID，便于留言关联

        // 16. 如果勾选了记住密码，就把账号密码存入 Cookie 保存7天
        if ($remember ==="on") {
            // 设置Cookie，有效期 7天
            setcookie("login_name", $name, time() + 180);
            setcookie("login_pwd",  $password, time() + 180);
        }else{
            // 没勾选：清空Cookie
            setcookie("login_name", "", time() - 3600);
            setcookie("login_pwd",  "", time() - 3600);
        }
        // 17. 登录成功，跳转到欢迎页面
        header("Location:welcome.php ");
        exit;
    }

END:
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <title>登录</title>


</head>
<body>

<h2>账户登录</h2>

<!--如果有错误信息，用 JS 弹窗显示-->
<?php if($x): ?>
    <script>
        alert("<?php echo $x ?>");
    </script>
<?php endif; ?>

<!-- 登录表单 -->
<form method="post" action="">
    <label for="name">用户名</label>
    <input id="name" name="name" type="text" placeholder="请输入用户名" value="<?php echo $cookie_name; ?>"><br><br>

    <label for="password">密码</label>
    <input id="password" name="password" type="password" placeholder="请输入密码" value="<?php echo $cookie_pwd; ?>"><br><br>

    <!-- 记住密码复选框 -->
    <label style="display: flex; align-items: center; gap: 8px; margin: 10px 0 20px;">
        <input type="checkbox" name="remember" <?php echo $cookie_name ? "checked" : ""; ?>>
        <span>记住用户名和密码</span>
    </label>

    <button type="submit" style="width: 100%;">立即登录</button>

    <div style="text-align: center; margin-top: 25px;">
        <a href="Reset Password.php">忘记密码？重置密码</a><br><br>
        <a href="reg.php">没有账号？立即注册</a>
    </div>
</form>

</body>
</html>

