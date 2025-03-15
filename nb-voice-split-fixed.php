<?php
// filepath: /e:/orangejeff/Speaker-split.php
// filename: audio_waveform_editor.php
// Version 1.4
// Created by: NetBound Team
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetBound Tools: Audio Splitter</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f4f4f9;
            width: 100%;
            max-width: 768px;
            margin: 0 auto;
            box-sizing: border-box;
        }

        /* Layout Components */
        .container {
            background: #f4f4f9;
            width: 100%;
            margin: 0;
            box-sizing: border-box;
            padding: 0 20px;
        }

        .editor-header {
            background: #f4f4f9;
            padding: 0 0 10px 0;
            border-bottom: 1px solid #dee2e6;
            width: 100%;
            box-sizing: border-box;
        }

        .editor-title {
            margin: 20px 0 8px 0;
            color: #0056b3;
            line-height: 1.2;
            font-weight: bold;
            font-size: 18px;
        }

        /* Waveform container with responsive design */
        #waveform-container {
            position: relative;
            margin: 20px 0;
            padding: 10px;
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            width: 100%;
            box-sizing: border-box;
        }

        .waveform-wrapper {
            position: relative;
            width: 100%;
            min-height: 150px;
            background: #fff;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 10px;
            transition: min-height 0.3s ease;
            padding: 5px;
            box-sizing: border-box;
        }

        #waveform {
            width: 100%;
            height: 150px;
            background-color: #e0f0ff;
            border: 2px solid #0056b3;
            position: relative;
            transition: all 0.3s ease;
            overflow: hidden;
            box-sizing: border-box;
        }

        #waveform.stereo {
            height: 180px;
        }

        .waveform-wrapper.stereo {
            min-height: 180px;
        }

        /* Channel labels */
        .channel-label {
            position: absolute;
            left: 5px;
            font-size: 12px;
            color: #666;
            z-index: 2;
            background-color: rgba(255, 255, 255, 0.8);
            padding: 2px 5px;
            border-radius: 3px;
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s ease;
        }

        #waveform.stereo .channel-label {
            opacity: 1;
        }

        .channel-label.left {
            top: 5px;
        }

        .channel-label.right {
            bottom: 5px;
        }

        /* Button and controls styling */
        .button-controls,
        .button-group {
            width: 100%;
            padding: 10px 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .button-blue {
            background-color: #0056b3;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            min-width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            padding: 0 8px;
            font-size: 14px;
            position: relative;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .button-blue:hover {
            background-color: #004494;
        }

        .button-blue:active,
        .button-blue.playing {
            transform: translateY(1px);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            background-color: #004494;
        }

        .button-blue.warning {
            background-color: #6c757d;
        }

        .button-blue.warning:hover {
            background-color: #5a6268;
        }

        /* Play button special styling */
        #playPause {
            width: 40px;
        }

        #playPause i.fas {
            transition: all 0.2s ease;
        }

        #playPause.playing i.fas {
            transform: scale(1.1);
        }

        /* Controls responsive layout */
        .controls {
            display: flex;
            justify-content: center;
            margin: 10px 0;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Make controls wrap better on mobile */
        @media (max-width: 576px) {
            .controls {
                justify-content: flex-start;
            }

            .button-blue {
                min-width: calc(25% - 10px);
                flex-grow: 1;
            }
        }

        .zoom-controls {
            margin: 0;
            gap: 10px;
            display: flex;
            flex-wrap: nowrap;
        }

        /* Status bar and logs */
        .persistent-status-bar {
            width: 100%;
            height: 84px;
            min-height: 84px;
            max-height: 84px;
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
            margin: 0;
            font-size: 13px;
            color: #666;
            padding: 2px 5px;
            line-height: 24px;
            height: 24px;
        }

        .status-message:first-child {
            background-color: #0056b3;
            color: white;
        }

        .status-message:first-child.error {
            background-color: #dc3545;
            color: white;
        }

        .status-message:first-child.success {
            background: #28a745;
        }

        /* Region entries */
        .regions-log {
            margin-top: 20px;
            border: 1px solid #ddd;
            padding: 10px;
            font-family: monospace;
            max-height: 200px;
            overflow-y: auto;
            width: 100%;
            box-sizing: border-box;
        }

        .region-entry {
            padding: 5px;
            border-bottom: 1px solid #eee;
        }

        .region-entry.speaker1 {
            color: #ff8c00;
        }

        .region-entry.speaker2 {
            color: #28a745;
        }

        .region-entry.trash {
            color: #6c757d;
        }

        /* Processed files */
        .processed-files {
            margin-top: 20px;
            width: 100%;
        }

        .processed-files a {
            display: block;
            margin: 10px 0;
            color: #0056b3;
            text-decoration: none;
        }

        .command-button {
            background: #0056b3;
            color: white;
            border: none;
            border-radius: 3px;
            padding: 6px 12px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
            box-sizing: border-box;
        }

        .command-button:hover {
            background-color: #004494;
        }

        .command-button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
            opacity: 0.6;
        }

        /* Make buttons more responsive */
        @media (max-width: 576px) {
            .command-button {
                flex: 1 1 calc(50% - 5px);
                justify-content: center;
                white-space: normal;
            }
        }

        /* Waveform header */
        .waveform-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-family: monospace;
            background-color: #f8f9fa;
            padding: 5px 10px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            gap: 5px;
            flex-wrap: wrap;
            width: 100%;
            box-sizing: border-box;
        }

        /* Responsive layout for waveform header */
        @media (max-width: 576px) {
            .waveform-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .waveform-header span {
                margin-bottom: 5px;
            }

            .zoom-controls {
                width: 100%;
                justify-content: space-between;
            }
        }

        .waveform-header span {
            white-space: nowrap;
            flex-shrink: 0;
        }

        /* Add margin to processing button for better spacing */
        #processAudio {
            margin-top: 20px;
            width: 100%;
        }

        /* Frame-specific adjustments */
        body.in-frame {
            max-width: 768px !important;
            margin-left: 20px !important;
            margin-right: 0 !important;
        }

        body.standalone {
            max-width: 768px !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }

        /* Prevent flickering during page load */
        body {
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        body.in-frame,
        body.standalone {
            opacity: 1;
        }

        /* Add CSS in the <style> section */
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

        .status-bar.drag-over {
            background: #e3f2fd;
            border-color: #2196f3;
            border-style: dashed;
        }

        /* Add this to your style section */
        .controls {
            border-bottom: none;
            /* Remove underline */
            padding-bottom: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .button-group {
            display: flex;
            gap: 2px;
        }
    </style>
    <script src="https://unpkg.com/wavesurfer.js@6.6.4"></script>
    <script src="https://unpkg.com/wavesurfer.js@6.6.4/dist/plugin/wavesurfer.regions.min.js"></script>
</head>

<body>
    <div class="container">
        <div class="editor-header">
            <h1 class="editor-title">NetBound Tools: Speaker Splitter</h1>
            <div class="persistent-status-bar status-bar" id="status-messages">
                <div class="status-message info">Waiting for audio...</div>
            </div>
            <div class="button-controls">
                <div class="button-group">
                    <button class="command-button" id="btnOpen">
                        <i class="fas fa-microphone"></i> Open Wav
                    </button>
                    <button class="command-button" id="btnRestart">
                        <i class="fas fa-redo"></i> Restart
                    </button>
                </div>
            </div>
        </div>

        <input type="file" id="fileInput" accept=".wav" style="display: none;">

        <div id="waveform-container">
            <div class="waveform-header">
                <span id="duration-display">Duration: 0:00</span>
                <span id="window-display">Viewable: 0:00 - 0:00</span>
                <div class="controls zoom-controls">
                    <button type="button" id="zoomIn" class="button-blue" title="Zoom In"><i class="fas fa-search-plus"></i></button>
                    <button type="button" id="zoomOut" class="button-blue" title="Zoom Out"><i class="fas fa-search-minus"></i></button>
                    <button type="button" id="zoomFit" class="button-blue" title="Fit to Window"><i class="fas fa-expand"></i></button>
                    <button type="button" id="clearRegions" class="button-blue warning" title="Clear All Segments"><i class="fas fa-eraser"></i></button>
                </div>
            </div>
            <div class="waveform-wrapper">
                <div id="waveform">
                    <div class="channel-label left">Left Channel</div>
                    <div class="channel-label right">Right Channel</div>
                </div>
            </div>
            <div class="controls">
                <div class="button-group">
                    <button id="jumpStart" class="button-blue" title="Jump to start"><i class="fas fa-step-backward"></i></button>
                    <button id="jumpBack" class="button-blue" title="Jump back"><i class="fas fa-backward"></i></button>
                    <button id="playPause" class="button-blue" title="Play/Pause"><i class="fas fa-play-pause"></i></button>
                    <button id="jumpForward"

</final_file_content>

IMPORTANT: For any future changes to this file, use the final_file_content shown above as your reference. This content reflects the current state of the file, including any auto-formatting (e.g., if you used single quotes but the formatter converted them to double quotes). Always base your SEARCH/REPLACE operations on this final version to ensure accuracy.

<environment_details>
# VSCode Visible Files
nb-voice-split-fixed.php

# VSCode Open Tabs
abaaaa.php
nb-voice-split-fixed.php

# Current Time
3/15/2025, 3:32:08 AM (America/Vancouver, UTC-7:00)

# Current Mode
ACT MODE
</environment_details>

</final_file_content>

IMPORTANT: For any future changes to this file, use the final_file_content shown above as your reference. This content reflects the current state of the file, including any auto-formatting (e.g., if you used single quotes but the formatter converted them to double quotes). Always base your SEARCH/REPLACE operations on this final version to ensure accuracy.

<environment_details>
# VSCode Visible Files
nb-voice-split-fixed.php

# VSCode Open Tabs
abaaaa.php
nb-voice-split-fixed.php

# Current Time
3/15/2025, 3:32:05 AM (America/Vancouver, UTC-7:00)

# Current Mode
ACT MODE
</environment_details>

</final_file_content>

IMPORTANT: For any future changes to this file, use the final_file_content shown above as your reference. This content reflects the current state of the file, including any auto-formatting (e.g., if you used single quotes but the formatter converted them to double quotes). Always base your SEARCH/REPLACE operations on this final version to ensure accuracy.

<environment_details>
# VSCode Visible Files
nb-voice-split-fixed.php

# VSCode Open Tabs
abaaaa.php
nb-voice-split-fixed.php

# Current Time
3/15/2025, 3:32:29 AM (America/Vancouver, UTC-7:00)

# Current Mode
ACT MODE
</environment_details>

</final_file_content>

IMPORTANT: For any future changes to this file, use the final_file_content shown above as your reference. This content reflects the current state of the file, including any auto-formatting (e.g., if you used single quotes but the formatter converted them to double quotes). Always base your SEARCH/REPLACE operations on this final version to ensure accuracy.

<environment_details>
# VSCode Visible Files
nb-voice-split-fixed.php

# VSCode Open Tabs
abaaaa.php
nb-voice-split-fixed.php

# Current Time
3/15/2025, 3:32:51 AM (America/Vancouver, UTC-7:00)

# Current Mode
ACT MODE
</environment_details>
