<!DOCTYPE html>
<html>
<head>
    <title>Voice Split Tool</title>
    <link rel="stylesheet" type="text/css" href="nb-voice-split.css">
</head>
<body>
    <div id="app-container">
        <div id="toolbar">
            <input type="file" id="audioFileInput" accept="audio/wav" />
            <button onclick="loadAudio()">Load Audio</button>
            <div class="save-buttons">
                <button onclick="saveProcessedAudio('speaker1', 'default')" id="save-speaker1">Save Speaker 1</button>
                <button onclick="saveProcessedAudio('speaker2', 'default')" id="save-speaker2">Save Speaker 2</button>
                <button onclick="saveProcessedAudio('both', 'full_lr')" id="save-stereo">Save Stereo</button>
            </div>
        </div>
        <div id="status-messages"></div>
        <div id="waveform-container">
            <div id="waveform"></div>
            <div id="timeline"></div>
        </div>
        <div id="region-controls">
            <button onclick="setRegionType('speaker1')" class="region-btn speaker1" disabled>Speaker 1</button>
            <button onclick="setRegionType('speaker2')" class="region-btn speaker2" disabled>Speaker 2</button>
            <button onclick="setRegionType('trash')" class="region-btn trash" disabled>Trash</button>
            <button onclick="deleteSelectedRegion()" class="region-btn delete" disabled>Delete Region</button>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/wavesurfer.js/6.6.4/wavesurfer.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/wavesurfer.js/6.6.4/plugin/wavesurfer.regions.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/wavesurfer.js/6.6.4/plugin/wavesurfer.timeline.min.js"></script>

    <script>
        let wavesurfer;
        let originalAudioFile;
        let sequentialRegions = [];
        let selectedRegion = null;
        let isProcessing = false;

        function initializeWaveSurfer() {
            return WaveSurfer.create({
                container: '#waveform',
                waveColor: '#4F4A85',
                progressColor: '#383351',
                responsive: true,
                height: 128,
                plugins: [
                    WaveSurfer.regions.create(),
                    WaveSurfer.timeline.create({
                        container: '#timeline'
                    })
                ]
            });
        }

        function loadAudio() {
            const fileInput = document.getElementById('audioFileInput');
            if (fileInput.files.length === 0) {
                updateStatus('Please select an audio file', 'error');
                return;
            }

            originalAudioFile = fileInput.files[0];
            const url = URL.createObjectURL(originalAudioFile);

            if (wavesurfer) {
                wavesurfer.destroy();
            }

            wavesurfer = initializeWaveSurfer();
            wavesurfer.load(url);

            wavesurfer.on('ready', function() {
                updateStatus('Audio loaded successfully', 'success');
                setupRegionHandling();
                updateButtons();
            });

            wavesurfer.on('error', function(err) {
                updateStatus('Error loading audio: ' + err, 'error');
            });
        }

        function setupRegionHandling() {
            sequentialRegions = [];
            selectedRegion = null;

            wavesurfer.enableDragSelection({
                color: 'rgba(79, 74, 133, 0.2)'
            });

            wavesurfer.on('region-created', function(region) {
                region.type = 'speaker1';
                region.element.title = 'Speaker 1';
                sequentialRegions.push(region);
                updateRegionDisplay(region);
                updateButtons();
            });

            wavesurfer.on('region-click', function(region, e) {
                e.stopPropagation();

                if (selectedRegion && selectedRegion !== region) {
                    selectedRegion.element.classList.remove('selected');
                }

                selectedRegion = region;
                region.element.classList.add('selected');
                updateRegionDisplay(region);
                updateButtons();
            });

            wavesurfer.on('interaction', function(e) {
                if (e.targetRegions.length === 0) {
                    if (selectedRegion) {
                        selectedRegion.element.classList.remove('selected');
                        selectedRegion = null;
                        updateButtons();
                    }
                }
            });
        }

        function updateRegionDisplay(region) {
            region.element.style.backgroundColor = getColorForType(region.type);
            region.element.title = getTypeLabel(region.type);

            // Update button states to reflect current region type
            document.querySelectorAll('.region-btn').forEach(btn => {
                btn.classList.toggle('active', btn.classList.contains(region.type));
            });
        }

        function updateButtons() {
            const hasSelection = selectedRegion !== null;
            const buttons = document.querySelectorAll('.region-btn');

            buttons.forEach(btn => {
                btn.disabled = !hasSelection || isProcessing;
                btn.classList.toggle('active', hasSelection && btn.classList.contains(selectedRegion?.type));
            });
        }

        function getColorForType(type) {
            const colors = {
                'speaker1': 'rgba(65, 105, 225, 0.3)',
                'speaker2': 'rgba(50, 205, 50, 0.3)',
                'trash': 'rgba(255, 0, 0, 0.3)'
            };
            return colors[type] || colors.speaker1;
        }

        function getTypeLabel(type) {
            const labels = {
                'speaker1': 'Speaker 1',
                'speaker2': 'Speaker 2',
                'trash': 'Trash'
            };
            return labels[type] || 'Unknown';
        }

        function setRegionType(type) {
            if (selectedRegion && !isProcessing) {
                selectedRegion.type = type;
                updateRegionDisplay(selectedRegion);
            }
        }

        function deleteSelectedRegion() {
            if (selectedRegion && !isProcessing) {
                const index = sequentialRegions.indexOf(selectedRegion);
                if (index > -1) {
                    sequentialRegions.splice(index, 1);
                }
                selectedRegion.remove();
                selectedRegion = null;
                updateButtons();
            }
        }

        function saveProcessedAudio(speaker, option) {
            if (isProcessing) {
                updateStatus('Processing in progress...', 'info');
                return;
            }

            if (!originalAudioFile || !(originalAudioFile instanceof File)) {
                updateStatus('No audio file loaded', 'error');
                return;
            }

            if (sequentialRegions.length === 0) {
                updateStatus('No regions defined', 'error');
                return;
            }

            isProcessing = true;
            updateButtons();

            const loadingIndicator = document.createElement('div');
            loadingIndicator.className = 'loading-indicator';
            loadingIndicator.innerHTML = '<div class="spinner"></div><p>Processing audio...</p>';
            document.body.appendChild(loadingIndicator);

            const formData = new FormData();
            formData.append('audioFile', originalAudioFile);
            formData.append('speaker', speaker);
            formData.append('option', option);
            formData.append('fileName', originalAudioFile.name);

            const groupedRegions = {
                speaker1: sequentialRegions.filter(r => r.type === 'speaker1')
                    .map(r => ({ start: r.start, end: r.end })),
                speaker2: sequentialRegions.filter(r => r.type === 'speaker2')
                    .map(r => ({ start: r.start, end: r.end })),
                trash: sequentialRegions.filter(r => r.type === 'trash')
                    .map(r => ({ start: r.start, end: r.end }))
            };

            formData.append('regions', JSON.stringify(groupedRegions));

            updateStatus(`Processing ${speaker} with option: ${option}...`, "info");

            fetch('process_advanced_audio.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(result => {
                if (document.body.contains(loadingIndicator)) {
                    document.body.removeChild(loadingIndicator);
                }

                if (result.success) {
                    updateStatus('Processing complete!', 'success');

                    if (result.fileUrl) {
                        const downloadLink = document.createElement('a');
                        downloadLink.href = result.fileUrl;
                        downloadLink.download = result.fileName || `${speaker}_processed.wav`;
                        downloadLink.style.display = 'none';
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);

                        updateStatus(`File saved as ${result.fileName}`, 'success');
                    }
                } else {
                    throw new Error(result.message || 'Processing failed');
                }
            })
            .catch(error => {
                if (document.body.contains(loadingIndicator)) {
                    document.body.removeChild(loadingIndicator);
                }
                updateStatus(`Error: ${error.message}`, "error");
                console.error("Processing error:", error);
            })
            .finally(() => {
                isProcessing = false;
                updateButtons();
            });
        }

        function updateStatus(message, type = 'info') {
            const statusDiv = document.createElement('div');
            statusDiv.className = `status-message ${type}`;
            statusDiv.textContent = message;

            const container = document.getElementById('status-messages');
            container.insertBefore(statusDiv, container.firstChild);

            while (container.children.length > 5) {
                container.removeChild(container.lastChild);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateStatus('Ready to load audio file', 'info');
            updateButtons();
        });
    </script>
</body>
</html>
