<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NB Image Overlay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="shared-styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f8ff; /* Light blue background */
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }

        h1 {
            color: #007bff; /* Blue title */
            text-align: center;
            margin-top: 20px;
        }

        .upload-area {
            margin: 20px;
            text-align: center;
        }

        .upload-label {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff; /* Blue upload button */
            color: white;
            cursor: pointer;
            border-radius: 5px;
        }

        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin: 20px;
        }

        .image-preview {
            max-width: 768px; /* Match 768p */
            max-height: 500px;
            margin: 5px;
            border: 1px solid #ddd;
            padding: 5px;
            box-sizing: border-box;
        }

        .overlay-controls {
            margin: 20px;
            text-align: center;
        }

        #imageCanvas {
            display: block;
            margin: 20px auto;
            border: 1px solid #ddd;
            max-width: 768px; /* Match 768p */
        }
    </style>
</head>
<body>
    <h1>NB Image Overlay</h1>

    <div class="upload-area">
        <input type="file" id="imageUpload" accept="image/*" multiple>
        <label for="imageUpload" class="upload-label">Upload Images</label>
    </div>

    <div id="imagePreviewContainer" class="image-preview-container">
        <!-- Image previews will be displayed here -->
    </div>

    <div class="overlay-controls">
        <input type="text" id="overlayText" placeholder="Enter text to overlay">
        <select id="fontSelect">
            <option value="Arial">Arial</option>
            <option value="Verdana">Verdana</option>
            <option value="Times New Roman">Times New Roman</option>
            <option value="Courier New">Courier New</option>
        </select>
        <input type="color" id="textColor" value="#000000">
    </div>

    <canvas id="imageCanvas"></canvas>

    <script>
        document.getElementById('imageUpload').addEventListener('change', function(event) {
            const files = event.target.files;
            const previewContainer = document.getElementById('imagePreviewContainer');

            previewContainer.innerHTML = ''; // Clear previous previews

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();

                reader.onload = function(e) {
                    const image = document.createElement('img');
                    image.src = e.target.result;
                    image.classList.add('image-preview');
                    previewContainer.appendChild(image);
                }

                reader.readAsDataURL(file);
            }
        });
    </script>

    <script>
        const canvas = document.getElementById('imageCanvas');
        const ctx = canvas.getContext('2d');
        const imageUpload = document.getElementById('imageUpload');
        const overlayText = document.getElementById('overlayText');
        const fontSelect = document.getElementById('fontSelect');
        const textColor = document.getElementById('textColor');
        let currentImage = null;
        let currentImageIndex = 0;
        let images = [];

        imageUpload.addEventListener('change', function(event) {
            const files = event.target.files;
            images = []; // Clear previous images
            currentImageIndex = 0;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();

                reader.onload = function(e) {
                    const image = new Image();
                    image.src = e.target.result;
                    images.push(image);

                    if (i === 0) {
                        currentImage = image;
                        currentImage.onload = function() {
                            resizeCanvas();
                            drawImage();
                        }
                    }
                }

                reader.readAsDataURL(file);
            }
        });

        overlayText.addEventListener('input', drawImage);
        fontSelect.addEventListener('change', drawImage);
        textColor.addEventListener('input', drawImage);

        function resizeCanvas() {
            const maxWidth = window.innerWidth;
            const maxHeight = window.innerHeight;
            let width = currentImage.width;
            let height = currentImage.height;

            if (width > maxWidth) {
                height *= maxWidth / width;
                width = maxWidth;
            }

            if (height > maxHeight) {
                width *= maxHeight / height;
            }

            canvas.width = width;
            canvas.height = height;
        }

        function drawImage() {
            if (!currentImage) return;

            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(currentImage, 0, 0, canvas.width, canvas.height);

            ctx.font = `30px ${fontSelect.value}`;
            ctx.fillStyle = textColor.value;
            ctx.textAlign = 'center';
            ctx.fillText(overlayText.value, canvas.width / 2, canvas.height / 2);
        }

        function saveImage() {
            if (!currentImage) return;

            const dataURL = canvas.toDataURL('image/png');
            const link = document.createElement('a');
            link.href = dataURL;
            link.download = 'image-with-overlay.png';
            link.click();
        }

        function showNextImage() {
            currentImageIndex = (currentImageIndex + 1) % images.length;
            currentImage = images[currentImageIndex];
            currentImage.onload = function() {
                resizeCanvas();
                drawImage();
            }
        }

        function showPreviousImage() {
            currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
            currentImage = images[currentImageIndex];
            currentImage.onload = function() {
                resizeCanvas();
                drawImage();
            }
        }

        function showNextNextNextImage() {
            currentImageIndex = (currentImageIndex + 3) % images.length;
            currentImage = images[currentImageIndex];
            currentImage.onload = function() {
                resizeCanvas();
                drawImage();
            }
        }

        const nextButton = document.createElement('button');
        nextButton.textContent = 'Next';
        nextButton.addEventListener('click', showNextImage);
        document.body.appendChild(nextButton);

        const previousButton = document.createElement('button');
        previousButton.textContent = 'Previous';
        previousButton.addEventListener('click', showPreviousImage);
        document.body.appendChild(previousButton);

         const nextNextNextButton = document.createElement('button');
        nextNextNextButton.textContent = 'Next Next Next';
        nextNextNextButton.addEventListener('click', showNextNextNextImage);
        document.body.appendChild(nextNextNextButton);

        const saveButton = document.createElement('button');
        saveButton.textContent = 'Save Image';
        saveButton.addEventListener('click', saveImage);
        document.body.appendChild(saveButton);
    </script>
</body>
</html>
<?php
// NB-image-overlay.php
?>
