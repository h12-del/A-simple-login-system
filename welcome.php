<link rel="stylesheet" href="style.css">
<?php
// ===================== 固定代码（禁止修改） =====================
session_start();                    // 开启会话，维持登录状态
require_once "guolv.php";     // 引入全局过滤脚本，防止恶意内容
include 'pdo_connect.php';// 连接数据库文件

// ===================== 登录验证 =====================
// 如果用户未登录，跳转到登录页
if (!isset($_SESSION['name'])) {
    header("Location: login.php");
    exit;
}

// ===================== 定义用户基础变量 =====================
$is_admin = $_SESSION['is_admin'];   // 是否管理员（1=是，0=否）
$username = $_SESSION['name'];       // 当前登录用户名


// ===================== 分开提示变量（互不干扰） =====================
$px = "";  // 修改密码区域的提示
$mx = "";  // 留言板区域的提示

// ===================== 处理修改密码功能 =====================
// 只有点击修改密码表单才会执行
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['old_pwd'])) {
    // 获取并过滤表单数据
    $old_pwd = test_input(isset($_POST['old_pwd']) ? $_POST['old_pwd'] : "");
    $new_pwd = test_input(isset($_POST['new_pwd']) ? $_POST['new_pwd'] : "");
    $confirm_pwd = test_input(isset($_POST['confirm_pwd']) ? $_POST['confirm_pwd'] : "");

    // 查询当前用户的加密密码
    $sql = "SELECT password FROM registration_information WHERE name = :name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':name' => $username]);
    $row = $stmt->fetch();

    // 验证原密码是否正确
    if ( !password_verify($old_pwd, $row['password'])) {
        $px = "原密码错误！";
        goto END;
    }


    // 判断两次新密码是否一致
    if ($new_pwd != $confirm_pwd) {
        $px = "两次输入的新密码不一致！";
        goto END;
    }

    // 新密码加密
    $hash_pwd = password_hash($new_pwd, PASSWORD_DEFAULT);

    // 更新数据库密码
    $sql = "UPDATE registration_information SET password = :password WHERE name = :name";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([':password' => $hash_pwd, ':name' => $username]);
    if ($result) {
        $px = "密码修改成功！";
    } else {
        $error = $stmt->errorInfo();
        $px = "密码修改失败！" . $error[2];
    }
    goto END;
}

// ===================== 处理删除留言功能 =====================
if (isset($_GET["id"])) {
    $id = $_GET["id"];

    // 查询留言id
    $sql = "SELECT name FROM message WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() == 0) {
        $mx = "留言不存在";
        goto END;
    }

    //查询留言作者姓名
    $row = $stmt->fetch();
    $author = $row['name'];
    // 权限判断：本人或管理员才能删除
    if (!($is_admin == 1 || $author == $username)) {
        $mx = "你没有权限删除这条留言!";
        goto END;
    }

    // 执行删除
    $sql = "DELETE FROM message WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([':id' => $id]);
    if ($result) {
        $mx = "删除成功！";
    }else {
        $error = $stmt->errorInfo();
        $mx = "删除失败！" . $error[2];
        goto END;
    }
}

// ===================== 处理发表留言功能 =====================
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['old_pwd'])) {
    $content = test_input(isset($_POST['content']) ? $_POST['content'] : "");

    if (empty($content)) {
        $mx = "留言内容不能为空！";
        goto END;
    }

    // 插入留言到数据库
    $sql = "INSERT INTO message (name, content, created_at) VALUES (:name, :content, NOW())";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([':name' => $username ,':content' => $content]);
    if ($result) {
        $mx = "留言发布成功！";
    }else{
        $error = $stmt->errorInfo();
        $mx ="留言发布失败!" . $error[2];
        goto END;
    }
}

// ===================== 代码结束标记 =====================
END:

// ===================== 查询所有留言 =====================
$sql = "SELECT * FROM message ORDER BY created_at DESC";
$stmt = $pdo->query($sql);
$all_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>欢迎登录</title>
</head>
<body>

<!-- 顶部欢迎区域 -->
<div class="welcome-area">
    <h1>欢迎登录</h1>
    <p>用户：<?php echo $username; ?></p>
    <?php if ($is_admin == 1): ?>
        <a href="admin.php" class="btn btn-green">管理后台</a>
    <?php endif; ?>
    <a href="logout.php" class="btn btn-red">退出登录</a>
</div>

<!-- 左右横排布局容器（样式全部在外联CSS里） -->
<div class="welcome-container">

    <!-- 左侧：修改密码区域 -->
    <div class="pwd-area">
        <h2>修改密码</h2>
        <!-- 密码区自己的提示 -->
        <?php if (!empty($px)): ?>
            <p class="msg-tip"><?php echo $px; ?></p>
        <?php endif; ?>

        <form method="post">
            <p>原密码：</p>
            <input type="password" name="old_pwd" required>
            <p>新密码：</p>
            <input type="password" name="new_pwd" required>
            <p>确认新密码：</p>
            <input type="password" name="confirm_pwd" required>
            <button type="submit" class="btn btn-orange">修改密码</button>
        </form>
    </div>

    <!-- 右侧：留言板区域 -->
    <div class="board-area">
        <h2>留言板</h2>
        <p>当前用户：<?php echo $username; ?></p>

        <!-- 留言区自己的提示 -->
        <?php if (!empty($mx)): ?>
            <p class="msg-tip"><?php echo $mx; ?></p>
        <?php endif; ?>

        <form method="post">
            <p>留言内容：</p>
            <textarea name="content" rows="4"></textarea>
            <button type="submit" class="btn btn-blue">提交留言</button>
        </form>

        <h3 class="list-title">留言列表</h3>
        <?php foreach($all_messages as $row): ?>
            <div class="message-item">
                <p>时间：<?php echo $row['created_at']; ?></p>
                <p>用户：<?php echo $row['name']; ?></p>
                <p>内容：<?php echo $row['content']; ?></p>

                <?php if ($is_admin == 1 || $row['name'] == $username): ?>
                    <a href="?id=<?php echo $row['id']; ?>" onclick="return confirm('确定删除吗？')" class="delete-link">删除留言</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

</div>

</body>
</html>