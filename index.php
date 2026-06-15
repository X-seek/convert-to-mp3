<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TO MP3 Converter</title>
    <link rel="stylesheet" href="assets/style1.css">
</head>

<body>

    <div class="bg"></div>
    <div class="container">
        <div class="card">
            <h1>TO MP3</h1>
            <p>Upload Video → Convert → Download MP3</p>

            <form action="convert.php"
                method="POST"
                enctype="multipart/form-data"
                id="convertForm">
                <!-- DROP ZONE -->
                <div class="drop-zone" id="dropZone">
                    <div class="drop-text">
                        🎬 Drag & Drop Video Here<br>
                        หรือคลิกเพื่อเลือกไฟล์
                    </div>
                </div>

                <input
                    type="file"
                    name="video"
                    accept="video/*"
                    id="videoInput">

                <!-- แสดงชื่อไฟล์ -->
                <div id="selectedFile"></div>
                <p>OR</p>
                <input
                    type="url"
                    name="video_url"
                    placeholder="วางลิงก์วิดีโอ">
                <!-- PROGRESS -->
                <div class="progress-box" id="progressBox">
                    <div class="progress-bar" id="progressBar">0%</div>
                </div>
                <button type="submit" id="convertBtn">
                    Convert To MP3
                </button>
            </form>
        </div>
    </div>

    <script src="assets/script1.js"></script>

</body>

</html>