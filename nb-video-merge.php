<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>NetBound Tools: Video Merger</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f4f4f9;
            width: 768px;
            margin: 0 auto;
        }

        /* In iframe, use left alignment */
        body.in-iframe {
            margin: 0;
        }

        .tool-container {
            background: #f4f4f9;
            height: auto;
            margin: 0;
            max-width: 768px;
            width: 100%;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .work-area {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .preview-area {
            margin-top: 5px;
            width: 100%;
        }

        .files-container {
            display: flex;
            width: 100%;
            gap: 10px;
            margin-bottom: 10px;
        }

        .tool-title {
            margin: 10px 0;
            padding: 0;
            color: #0056b3;
            line-height: 1.2;
            font-weight: bold;
            font-size: 18px;
        }

        .button-controls {
            width: 100%;
            padding: 10px 0;
            display: flex;
            justify-content: flex-start;
            gap: 10px;
            flex-wrap: nowrap;
            margin-top: 10px;
        }

        .command-button {
            background: #0056b3;
            color: white;
            border: none;
            border-radius: 3px;
            padding: 6px 8px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .command-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .command-button:hover:not(:disabled) {
            background: #004494;
        }

        .status-bar {
            width: 100%;
            height: 90px;
            min-height: 90px;
            max-height: 90px;
            overflow-y: auto;
            border: 1px solid #ddd;
            background: #fff;
            padding: 5px;
            margin: 10px 0;
            border-radius: 4px;
            display: flex;
            flex-direction: column-reverse;
            box-sizing: border-box;
        }

        .status-progress {
            height: 100%;
            width: 0%;
            background: linear-gradient(to right, #4CAF50, #2E7D32);
            transition: width 0.3s ease;
            position: relative;
        }

        .status-text {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.3);
            z-index: 2;
        }

        .status-message {
            padding: 5px;
            margin: 2px 0;
            border-radius: 3px;
            color: #666;
        }

        .status-message:first-child {
            color: white;
        }

        .status-message.info {
            border-left: 3px solid #2196f3;
        }

        .status-message.info:first-child {
            background: #2196f3;
        }

        .status-message.success {
            border-left: 3px solid #4caf50;
        }

        .status-message.success:first-child {
            background: #4caf50;
        }

        .status-message.error {
            border-left: 3px solid #f44336;
        }

        .status-message.error:first-child {
            background: #f44336;
        }

        .filename-control {
            width: 100%;
            display: flex;
            gap: 10px;
            margin: 10px 0;
        }

        .filename-input {
            flex: 1;
            padding: 6px 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-family: inherit;
            font-size: 14px;
        }

        .status-bar.drag-over {
            background: #e3f2fd;
            border-color: #2196f3;
            border-style: dashed;
        }

        .save-button-container {
            display: flex;
            justify-content: center;
            width: 100%;
            margin: 10px 0;
        }

        .save-button {
            background: #0056b3;
            color: white;
            border: none;
            border-radius: 3px;
            padding: 8px 16px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: background-color 0.2s;
            width: 100%;
            max-width: 300px;
        }

        .save-button:hover:not(:disabled) {
            background: #004494;
        }

        .save-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .content-area {
            width: 100%;
            height: 300px;
            background: #2a2a2a;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 10px 0;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            position: relative;
        }

        .file-info {
            margin: 3px 0 0 0;
            padding-bottom: 2px;
        }

        .merge-controls {
            margin: 5px 0 !important;
            display: flex;
            gap: 10px;
            justify-content: center;
            width: 100%;
        }

        .save-button-container {
            margin: 5px 0 !important;
            width: 100%;
        }

        /* Video preview and controls */
        .preview-area {
            margin-top: 5px;
        }

        /* Progress bar styling */
        .progress-container {
            width: 100%;
            height: 30px;
            background-color: #e9ecef;
            border-radius: 4px;
            margin: 5px 0;
            overflow: hidden;
            position: relative;
            border: 1px solid #dee2e6;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(to right, #4CAF50, #2E7D32);
            width: 0;
            transition: width 0.3s ease;
            position: relative;
            box-shadow: inset 0 -1px 1px rgba(255, 255, 255, 0.3);
        }

        .progress-text {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
            z-index: 2;
        }

        .progress-details {
            position: absolute;
            right: 8px;
            top: 0;
            color: white;
            font-size: 12px;
            line-height: 30px;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.3);
        }

        .tool-container {
            position: relative;
            min-height: 100vh;
            padding-bottom: 60px;
        }

        .video-preview {
            width: 100%;
            height: 100%;
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            background: #000;
        }

        .file-info {
            flex: 1;
            padding: 4px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 2px 0 0 0;
            position: relative;
            padding-bottom: 0;
        }

        .file-info-layout {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .file-info-content {
            flex: 1;
            min-width: 0;
        }

        .file-info-title {
            font-weight: bold;
            margin-bottom: 2px;
            color: #0056b3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            position: relative;
            cursor: default;
            display: inline-block;
            max-width: 100px;
            vertical-align: bottom;
            font-size: 13px;
        }

        .file-info-title::after {
            content: attr(title);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.9);
            color: #fff;
            padding: 8px 10px;
            border-radius: 4px;
            font-size: 13px;
            white-space: normal;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s, visibility 0.2s;
            z-index: 100;
            pointer-events: none;
            max-width: 400px;
            text-align: left;
            word-break: break-word;
            line-height: 1.4;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .file-info-title:hover::after {
            opacity: 1;
            visibility: visible;
        }

        .file-details {
            font-size: 12px;
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        * {
            box-sizing: border-box;
        }

        /* Improved filename truncation */
        .file-info-title {
            max-width: 100px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
            vertical-align: bottom;
            font-size: 13px;
            position: relative;
            cursor: default;
        }

        .file-info-title::after {
            content: attr(title);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.9);
            color: #fff;
            padding: 8px 10px;
            border-radius: 4px;
            font-size: 13px;
            white-space: normal;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s, visibility 0.2s;
            z-index: 100;
            pointer-events: none;
            max-width: 400px;
            text-align: left;
            word-break: break-word;
            line-height: 1.4;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .file-info-title:hover::after {
            opacity: 1;
            visibility: visible;
        }

        .progress-indeterminate {
            background: linear-gradient(to right, #0056b3 30%, #4b96e1 50%, #0056b3 70%);
            background-size: 200% 100%;
            animation: progress-animation 1.5s linear infinite;
        }

        @keyframes progress-animation {
            0% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        .progress-indeterminate {
            background: linear-gradient(to right, #0056b3 30%, #4b96e1 50%, #0056b3 70%);
            background-size: 200% 100%;
            animation: progress-animation 1.5s linear infinite;
        }

        @keyframes progress-animation {
            0% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        .file-progress-container {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 24px;
            background-color: #e9ecef;
            border-radius: 0 0 4px 4px;
            overflow: hidden;
            display: none;
            border-top: 1px solid #dee2e6;
        }

        .file-progress-bar {
            height: 100%;
            width: 0%;
            background: linear-gradient(to right, #17a2b8, #138496);
            transition: width 0.3s ease;
            position: relative;
        }

        .file-progress-text {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 12px;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.3);
            z-index: 2;
        }

        .file-progress-indeterminate {
            background: repeating-linear-gradient(45deg,
                    #0056b3,
                    #0056b3 10px,
                    #4b96e1 10px,
                    #4b96e1 20px);
            background-size: 200% 100%;
            animation: progress-move 15s linear infinite;
        }

        @keyframes progress-move {
            0% {
                background-position: 0% 0%;
            }

            100% {
                background-position: 100% 0%;
            }
        }

        .thumbnail-container {
            width: 50px;
            min-width: 50px;
            height: 50px;
            background-color: #000;
            border-radius: 3px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2px;
        }

        .video-thumbnail {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        /* Ghost button style */
        .command-button.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* Add these styles after your existing CSS */

        .format-badge {
            position: absolute;
            top: 4px;
            right: 4px;
            background: #0056b3;
            color: white;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            z-index: 10;
        }

        .processing-pulse {
            animation: pulse 2s infinite;
            background-image: linear-gradient(45deg,
                    rgba(255, 255, 255, 0.15) 25%,
                    transparent 25%,
                    transparent 50%,
                    rgba(255, 255, 255, 0.15) 50%,
                    rgba(255, 255, 255, 0.15) 75%,
                    transparent 75%,
                    transparent);
            background-size: 40px 40px;
            animation: progress-bar-stripes 1s linear infinite, pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }

            100% {
                opacity: 1;
            }
        }

        @keyframes progress-bar-stripes {
            from {
                background-position: 40px 0;
            }

            to {
                background-position: 0 0;
            }
        }

        /* Fix for thumbnail container to position badges correctly */
        .thumbnail-container {
            position: relative;
        }

        /* Make progress more visible */
        .progress-container {
            display: block !important;
            /* Always show progress container */
            margin: 15px 0;
            height: 40px;
            /* Taller progress bar */
        }

        .progress-text {
            font-size: 14px !important;
            line-height: 1.2;
            padding: 2px;
        }
    </style>
</head>






















// Handle first video selection
firstFileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        firstVideo = e.target.files[0];
        document.getElementById('firstFileName').textContent = firstVideo.name;
        document.getElementById('firstFileName').title = firstVideo.name;
        document.getElementById('firstFileDetails').textContent =
            `${(firstVideo.size / (1024 * 1024)).toFixed(2)} MB`;



































        // Create thumbnail
        const objectUrl = URL.createObjectURL(firstVideo);
        videoPreview.src = objectUrl;
        document.getElementById('firstThumbnail').src = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';


















































































































































































































































































































































































        updateStatus(`First video selected: ${firstVideo.name}`, 'success');
    }
});
</body>

</html>
