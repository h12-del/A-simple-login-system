<link rel="stylesheet" href="222.css">
<?php
session_start();
require_once 'guolv.php';
include 'pdo_connect.php';
$x = "";
$newname = [];
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    $x = "不是管理员用户，2秒后返回登陆页面";
    header('refresh:2;url=login.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['del_id'])) {
    $del_id = test_input($_POST['del_id']);
    $sql = "DELETE FROM registration_information WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $del_id]);
    if ($stmt->rowCount() > 0) {
        $x = "用户删除成功！";
    } else {
        $x = "删除失败，用户不存在！";
    }
    goto END;
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_id'])) {
    $id = test_input($_POST['edit_id']);
    $email = test_input($_POST['edit_email']);
    $is_admin = test_input($_POST['edit_is_admin']);

    $sql = "SELECT id FROM registration_information WHERE email = :email AND id <> :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email, ':id' => $id]);

    if ($stmt->rowCount() > 0) {
        $x = "该邮箱已被其他用户使用，无法重复！";
        goto END;
    }

    $sql = "UPDATE registration_information SET email =:email , is_admin =:is_admin WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([ ':email' => $email, ':id' => $id, ':is_admin' => $is_admin]);
    if ($stmt->rowCount() > 0) {
        $x = "用户修改信息成功！";
    } else {
        $x = "修改失败！";
    }
    goto END;
}

// 添加新用户
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $add_name = test_input($_POST['add_name']);
    $add_email = test_input($_POST['add_email']);
    $add_password = $_POST['add_password'];
    $add_is_admin = test_input($_POST['add_is_admin']);

    // 验证用户名不为空
    if (empty($add_name)) {
        $x = "用户名不能为空！";
        goto END;
    }

    // 验证用户名是否重复
    $sql = "SELECT id FROM registration_information WHERE name = :name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':name' => $add_name]);
    if ($stmt->rowCount() > 0) {
        $x = "用户名已存在，请更换！";
        goto END;
    }

    // 验证邮箱格式
    if (!filter_var($add_email, FILTER_VALIDATE_EMAIL)) {
        $x = "邮箱格式不正确！";
        goto END;
    }

    // 验证邮箱是否重复
    $sql = "SELECT id FROM registration_information WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $add_email]);
    if ($stmt->rowCount() > 0) {
        $x = "邮箱已被注册，请更换！";
        goto END;
    }

    // 验证密码：只包含字母和数字，3-6位
    if (!preg_match('/^[a-zA-Z0-9]{3,6}$/', $add_password)) {
        $x = "密码只能包含字母和数字，长度3-6位！";
        goto END;
    }

    // ✅ 密码加密（PHP官方推荐加密方式）
    $hashed_password = password_hash($add_password, PASSWORD_DEFAULT);

    // ✅ 插入新用户到数据库
    $sql = "INSERT INTO registration_information (name, email, password, is_admin) VALUES (:name, :email, :password, :is_admin)";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
            ':name' => $add_name,
            ':email' => $add_email,
            ':password' => $hashed_password,
            ':is_admin' => $add_is_admin
    ]);

    if ($result) {
        $x = "新用户添加成功！";
    } else {
        $x = "添加用户失败！";
    }
    goto END;
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $filecount = count($_FILES['file']['name']);
    for ($i = 0; $i < $filecount; $i++) {
        $name = $_FILES['file']['name'][$i];
        $tmp_name = $_FILES['file']['tmp_name'][$i];
        $size = $_FILES['file']['size'][$i];
        if (empty($tmp_name)) {
            $x .= "未上传文件<br>";
            continue;
        }

        $maxsize = 20 * 1024 * 1024;
        if ($size > $maxsize) {
            $x .= "你上传的文件大小超过最大限制！<br>";
            continue;
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $ally = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($ext, $ally)) {
            $x .= "只允许上传jpg, jpeg, png, gif类型的文件<br>";
            continue;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $type = finfo_file($finfo, $tmp_name);
        finfo_close($finfo);
        $ally = ['image/jpg', 'image/gif', 'image/jpeg', 'image/png',];
        if (!in_array($type, $ally)) {
            $x .= "只允许上传jpg, jpeg, png, gif类型的文件<br>";
            continue;
        }
        $info = @getimagesize($tmp_name);
        if ($info === false) {
            $x .= "只允许上传jpg, jpeg, png, gif类型的文件<br>";
            continue;
        }
        $width = $info[0];
        $height = $info[1];
        if ($width > 4000 || $height > 4000) {
            $x .= "你的图片分辨率太高<br>";
            continue;
        }
        $content = file_get_contents($tmp_name);
        $ally = ['<?php', '<?PHP', '<?='];
        $has_php = false;
        foreach ($ally as $value) {
            if (strpos($content, $value) !== false) {
                $has_php = true;
                break;
            }
        }
        if($has_php){
            $x .= "上传内容不合法<br>";
            continue;
        }
        if (!is_dir('upload')) {
            mkdir('upload', 0755, true);
        }
        $file_path = "upload/" . bin2hex(random_bytes(16)) . "." . $ext;
        if (move_uploaded_file($tmp_name, $file_path)) {
            $x .= "文件上传成功: " . $file_path . "<br>";
            $newname[] = $file_path;
        } else {
            $x .= "文件上传失败<br>";
        }
    }
}
END:
$userall = [];
$sql = "SELECT * FROM registration_information ORDER BY id ";
$stmt = $pdo->prepare($sql);
$stmt->execute();
while ($row = $stmt->fetch()) {
    $userall[] = $row;
}
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <title>管理员页面</title>
</head>
<body>
<h2>管理员页面</h2>
<p class="tip"><?php echo $x; ?></p>

<!-- 左右布局容器 -->
<div class="admin-container">
    <!-- 左侧：添加新用户 -->
    <div class="admin-left">
        <h3>添加新用户</h3>
        <div class="card-panel">
            <form method="post">
                <input type="hidden" name="add_user" value="1">
                <div class="form-group">
                    <label for="add_name">用户名</label>
                    <input type="text" id="add_name" name="add_name" placeholder="请输入用户名" required>
                </div>
                <div class="form-group">
                    <label for="add_email">邮箱</label>
                    <input type="email" id="add_email" name="add_email" placeholder="请输入邮箱" required>
                </div>
                <div class="form-group">
                    <label for="add_password">密码</label>
                    <input type="text" id="add_password" name="add_password" placeholder="3-6位字母和数字" required pattern="[a-zA-Z0-9]{3,6}" title="密码只能包含字母和数字，长度3-6位">
                </div>
                <div class="form-group">
                    <label for="add_is_admin">角色</label>
                    <select id="add_is_admin" name="add_is_admin" required>
                        <option value="0">普通用户</option>
                        <option value="1">管理员</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit">添加用户</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 右侧：图片上传 -->
    <div class="admin-right">
        <h3>图片上传</h3>
        <div class="card-panel">
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="file_upload">选择图片</label>
                    <input type="file" id="file_upload" name="file[]" accept="image/*" multiple>
                </div>
                <div class="form-actions">
                    <button type="submit">上传图片</button>
                </div>
            </form>
            <?php if (!empty($newname)): ?>
                <div class="preview-area show">
                    <p class="preview-title">上传预览（共 <?php echo count($newname); ?> 张）</p>
                    <?php foreach ($newname as $img): ?>
                        <div class="preview-item">
                            <img src="<?php echo $img; ?>">
                            <p class="preview-path"><?php echo $img; ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
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
                    <button onclick="openEditModal('<?php echo $user['id']; ?>',
                            '<?php echo addslashes($user['name']); ?>',
                            '<?php echo $user['email']; ?>',
                            '<?php echo $user['is_admin']; ?>')">编辑</button>
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
<a href="../222/logout.php">退出登录</a>
<div id="editModal" class="modal-overlay">
    <div class="modal-content">
        <span class="modal-close" onclick="closeEditModal()">&times;</span>
        <h3>修改用户信息</h3>
        <form method="post">
            <input type="hidden" name="edit_id" id="edit_id">
            <label for="edit_name">用户名:</label>
            <input id="edit_name" type="text" name="edit_name" placeholder="请输入用户名" required readonly disabled><br><br>
            <label for="edit_email">邮箱:</label>
            <input id="edit_email" type="email" name="edit_email" placeholder="请输入邮箱" required><br><br>
            <label for="edit_is_admin">角色:</label>
            <select id="edit_is_admin" name="edit_is_admin" required>
                <option value="1">管理员</option>
                <option value="0">普通用户</option>
            </select><br><br>
            <button type="submit">保存修改</button>
        </form>
    </div>
</div>

<script>
    function openEditModal(id, name, email, is_admin) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_is_admin').value = is_admin;
        document.getElementById('editModal').style.display = 'flex';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    document.getElementById('editModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeEditModal();
        }
    });
</script>
</body>
</html>