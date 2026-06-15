<?php

$uploadDir = "uploads/";
$outputDir = "output/";

if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);

$videoPath = "";

// กรณีอัปโหลดไฟล์
if (
    isset($_FILES['video']) &&
    $_FILES['video']['error'] == 0 &&
    !empty($_FILES['video']['name'])
) {
    $videoName = time() . "_" . basename($_FILES['video']['name']);
    $videoPath = $uploadDir . $videoName;
    move_uploaded_file($_FILES['video']['tmp_name'], $videoPath);
}
// กรณีใส่ URL
elseif (isset($_POST['video_url']) && !empty(trim($_POST['video_url']))) {

    $url = trim($_POST['video_url']);

    // ลิงก์ YouTube
    if (strpos($url, "youtube.com") !== false || strpos($url, "youtu.be") !== false) {

        $ytDlp = 'yt-dlp'; // ✅ แก้จาก Windows path
        $outputTemplate = $uploadDir . '%(title)s.%(ext)s';
        $command = $ytDlp . ' -f best -o ' . escapeshellarg($outputTemplate) . ' ' . escapeshellarg($url) . ' 2>&1';
        shell_exec($command);

        $files = glob($uploadDir . '*.*');
        rsort($files);

        if (!empty($files)) {
            $videoPath = $files[0];
        } else {
            echo json_encode(["status" => "error", "message" => "yt-dlp ดาวน์โหลดไม่สำเร็จ"]);
            exit;
        }
    }
    // ลิงก์ไฟล์วิดีโอโดยตรง
    else {
        $videoPath = $uploadDir . time() . "_video";
        $videoData = @file_get_contents($url);
        if ($videoData === false) {
            echo json_encode(["status" => "error", "message" => "ไม่สามารถดาวน์โหลดไฟล์จาก URL ได้"]);
            exit;
        }
        file_put_contents($videoPath, $videoData);
    }
} else {
    echo json_encode(["status" => "error", "message" => "กรุณาเลือกไฟล์หรือใส่ URL"]);
    exit;
}

// ตรวจสอบไฟล์
if (!file_exists($videoPath)) {
    echo json_encode(["status" => "error", "message" => "ไม่พบไฟล์วิดีโอ"]);
    exit;
}

// แปลงเป็น MP3
$originalName = pathinfo(basename($videoPath), PATHINFO_FILENAME);
$originalName = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $originalName);
$mp3Name = $originalName . ".mp3";
$mp3Path = $outputDir . $mp3Name;

$ffmpegCommand = 'ffmpeg -y -i ' . escapeshellarg($videoPath) . ' -vn -codec:a libmp3lame -b:a 320k ' . escapeshellarg($mp3Path) . ' 2>&1';
shell_exec($ffmpegCommand);

// ลบไฟล์วิดีโอชั่วคราว
@unlink($videoPath);

// ส่งผลลัพธ์
if (file_exists($mp3Path)) {
    echo json_encode(["status" => "success", "file" => $mp3Name]);
} else {
    echo json_encode(["status" => "error", "message" => "Convert Failed"]);
}
exit;
