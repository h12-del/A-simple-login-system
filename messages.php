<?php
session_start();
require_once "D:\php\过滤.php";
include 'pdo_connect.php';

// 检查登录
if (!isset($_SESSION['name'])) {
    echo "请先登录，2秒后跳转...";
    header("refresh:2;url=login.php");
    exit;
}


$current_user = $_SESSION['name'];
$is_admin = $_SESSION['is_admin'];
$x ="";
// 处理删除留言（增加权限判断）
if (isset($_GET["id"])) {
    $id = $_GET["id"];
    // 2.1 查询留言作者
    $sql_check = "SELECT name FROM message WHERE id = :id";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([':id' => $id]);
    if ($stmt_check->rowCount() == 0) {
        $x = "留言不存在！";
        goto END;
    }
    $row = $stmt_check->fetch();
    $author = $row['name'];

    // 2.2 权限判断：管理员 或 留言作者本人
    if (!($is_admin ==1 || $author == $current_user)) {
        $x = "你没有权限删除这条留言!";
        goto END;
    }
    //2.3 执行删除
    $sql_del = "DELETE FROM message WHERE id = :id";
    $stmt_del = $pdo->prepare($sql_del);
    if ($stmt_del->execute([':id' => $id])) {
        $x = "删除成功！";
        header("Location: messages.php");
        exit;
    } else {
        $x = "删除失败！";
    }
    goto END;
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $current_user;
    $content= test_input(isset($_POST["content"]) ? $_POST["content"] : "");

    if (empty($content)) {
        $x = "留言内容不能为空！";
        goto END;
    }

    $sql = "INSERT INTO message (name, content, created_at) VALUES (:name, :content, NOW())";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([':name' => $name, ':content' => $content]);
    if ($result) {
        $x = "数据插入成功！新建ID为：" . $pdo->lastInsertId();
    } else {
        $error = $stmt->errorInfo();
        $x = "数据插入失败" . $error[2];
    }
    goto END;
}
END:
$sql ="SELECT * FROM message ORDER BY created_at DESC";
$stmt = $pdo->query($sql);
$all_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>留言板</title>
</head>
<body>

<h2>留言板</h2>
<p>当前登录用户：<strong><?php echo $current_user ?? '未登录';?></strong>
    <?php if (isset($is_admin) && $is_admin == 1) echo "(管理员)"; ?></p>
<p style="color:red;"><?php echo $x; ?></p>

<hr>
<?php if (isset($current_user)):?>

    <form method="POST">
        <p>留言内容：</p>
        <textarea name="content" rows="4" cols="50" required></textarea><br><br>
        <button type="submit">提交留言</button>
    </form>
<?php else: ?>
    <p>请先登录后再留言。</p>
<?php endif; ?>

<hr>

<h3>留言列表</h3>

<?php
if (count($all_messages) > 0) {
    echo "查询到了" . count($all_messages) . "条留言：" . "<br>";
    foreach ($all_messages as $row) {
        echo $row['created_at']."<br>";
        echo "用户名：".$row['name']."<br>";
        echo "内容：".$row['content']."<br>";

        //显示删除链接的条件：登录用户 且 (管理员 或 留言作者是当前用户)
        if (isset($current_user) && ($is_admin == 1 || $row['name'] == $current_user)) {
            echo "<a href='messages.php?id=" . $row['id'] . "' onclick='return confirm(\"确定删除吗？\");'>删除留言</a><br>";
        }
        echo "<hr>";
    }


} else{
    echo "暂无留言";

}
?>

</body>
</html>

