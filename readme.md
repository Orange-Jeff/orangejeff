# Video Extract & Split Tool - Usage Tips

## Performance Optimizations

While this tool works entirely in the browser without server-side dependencies, here are some tips to improve performance:

1. **Video Size**: Smaller videos (in resolution and file size) will process faster.

2. **Browser Memory**: For large videos, consider closing other tabs to free up browser memory.

3. **Cache Management**: The browser may cache the video file, so subsequent loads of the same file will be faster.

4. **Extraction Efficiency**: When extracting frames at intervals, choose reasonable intervals to avoid generating too many files at once.

5. **Split Points**: Adding too many split points on large videos may cause browser performance issues. Consider working with smaller video segments if needed.

6. **Video Format**: MP4 videos with H.264 encoding work best with this tool.

## Known Limitations

- The tool downloads the entire video file for each segment, as browser limitations prevent true video splitting.
- Very large video files may exceed browser memory capabilities.
- Video processing is done on the client side, so performance depends on your computer's capabilities.
- Some browsers may limit how many files can be downloaded in succession.

## Tips for Best Experience

- Use Chrome or Edge for best compatibility.
- Prefer videos under 200MB for smoother operation.
- Allow time for downloads to complete before starting new operations.
- Use the "Rename" feature to give meaningful names to your segments.
