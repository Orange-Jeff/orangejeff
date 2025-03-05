<?php

/**
 * PHP Test Utility to Concatenate (Join) Two Video Files using vfffmpeg64.exe
 *
 * This script attempts to join two video files together using the vfffmpeg64.exe
 * command-line utility. It dynamically locates vfffmpeg64.exe and vfffprobe.exe
 * in the same directory as the script.
 */

// --- Dynamic Path Detection ---
$script_dir = __DIR__;
$ffmpeg_path = $script_dir . DIRECTORY_SEPARATOR . 'vfffmpeg64.exe';
$ffprobe_path = $script_dir . DIRECTORY_SEPARATOR . 'vfffprobe.exe';

// --- Input and Output Files (from POST request) ---
$video1 = $_POST['video1'] ?? '';
$video2 = $_POST['video2'] ?? '';
$output_file = $_POST['output_file'] ?? '';

// --- Error Checking (Paths and Files) ---
$errors = [];

if (!file_exists($ffmpeg_path)) {
    $errors[] = "Error: vfffmpeg64.exe not found at: " . $ffmpeg_path;
}

// Only check if files exist if they've been submitted
if ($video1 && !file_exists($video1)) {
    $errors[] = "Error: Input video file 1 not found: " . $video1;
}
if ($video2 && !file_exists($video2)) {
    $errors[] = "Error: Input video file 2 not found: " . $video2;
}
if ($output_file && !is_writable(dirname($output_file))) {
    // Check if the directory of the output file is writable
    $errors[] = "Error: Output directory is not writable: " . dirname($output_file);
}


// --- Process Request (if form submitted and no errors) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    // --- FFmpeg Command ---
    // Method 1: Using the concat demuxer (recommended for most cases)
    $concat_list_file = tempnam(sys_get_temp_dir(), 'concat_list');
    file_put_contents($concat_list_file, "file '" . $video1 . "'\nfile '" . $video2 . "'");

    $command = '"' . $ffmpeg_path . '" -f concat -safe 0 -i "' . $concat_list_file . '" -c copy "' . $output_file . '"';

    // --- Execute the Command ---
    $output = []; // Initialize $output
    $return_var = -1; // Initialize $return_var
    exec($command, $output, $return_var);

    // --- Process Results ---
    if ($return_var === 0) {
        $result_message = "Success! Videos joined. Output file: " . $output_file . "\n\n";
        $result_message .= "FFmpeg Output:\n" . implode("\n", $output);

        unlink($concat_list_file);

        if (file_exists($ffprobe_path)) {
            $command = '"' . $ffprobe_path . '" -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "' . $output_file . '"';
            $duration = shell_exec($command);
            $result_message .= "\nVideo Duration: " . $duration;
        }
    } else {
        $result_message = "Error! FFmpeg did not complete successfully.\n\n";
        $result_message .= "Return code: " . $return_var . "\n\n";
        $result_message .= "FFmpeg Output:\n" . implode("\n", $output);
        unlink($concat_list_file);
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Video Concatenation Tool</title>
    <style>
        .error { color: red; }
    </style>
</head>
<body>

<h1>Video Concatenation Tool</h1>

<?php if (!empty($errors)): ?>
    <div class="error">
        <?php foreach ($errors as $error): ?>
            <p><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (isset($result_message)): ?>
    <div>
        <pre><?php echo htmlspecialchars($result_message); ?></pre>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <label for="video1">Video File 1:</label>
    <input type="file" id="video1" name="video1" accept="video/*" required><br><br>

    <label for="video2">Video File 2:</label>
    <input type="file" id="video2" name="video2" accept="video/*" required><br><br>

    <label for="output_file">Output File:</label>
    <input type="file" id="output_file" name="output_file" required><br><br>
    <input type="hidden" id="output_file_path" name="output_file_path" >

    <button type="submit">Join Videos</button>
</form>

<script>
    // Function to handle file selection and set hidden input values
    function handleFileSelection(inputId, hiddenInputId) {
      const fileInput = document.getElementById(inputId);
      const hiddenInput = document.getElementById(hiddenInputId);

      fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
          // For actual file path, we rely on the server-side (PHP) to handle it
          //  after the form is submitted.  We cannot get the full path from
          //  the client-side JavaScript due to security restrictions.
          //  We set a placeholder here, and PHP will replace it.
          if(hiddenInputId){
              hiddenInput.value = this.files[0].name; // Placeholder
          }

        }
      });
    }

    // Set up event listeners for both file inputs
    handleFileSelection('video1');
    handleFileSelection('video2');
    handleFileSelection('output_file','output_file_path');

    //override the output file selection
    document.getElementById('output_file').addEventListener('click', function(event) {
        //prevent default behavior
        event.preventDefault();

        //prompt the user for the output file name
        var filename = prompt("Please enter output file name", "joined_video.mp4");

        //if the user entered a file name
        if (filename != null) {
            //set the hidden input value
            document.getElementById('output_file_path').value = filename;
            //set the file name
            document.getElementById('output_file').value = '';
            var fileInput = document.getElementById('output_file');
            var newFileInput = document.createElement('input');
            newFileInput.type = 'text';
            newFileInput.id = fileInput.id;
            newFileInput.name = fileInput.name;
            newFileInput.value = filename;
            newFileInput.readOnly = true;
            fileInput.parentNode.replaceChild(newFileInput,fileInput);

        }
    });

    document.querySelector('form').addEventListener('submit', function(e) {
        var video1 = document.getElementById('video1').value;
        var video2 = document.getElementById('video2').value;
        var output_file =  document.getElementById('output_file_path').value;

        if (!video1 || !video2 || !output_file) {
            alert('Please select input and output files.');
            e.preventDefault(); // Prevent form submission
            return;
        }

        // Get the selected files
        const fileInput1 = document.getElementById('video1');
        const fileInput2 = document.getElementById('video2');

        if (fileInput1.files.length > 0 && fileInput2.files.length > 0)
        {
            //set the form values for submission
            document.getElementById('video1').value = fileInput1.files[0].name;
            document.getElementById('video2').value = fileInput2.files[0].name;
        }
     });

</script>

</body>
</html>
