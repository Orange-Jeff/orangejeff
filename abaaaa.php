<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Progress Test</title>
    <style>
        .status-bar {
            width: 100%;
            max-width: 500px;
            margin: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .status-message {
            position: relative;
            padding: 10px;
            margin: 5px 0;
            background: #fff;
            border-radius: 3px;
            font-family: sans-serif;
        }

        .status-message.processing {
            background: #2196f3;
            color: white;
            padding-right: 50px;
        }

        .status-message.processing::before {
            content: attr(data-progress) '%';
            position: absolute;
            right: 10px;
            font-weight: bold;
        }

        .progress-fill {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 0%;
            background: rgba(255, 255, 255, 0.2);
            transition: width 0.1s linear;
        }
    </style>
</head>

<body>
    <div class="status-bar" id="statusBar"></div>
    <button onclick="startTest()">Start Progress Test</button>

    <script>
        const status = {
            currentProgress: null,

            update(message, type = 'info', isProcessing = false) {
                const container = document.getElementById('statusBar');
                container.innerHTML = ''; // Clear previous

                const messageDiv = document.createElement('div');
                messageDiv.className = `status-message ${isProcessing ? 'processing' : ''}`;

                if (isProcessing) {
                    messageDiv.setAttribute('data-progress', '0');
                    messageDiv.innerHTML = `${message}<div class="progress-fill"></div>`;
                    this.currentProgress = messageDiv;
                } else {
                    messageDiv.textContent = message;
                }

                container.appendChild(messageDiv);
                return messageDiv;
            },

            updateProgress(percent) {
                if (this.currentProgress) {
                    const p = Math.min(100, Math.round(percent));
                    console.log('Updating progress:', p + '%');

                    this.currentProgress.setAttribute('data-progress', p);
                    const fill = this.currentProgress.querySelector('.progress-fill');
                    if (fill) {
                        fill.style.width = `${p}%`;
                    }
                }
            }
        };

        function startTest() {
            const duration = 5; // 5 seconds test
            const progressMsg = status.update('Testing progress...', 'info', true);
            const startTime = Date.now();

            function updateFrame() {
                const elapsed = (Date.now() - startTime) / 1000;
                const progress = (elapsed / duration) * 100;

                console.log('Frame update:', {
                    elapsed: elapsed.toFixed(1),
                    progress: progress.toFixed(1)
                });

                status.updateProgress(progress);
                progressMsg.firstChild.textContent =
                    `Testing... ${Math.max(0, duration - elapsed).toFixed(1)}s remaining`;

                if (elapsed < duration) {
                    requestAnimationFrame(updateFrame);
                } else {
                    status.update('Test complete!');
                }
            }

            requestAnimationFrame(updateFrame);
        }
    </script>
</body>

</html>
