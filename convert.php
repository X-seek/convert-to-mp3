<?php

header('Content-Type: application/json; charset=utf-8');

$uploadDir = __DIR__ . "/uploads/";
$outputDir = __DIR__ . "/output/";

if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);

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
        echo json_encode(["status" => "error", "message" => "ไม่สามารถอัปโหลดไฟล์ได้"]);
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| URL
|--------------------------------------------------------------------------
*/ elseif (isset($_POST['video_url']) && !empty(trim($_POST['video_url']))) {

    $url = trim($_POST['video_url']);

    /*
    | YouTube
    */
    if (strpos($url, "youtube.com") !== false || strpos($url, "youtu.be") !== false) {

        $ytDlp = "yt-dlp";
        $outputTemplate = $uploadDir . "%(title)s.%(ext)s";

        // เขียน cookies จาก env variable ลง temp file
        $cookieFile = "/tmp/yt_cookies_" . time() . ".txt";
        $cookieEnv = getenv('YOUTUBE_COOKIES');
        $cookieFlag = "";

        if ($cookieEnv && strlen(trim($cookieEnv)) > 10) {
            file_put_contents($cookieFile, $cookieEnv);
            $cookieFlag = " --cookies " . escapeshellarg($cookieFile);
        }

        $command = $ytDlp .
            $cookieFlag .
            " -f bestaudio" .
            " --no-playlist" .
            " --extractor-args youtube:player_client=web" .
            " -o " . escapeshellarg($outputTemplate) .
            " " . escapeshellarg($url);

        // รัน command และ redirect stderr ไป temp file
        $errFile = "/tmp/yt_err_" . time() . ".txt";
        $fullCommand = $command . " > /dev/null 2>" . escapeshellarg($errFile);
        shell_exec($fullCommand);
        $ytOutput = @file_get_contents($errFile);

        // ลบ temp files
        @unlink($cookieFile);
        @unlink($errFile);

        $files = glob($uploadDir . "*.*");

        if (empty($files)) {
            if (
                strpos($ytOutput, "Sign in to confirm") !== false ||
                strpos($ytOutput, "not a bot") !== false
            ) {
                echo json_encode([
                    "status" => "error",
                    "message" => "YouTube ปฏิเสธ (Bot Protection) — cookies อาจหมดอายุ"
                ]);
                exit;
            }

            if (strpos($ytOutput, "429") !== false) {
                echo json_encode([
                    "status" => "error",
                    "message" => "YouTube rate limit (Too Many Requests) — ลองใหม่ในอีกสักครู่"
                ]);
                exit;
            }

            echo json_encode([
                "status" => "error",
                "message" => "yt-dlp error: " . substr($ytOutput, -300)
            ]);
            exit;
        }

        rsort($files);
        $videoPath = $files[0];
    }
    /*
    | Direct URL
    */ else {
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

/*
|--------------------------------------------------------------------------
| ตรวจสอบไฟล์
|--------------------------------------------------------------------------
*/
if (!file_exists($videoPath)) {
    echo json_encode(["status" => "error", "message" => "ไม่พบไฟล์วิดีโอ"]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Convert MP3
|--------------------------------------------------------------------------
*/
$originalName = pathinfo(basename($videoPath), PATHINFO_FILENAME);
$originalName = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $originalName);

if (trim($originalName) === "") {
    $originalName = "audio_" . time();
}

$mp3Name = $originalName . ".mp3";
$mp3Path = $outputDir . $mp3Name;

$ffmpegCommand = "ffmpeg -y -i " .
    escapeshellarg($videoPath) .
    " -vn -codec:a libmp3lame -b:a 320k " .
    escapeshellarg($mp3Path) .
    " 2>/dev/null";

shell_exec($ffmpegCommand);

@unlink($videoPath);

/*
|--------------------------------------------------------------------------
| ส่งผลลัพธ์
|--------------------------------------------------------------------------
*/
if (file_exists($mp3Path)) {
    echo json_encode(["status" => "success", "file" => $mp3Name]);
} else {
    echo json_encode(["status" => "error", "message" => "Convert Failed"]);
}

exit;
