/*
 * Audio processing web worker for NetBound Audio Extractor
 *
 * HOW TO USE THIS WORKER:
 * -----------------------
 *
 * // Initialize the worker
 * const audioWorker = new Worker('/E:/OrangeJeff/audio-extract.js');
 *
 * // Example 1: Convert Float32 audio to Int16 (for MP3 encoding)
 * audioWorker.postMessage({
 *    command: 'convertFloat32ToInt16',
 *    data: floatSamplesArray
 * });
 *
 * // Example 2: Process audio chunks with progress reporting
 * audioWorker.postMessage({
 *    command: 'processAudioChunk',
 *    data: {
 *       chunk: audioChunk,
 *       index: chunkIndex,
 *       total: totalChunks
 *    }
 * });
 *
 * // Handle responses from the worker
 * audioWorker.onmessage = function(e) {
 *    if (e.data.command === 'result') {
 *       // Handle processed data
 *       const processedData = e.data.data;
 *       // Use the data (e.g., for MP3 encoding)
 *    } else if (e.data.command === 'chunkProcessed') {
 *       // Handle progress updates
 *       const progress = e.data.progress; // 0-100
 *       // Update UI with progress
 *    } else if (e.data.command === 'error') {
 *       console.error('Worker error:', e.data.message);
 *    }
 * };
 *
 * // Terminate worker when done
 * // audioWorker.terminate();
 */

// Handle messages from the main thread
self.onmessage = function(e) {
    const { command, data } = e.data;

    switch (command) {
        case 'convertFloat32ToInt16':
            const result = convertFloat32ToInt16(data);
            self.postMessage({ command: 'result', data: result });
            break;

        case 'processAudioChunk':
            processAudioChunk(data);
            break;

        default:
            self.postMessage({ command: 'error', message: 'Unknown command' });
    }
};

// Convert Float32 to Int16 for MP3 encoding
function convertFloat32ToInt16(buffer) {
    let l = buffer.length;  // Using let instead of const to allow decrementing
    const buf = new Int16Array(l);

    for (let i = 0; i < l; i++) {
        buf[i] = Math.min(1, Math.max(-1, buffer[i])) * 0x7FFF;
    }

    return buf;
}

// Process an audio chunk (can be expanded for more complex operations)
function processAudioChunk(data) {
    const { chunk, index, total } = data;

    // Process the chunk (placeholder for more complex operations)
    const processed = chunk;

    // Report progress
    const progress = Math.round((index / total) * 100);

    // Send back the processed chunk
    self.postMessage({
        command: 'chunkProcessed',
        index: index,
        progress: progress,
        result: processed
    });
}
