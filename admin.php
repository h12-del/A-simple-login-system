<link rel="stylesheet" href="style.css">
<?php
session_start();
require_once "guolv.php";
include 'pdo_connect.php';
$x = "";

// 管理员权限判断
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    $x = "不是管理员用户，2秒后返回登录页面";
    header("refresh:2;url=login.php");
    exit;
}

// 功能1：删除用户
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["del_id"])) {
    $del_id = test_input($_POST["del_id"]);

    $sql = "DELETE FROM registration_information WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":id" => $del_id]);

    if ($stmt->rowCount() > 0) {
        $x = "用户删除成功！";
    } else {
        $x = "删除失败！用户不存在";
    }
    goto END;
}

// 功能2：修改用户
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["edit_id"])) {
    $id = test_input($_POST["edit_id"]);
    $name = test_input($_POST["name"]);
    $email = test_input($_POST["email"]);
    $is_admin = test_input($_POST["is_admin"]);

    $sql = "UPDATE registration_information SET name=:name, email=:email, is_admin=:is_admin WHERE id=:id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
            ":name" => $name,
            ":email" => $email,
            ":is_admin" => $is_admin,
            ":id" => $id
    ]);

    if ($stmt->rowCount() > 0) {
        $x = "用户信息修改成功！";
    } else {
        $x = "修改失败！未找到用户或信息未变化！";
    }
    goto END;
}

// 功能3：图片上传
if ($_SERVER["REQUEST_METHOD"] == "POST" ) {

    $name = $_FILES['file']['name'];
    $tmp_name = $_FILES['file']['tmp_name'];
    $size = $_FILES['file']['size'];

    // 文件大小限制 20MB
    $maxsize = 20 * 1024 * 1024;
    if ($size > $maxsize) {
        $x = "你上传的文件大小超过最大限制";
        goto END;
    }

    // 拓展名限制
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $ally = ['jpg', 'jpeg', 'gif', 'png'];
    if (!in_array($ext, $ally)) {
        $x = "只允许上传jpg,png,gif类型的文件";
        goto END;
    }

    // MIME类型
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $type = finfo_file($finfo, $tmp_name);
    finfo_close($finfo);
    $ally = ['image/gif', 'image/jpeg', 'image/png'];
    if (!in_array($type, $ally)) {
        $x = "只允许上传jpg,png,gif类型的文件";
        goto END;
    }

    // 是否为图片
    $info = @getimagesize($tmp_name);
    if ($info === false) {
        $x = "只允许上传jpg,png,gif类型的文件";
        goto END;
    }

    // 分辨率
    $width = $info[0];
    $height = $info[1];
    if ($width > 4000 || $height > 4000) {
        $x = "你的图片分辨率过高";
        goto END;
    }

    // 内容过滤（防止PHP代码注入）
    $content = file_get_contents($tmp_name);
    $ally = ['<?php','<?PHP', '<?='];
    foreach ($ally as $value) {
        if (strpos($content, $value)!==false) {
            $x = "上传内容不合法";
            goto END;
        }
    }

    // 生成随机文件名，防止重名
    $newname = "upload/" . bin2hex(random_bytes(16)) . "." . $ext;
    if (move_uploaded_file($tmp_name, $newname)) {
        $x = "文件上传成功！";
        $last_upload = $newname; // 记录最后上传的图片路径，用于预览
    } else {
        $x = "文件上传失败，请检查目录权限";
    }
    goto END;
}

END:

// 查询所有用户数据
$userall = [];
$sql = "SELECT * FROM registration_information ORDER BY id";
$stmt = $pdo->prepare($sql);
$stmt->execute();
while ($row = $stmt->fetch()) {
    $userall[] = $row;
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <title>管理员页面</title>
</head>
<body>

<h2>管理员用户管理</h2>
<!-- 提示信息：显示成功/失败 -->
<p class="tip"><?php echo $x; ?></p>

<!-- 图片上传功能 -->
<div class="card-panel">
    <h3>图片上传</h3>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="file" accept="image/*" multiple><br><br>
        <button type="submit">上传文件</button>
    </form>

    <!-- 上传成功后显示预览 -->
    <?php if (isset($last_upload)): ?>
        <div class="preview-area">
            <p>上传预览：</p>
            <img src="<?php echo $last_upload; ?>" alt="上传的图片" style="max-width: 300px; max-height: 300px; border-radius: 12px;">
            <p>路径：<?php echo $last_upload; ?></p>
        </div>
    <?php endif; ?>
</div>

<!-- 用户列表表格 -->
<div class="card-panel">
    <table>
        <tr>
            <th>序号</th>
            <th>用户名</th>
            <th>邮箱</th>
            <th>角色</th>
            <th>功能</th>
        </tr>
        <?php foreach ($userall as $user): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo $user['name']; ?></td>
                <td><?php echo $user['email']; ?></td>
                <td><?php echo $user['is_admin'] == 1 ? "管理员" : "普通用户"; ?></td>
                <td>
                    <button onclick="openEditModal(
                            '<?php echo $user['id']; ?>',
                            '<?php echo $user['name']; ?>',
                            '<?php echo $user['email']; ?>',
                            '<?php echo $user['is_admin']; ?>'
                            )">编辑</button>

                    <form method="post" onsubmit="return confirm('确定要删除吗？')" style="display:inline;">
                        <input type="hidden" name="del_id" value="<?php echo $user['id']; ?>">
                        <button type="submit">删除</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<br>
<a href="logout.php">退出登录</a>

<!-- 修改用户弹窗（不占用页面高度） -->
<div id="editModal" class="modal-overlay">
    <div class="modal-content">
        <span class="modal-close" onclick="closeEditModal()">&times;</span>
        <h3>修改用户信息</h3>
        <form method="post">
            <input type="hidden" name="edit_id" id="edit_id">
            用户名：<br>
            <input type="text" name="name" id="edit_name" required><br><br>
            邮箱：<br>
            <input type="email" name="email" id="edit_email" required><br><br>
            角色：<br>
            <select name="is_admin" id="edit_is_admin" required>
                <option value="1">管理员</option>
                <option value="0">普通用户</option>
            </select><br><br>
            <button type="submit">保存修改</button>
        </form>
    </div>
</div>

<script>
    // 打开修改弹窗并填充数据
    function openEditModal(id, name, email, is_admin) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_is_admin').value = is_admin;
        document.getElementById('editModal').style.display = 'flex';
    }

    // 关闭修改弹窗
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    // 点击弹窗背景也能关闭
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });
</script>
</body>
</html>