<?php

header('Content-Type: application/json; charset=utf-8');

$uploadDir = __DIR__ . "/uploads/";
$outputDir = __DIR__ . "/output/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

$videoPath = "";

/*
|--------------------------------------------------------------------------
| อัปโหลดไฟล์
|--------------------------------------------------------------------------
*/

if (
    isset($_FILES['video']) &&
    $_FILES['video']['error'] === 0 &&
    !empty($_FILES['video']['name'])
) {

    $videoName = time() . "_" . basename($_FILES['video']['name']);
    $videoPath = $uploadDir . $videoName;

    if (!move_uploaded_file($_FILES['video']['tmp_name'], $videoPath)) {
        echo json_encode([
            "status" => "error",
            "message" => "ไม่สามารถอัปโหลดไฟล์ได้"
        ]);
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| URL
|--------------------------------------------------------------------------
*/ elseif (
    isset($_POST['video_url']) &&
    !empty(trim($_POST['video_url']))
) {

    $url = trim($_POST['video_url']);

    /*
    |--------------------------------------------------------------------------
    | YouTube
    |--------------------------------------------------------------------------
    */

    if (
        strpos($url, "youtube.com") !== false ||
        strpos($url, "youtu.be") !== false
    ) {

        $ytDlp = "yt-dlp";

        $outputTemplate =
            $uploadDir .
            "%(title)s.%(ext)s";

        $command =
            $ytDlp .
            " -o " .
            escapeshellarg($outputTemplate) .
            " " .
            escapeshellarg($url) .
            " 2>&1";

        $ytOutput = shell_exec($command . " 2>&1");

        $files = glob($uploadDir . "*.*");

        if (empty($files)) {

            if (
                strpos($ytOutput, "Sign in to confirm") !== false ||
                strpos($ytOutput, "not a bot") !== false
            ) {
                echo json_encode([
                    "status" => "error",
                    "message" => "YouTube ปฏิเสธการดาวน์โหลดจากเซิร์ฟเวอร์ (Bot Protection)"
                ]);
                exit;
            }

            echo json_encode([
                "status" => "error",
                "message" => "yt-dlp ดาวน์โหลดไม่สำเร็จ"
            ]);
            exit;
        }

        rsort($files);
        $videoPath = $files[0];
    }

    /*
    |--------------------------------------------------------------------------
    | Direct Video URL
    |--------------------------------------------------------------------------
    */ else {

        $videoPath =
            $uploadDir .
            time() .
            "_video";

        $videoData = @file_get_contents($url);

        if ($videoData === false) {
            echo json_encode([
                "status" => "error",
                "message" => "ไม่สามารถดาวน์โหลดไฟล์จาก URL ได้"
            ]);
            exit;
        }

        file_put_contents($videoPath, $videoData);
    }
} else {

    echo json_encode([
        "status" => "error",
        "message" => "กรุณาเลือกไฟล์หรือใส่ URL"
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| ตรวจสอบไฟล์
|--------------------------------------------------------------------------
*/

if (!file_exists($videoPath)) {

    echo json_encode([
        "status" => "error",
        "message" => "ไม่พบไฟล์วิดีโอ"
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Convert MP3
|--------------------------------------------------------------------------
*/

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

if (trim($originalName) === "") {
    $originalName = "audio_" . time();
}

$mp3Name = $originalName . ".mp3";
$mp3Path = $outputDir . $mp3Name;

$ffmpegCommand =
    "ffmpeg -y -i " .
    escapeshellarg($videoPath) .
    " -vn -codec:a libmp3lame -b:a 320k " .
    escapeshellarg($mp3Path) .
    " 2>&1";

$ffmpegOutput = shell_exec($ffmpegCommand);

/*
|--------------------------------------------------------------------------
| ลบไฟล์ต้นฉบับ
|--------------------------------------------------------------------------
*/

@unlink($videoPath);

/*
|--------------------------------------------------------------------------
| ส่งผลลัพธ์
|--------------------------------------------------------------------------
*/

if (file_exists($mp3Path)) {

    echo json_encode([
        "status" => "success",
        "file" => $mp3Name
    ]);
} else {

    echo json_encode([
        "status" => "error",
        "message" => "Convert Failed"
    ]);
}

exit;
