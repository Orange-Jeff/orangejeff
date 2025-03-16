<?php
/*
| Filename: nb-audio-extract.php
| Dependants: audio-extract.js
|
| NetBound Tools Audio Extractor. Provides an interface for extracting audio from video files.
|
| Functions:
| - (Various JavaScript functions for handling file upload, audio extraction, and UI updates)
*/
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>NetBound Tools: Audio Extractor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f4f4f9;
            width: 768px;
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
            /* Reverse the display order */
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

        .filename-input:read-only {
            background: #f8f8f8;
        }

        #video-preview {
            width: 100%;
            height: 432px;
            background: #2a2a2a;
            object-fit: contain;
        }

        .status-bar.drag-over {
            background: #e3f2fd;
            border-color: #2196f3;
            border-style: dashed;
        }

        .button-pair {
            display: flex;
            overflow: hidden;
            width: fit-content;
        }

        .action-button {
            background: #0056b3;
            color: white;
            border: none;
            padding: 6px 8px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border-top-left-radius: 3px;
            border-bottom-left-radius: 3px;
        }

        .plus-button {
            background: #0056b3;
            color: white;
            border: none;
            border-left: 1px solid rgba(255, 255, 255, 0.2);
            padding: 6px 8px;
            cursor: pointer;
            font-size: 14px;
        }

        /* Hover states */
        .action-button:hover,
        .plus-button:hover {
            background: #004494;
        }

        /* Disabled states */
        .action-button:disabled,
        .plus-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lamejs/1.2.1/lame.all.js"></script>
</head>





















// Alternative WAV processing method
async function processAudioBlobToWavAlternative(audioBlob, originalFileName) {
    try {
        status.update('Using alternative WAV conversion...', 'info');
























        // Create an audio element to decode the blob
        const audio = document.createElement('audio');
        audio.src = URL.createObjectURL(audioBlob);
















        // Wait for audio to load
        await new Promise(resolve => {
            audio.oncanplaythrough = resolve;
            audio.load();
        });




        // Create offline context to render audio to buffer
        const offlineCtx = new OfflineAudioContext(
            1, // Force mono
            audio.duration * 44100, // Sample rate * duration
            44100 // Standard sample rate
        );





        // Create source from audio element
        const source = offlineCtx.createMediaElementSource(audio);
        source.connect(offlineCtx.destination);



        // Play and render
        audio.play();
        const renderedBuffer = await offlineCtx.startRendering();
        audio.pause();

















































        // Convert rendered buffer to WAV
        const wavBuffer = audioBufferToWav(renderedBuffer);
        const blob = new Blob([wavBuffer], {
            type: 'audio/wav'
        });






        // Download file
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = originalFileName.replace(/\.[^.]+$/, '.wav');
        link.click();
        URL.revokeObjectURL(url);
        URL.revokeObjectURL(audio.src);





        status.update('WAV conversion complete. File saved', 'success');





        // Re-enable buttons
        document.getElementById('btnSaveMp3').disabled = false;
        document.getElementById('btnSaveWav').disabled = false;
        document.getElementById('btnRename').disabled = false;
        isProcessing = false;




        return;















    } catch (error) {
        // If direct extraction failed, show appropriate message
        if (error.format === 'mp3') {
            status.update('MP3 extraction requires the video plays. You can mute.', 'info');
        } else {
            status.update('Direct extraction failed. Falling back to standard extraction...', 'info');
        }




        status.update(`Error: ${error.message}`, 'error');
        console.error('Audio extraction error:', error);









































































































































































































































































































































































































































































































































        // Re-enable buttons on error
        const buttons = document.querySelectorAll('.command-button');
        buttons.forEach(btn => btn.disabled = false);
        isProcessing = false;
    }
}</body>

</html>
