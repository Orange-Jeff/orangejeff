<?php

/**
 * PHP WAV Processing Library
 * A lightweight library for manipulating WAV audio files in PHP without external dependencies
 */

class WavFile
{
    private $filename;
    private $handle;
    private $sampleRate;
    private $channels;
    private $bitsPerSample;
    private $dataSize;
    private $headerSize;
    private $bytesPerSample;
    private $bytesPerSecond;

    /**
     * Open a WAV file for processing
     */
    public function __construct($filename)
    {
        $this->filename = $filename;
        $this->handle = fopen($filename, 'rb');

        if (!$this->handle) {
            throw new Exception("Could not open WAV file: $filename");
        }

        $this->parseHeader();
    }

    /**
     * Parse the WAV file header
     */
    private function parseHeader()
    {
        // Read and verify RIFF header
        $header = fread($this->handle, 12);
        $riffHeader = unpack('NchunkID/VchunkSize/NriffType', $header);

        if ($riffHeader['chunkID'] !== 0x52494646) { // "RIFF" in hex
            throw new Exception("Not a valid RIFF file");
        }

        if ($riffHeader['riffType'] !== 0x57415645) { // "WAVE" in hex
            throw new Exception("Not a valid WAVE file");
        }

        // Find the format chunk
        while (!feof($this->handle)) {
            $chunkHeader = fread($this->handle, 8);
            if (strlen($chunkHeader) < 8) break;

            $chunk = unpack('NchunkID/VchunkSize', $chunkHeader);

            if ($chunk['chunkID'] === 0x666d7420) { // "fmt " in hex
                $formatChunk = fread($this->handle, $chunk['chunkSize']);
                $format = unpack('vaudioFormat/vchannels/VsampleRate/VbyteRate/vblockAlign/vbitsPerSample', $formatChunk);

                $this->channels = $format['channels'];
                $this->sampleRate = $format['sampleRate'];
                $this->bitsPerSample = $format['bitsPerSample'];
                $this->bytesPerSample = $this->bitsPerSample / 8 * $this->channels;
                $this->bytesPerSecond = $this->sampleRate * $this->bytesPerSample;
            } else if ($chunk['chunkID'] === 0x64617461) { // "data" in hex
                $this->dataSize = $chunk['chunkSize'];
                $this->headerSize = ftell($this->handle);
                break;
            } else {
                // Skip other chunks
                fseek($this->handle, $chunk['chunkSize'], SEEK_CUR);
            }
        }

        if (!isset($this->dataSize)) {
            throw new Exception("No data chunk found in WAV file");
        }

        // Rewind to beginning of data
        fseek($this->handle, $this->headerSize, SEEK_SET);
    }

    /**
     * Get a segment of audio data by time
     */
    public function getSegment($startTime, $duration)
    {
        $startByte = $this->headerSize + (int)($startTime * $this->bytesPerSecond);
        $lengthBytes = (int)($duration * $this->bytesPerSecond);

        // Ensure we don't read beyond file
        $lengthBytes = min($lengthBytes, $this->dataSize - ($startByte - $this->headerSize));

        // Seek to starting position
        fseek($this->handle, $startByte, SEEK_SET);

        // Read audio data
        return fread($this->handle, $lengthBytes);
    }

    /**
     * Get header data for creating a new WAV file
     */
    public function getHeader()
    {
        fseek($this->handle, 0, SEEK_SET);
        return fread($this->handle, $this->headerSize);
    }

    /**
     * Get file metadata
     */
    public function getMetadata()
    {
        return [
            'channels' => $this->channels,
            'sampleRate' => $this->sampleRate,
            'bitsPerSample' => $this->bitsPerSample,
            'bytesPerSample' => $this->bytesPerSample,
            'bytesPerSecond' => $this->bytesPerSecond,
            'dataSize' => $this->dataSize,
            'headerSize' => $this->headerSize
        ];
    }

    /**
     * Create a new WAV file from segments
     */
    public static function createFromSegments($outputFile, $sourceFile, $segments)
    {
        $source = new self($sourceFile);
        $metadata = $source->getMetadata();

        $fh = fopen($outputFile, 'wb');
        if (!$fh) {
            throw new Exception("Could not create output file: $outputFile");
        }

        // Total data size calculation
        $totalDataSize = 0;
        foreach ($segments as $segment) {
            $totalDataSize += (int)($segment['duration'] * $metadata['bytesPerSecond']);
        }

        // Write RIFF header
        fwrite($fh, "RIFF");
        fwrite($fh, pack('V', 36 + $totalDataSize)); // File size - 8
        fwrite($fh, "WAVE");

        // Write format chunk
        fwrite($fh, "fmt ");
        fwrite($fh, pack('V', 16)); // Chunk size
        fwrite($fh, pack('v', 1)); // PCM format
        fwrite($fh, pack('v', $metadata['channels'])); // Channels
        fwrite($fh, pack('V', $metadata['sampleRate'])); // Sample rate
        fwrite($fh, pack('V', $metadata['bytesPerSecond'])); // Byte rate
        fwrite($fh, pack('v', $metadata['bytesPerSample'])); // Block align
        fwrite($fh, pack('v', $metadata['bitsPerSample'])); // Bits per sample

        // Write data chunk header
        fwrite($fh, "data");
        fwrite($fh, pack('V', $totalDataSize)); // Data size

        // Write segments data
        foreach ($segments as $segment) {
            $data = $source->getSegment($segment['start'], $segment['duration']);
            fwrite($fh, $data);
        }

        fclose($fh);
        return true;
    }

    /**
     * Create a synchronized WAV file with active segments and silent gaps
     * @param string $outputFile The output file path
     * @param string $sourceFile The source WAV file path
     * @param array $activeSegments Array of segments to keep (start/end pairs)
     * @param float $totalDuration Total duration of the output file
     * @return bool Success status
     */
    public static function createSynchronizedTrack($outputFile, $sourceFile, $activeSegments, $totalDuration)
    {
        $source = new self($sourceFile);
        $metadata = $source->getMetadata();

        $fh = fopen($outputFile, 'wb');
        if (!$fh) {
            throw new Exception("Could not create output file: $outputFile");
        }

        // Calculate total data size based on the whole file duration
        $totalDataSize = (int)($totalDuration * $metadata['bytesPerSecond']);

        // Write RIFF header
        fwrite($fh, "RIFF");
        fwrite($fh, pack('V', 36 + $totalDataSize)); // File size - 8
        fwrite($fh, "WAVE");

        // Write format chunk
        fwrite($fh, "fmt ");
        fwrite($fh, pack('V', 16)); // Chunk size
        fwrite($fh, pack('v', 1)); // PCM format
        fwrite($fh, pack('v', $metadata['channels'])); // Channels
        fwrite($fh, pack('V', $metadata['sampleRate'])); // Sample rate
        fwrite($fh, pack('V', $metadata['bytesPerSecond'])); // Byte rate
        fwrite($fh, pack('v', $metadata['bytesPerSample'])); // Block align
        fwrite($fh, pack('v', $metadata['bitsPerSample'])); // Bits per sample

        // Write data chunk header
        fwrite($fh, "data");
        fwrite($fh, pack('V', $totalDataSize)); // Data size

        // Sort segments by start time
        usort($activeSegments, function ($a, $b) {
            return $a['start'] <=> $b['start'];
        });

        // Process each segment and fill with silence between
        $currentPosition = 0;

        foreach ($activeSegments as $segment) {
            $segmentStart = $segment['start'];
            $segmentEnd = $segment['end'];

            // Write silence from current position to segment start
            if ($segmentStart > $currentPosition) {
                $silenceDuration = $segmentStart - $currentPosition;
                $silenceBytes = (int)($silenceDuration * $metadata['bytesPerSecond']);
                self::writeSilence($fh, $silenceBytes, $metadata['bitsPerSample']);
            }

            // Write audio segment
            $segmentDuration = $segmentEnd - $segmentStart;
            $audioData = $source->getSegment($segmentStart, $segmentDuration);
            fwrite($fh, $audioData);

            // Update current position
            $currentPosition = $segmentEnd;
        }

        // Write final silence if needed
        if ($currentPosition < $totalDuration) {
            $silenceDuration = $totalDuration - $currentPosition;
            $silenceBytes = (int)($silenceDuration * $metadata['bytesPerSecond']);
            self::writeSilence($fh, $silenceBytes, $metadata['bitsPerSample']);
        }

        fclose($fh);
        $source->close();
        return true;
    }

    /**
     * Write silence to a file
     * @param resource $fileHandle File handle to write to
     * @param int $bytes Number of bytes to write
     * @param int $bitsPerSample Bits per sample (8, 16, 24, or 32)
     */
    private static function writeSilence($fileHandle, $bytes, $bitsPerSample)
    {
        // For performance, write silence in chunks
        $chunkSize = 8192;
        $bytesPerSample = $bitsPerSample / 8;

        // Create a buffer of silence based on bit depth
        switch ($bitsPerSample) {
            case 8:
                $silenceByte = "\x80"; // 8-bit silence is 128 (center)
                break;
            case 16:
                $silenceByte = "\x00\x00"; // 16-bit silence is 0
                break;
            case 24:
                $silenceByte = "\x00\x00\x00"; // 24-bit silence is 0
                break;
            case 32:
                $silenceByte = "\x00\x00\x00\x00"; // 32-bit silence is 0
                break;
            default:
                $silenceByte = "\x00";
        }

        // Calculate how many samples in a chunk
        $samplesPerChunk = floor($chunkSize / $bytesPerSample);
        $silenceChunk = str_repeat($silenceByte, $samplesPerChunk);
        $chunkBytes = strlen($silenceChunk);

        // Write complete chunks
        $bytesRemaining = $bytes;
        while ($bytesRemaining >= $chunkBytes) {
            fwrite($fileHandle, $silenceChunk);
            $bytesRemaining -= $chunkBytes;
        }

        // Write remaining bytes
        if ($bytesRemaining > 0) {
            $remainingSamples = floor($bytesRemaining / $bytesPerSample);
            $remainingSilence = str_repeat($silenceByte, $remainingSamples);
            fwrite($fileHandle, $remainingSilence);
        }
    }

    /**
     * Create a stereo WAV file from two source tracks
     * @param string $outputFile The output stereo file path
     * @param string $leftChannelFile Path to the left channel audio file (Speaker 1)
     * @param string $rightChannelFile Path to the right channel audio file (Speaker 2)
     * @return bool Success status
     */
    public static function createStereoFile($outputFile, $leftChannelFile, $rightChannelFile)
    {
        // Open both input files
        $leftSource = new self($leftChannelFile);
        $rightSource = new self($rightChannelFile);

        $leftMetadata = $leftSource->getMetadata();
        $rightMetadata = $rightSource->getMetadata();

        // Check compatibility
        if ($leftMetadata['sampleRate'] !== $rightMetadata['sampleRate']) {
            throw new Exception("Sample rates must match for stereo conversion");
        }

        if ($leftMetadata['bitsPerSample'] !== $rightMetadata['bitsPerSample']) {
            throw new Exception("Bit depths must match for stereo conversion");
        }

        // Get total duration (use the longer of the two files)
        $leftDuration = $leftMetadata['dataSize'] / $leftMetadata['bytesPerSecond'] * $leftMetadata['channels'];
        $rightDuration = $rightMetadata['dataSize'] / $rightMetadata['bytesPerSecond'] * $rightMetadata['channels'];
        $totalDuration = max($leftDuration, $rightDuration);

        // Prepare output file
        $fh = fopen($outputFile, 'wb');
        if (!$fh) {
            throw new Exception("Could not create output file: $outputFile");
        }

        // Calculate parameters for the stereo output
        $channels = 2; // Stereo
        $bitsPerSample = $leftMetadata['bitsPerSample'];
        $sampleRate = $leftMetadata['sampleRate'];
        $bytesPerSample = $bitsPerSample / 8;  // Bytes per mono sample
        $bytesPerFrame = $bytesPerSample * $channels; // Bytes per stereo frame
        $bytesPerSecond = $sampleRate * $bytesPerFrame;
        $totalDataSize = (int)($totalDuration * $sampleRate * $bytesPerFrame);

        // Write RIFF header
        fwrite($fh, "RIFF");
        fwrite($fh, pack('V', 36 + $totalDataSize)); // File size - 8
        fwrite($fh, "WAVE");

        // Write format chunk
        fwrite($fh, "fmt ");
        fwrite($fh, pack('V', 16)); // Chunk size
        fwrite($fh, pack('v', 1)); // PCM format
        fwrite($fh, pack('v', $channels)); // Channels (2 for stereo)
        fwrite($fh, pack('V', $sampleRate)); // Sample rate
        fwrite($fh, pack('V', $bytesPerSecond)); // Byte rate
        fwrite($fh, pack('v', $bytesPerFrame)); // Block align
        fwrite($fh, pack('v', $bitsPerSample)); // Bits per sample

        // Write data chunk header
        fwrite($fh, "data");
        fwrite($fh, pack('V', $totalDataSize)); // Data size

        // Define a smaller chunk duration for processing to avoid memory issues
        $chunkDuration = 0.5; // Process half a second at a time
        $chunkSamples = (int)($chunkDuration * $sampleRate);
        $chunkBytesPerChannel = $chunkSamples * $bytesPerSample;

        // Create a buffer for each channel's silence based on bit depth
        $silenceByte = ($bitsPerSample === 8) ? "\x80" : "\x00";
        $silenceSample = str_repeat($silenceByte, $bytesPerSample);

        // Process the file in chunks
        $time = 0;
        while ($time < $totalDuration) {
            // Calculate how much to read in this chunk
            $currentChunkDuration = min($chunkDuration, $totalDuration - $time);

            // Get left channel data or silence
            if ($time < $leftDuration) {
                $leftData = $leftSource->getSegment($time, $currentChunkDuration);
                // Ensure we have the right amount of data (mono to mono conversion)
                $leftDataLength = strlen($leftData);
            } else {
                $leftDataLength = 0;
            }

            // Get right channel data or silence
            if ($time < $rightDuration) {
                $rightData = $rightSource->getSegment($time, $currentChunkDuration);
                // Ensure we have the right amount of data (mono to mono conversion)
                $rightDataLength = strlen($rightData);
            } else {
                $rightDataLength = 0;
            }

            // Calculate samples in this chunk
            $samplesInThisChunk = (int)($currentChunkDuration * $sampleRate);

            // Interleave the channels
            $stereoChunk = '';
            for ($i = 0; $i < $samplesInThisChunk; $i++) {
                // Calculate position in each mono stream
                $pos = $i * $bytesPerSample;

                // Get left sample or silence
                if ($pos < $leftDataLength) {
                    $leftSample = substr($leftData, $pos, $bytesPerSample);
                } else {
                    $leftSample = $silenceSample;
                }

                // Get right sample or silence
                if ($pos < $rightDataLength) {
                    $rightSample = substr($rightData, $pos, $bytesPerSample);
                } else {
                    $rightSample = $silenceSample;
                }

                // Interleave
                $stereoChunk .= $leftSample . $rightSample;
            }

            // Write this stereo chunk
            fwrite($fh, $stereoChunk);

            // Move to next chunk
            $time += $currentChunkDuration;
        }

        fclose($fh);
        $leftSource->close();
        $rightSource->close();

        return true;
    }

    /**
     * Close the file handle
     */
    public function close()
    {
        if ($this->handle) {
            fclose($this->handle);
            $this->handle = null;
        }
    }

    /**
     * Clean up when object is destroyed
     */
    public function __destruct()
    {
        $this->close();
    }
}
