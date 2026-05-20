<?php
session_start();

if (!isset($_SESSION['name'])) {
    header("Location: login.php");
    exit;
}
$is_admin = $_SESSION['is_admin'];
$username = $_SESSION['name'];
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>欢迎页面</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Microsoft YaHei', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #12141d;
            background: linear-gradient(135deg, #12141d, #1e2230, #252b3d);
            color: #fff;
        }

        .container {
            width: 100%;
            max-width: 460px;
            padding: 0 20px;
        }

        .glass {
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(20px);
            border-radius: 22px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 50px 40px;
            text-align: center;
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.3);
        }

        h2 {
            font-size: 26px;
            margin-bottom: 12px;
            color: #f0f0f0;
        }

        .admin {
            color: #4ade80;
            font-weight: 600;
            font-size: 18px;
            margin: 10px 0 30px 0;
        }

        .user {
            color: #93c5fd;
            font-weight: 600;
            font-size: 18px;
            margin: 10px 0 30px 0;
        }

        .btn {
            display: inline-block;
            padding: 14px 28px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
            margin: 6px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-admin {
            background: #16a34a;
            color: #fff;
        }

        .btn-admin:hover {
            background: #15803d;
            transform: translateY(-2px);
        }

        .btn-logout {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .btn-logout:hover {
            background: rgba(255, 255, 255, 0.18);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="glass">
        <h2>欢迎登录</h2>

        <?php if ($is_admin == 1): ?>
            <p class="admin">管理员：<?php echo $username; ?></p>
            <a href="messages.php" class="btn btn-normal">留言板</a>
            <a href="admin.php" class="btn btn-admin">管理后台</a>
            <a href="logout.php" class="btn btn-logout">退出登录</a>
        <?php else: ?>
            <p class="user">用户：<?php echo $username; ?></p>
            <a href="messages.php" class="btn btn-normal">留言板</a>
            <a href="upload.php" class="btn btn-normal">上传图片</a>
            <a href="logout.php" class="btn btn-logout">退出登录</a>
        <?php endif; ?>
    </div>
</div>

</body>
</html>