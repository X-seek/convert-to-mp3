<?php

$uploadDir = "uploads/";
$outputDir = "output/";

// สร้างโฟลเดอร์หากยังไม่มี
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

$videoPath = "";
// ===========================
// กรณีอัปโหลดไฟล์
// ===========================
if (
    isset($_FILES['video']) &&
    $_FILES['video']['error'] == 0 &&
    !empty($_FILES['video']['name'])
) {

    $videoName = time() . "_" . basename($_FILES['video']['name']);

    $videoPath = $uploadDir . $videoName;

    move_uploaded_file(
        $_FILES['video']['tmp_name'],
        $videoPath
    );
}
// ===========================
// กรณีใส่ URL
// ===========================
elseif (
    isset($_POST['video_url']) &&
    !empty(trim($_POST['video_url']))
) {

    $url = trim($_POST['video_url']);

    $videoName = "%(title)s";
    $videoPath = $uploadDir . $videoName;

    // ลิงก์ YouTube
    if (
        strpos($url, "youtube.com") !== false ||
        strpos($url, "youtu.be") !== false
    ) {

        $ytDlp = 'C:\\tools\\yt-dlp-2026-06-09.exe';

        $command =
            '"' . $ytDlp . '" -f best -o "' .
            $videoPath .
            '%(title)s.%(ext)s" ' .
            escapeshellarg($url) .
            ' 2>&1';

        $result = shell_exec($command);
        $files = glob($uploadDir . '*.*');

        rsort($files);

        if (!empty($files)) {
            $videoPath = $files[0];
        } else {
            die("yt-dlp ดาวน์โหลดไม่สำเร็จ");
        }
    }

    // ลิงก์ไฟล์วิดีโอโดยตรง
    else {

        $videoData = @file_get_contents($url);

        if ($videoData === false) {
            die("ไม่สามารถดาวน์โหลดไฟล์จาก URL ได้");
        }

        file_put_contents(
            $videoPath,
            $videoData
        );
    }
} else {

    die("กรุณาเลือกไฟล์หรือใส่ URL");
}

// ===========================
// ตรวจสอบไฟล์
// ===========================
if (!file_exists($videoPath)) {
    die("ไม่พบไฟล์วิดีโอ");
}

// ===========================
// แปลงเป็น MP3
// ===========================
$originalName =
    pathinfo(
        basename($videoPath),
        PATHINFO_FILENAME
    );

$originalName =
    preg_replace(
        '/[^\p{L}\p{N}\s\-_]/u',
        '',
        $originalName
    );

$mp3Name = $originalName . ".mp3";

$mp3Path = $outputDir . $mp3Name;

$ffmpegCommand =
    'ffmpeg -y -i "' .
    $videoPath .
    '" -vn -codec:a libmp3lame -b:a 320k "' .
    $mp3Path .
    '" 2>&1';

shell_exec($ffmpegCommand);
echo json_encode([
    "status" => "success",
    "file" => $mp3Name
]);
exit;

// ===========================
// ดาวน์โหลดไฟล์ MP3
// ===========================
if (file_exists($mp3Path)) {

    header("Content-Type: audio/mpeg");
    header(
        'Content-Disposition: attachment; filename="' .
            $mp3Name .
            '"'
    );
    header(
        "Content-Length: " .
            filesize($mp3Path)
    );

    echo json_encode([
        "status" => "success",
        "file" => $mp3Name
    ]);
    exit;

    // ลบไฟล์ชั่วคราว
    @unlink($videoPath);
    @unlink($mp3Path);

    exit;
}

echo "Convert Failed";
