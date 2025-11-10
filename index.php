<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>QR Code Scanner with Camera Switch</title>

    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <style>
    body {
        font-family: Arial, sans-serif;
        background: #f5f5f5;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100vh;
        margin: 0;
    }

    #reader {
        width: 400px;
        max-width: 90vw;
        margin: 20px auto;
    }

    #result {
        font-size: 18px;
        background: #fff;
        padding: 10px 20px;
        border-radius: 8px;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        margin-bottom: 10px;
    }

    button {
        padding: 10px 20px;
        border-radius: 5px;
        border: none;
        background: #28a745;
        color: white;
        cursor: pointer;
        margin-bottom: 20px;
    }

    button:hover {
        background: #218838;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.6);
        justify-content: center;
        align-items: center;
    }

    .modal-content {
        background: #fff;
        padding: 25px;
        border-radius: 10px;
        text-align: center;
        width: 80%;
        max-width: 300px;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
        animation: popIn 0.3s ease;
    }

    @keyframes popIn {
        from {
            transform: scale(0.8);
            opacity: 0;
        }

        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    .modal h2 {
        margin: 0;
        color: #28a745;
    }
    </style>
</head>

<body>
    <h1>QR Code Scanner</h1>

    <button id="switchCameraBtn">Switch Camera</button>

    <div id="reader"></div>
    <div id="result">Result: <span id="decoded"></span></div>


    <audio id="successSound">
        <source src="successfully_login.mp3" type="audio/mpeg">
        Your browser does not support the audio element.
    </audio>

    <audio id="successfullylogout">
        <source src="successfully_logout.mp3" type="audio/mpeg">
        Your browser does not support the audio element.
    </audio>
    <audio id="already_login">
        <source src="already_login.mp3" type="audio/mpeg">
        Your browser does not support the audio element.
    </audio>
    <audio id="already_logout">
        <source src="already_logout.mp3" type="audio/mpeg">
        Your browser does not support the audio element.
    </audio>

    <audio id="qr_invalid">
        <source src="qr_invalid.mp3" type="audio/mpeg">
        Your browser does not support the audio element.
    </audio>

    <div id="successModal" class="modal">
        <div class="modal-content">
            <h2>✅ QR Code Scanned!</h2>
            <p id="modalText"></p>
        </div>
    </div>

    <script>
    const successSound = document.getElementById("successSound");
    const successfullylogout = document.getElementById("successfullylogout");

    const already_login = document.getElementById("already_login");
    const already_logout = document.getElementById("already_logout");
    const qr_invalid = document.getElementById("qr_invalid");
    const modal = document.getElementById("successModal");
    const modalText = document.getElementById("modalText");
    const decodedDisplay = document.getElementById("decoded");
    const switchCameraBtn = document.getElementById("switchCameraBtn");

    let html5QrCode;
    let cameras = [];
    let cameraIndex = 0;
    let isScanning = false;

    function showModal(text) {
        modalText.innerText = text;
        modal.style.display = "flex";

        setTimeout(() => {
            modal.style.display = "none";
            isScanning = true; // allow scanning again
            location.reload();
        }, 4000);
    }

    // ✅ AJAX Call using Fetch
    function sendToAPI(companyId) {
        fetch("https://ngsattendance.test/qrscan/scan", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    companyId: companyId
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log("API Response:", data.code);



                if (data.code == 200) {
                    successSound.currentTime = 0;
                    successSound.play().catch(err => console.warn("Audio play blocked:", err))
                    showModal("Scan Sent Successfully!" + data.code);
                } else if (data.code == 203) {
                    already_login.currentTime = 0;
                    already_login.play().catch(err => console.warn("Audio play blocked:", err))
                    showModal("You have already timed in." + data.code);
                } else if (data.code == 222) {
                    successfullylogout.currentTime = 0;
                    successfullylogout.play().catch(err => console.warn("Audio play blocked:", err))
                    showModal("Successfully timed out." + data.code);


                } else if (data.code == 223) {
                    already_logout.currentTime = 0;
                    already_logout.play().catch(err => console.warn("Audio play blocked:",
                        err))
                    showModal("You have already timed out." + data.code);

                } else {

                    qr_invalid.currentTime = 0;
                    qr_invalid.play().catch(err => console.warn("Audio play blocked:", err))
                    showModal("Qr invalid!" + data.code);

                }



            })
            .catch(error => {
                console.error("Error sending to API:", error);
                showModal("Error sending data!");
            });
    }










    function onScanSuccess(decodedText) {
        if (!isScanning) return;
        isScanning = false;

        decodedDisplay.innerText = decodedText;;

        // ✅ Call API here
        sendToAPI(decodedText);
    }

    function onScanFailure(error) {
        // ignored
    }

    function startScanning() {
        if (!cameras.length) return;

        const cameraId = cameras[cameraIndex].id;

        html5QrCode.start(
            cameraId, {
                fps: 10,
                qrbox: 250
            },
            onScanSuccess,
            onScanFailure
        ).then(() => {
            isScanning = true;
            console.log(`Scanning started on camera: ${cameras[cameraIndex].label}`);
        }).catch(err => console.error("Camera start error:", err));
    }

    function switchCamera() {
        if (!cameras.length) return;

        html5QrCode.stop().then(() => {
            cameraIndex = (cameraIndex + 1) % cameras.length;
            startScanning();
        }).catch(err => console.error("Failed to switch camera:", err));
    }

    switchCameraBtn.addEventListener("click", switchCamera);

    Html5Qrcode.getCameras().then(devices => {
        if (devices && devices.length) {
            cameras = devices;
            html5QrCode = new Html5Qrcode("reader");

            cameraIndex = devices.findIndex(c =>
                c.label.toLowerCase().includes('back') ||
                c.label.toLowerCase().includes('environment')
            );
            if (cameraIndex === -1) cameraIndex = 0;

            startScanning();
        } else {
            console.error("No cameras found.");
        }
    }).catch(err => console.error("Camera access error:", err));
    </script>
</body>

</html>