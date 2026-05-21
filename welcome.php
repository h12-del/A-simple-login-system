<?php
session_start();
require_once 'guolv.php';

// 创建回复表（如果不存在）
$createReplyTable = "CREATE TABLE IF NOT EXISTS message_reply (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    reply_name VARCHAR(100) NOT NULL,
    reply_content TEXT NOT NULL,
    reply_time DATETIME NOT NULL,
    FOREIGN KEY (message_id) REFERENCES message(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

include 'pdo_connect.php';

if (!isset($_SESSION['name'])) {
    header('Location: login.php');
    exit();
}

// 创建回复表
try {
    $pdo->exec($createReplyTable);
} catch (PDOException $e) {
    // 表已存在或创建失败
}

$is_admin = $_SESSION['is_admin'];
$username = $_SESSION['name'];
$px = "";
$mx = "";
$rx = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['old_pwd'])) {
    $old_pwd = test_input(isset($_POST['old_pwd']) ? $_POST['old_pwd'] : '');
    $new_pwd = test_input(isset($_POST['new_pwd']) ? $_POST['new_pwd'] : '');
    $confirm_pwd = test_input(isset($_POST['confirm_pwd']) ? $_POST['confirm_pwd'] : '');
    
    $sql = "SELECT password FROM registration_information WHERE name = :name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['name' => $username]);
    $row = $stmt->fetch();
    if (!password_verify($old_pwd, $row['password'])) {
        $px = "原密码错误";
        goto END;
    }
    if (!preg_match('/^[A-Za-z0-9]{3,6}$/', $new_pwd)) {
        $px = "密码只能是字母或数字，长度3到6位";
        goto END;
    }
    if ($new_pwd != $confirm_pwd) {
        $px = "两次密码不一致！";
        goto END;
    }
    $hash_pwd = password_hash($new_pwd, PASSWORD_DEFAULT);
    $sql = "UPDATE registration_information SET password = :password WHERE name = :name";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute(['password' => $hash_pwd, 'name' => $username]);
    if ($result) {
        $px = "密码修改成功！";
    } else {
        $error = $stmt->errorInfo();
        $px = "密码修改失败！" . $error[2];
    }
    goto END;
}
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT name FROM message WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    if ($stmt->rowCount() == 0) {
        $mx = "留言不存在";
        goto END;
    }
    $row = $stmt->fetch();
    $author = $row['name'];
    if (!($is_admin == 1 || $author == $username)) {
        $mx = "你没有权限删除这条留言！";
        goto END;
    }
    $sql = "DELETE  FROM message WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute(['id' => $id]);
    if ($result) {
        $mx = "删除成功！";
    } else {
        $error = $stmt->errorInfo();
        $mx = "删除失败" . $error[2];
        goto END;
    }
}
// 处理留言发布
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['content']) && !isset($_POST['reply_content'])) {
    $content = test_input(isset($_POST['content']) ? $_POST['content'] : '');
    if (empty($content)) {
        $mx = "留言不能为空！";
        goto END;
    }
    $sql = "INSERT INTO message (name, content, created_at) VALUES (:name, :content,  NOW())";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute(['name' => $username, 'content' => $content]);
    if ($result) {
        $mx = "留言发布成功！";
    } else {
        $error = $stmt->errorInfo();
        $mx = "留言发布失败！" . $error[2];
        goto END;
    }
}

// 处理回复提交
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reply_content']) && isset($_POST['message_id'])) {
    $reply_content = test_input(isset($_POST['reply_content']) ? $_POST['reply_content'] : '');
    $message_id = intval($_POST['message_id']);
    
    if (empty($reply_content)) {
        $rx = "回复内容不能为空！";
        goto END;
    }
    
    // 检查留言是否存在
    $checkSql = "SELECT id FROM message WHERE id = :id";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute(['id' => $message_id]);
    if ($checkStmt->rowCount() == 0) {
        $rx = "留言不存在！";
        goto END;
    }
    
    $sql = "INSERT INTO message_reply (message_id, reply_name, reply_content, reply_time) VALUES (:message_id, :reply_name, :reply_content, NOW())";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute(['message_id' => $message_id, 'reply_name' => $username, 'reply_content' => $reply_content]);
    if ($result) {
        $rx = "回复成功！";
    } else {
        $error = $stmt->errorInfo();
        $rx = "回复失败！" . $error[2];
    }
    goto END;
}
END:
// 获取所有留言及其回复
$sql = "SELECT * FROM message ORDER BY created_at DESC";
$stmt = $pdo->query($sql);
$all_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 为每条留言获取回复
foreach ($all_messages as &$msg) {
    $replySql = "SELECT * FROM message_reply WHERE message_id = :message_id ORDER BY reply_time ASC";
    $replyStmt = $pdo->prepare($replySql);
    $replyStmt->execute(['message_id' => $msg['id']]);
    $msg['replies'] = $replyStmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($msg);
?>
<link rel="stylesheet" href="222.css">
<!DOCTYPE html>
<html lang="zh-cn" xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="UTF-8">
    <title>欢迎页面</title>
</head>
<body>
<div class="welcome-area">
    <h1>欢迎登录</h1>
    <p>用户：<?php echo test_input($username) ?></p>
    <?php if ($is_admin == 1) : ?>
        <a href="admin.php" class="btn btn-green">管理后台</a>
    <?php endif; ?>
    <a href="../222/logout.php" class="btn btn-red">退出登录</a>
</div>
<div class="welcome-container">
    <div class="pwd-area">
        <h2>修改密码</h2>
        <?php if (!empty($px)) : ?>
            <p class="msg-tip"><?php echo test_input($px); ?></p>
        <?php endif; ?>
        <form method="post">
            <label for="old_pwd">原密码:</label>
            <input id="old_pwd" type="password" name="old_pwd" placeholder="请输入原密码"><br><br>
            <label for="new_pwd">新密码:</label>
            <input id="new_pwd" type="password" name="new_pwd" placeholder="请输入新密码"><br><br>
            <label for="confirm_pwd">确认新密码:</label>
            <input id="confirm_pwd" type="password" name="confirm_pwd" placeholder="再次输入新密码"><br><br>
            <button type="submit">修改密码</button>
        </form>
    </div>
    <div class="board-area">
        <h2>留言板</h2>
        <p>当前用户：<?php echo $username ?></p>
        <?php if (!empty($mx)) : ?>
            <p class="msg-tip"><?php echo test_input($mx); ?></p>
        <?php endif; ?>
        <?php if (!empty($rx)) : ?>
            <p class="msg-tip"><?php echo test_input($rx); ?></p>
        <?php endif; ?>
        <form method="post">
            <p>留言内容：</p>
            <textarea name="content" rows="4"></textarea><br><br>
            <button type="submit" class="btn btn-blue">提交留言</button>
        </form>
        <h3 class="list-title">留言列表</h3>
        <?php foreach ($all_messages as $row) : ?>
            <div class="message-item">
                <div class="message-header">
                    <span class="message-user"><?php echo test_input($row['name']); ?></span>
                    <span class="message-time"><?php echo $row['created_at']; ?></span>
                </div>
                <div class="message-content"><?php echo test_input($row['content']); ?></div>
                <div class="message-actions">
                    <?php if ($is_admin == 1 || $row['name'] == $username) : ?>
                        <a href="?id=<?php echo $row['id']; ?>" onclick="return confirm('确认删除吗？')" class="delete-link">删除留言</a>
                    <?php endif; ?>
                </div>
                
                <!-- 回复列表 -->
                <?php if (!empty($row['replies'])) : ?>
                    <div class="reply-list">
                        <?php foreach ($row['replies'] as $reply) : ?>
                            <div class="reply-item">
                                <div class="reply-header">
                                    <span class="reply-user"><?php echo test_input($reply['reply_name']); ?></span>
                                    <span class="reply-time"><?php echo $reply['reply_time']; ?></span>
                                </div>
                                <div class="reply-content"><?php echo test_input($reply['reply_content']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- 回复表单 -->
                <div class="reply-form-container">
                    <form method="post" class="reply-form">
                        <input type="hidden" name="message_id" value="<?php echo $row['id']; ?>">
                        <textarea name="reply_content" rows="2" placeholder="写下你的回复..." required></textarea>
                        <button type="submit" class="btn-reply">回复</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
