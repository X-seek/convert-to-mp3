console.log("TO MP3 Ready");

const form = document.getElementById("convertForm");
const progressBox = document.getElementById("progressBox");
const progressBar = document.getElementById("progressBar");
const button = document.getElementById("convertBtn");
const fileInput = document.querySelector('input[name="video"]');
const urlInput = document.querySelector('input[name="video_url"]');

form.addEventListener("submit", async function (e) {
    e.preventDefault();

    if (fileInput.files.length === 0 && urlInput.value.trim() === "") {
        progressBox.style.display = "block";
        progressBar.style.width = "100%";
        progressBar.innerHTML = "ไม่ได้เพิ่มไฟล์หรือลิงก์";
        return;
    }

    progressBox.style.display = "block";
    button.disabled = true;
    button.innerHTML = "Converting...";

    let progress = 0;
    const interval = setInterval(() => {
        if (progress < 90) {
            progress += 5;
            progressBar.style.width = progress + "%";
            progressBar.innerHTML = progress + "%";
        }
    }, 300);

    try {
        const formData = new FormData(form);
        const res = await fetch("convert.php", {
            method: "POST",
            body: formData
        });

        clearInterval(interval);
        const data = await res.json();

        // ✅ เช็ค error ก่อน
        if (data.status !== "success" || !data.file) {
            progressBar.style.width = "100%";
            progressBar.innerHTML = "❌ Error: " + (data.message || "Convert Failed");
            button.disabled = false;
            button.innerHTML = "Convert To MP3";
            return;
        }

        progressBar.style.width = "100%";
        progressBar.innerHTML = "✅ Complete!";
        button.innerHTML = "⬇️ Download";
        button.disabled = false;

        button.onclick = () => {
            const link = document.createElement("a");
            link.href = "output/" + data.file;
            link.download = data.file;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            setTimeout(() => location.reload(), 1000);
        };

    } catch (err) {
        clearInterval(interval);
        progressBar.style.width = "100%";
        progressBar.innerHTML = "❌ เกิดข้อผิดพลาด กรุณาลองใหม่";
        button.disabled = false;
        button.innerHTML = "Convert To MP3";
    }
});

// Drop Zone
const dropZone = document.getElementById("dropZone");
const selectedFile = document.getElementById("selectedFile");
const clearFileBtn =
    document.getElementById("clearFileBtn");

dropZone.addEventListener("click", () => fileInput.click());

fileInput.addEventListener("change", () => {
    if (fileInput.files.length > 0) {
        selectedFile.innerHTML =
            "✓ " + fileInput.files[0].name;
        clearFileBtn.style.display = "inline-block";
    }
});

document.addEventListener("dragover", (e) => {
    e.preventDefault();
    dropZone.classList.add("dragover");
});

document.addEventListener("dragleave", (e) => {
    if (e.clientX === 0 || e.clientY === 0) {
        dropZone.classList.remove("dragover");
    }
});

document.addEventListener("drop", (e) => {
    e.preventDefault();
    dropZone.classList.remove("dragover");

    if (e.dataTransfer.files.length > 0) {
        const dt = new DataTransfer();
        dt.items.add(e.dataTransfer.files[0]);
        fileInput.files = dt.files;
        selectedFile.classList.add("show");
        selectedFile.innerHTML =
            "✓ เลือกไฟล์แล้ว : " +
            fileInput.files[0].name;
    }
});
clearFileBtn.addEventListener("click", () => {
    fileInput.value = "";
    selectedFile.innerHTML = "";
    clearFileBtn.style.display = "none";
});
