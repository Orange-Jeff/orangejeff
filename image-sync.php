<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>NetBound Tools: Image Sync</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f4f4f9;
            width: 768px;
            margin: 0 auto;
        }

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
            margin-top: 10px;
            width: 100%;
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
            gap: 10px;
            flex-wrap: nowrap;
            align-items: center;
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
            white-space: nowrap;
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

        .file-info {
            color: #666;
            font-size: 12px;
            margin: 2px 0;
            display: none;
        }

        .save-button-container {
            display: flex;
            width: fit-content;
            margin-left: auto;
        }

        .save-button {
            background: #0056b3;
            color: white;
            border: none;
            padding: 6px 12px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border-top-left-radius: 3px;
            border-bottom-left-radius: 3px;
        }

        .download-button {
            background: #0056b3;
            color: white;
            border: none;
            border-left: 1px solid rgba(255, 255, 255, 0.3);
            padding: 6px 8px;
            cursor: pointer;
            font-size: 14px;
            border-top-right-radius: 3px;
            border-bottom-right-radius: 3px;
        }

        .save-button:disabled,
        .download-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

.progress {
    position: absolute; /* Positioned within audio-waveform */
    left: 0;
    top: 0;
    height: 4px; /* Reduced height for waveform area */
    width: 100%;
    background: rgba(204, 204, 204, 0.5); /* Semi-transparent light gray background */
    border-radius: 0; /* No border radius */
    overflow: hidden;
    margin-top: 0; /* Reset margin-top */
}

        .progress-bar {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%; /* Back to 100% height of container */
            background: #0056b3; /* Dark button blue color */
            width: 0%;
            transition: width 0.1s linear;
            border: none; /* Removed red border */
        }

        .progress-text {
            position: absolute;
            right: 5px;
            top: -18px;
            font-size: 12px;
            color: #666;
        }

        .content-area {
            width: 100%;
            height: 432px;
            background: #2a2a2a;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 10px 0;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
        }

        canvas {
            max-width: 100%;
            max-height: 100%;
        }

        .audio-container {
            width: 100%;
            margin: 10px 0 5px 0;
            background: #f8f8f8;
            border-radius: 4px;
        }

        .audio-waveform {
            width: 100%;
            height: 60px;
            background: #f0f0f0;
            /* Light background to match the canvas */
            position: relative;
            display: none;
        }

        .playhead {
            position: absolute;
            top: 0;
            left: 0;
            width: 2px;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: none;
        }

        .filename-control {
            width: 100%;
            display: flex;
            gap: 10px;
            margin: 5px 0;
        }

        .output-control {
            width: 100%;
            display: flex;
            gap: 10px;
            margin: 5px 0;
        }

        .output-info {
            flex: 1;
            font-size: 14px;
            color: #555;
        }

        .filename-input {
            flex: 1;
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .duration-input {
            width: 80px;
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
            font-family: monospace;
        }

        .timer {
            color: #666;
            margin-left: 10px;
        }

        .hidden {
            display: none;
        }

        .status-bar.drag-over {
            background: #e3f2fd;
            border-color: #2196f3;
            border-style: dashed;
        }

        .status-message.processing {
            position: relative;
            overflow: hidden;
        }

        .processing-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: #4caf50;
            transition: width 0.1s linear;
            z-index: 1;
        }
    </style>
