console.log("TO MP3 Ready");

const form = document.getElementById("convertForm");
const progressBox = document.getElementById("progressBox");
const progressBar = document.getElementById("progressBar");
const button = document.getElementById("convertBtn");

const fileInput = document.getElementById("videoInput");
const urlInput = document.querySelector('input[name="video_url"]');

const dropZone = document.getElementById("dropZone");
const selectedFile = document.getElementById("selectedFile");
const clearFileBtn = document.getElementById("clearFileBtn");

// --------------------
// Submit
// --------------------

form.addEventListener("submit", async function (e) {
    e.preventDefault();

    const hasFile =
        fileInput &&
        fileInput.files &&
        fileInput.files.length > 0;

    const hasUrl =
        urlInput &&
        urlInput.value.trim() !== "";

    if (!hasFile && !hasUrl) {

        progressBox.style.display = "block";
        progressBar.style.width = "100%";
        progressBar.innerHTML =
            "❌ ไม่ได้เพิ่มไฟล์หรือลิงก์";

        return;
    }

    progressBox.style.display = "block";
    progressBar.style.width = "0%";
    progressBar.innerHTML = "0%";
    button.disabled = true;
    button.innerHTML = "Converting...";

    let progress = 0;
    const interval = setInterval(() => {

        if (progress < 90) {
            progress += 5;
            progressBar.style.width =
                progress + "%";
            progressBar.innerHTML =
                progress + "%";
        }
    }, 300);

    try {

        const formData =
            new FormData(form);
        const res = await fetch(
            "convert.php",
            {
                method: "POST",
                body: formData
            }
        );
        clearInterval(interval);
        const text =
            await res.text();

        let data;
        try {
            data = JSON.parse(text);
        }
        catch {
            progressBar.style.width =
                "100%";
            progressBar.innerHTML =
                "❌ Server Error";
            console.error(text);
            button.disabled = false;
            button.innerHTML =
                "Convert To MP3";
            return;
        }
        if (
            data.status !== "success" ||
            !data.file
        ) {
            progressBar.style.width =
                "100%";
            progressBar.innerHTML =
                "❌ " +
                (data.message ||
                    "Convert Failed");
            button.disabled = false;
            button.innerHTML =
                "Convert To MP3";
            return;
        }
        progressBar.style.width =
            "100%";
        progressBar.innerHTML =
            "✅ Complete!";
        button.disabled = false;
        button.innerHTML =
            "⬇️ Download";
        button.onclick = () => {
            const link =
                document.createElement("a");
            link.href =
                "output/" + data.file;
            link.download =
                data.file;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            setTimeout(() => {
                location.reload();

            }, 1000);
        };
    }
    catch (err) {

        clearInterval(interval);
        console.error(err);
        progressBar.style.width =
            "100%";
        progressBar.innerHTML =
            "❌ เกิดข้อผิดพลาด กรุณาลองใหม่";
        button.disabled = false;
        button.innerHTML =
            "Convert To MP3";
    }
});
// --------------------
// Click DropZone
// --------------------
if (dropZone && fileInput) {
    dropZone.addEventListener(
        "click",
        () => {
            fileInput.click();
        }
    );
}
// --------------------
// Choose File
// --------------------

if (fileInput) {
    fileInput.addEventListener(
        "change",
        () => {
            if (
                fileInput.files &&
                fileInput.files.length > 0
            ) {
                selectedFile.innerHTML =
                    "✓ เลือกไฟล์แล้ว : " +
                    fileInput.files[0].name;
                selectedFile.classList.add(
                    "show"
                );
                if (clearFileBtn) {
                    clearFileBtn.style.display =
                        "inline-block";
                }
            }
        }
    );
}

// --------------------
// Drag Over
// --------------------

document.addEventListener(
    "dragover",
    (e) => {
        e.preventDefault();

        if (dropZone) {

            dropZone.classList.add(
                "dragover"
            );
        }
    }
);
// --------------------
// Drag Leave
// --------------------

document.addEventListener(
    "dragleave",
    (e) => {
        if (
            e.clientX === 0 ||
            e.clientY === 0
        ) {

            if (dropZone) {

                dropZone.classList.remove(
                    "dragover"
                );
            }
        }
    }
);
// --------------------
// Drop File
// --------------------

document.addEventListener(
    "drop",
    (e) => {
        e.preventDefault();

        if (dropZone) {

            dropZone.classList.remove(
                "dragover"
            );
        }
        if (
            !fileInput ||
            !e.dataTransfer ||
            e.dataTransfer.files.length === 0
        ) {
            return;
        }
        try {
            const dt =
                new DataTransfer();
            dt.items.add(
                e.dataTransfer.files[0]
            );
            fileInput.files =
                dt.files;
        }
        catch (err) {
            console.error(
                "DataTransfer Error",
                err
            );
        }
        selectedFile.innerHTML =
            "✓ เลือกไฟล์แล้ว : " +
            e.dataTransfer.files[0].name;
        selectedFile.classList.add(
            "show"
        );
        if (clearFileBtn) {

            clearFileBtn.style.display =
                "inline-block";
        }
    }
);
// --------------------
// Clear File
// --------------------

if (clearFileBtn) {
    clearFileBtn.addEventListener(
        "click",
        () => {
            if (fileInput) {

                fileInput.value = "";
            }
            selectedFile.innerHTML = "";

            selectedFile.classList.remove(
                "show"
            );
            clearFileBtn.style.display =
                "none";
        }
    );
}
