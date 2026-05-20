<?php
require_once 'D:\php\过滤.php';
$x = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_FILES['file']['name'];
    $tmp_name = $_FILES['file']['tmp_name'];
    $size = $_FILES['file']['size'];
//    判断文件大小
    $maxsize = 20 * 1024 * 1024;
    if ($size > $maxsize){
        $x = "你上传的文件大小超过最大限制";
        goto END;
    }

//    拓展名限制
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $ally = ['jpg', 'jpeg', 'gif', 'png'];
    if (!in_array($ext, $ally)) {
        $x = "只允许上传jpj,png,gif类型的文件";
        goto END;
    }
//    MIME类型
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $type = finfo_file($finfo, $tmp_name);
    finfo_close($finfo);
    $ally=['image/gif', 'image/jpeg', 'image/png'];
    if (!in_array($type, $ally)) {
        $x = "只允许上传jpj,png,gif类型的文件";
        goto END;
    }

//    是否为图片
    $info = @getimagesize($tmp_name);
    if ($info === false) {
        $x = "只允许上传jpg,png,gif类型的文件";
        goto END;
    }

//    分辨率
    $width = $info[0];
    $height = $info[1];
    if ($width > 4000 || $height > 4000) {
        $x = "你的图片分辨率过高";
        goto END;
    }

//内容过滤

    $content = file_get_contents($tmp_name);
    $ally = ['<?php','<?PHP', '<?='];
    foreach ($ally as $value) {
        if (strpos($content, $value)!==false) {
            $x = "上传内容不合法";
            goto END;
        }
    }
    $newname = "upload/".bin2hex(random_bytes(16)).".".$ext;
    if(move_uploaded_file($tmp_name, $newname)) {
        $x = "文件上传成功，路径为：".$newname;
    }

END:
}


?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <title>注册</title>
</head>
<body>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="file" multiple>
        <input type="submit">
    </form>

<br>
    <p style="color:green"><?php echo test_input($x) ?></p>
</body>
</html>

