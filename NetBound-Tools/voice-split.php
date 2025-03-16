<?php
function showInterface() {
    ?>
    <div class="tool-container">
        <div class="audio-controls">
            <input type="file" id="audioFileInput" accept="audio/wav" />
            <button onclick="loadAudio()">Load Audio</button>
        </div>
        <div id="waveform"></div>
        <div id="timeline"></div>
        <div class="control-panel">
            <div class="region-controls">
                <button onclick="setRegionType('speaker1')">Speaker 1</button>
                <button onclick="setRegionType('speaker2')">Speaker 2</button>
                <button onclick="deleteSelectedRegion()">Delete Region</button>
            </div>
            <div class="save-controls">
                <button onclick="saveProcessedAudio('speaker1', 'default')">Save Speaker 1</button>
                <button onclick="saveProcessedAudio('speaker2', 'default')">Save Speaker 2</button>
                <button onclick="saveProcessedAudio('combined', 'full_lr')">Save Combined</button>
            </div>
        </div>
        <div id="status"></div>
    </div>

    <script>
        let wavesurfer;
        let selectedRegion = null;
        let originalAudioFile;
        let regions = [];

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

            wavesurfer = WaveSurfer.create({
                container: '#waveform',
                waveColor: '#4F4A85',
                progressColor: '#383351',
                plugins: [
                    WaveSurfer.regions.create(),
                    WaveSurfer.timeline.create({
                        container: '#timeline'
                    })
                ]
            });

            wavesurfer.load(url);
            wavesurfer.on('ready', function() {
                setupRegions();
            });
        }

        function setupRegions() {
            regions = [];
            wavesurfer.enableDragSelection({});

            wavesurfer.on('region-created', function(region) {
                region.type = 'speaker1';
                regions.push(region);
            });

            wavesurfer.on('region-click', function(region) {
                selectedRegion = region;
            });
        }

        function setRegionType(type) {
            if (selectedRegion) {
                selectedRegion.type = type;
                selectedRegion.element.title = type;
                updateRegionColor(selectedRegion);
            }
        }

        function updateRegionColor(region) {
            const colors = {
                'speaker1': 'rgba(0, 0, 255, 0.2)',
                'speaker2': 'rgba(0, 255, 0, 0.2)'
            };
            region.element.style.backgroundColor = colors[region.type] || colors.speaker1;
        }

        function deleteSelectedRegion() {
            if (selectedRegion) {
                const index = regions.indexOf(selectedRegion);
                if (index > -1) {
                    regions.splice(index, 1);
                }
                selectedRegion.remove();
                selectedRegion = null;
            }
        }

        function saveProcessedAudio(speaker, option) {
            if (!originalAudioFile) {
                updateStatus('No audio file loaded', 'error');
                return;
            }

            if (regions.length === 0) {
                updateStatus('No regions defined', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('audioFile', originalAudioFile);
            formData.append('speaker', speaker);
            formData.append('option', option);

            const groupedRegions = {
                speaker1: regions.filter(r => r.type === 'speaker1')
                    .map(r => ({ start: r.start, end: r.end })),
                speaker2: regions.filter(r => r.type === 'speaker2')
                    .map(r => ({ start: r.start, end: r.end }))
            };

            formData.append('regions', JSON.stringify(groupedRegions));

            updateStatus('Processing audio...', 'info');

            fetch('process_advanced_audio.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    updateStatus('Processing complete', 'success');
                    if (result.fileUrl) {
                        const link = document.createElement('a');
                        link.href = result.fileUrl;
                        link.download = result.fileName || 'processed.wav';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }
                } else {
                    throw new Error(result.message || 'Processing failed');
                }
            })
            .catch(error => {
                updateStatus('Error: ' + error.message, 'error');
                console.error(error);
            });
        }

        function updateStatus(message, type = 'info') {
            const statusDiv = document.getElementById('status');
            statusDiv.textContent = message;
            statusDiv.className = type;
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadAudio();
        });
    </script>
    <?php
}

showInterface();
?>
