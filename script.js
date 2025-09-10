document.addEventListener('DOMContentLoaded', function () {
    console.log('DOMContentLoaded fired. Initializing script.js...');
    const baseURL = window.location.origin + '/videocapture/';
    // Parse URL parameters for immediate video playback
    const urlParams = new URLSearchParams(window.location.search);
    const initialVideoId = urlParams.get('video_id');
    // video_path parameter removed since we no longer store video paths
    const initialVideoName = urlParams.get('video_name');
    console.log('initialVideoId:', initialVideoId, 'initialVideoName:', initialVideoName);

    const videoPlayer = document.getElementById('videoPlayer');
    window.player = new Plyr(videoPlayer); // Initialize Plyr and make it global
    const nowPlayingInfo = null;

    let currentVideoId = null; // To store the ID of the currently playing video
    let lastDetailsData = null; // To store the last fetched video details

    // Function to switch between upload interface and video player
    function switchToVideoPlayer(videoId, videoName) {
        const uploadInterfaceWrapper = document.querySelector('.upload-interface-wrapper');
        
        const topHeading = document.getElementById('pageUploadHeading');
        if (topHeading) {
            topHeading.textContent = videoName || 'Video Player';
        }

        if (uploadInterfaceWrapper) {
            // Transform the container to be the video player
            uploadInterfaceWrapper.style.cssText = 'flex: 1; display: flex; padding: 0; border: 0; background-color: #000; border-radius: 8px; position: relative;';
            
            // Replace content with the video element
            uploadInterfaceWrapper.innerHTML = `
                <video id="videoPlayer" controls style="width: 100%; height: 100%; object-fit: contain;">
                    <source id="videoSource" src="get_video_file.php?video_id=${videoId}&t=${Date.now()}">
                    Your browser does not support the video tag.
                </video>
                <button id="deleteVideoBtn" style="position: absolute; top: 10px; right: 10px; background: #dc3545; color: white; border: none; padding: 8px; border-radius: 50%; cursor: pointer; font-size: 0.8em; z-index: 10; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;" title="Delete Video">
                    <span class="material-symbols-outlined" style="font-size: 18px;">close</span>
                </button>
            `;
            
            // Reinitialize Plyr for the new video element
            const newVideoPlayer = document.getElementById('videoPlayer');
            if (newVideoPlayer) {
                if (window.player) {
                    try { window.player.destroy(); } catch (e) {}
                }
                window.player = new Plyr(newVideoPlayer);
            }
            
            // Add event listener for the delete video button
            const deleteVideoBtn = document.getElementById('deleteVideoBtn');
            if (deleteVideoBtn) {
                deleteVideoBtn.addEventListener('click', () => {
                    deleteVideo(videoId || currentVideoId || null);
                });
            }
        }
    }
    
    
    // Function to delete video file only (preserve data)
    function deleteVideo(videoId) {
        console.log('Attempting to delete video file with ID:', videoId);
        console.log('Type of videoId:', typeof videoId);
        console.log('Current videoId from global:', currentVideoId);
        console.log('Initial videoId from server:', initialVideoIdFromServer);
        
        // Use currentVideoId if videoId is not provided or is null/undefined
        if (!videoId || videoId === null || videoId === undefined) {
            videoId = currentVideoId;
            console.log('Using currentVideoId as fallback:', videoId);
        }
        
        if (confirm('Are you sure you want to delete this video file? The video details and improvements will be preserved.')) {
            console.log('User confirmed file deletion, sending request for video ID:', videoId);
            console.log('Current page URL:', window.location.href);
            
            // Delete only the video file, preserve all data
            if (videoId) {
                fetch('delete_video_file.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ video_id: videoId })
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    if (data.success) {
                        console.log('Video file deleted successfully, data preserved');
                        alert('Video file deleted successfully! Video details and improvements have been preserved.');
                        
                        // Do hard refresh immediately after video file deletion for better data fetch
                        console.log('Performing hard refresh after video file deletion for better data fetch');
                        window.location.reload(true);
                    } else {
                        console.log('File deletion failed:', data.message);
                        alert('Error deleting video file: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('File deletion error:', error);
                    alert('Error deleting video file: ' + error.message);
                });
            } else {
                console.log('No video ID available for file deletion');
                alert('No video ID available for deletion');
            }
        }
    }
    
    
    // Function to perform video cleanup and return to upload interface
    function performVideoCleanup() {
        console.log('Performing video cleanup...');
        
        // Clear localStorage
        localStorage.removeItem('lastVideoId');
        localStorage.removeItem('lastVideoName');
        
        // Clear any video player
        if (window.player) {
            try {
                window.player.destroy();
                window.player = null;
            } catch (e) {
                console.log('Error destroying player:', e);
            }
        }
        
        // Switch back to upload interface
        switchToUploadInterface();
        
        console.log('Video cleanup completed, returned to upload interface');
        alert('Video deleted successfully!');
    }
    
    // Simple function to clear video and return to upload interface (fallback)
    function clearVideoAndReturnToUpload() {
        console.log('Clearing video and returning to upload interface...');
        performVideoCleanup();
    }
    
    // Function to switch back to upload interface
    function switchToUploadInterface() {
        const wrapper = document.querySelector('.upload-interface-wrapper');
        
        if (wrapper) {
            // Restore original styles for the upload form
            const originalStyles = "flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; border: 2px dashed #007bff; border-radius: 8px; padding: 15px; background-color: #f8f9fa; box-sizing: border-box;";
            wrapper.style.cssText = originalStyles;
            
            // Update the page heading back to upload
            const topHeading = document.getElementById('pageUploadHeading');
            if (topHeading) {
                topHeading.textContent = 'Upload Video';
            }
            
            // Replace video player with upload interface
            wrapper.innerHTML = `
                <!-- Upload Tabs -->
                <div class="upload-tabs" style="border-bottom: 1px solid #ddd; margin-bottom: 15px; width: 100%; box-sizing: border-box; display: flex;">
                    <button class="tab-link active" onclick="openUploadTab(event, 'tabFileUpload')" style="background: none; border: none; padding: 8px 12px; cursor: pointer; border-bottom: 2px solid #007bff; color: #007bff; font-weight: 500; flex: 1; text-align: center; box-sizing: border-box;">Upload File</button>
                    <button class="tab-link" onclick="openUploadTab(event, 'tabOneDrive')" style="background: none; border: none; padding: 8px 12px; cursor: pointer; color: #007bff; flex: 1; text-align: center; box-sizing: border-box;">From OneDrive Link</button>
                </div>

                <!-- File Upload Tab -->
                <div id="tabFileUpload" class="tab-content" style="display: block; width: 100%; box-sizing: border-box; overflow: hidden;">
                    <form id="uploadForm" enctype="multipart/form-data" style="width: 100%; box-sizing: border-box;">
                        <div class="form-group" id="videoCaptureSelection" style="margin-bottom: 10px; box-sizing: border-box;">
                            <label for="videoCaptureNameDisplay" style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9em;">Video Capture:</label>
                            <span id="videoCaptureNameDisplay" class="form-control-static" style="display: block; padding: 4px; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9em; word-wrap: break-word; overflow-wrap: break-word; box-sizing: border-box;">Video deleted - ready for new upload</span>
                        </div>

                        <div class="form-group" style="margin-bottom: 10px; box-sizing: border-box;">
                            <label for="video" style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9em;">Video File</label>
                            <input type="file" id="video" name="video" accept="video/*" required style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9em;">
                        </div>
                        <button type="submit" class="btn-primary" style="width: 100%; padding: 8px; box-sizing: border-box; font-size: 0.9em;">Upload Video</button>
                        <div id="uploadProgress" class="progress-bar" style="display:none; margin-top: 8px; box-sizing: border-box;">
                            <div class="progress"></div>
                        </div>
                        <div id="uploadMessage" style="margin-top: 8px; box-sizing: border-box; font-size: 0.85em;"></div>
                    </form>
                </div>

                <!-- OneDrive Tab -->
                <div id="tabOneDrive" class="tab-content" style="display: none; width: 100%; box-sizing: border-box; overflow: hidden;">
                    <form id="oneDriveForm" style="width: 100%; box-sizing: border-box;">
                        <div class="form-group" id="videoCaptureSelectionOneDrive" style="margin-bottom: 10px; box-sizing: border-box;">
                            <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9em;">Video Capture:</label>
                            <span id="videoCaptureNameDisplayOneDrive" class="form-control-static" style="display: block; padding: 4px; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9em; word-wrap: break-word; overflow-wrap: break-word; box-sizing: border-box;">Video deleted - ready for new upload</span>
                        </div>
                        <div class="form-group" style="margin-bottom: 10px; box-sizing: border-box;">
                            <label for="oneDriveUrl" style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9em;">Paste Embed Code</label>
                            <textarea id="oneDriveUrl" name="oneDriveUrl" class="form-control" rows="2" placeholder="Paste OneDrive share link or embed code here" required style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; resize: vertical; box-sizing: border-box; font-size: 0.9em; word-wrap: break-word; overflow-wrap: break-word;"></textarea>
                        </div>
                        <button type="button" class="btn-primary" id="oneDriveImportBtn" style="width: 100%; padding: 8px; box-sizing: border-box; font-size: 0.9em;">Import Video</button>
                    </form>
                    <div id="oneDriveMessage" style="margin-top: 8px; word-wrap: break-word; overflow-wrap: break-word; box-sizing: border-box; font-size: 0.85em;"></div>
                </div>
            `;
            
            // Reattach event listeners for the new upload forms
            attachUploadEventListeners();
        }
    }
    
    // Function to attach upload event listeners
    function attachUploadEventListeners() {
        // Reattach upload form listener
        const uploadForm = document.getElementById('uploadForm');
        if (uploadForm) {
            uploadForm.addEventListener('submit', function(e) {
                e.preventDefault();
                if (!currentVideoId) {
                    alert('Please select a video capture first.');
                    return;
                }

                const fileInput = document.getElementById('video');
                if (!fileInput || fileInput.files.length === 0) {
                    alert('Please choose a video file.');
                    return;
                }

                const formData = new FormData();
                formData.append('video_id', currentVideoId);
                formData.append('video', fileInput.files[0]);

                fetch('upload_existing_video.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    console.log('Upload response:', data);
                    if (data && data.success) {
                        alert('Video uploaded successfully!');
                        
                        // Switch to video player and load video details
                        if (currentVideoId) {
                            const videoName = document.getElementById('videoCaptureNameDisplay').textContent;
                            switchToVideoPlayer(currentVideoId, videoName);
                            loadVideoAndDetails(currentVideoId, videoName, false);
                        }
                    } else {
                        alert('Upload failed: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(err => {
                    console.error('Upload error:', err);
                    alert('An error occurred during upload: ' + err.message);
                });
            });
        }
        
        // Reattach OneDrive import listener
        const oneDriveImportBtn = document.getElementById('oneDriveImportBtn');
        if (oneDriveImportBtn) {
            oneDriveImportBtn.addEventListener('click', function() {
                const oneDriveUrl = document.getElementById('oneDriveUrl').value;
                
                // Get video ID from URL parameters or currentVideoId
                let videoId = currentVideoId || initialVideoId;
                
                if (!videoId) {
                    alert('Please select a video capture first.');
                    return;
                }
                if (!oneDriveUrl) {
                    alert('Please enter a OneDrive share link.');
                    return;
                }

                // Process the input - could be URL or embed code
                let processedData = oneDriveUrl.trim();
                let embedCode = '';
                
                // Check if it's an embed code (contains iframe)
                if (processedData.includes('<iframe') && processedData.includes('embed')) {
                    embedCode = processedData;
                    // Extract src URL from embed code
                    const srcMatch = processedData.match(/src="([^"]+)"/);
                    if (srcMatch) {
                        processedData = srcMatch[1];
                        console.log('Extracted URL from embed code:', processedData);
                    }
                }
                
                // Save the processed data to database
                fetch('save_onedrive_link.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        video_id: videoId,
                        onedrive_url: processedData,
                        embed_code: embedCode
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data && data.success) {
                        const videoName = document.getElementById('videoCaptureNameDisplayOneDrive').textContent;
                        
                        if (embedCode) {
                            // Use embed code - replace upload interface with iframe
                            const uploadInterfaceWrapper = document.querySelector('.upload-interface-wrapper');
                            
                            if (uploadInterfaceWrapper) {
                                // ONLY update the top page heading
                                const topHeading = document.getElementById('pageUploadHeading');
                                if (topHeading) {
                                    topHeading.textContent = data.video_name || 'OneDrive Video';
                                }

                                // Transform the container to be the video player
                                uploadInterfaceWrapper.style.cssText = 'flex: 1; display: flex; padding: 0; border: 0; background-color: #000; border-radius: 8px; position: relative;';

                                // Sanitize and update embed code to fill container
                                let modifiedEmbedCode = embedCode
                                    .replace(/width="[^"]*"/, 'width="100%"')
                                    .replace(/height="[^"]*"/, 'height="100%"');

                                // Replace content with OneDrive embed
                                uploadInterfaceWrapper.innerHTML = `
                                    <div style="width: 100%; height: 100%;">${modifiedEmbedCode}</div>
                                    <button id="deleteVideoBtn" style="position: absolute; top: 10px; right: 10px; background: #dc3545; color: white; border: none; padding: 8px; border-radius: 50%; cursor: pointer; z-index: 10; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;" title="Delete Video">
                                        <span class="material-symbols-outlined" style="font-size: 18px;">close</span>
                                    </button>
                                `;
                                
                                // Add event listener for the delete video button
                                const deleteVideoBtn = document.getElementById('deleteVideoBtn');
                                if (deleteVideoBtn) {
                                    deleteVideoBtn.addEventListener('click', () => {
                                        deleteVideo(videoId || currentVideoId || null);
                                    });
                                }
                            }
                            alert('OneDrive video embedded successfully!');
                        } else {
                            // No embed code, so it's a direct link - use the standard player
                            switchToVideoPlayer(currentVideoId, data.video_name || 'OneDrive Video');
                            
                            // Set the source for the new player
                            if (window.player) {
                               window.player.source = {
                                    type: 'video',
                                    sources: [{ src: processedData, type: 'video/mp4' }],
                               };
                            }
                            
                            alert('OneDrive video loaded!');
                        }
                        
                        // Load video details
                        loadVideoAndDetails(currentVideoId, processedData || videoName, false);
                    } else {
                        alert('Failed to save OneDrive data: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(err => {
                    console.error('OneDrive link save error:', err);
                    alert('An error occurred while saving OneDrive link: ' + err.message);
                });
            });
        }
    }

    // Handle Upload Form submission (for actual video file upload)
    const uploadForm = document.getElementById('uploadForm');
    const uploadModal = document.getElementById('uploadModal');
    const closeUploadModal = document.getElementById('closeUploadModal');

    const fabUpload = document.getElementById('fabUpload');
    const uploadVideoBtn = document.getElementById('uploadVideoBtn');

    if (fabUpload) {
        fabUpload.addEventListener('click', function() {
            uploadModal.style.display = 'block';
        });
    }

    if (uploadVideoBtn) {
        uploadVideoBtn.addEventListener('click', function() {
            uploadModal.style.display = 'block';
        });
    }

    if (closeUploadModal) {
        closeUploadModal.addEventListener('click', function() {
            uploadModal.style.display = 'none';
        });
    }

    // Close the modal if the user clicks outside of it
    window.addEventListener('click', function(event) {
        if (event.target == uploadModal) {
            uploadModal.style.display = 'none';
        }
    });

    // Handle actual file upload from the + icon modal
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!currentVideoId) {
                alert('Please select a video capture first.');
                return;
            }

            const fileInput = document.getElementById('video');
            if (!fileInput || fileInput.files.length === 0) {
                alert('Please choose a video file.');
                return;
            }

            const formData = new FormData();
            formData.append('video_id', currentVideoId);
            formData.append('video', fileInput.files[0]);

            fetch('upload_existing_video.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                console.log('Upload response:', data);
                if (data && data.success) {
                    uploadModal.style.display = 'none';
                    alert('Video uploaded successfully.');
                    
                    // Use the local file name the user chose
                    const chosenFileName = (fileInput && fileInput.files && fileInput.files[0] && fileInput.files[0].name) ? fileInput.files[0].name : (document.getElementById('videoCaptureNameDisplay')?.textContent || '').trim();
                    if (currentVideoId) {
                        switchToVideoPlayer(currentVideoId, chosenFileName);
                        loadVideoAndDetails(currentVideoId, chosenFileName, false);
                    } else {
                        // Fallback to page reload if no current video
                        window.location.reload();
                    }
                } else {
                    alert('Upload failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(err => {
                console.error('Upload error:', err);
                alert('An error occurred during upload: ' + err.message);
            });
        });
    }

    // OneDrive Link Import Handler
    const oneDriveImportBtn = document.getElementById('oneDriveImportBtn');
    if (oneDriveImportBtn) {
        oneDriveImportBtn.addEventListener('click', function() {
            const oneDriveUrl = document.getElementById('oneDriveUrl').value;
            
            // Get video ID from URL parameters or currentVideoId
            let videoId = currentVideoId || initialVideoId;
            
            if (!videoId) {
                alert('Please select a video capture first.');
                return;
            }
            if (!oneDriveUrl) {
                alert('Please enter a OneDrive share link.');
                return;
            }

            // Process the input - could be URL or embed code
            let processedData = oneDriveUrl.trim();
            let embedCode = '';
            
            // Check if it's an embed code (contains iframe)
            if (processedData.includes('<iframe') && processedData.includes('embed')) {
                embedCode = processedData;
                // Extract src URL from embed code
                const srcMatch = processedData.match(/src="([^"]+)"/);
                if (srcMatch) {
                    processedData = srcMatch[1];
                    console.log('Extracted URL from embed code:', processedData);
                }
            }
            
            // Save the processed data to database
            fetch('save_onedrive_link.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    video_id: videoId,
                    onedrive_url: processedData,
                    embed_code: embedCode
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data && data.success) {
                    uploadModal.style.display = 'none';
                    
                    const videoName = document.getElementById('videoCaptureNameDisplayOneDrive').textContent;
                    
                    if (embedCode) {
                        // Use embed code - replace upload interface with iframe
                        const uploadInterfaceWrapper = document.querySelector('.upload-interface-wrapper');
                        const videoPlayerCard = document.querySelector('.video-player-card .sticky-header');
                        
                        if (uploadInterfaceWrapper && videoPlayerCard) {
                            // ONLY update the top page heading - NEVER touch Possible Improvements
                            const topHeading = document.getElementById('pageUploadHeading') || document.querySelector('.main-content-wrapper > h2');
                            if (topHeading) {
                                topHeading.textContent = processedData || (videoName || 'OneDrive Video');
                            }
                            
                            // ENSURE Possible Improvements heading is preserved
                            const improvementsHeading = document.getElementById('improvementsHeading');
                            if (improvementsHeading) {
                                improvementsHeading.textContent = 'Possible Improvements';
                            }
                            
                            // Replace upload interface with OneDrive embed
                            uploadInterfaceWrapper.innerHTML = `
                                <div class="video-player-wrapper" style="width: calc(100% + 30px); margin-left: -15px; margin-right: -15px; height: 100%; position: relative; background: #000; border-radius: 8px; overflow: hidden; box-sizing: border-box;">
                                    <div style="width: 100%; height: 250px; border-radius: 8px; overflow: hidden; display: flex; justify-content: center; align-items: center;">
                                        ${embedCode}
                                    </div>
                                    <button id="deleteVideoBtn" style="position: absolute; top: 10px; right: 10px; background: #dc3545; color: white; border: none; padding: 8px; border-radius: 50%; cursor: pointer; font-size: 0.8em; z-index: 10; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;" title="Delete Video">
                                        <span class="material-symbols-outlined" style="font-size: 18px;">close</span>
                                    </button>
                                </div>
                            `;
                            
                            // Add event listener for the delete video button
                            const deleteVideoBtn = document.getElementById('deleteVideoBtn');
                            if (deleteVideoBtn) {
                                console.log('Adding delete button listener for OneDrive video ID:', videoId);
                                deleteVideoBtn.addEventListener('click', () => {
                                    console.log('Delete button clicked for OneDrive video ID:', videoId);
                                    // Always try to delete, even if videoId is null/undefined
                                    deleteVideo(videoId || currentVideoId || null);
                                });
                            } else {
                                console.error('OneDrive delete button not found!');
                            }
                        }
                        alert('OneDrive video embedded successfully!');
                    } else {
                        // Render external player (embed OneDrive/Stream URL) instead of Plyr
                        const fileLabel = getFileNameFromUrl(processedData);
                        const uploadInterfaceWrapper = document.querySelector('.upload-interface-wrapper');
                        const videoPlayerCard = document.querySelector('.video-player-card .sticky-header');
                        // ONLY update the top page heading - NEVER touch Possible Improvements
                        const topHeading = document.getElementById('pageUploadHeading') || document.querySelector('.main-content-wrapper > h2');
                        if (topHeading) topHeading.textContent = processedData || fileLabel || 'OneDrive Video';
                        
                        // ENSURE Possible Improvements heading is preserved
                        const improvementsHeading = document.getElementById('improvementsHeading');
                        if (improvementsHeading) {
                            improvementsHeading.textContent = 'Possible Improvements';
                        }
                        if (uploadInterfaceWrapper) {
                            uploadInterfaceWrapper.innerHTML = `
                                <div class="video-player-wrapper" style="width: 100%; height: 100%; position: relative; background: #000; border-radius: 8px; overflow: hidden; box-sizing: border-box;">
                                    <iframe src="${processedData}"
                                        style="width:100%;height:250px;border:0;"
                                        allow="autoplay; encrypted-media"
                                        allowfullscreen
                                        title="OneDrive Video"></iframe>
                                </div>`;
                        }
                        alert('OneDrive video loaded!');
                    }
                    
                    // Load video details
                    loadVideoAndDetails(currentVideoId, processedData || videoName, false);
                } else {
                    alert('Failed to save OneDrive data: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(err => {
                console.error('OneDrive link save error:', err);
                alert('An error occurred while saving OneDrive link: ' + err.message);
            });
        });
    }

    const videoCaptureSelection = document.getElementById('videoCaptureSelection');
    const videoCaptureNameDisplay = document.getElementById('videoCaptureNameDisplay');
    const detailsTableBody = document.getElementById('detailsTableBody');



    function adjustPlayerHeight() {
        const improvementsCard = document.querySelector('.new-table-card');
        const videoPlayerElement = document.getElementById('videoPlayer');
        const plyrContainer = document.querySelector('.plyr');

        if (improvementsCard && videoPlayerElement) {
            const setPlayerHeight = () => {
                const tableHeight = improvementsCard.offsetHeight;
                if (tableHeight > 0) {
                    videoPlayerElement.style.height = `${tableHeight}px`;
                    if (plyrContainer) {
                        plyrContainer.style.height = `${tableHeight}px`;
                    }
                } else {
                    // Try again on next tick if not laid out yet
                    setTimeout(setPlayerHeight, 100);
                }
            };
            setTimeout(setPlayerHeight, 50);
            window.addEventListener('resize', setPlayerHeight);
        }
    }

    // Observe changes in the Possible Improvements table to keep the player height in sync
    const improvementsContainerForObserver = document.querySelector('.improvements-table-container');
    if (improvementsContainerForObserver) {
        const observer = new MutationObserver(() => adjustPlayerHeight());
        observer.observe(improvementsContainerForObserver, { childList: true, subtree: true });
    }

    // This function is the single point of entry for loading video content.
    // a) `onlyLoadData = true` is for the initial page load when the server has already rendered the video.
    // b) `onlyLoadData = false` is for dynamic loads (uploads, clicking a new video) to replace the player source.
    function loadVideoAndDetails(videoId, videoName, onlyLoadData = false) {
        if (!videoId) {
            console.error("loadVideoAndDetails called with no videoId.");
            return;
        }

        currentVideoId = videoId;
        
        // Persist the current video to local storage for subsequent visits
        try {
            localStorage.setItem('lastVideoId', String(videoId));
            if (typeof videoName === 'string') {
                localStorage.setItem('lastVideoName', String(videoName));
            }
        } catch (e) { /* ignore storage errors */ }

        // Update the name display in the upload modal
        const videoCaptureNameDisplayElement = document.getElementById('videoCaptureNameDisplay');
        if (videoCaptureNameDisplayElement) {
            videoCaptureNameDisplayElement.textContent = decodeURIComponent(videoName);
        }

        // Always fetch video data to check if we need to switch to video player
        fetch(`fetch_single_video.php?video_id=${videoId}`)
            .then(response => response.json())
            .then(videoData => {
                if (videoData.success && videoData.video) {
                    // Check if we need to switch to video player interface
                    const uploadInterfaceWrapper = document.querySelector('.upload-interface-wrapper');
                    const isUploadInterface = uploadInterfaceWrapper && uploadInterfaceWrapper.querySelector('.upload-tabs');
                    
                    if (isUploadInterface && (videoData.video.video_path || videoData.video.file_size)) {
                        // Switch to video player interface
                        console.log('Switching to video player - video has file:', videoData.video.video_path);
                        switchToVideoPlayer(videoId, videoName);
                    }
                    
                    if (!onlyLoadData) {
                        // This block handles replacing the video in the player (for dynamic loads)
                        const videoPlayer = document.getElementById('videoPlayer');
                        if (videoPlayer && (videoData.video.video_path || videoData.video.file_size)) {
                            let videoUrl = '';
                            
                            // Check if video_path is a OneDrive URL
                            if (videoData.video.video_path && videoData.video.video_path.startsWith('http')) {
                                // Use OneDrive URL directly
                                videoUrl = videoData.video.video_path;
                                console.log('Using OneDrive URL directly:', videoUrl);
                            } else {
                                // Use local file via get_video_file.php
                                videoUrl = `get_video_file.php?video_id=${videoId}&t=${Date.now()}`;
                                console.log('Using local file via get_video_file.php:', videoUrl);
                            }
                            
                            // Use Plyr's API to change source
                            if(window.player) {
                                window.player.source = {
                                    type: 'video',
                                    sources: [{
                                        src: videoUrl,
                                        type: 'video/mp4',
                                    }],
                                };
                            }
                            videoPlayer.style.display = 'block';
                        } else if (videoPlayer) {
                            videoPlayer.style.display = 'none';
                        }
                    }
                }
            });

        // This block always runs to load the associated table data
        const detailsTableBody = document.getElementById('detailsTableBody');
        console.log(`Fetching video details for videoId: ${videoId}`);
                    fetch(`fetch_video_details.php?video_id=${videoId}`)
            .then(response => {
                console.log('Video details response received:', response.status);
                return response.json();
            })
                        .then(detailsData => {
                console.log('Video details data:', detailsData);
                lastDetailsData = detailsData; // Cache for later use
                detailsTableBody.innerHTML = ''; // Clear existing details
                if (detailsData.success && detailsData.details) {
                                detailsData.details.forEach((detail, index) => {
                                    const newRow = detailsTableBody.insertRow();
                                    newRow.dataset.id = detail.id;
                                    // Always use sequential number for display (1, 2, 3...)
                                    const displayId = index + 1;
                                    newRow.innerHTML = `
                                        <td>${displayId}</td>
                            <td><input type="text" class="form-control" name="operator" value="${detail.operator || ''}"></td>
                            <td><input type="text" class="form-control" name="description" value="${detail.description || ''}"></td>
                                        <td>
                                            <select class="form-control" name="va_nva_enva">
                                                <option value="VA" ${detail.va_nva_enva === 'VA' ? 'selected' : ''}>VA</option>
                                                <option value="NVA" ${detail.va_nva_enva === 'NVA' ? 'selected' : ''}>NVA</option>
                                                <option value="ENVA" ${detail.va_nva_enva === 'ENVA' ? 'selected' : ''}>ENVA</option>
                                            </select>
                                        </td>
                            <td>
                                <select class="form-control" name="activity_type">
                                    <option value="manual" ${(detail.activity_type || 'manual') === 'manual' ? 'selected' : ''}>manual</option>
                                    <option value="walk" ${(detail.activity_type || 'manual') === 'walk' ? 'selected' : ''}>walk</option>
                                    <option value="auto" ${(detail.activity_type || 'manual') === 'auto' ? 'selected' : ''}>auto</option>
                                            </select>
                                        </td>
                                        <td>
                                            <div class="time-input-container">
                                    <input type="text" class="form-control" name="start_time" value="${detail.start_time || ''}">
                                                <button class="btn-get-time" title="Get Current Time"><span class="material-symbols-outlined">schedule</span></button>
                                                <button class="btn-play-time" title="Play from this time"><span class="material-symbols-outlined">play_arrow</span></button>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="time-input-container">
                                    <input type="text" class="form-control" name="end_time" value="${detail.end_time || ''}">
                                                <button class="btn-get-time" title="Get Current Time"><span class="material-symbols-outlined">schedule</span></button>
                                                <button class="btn-play-time" title="Play from this time"><span class="material-symbols-outlined">play_arrow</span></button>
                                            </div>
                                        </td>
                                        <td><button class="btn-danger delete-row-btn" data-id="${detail.id}">Delete</button></td>
                                    `;
                                });
                            } else {
                    detailsTableBody.innerHTML = `<tr><td colspan="8">${detailsData.error || 'No details found.'}</td></tr>`;
                            }

                // Now fetch possible improvements, as it depends on video details for dropdowns
                console.log(`Fetching possible improvements for videoId: ${videoId}`);
                return fetch(`fetch_possible_improvements.php?video_id=${videoId}`);
            })
            .then(response => {
                console.log('Possible improvements response received:', response.status);
                return response.json();
            })
                                .then(improvementsData => {
                console.log('Possible improvements data:', improvementsData);
                                    const improvementsTableBody = document.getElementById('improvementsTableBody');
                                    improvementsTableBody.innerHTML = '';
                if (improvementsData.success && improvementsData.improvements) {
                                        improvementsData.improvements.forEach((imp, index) => {
                        const newRow = improvementsTableBody.insertRow();
                                            newRow.dataset.id = imp.id || null; // Keep database ID for operations
                                            
                                            // Create dropdown for ID selection using id_fe values from video details
                                            // Use the actual video_detail_id for selection
                                            const idDropdown = createIdDropdown(lastDetailsData?.details || [], imp.video_detail_id || '');
                                            newRow.innerHTML = `
                            <td>${idDropdown}</td>
                            <td><input type="text" class="form-control" name="cycle_number" placeholder="Cycle#" value="${imp.cycle_number || ''}"></td>
                            <td><input type="text" class="form-control" name="improvement" placeholder="Improvement" value="${imp.improvement || ''}"></td>
                            <td><input type="text" class="form-control" name="type_of_benefits" placeholder="Benefits" value="${imp.type_of_benefits || ''}"></td>
                                                <td><button class="btn-danger delete-row-btn" data-id="${imp.id || 'null'}">Delete</button></td>
                                            `;
                        });
                } else {
                    improvementsTableBody.innerHTML = `<tr><td colspan="5">${improvementsData.error || 'No improvements found.'}</td></tr>`;
                }
            })
            .catch(error => {
                console.error("Error fetching video data tables:", error);
                detailsTableBody.innerHTML = `<tr><td colspan="8">A network error occurred while loading details.</td></tr>`;
                document.getElementById('improvementsTableBody').innerHTML = `<tr><td colspan="5">A network error occurred while loading improvements.</td></tr>`;
            });
    }

    // --- INITIAL PAGE LOAD ---
    const bodyEl = document.querySelector('body');
    const initialVideoIdFromServer = bodyEl.dataset.videoId;
    const initialVideoNameFromServer = bodyEl.dataset.videoName;

    console.log('Initial page load - videoId from server:', initialVideoIdFromServer, 'videoName:', initialVideoNameFromServer);

    if (initialVideoIdFromServer && initialVideoIdFromServer.trim() !== '') {
        // Server provided a video, check if it has an uploaded file
        console.log(`Initial load: Server provided videoId ${initialVideoIdFromServer}. Checking if video has file.`);
        currentVideoId = initialVideoIdFromServer; // Set the current video ID
        
        // Load video data to check if it has a file
        loadVideoAndDetails(initialVideoIdFromServer, initialVideoNameFromServer, true);
    } else {
        // No video from server (e.g., first visit). Try loading the last one from local storage.
        console.log("Initial load: No server-provided videoId. Checking local storage.");
        try {
            const storedId = localStorage.getItem('lastVideoId');
            const storedName = localStorage.getItem('lastVideoName') || '';
            if (storedId) {
                console.log(`Initial load: Found videoId ${storedId} in local storage. Checking if video has file.`);
                currentVideoId = storedId; // Set the current video ID
                // Load video data to check if it has a file
                loadVideoAndDetails(storedId, storedName, false);
            } else {
                console.log('No video found in localStorage either. Tables will remain empty.');
            }
        } catch (e) {
            console.error("Could not access local storage.", e);
        }
    }

    // --- EVENT HANDLERS ---
    
    // Upload Form Submission (handled earlier in the script)

    const addRowBtn = document.getElementById('addRowBtn');
    if (addRowBtn) {
        addRowBtn.addEventListener('click', function() {
            const currentRows = detailsTableBody.querySelectorAll('tr');
            const newId = currentRows.length + 1;
            const newRow = detailsTableBody.insertRow();
            newRow.dataset.id = null;

            let previousEndTime = '';
            if (currentRows.length > 0) {
                const lastRow = currentRows[currentRows.length - 1];
                const lastRowEndTimeInput = lastRow.querySelector('input[name="end_time"]');
                if (lastRowEndTimeInput) {
                    previousEndTime = lastRowEndTimeInput.value;
                }
            }

            newRow.innerHTML = `
                <td>${newId}</td>
                <td><input type="text" class="form-control" name="operator" placeholder="Operator"></td>
                <td><input type="text" class="form-control" name="description" placeholder="Description"></td>
                <td>
                    <select class="form-control" name="va_nva_enva">
                        <option value="VA">VA</option>
                        <option value="NVA">NVA</option>
                        <option value="ENVA">ENVA</option>
                    </select>
                </td>
                <td>
                    <select class="form-control" name="activity_type">
                        <option value="manual">manual</option>
                        <option value="walk">walk</option>
                        <option value="auto">auto</option>
                    </select>
                </td>
                <td>
                    <div class="time-input-container">
                        <input type="text" class="form-control" name="start_time" placeholder="Start Time" value="${previousEndTime}">
                        <button class="btn-get-time" title="Get Current Time"><span class="material-symbols-outlined">schedule</span></button>
                        <button class="btn-play-time" title="Play from this time"><span class="material-symbols-outlined">play_arrow</span></button>
                    </div>
                </td>
                <td>
                    <div class="time-input-container">
                        <input type="text" class="form-control" name="end_time" placeholder="End Time">
                        <button class="btn-get-time" title="Get Current Time"><span class="material-symbols-outlined">schedule</span></button>
                        <button class="btn-play-time" title="Play from this time"><span class="material-symbols-outlined">play_arrow</span></button>
                    </div>
                </td>
                <td><button class="btn-danger delete-row-btn">Delete</button></td>
            `;
            
            // The sticky header is now handled by the sticky-header class
        });
    }

    detailsTableBody.addEventListener('click', function(event) {
        const target = event.target;
        const deleteButton = target.closest('.delete-row-btn');
        const getTimeButton = target.closest('.btn-get-time');
        const playButton = target.closest('.btn-play-time');

        if (getTimeButton) {
            if (window.player.paused) {
                const container = getTimeButton.closest('.time-input-container');
                const input = container.querySelector('input');
                const currentTime = window.player.currentTime;
                const hours = Math.floor(currentTime / 3600).toString().padStart(2, '0');
                const minutes = Math.floor((currentTime % 3600) / 60).toString().padStart(2, '0');
                const seconds = Math.floor(currentTime % 60).toString().padStart(2, '0');
                input.value = `${hours}:${minutes}:${seconds}`;
            } else {
                alert('Please pause the video to get the current time.');
            }
        } else if (deleteButton) {
            const row = deleteButton.closest('tr');
            const detailId = row.dataset.id;

            if (detailId && detailId !== 'null') {
                if (confirm('Are you sure you want to delete this video detail?')) {
                    fetch('delete_item.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `id=${detailId}&type=video_detail`,
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Video detail deleted successfully!');
                            const deletedId = detailId;
                            row.remove();
                            updateDetailsRowNumbers();
                            // Reset any improvements referencing this ID back to "Select ID"
                            rebuildImprovementsIdSelects(deletedId);
                        } else {
                            alert('Error deleting video detail: ' + data.error);
                        }
                    })
                    .catch(error => console.error('Error:', error));
                }
            } else {
                if (confirm('Are you sure you want to delete this new row?')) {
                    row.remove();
                    updateDetailsRowNumbers();
                }
            }
        } else if (playButton) {
            const timeInput = playButton.closest('.time-input-container').querySelector('input');
            const time = timeInput.value;
            const timeParts = time.split(':');
            if (timeParts.length === 3) {
                const totalSeconds = parseInt(timeParts[0], 10) * 3600 + parseInt(timeParts[1], 10) * 60 + parseInt(timeParts[2], 10);
                if (!isNaN(totalSeconds)) {
                    window.player.currentTime = totalSeconds;
                    window.player.play();
                }
            }
        }
    });

    const saveAllDetailsBtn = document.getElementById('saveAllDetailsBtn');
    console.log('Save all details button found:', !!saveAllDetailsBtn);
    
    if (saveAllDetailsBtn) {
        saveAllDetailsBtn.addEventListener('click', async function() {
            console.log('Save all details button clicked');
            console.log('Current video ID:', currentVideoId);
            
            if (!currentVideoId) {
                alert('Please select a video first.');
                return;
            }

            const allDetails = [];
            const rows = detailsTableBody ? detailsTableBody.querySelectorAll('tr') : [];
            console.log('Number of rows found:', rows.length);
            let allFilled = true;

            rows.forEach(row => {
                const inputs = row.querySelectorAll('input, select');
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        allFilled = false;
                        input.style.border = '1px solid red';
                    } else {
                        input.style.border = '';
                    }
                });
            });

            if (!allFilled) {
                alert('Please fill all details');
                return;
            }

            rows.forEach((row, index) => {
                const activityTypeValue = row.querySelector('select[name="activity_type"]').value;
                console.log('Saving activity_type:', activityTypeValue);
                
                // Calculate frontend ID (sequential number starting from 1)
                const id_fe = index + 1;
                
                allDetails.push({
                    id: row.dataset.id,
                    id_fe: id_fe,
                    operator: row.querySelector('input[name="operator"]').value,
                    description: row.querySelector('input[name="description"]').value,
                    va_nva_enva: row.querySelector('select[name="va_nva_enva"]').value,
                    activity_type: activityTypeValue,
                    start_time: row.querySelector('input[name="start_time"]').value,
                    end_time: row.querySelector('input[name="end_time"]').value,
                    video_id: currentVideoId
                });
            });
            
            console.log('All details being saved:', allDetails);
            console.log('Current video ID:', currentVideoId);
            console.log('Number of details to save:', allDetails.length);

            try {
                const response = await fetch('save_video_detail.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ details: allDetails })
                });
                
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);
                
                // Get the raw response text first
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                // Try to parse as JSON
                let data;
                try {
                    data = JSON.parse(responseText);
                    console.log('Response data:', data);
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError);
                    console.error('Response text:', responseText);
                    throw new Error('Invalid JSON response: ' + parseError.message);
                }
                
                if (data.success) {
                    if (data.warning) {
                        alert('Video details saved successfully!\n\nWarning: ' + data.warning);
                    } else {
                        alert('All video details saved successfully!');
                    }
                    loadVideoAndDetails(currentVideoId, document.getElementById('videoCaptureNameDisplay').textContent);
                } else {
                    alert('Error saving details: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error saving details:', error);
                console.error('Error details:', error.message, error.stack);
                alert('An error occurred while saving details: ' + error.message);
            }
        });
    }

    const addRowBenefitsBtn = document.getElementById('addRowBenefitsBtn');
    const improvementsTableBody = document.getElementById('improvementsTableBody');
    if (addRowBenefitsBtn) {
        addRowBenefitsBtn.addEventListener('click', function() {
            // Check if video details exist
            if (!lastDetailsData || !lastDetailsData.success || !lastDetailsData.details || lastDetailsData.details.length === 0) {
                alert('Please add video details first before adding improvements.');
                return;
            }
            
            // Get the first video detail ID as default
            const firstDetailId = lastDetailsData.details[0].id;
            
            const newRow = improvementsTableBody.insertRow(); // adds at the bottom
            newRow.dataset.id = null; // Will be set after saving to database
            
            // Get the next sequential number for display
            const currentRows = improvementsTableBody.querySelectorAll('tr');
            const displayId = currentRows.length; // This will be the next number

            // Create dropdown for ID selection using id_fe values from video details
            const idDropdown = createIdDropdown(lastDetailsData?.details || [], '');
            
            newRow.innerHTML = `
                <td>${idDropdown}</td>
                <td><input type="text" class="form-control" name="cycle_number" placeholder="Cycle#"></td>
                <td><input type="text" class="form-control" name="improvement" placeholder="Improvement"></td>
                <td><input type="text" class="form-control" name="type_of_benefits" placeholder="Benefits"></td>
                <td><button class="btn-danger delete-row-btn" data-id="${newRow.dataset.id}">Delete</button></td>
            `;
            adjustPlayerHeight();
        });
    }

    if (improvementsTableBody) {
        improvementsTableBody.addEventListener('click', function(event) {
            const target = event.target;
            if (target.classList.contains('delete-row-btn')) {
                const row = target.closest('tr');
                const improvementId = row.dataset.id;

                if (improvementId && improvementId !== 'null') {
                    if (confirm('Are you sure you want to delete this improvement?')) {
                        fetch('delete_item.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `id=${improvementId}&type=possible_improvement`,
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Improvement deleted successfully!');
                                row.remove();
                                adjustPlayerHeight();
                            } else {
                                alert('Error deleting improvement: ' + data.error);
                            }
                        })
                        .catch(error => console.error('Error:', error));
                    }
                } else {
                    if (confirm('Are you sure you want to delete this new row?')) {
                        row.remove();
                        adjustPlayerHeight();
                    }
                }
            }
        });
    }

    const saveAllBenefitsBtn = document.getElementById('saveAllBenefitsBtn');
    if (saveAllBenefitsBtn) {
        saveAllBenefitsBtn.addEventListener('click', async function() {
            if (!currentVideoId) {
                alert('Please select a video first.');
                return;
            }

            const allImprovements = [];
            const rows = improvementsTableBody.querySelectorAll('tr');
            let allFilled = true;

            rows.forEach(row => {
                const inputs = row.querySelectorAll('input, select');
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        allFilled = false;
                        input.style.border = '1px solid red';
                    } else {
                        input.style.border = '';
                    }
                });
            });

            if (!allFilled) {
                alert('Please fill all improvement details');
                return;
            }

            rows.forEach(row => {
                allImprovements.push({
                    id: row.dataset.id,
                    video_detail_id: row.cells[0]?.textContent?.trim() || '',
                    cycle_number: row.querySelector('input[name="cycle_number"]').value,
                    improvement: row.querySelector('input[name="improvement"]').value,
                    type_of_benefits: row.querySelector('input[name="type_of_benefits"]').value,
                    video_id: currentVideoId || null
                });
            });

            try {
                const response = await fetch('save_possible_improvement.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ improvements: allImprovements })
                });
                const data = await response.json();
                if (data.success) {
                    alert('All improvements saved successfully!');
                } else {
                    alert('Error saving improvements: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error saving improvements:', error);
                alert('An error occurred while saving improvements.');
            }
        });
    }




    // Possible Improvements Section Functionality
    const improvementsAddRow = document.getElementById('improvementsAddRow');
    const improvementsSaveAll = document.getElementById('improvementsSaveAll');



    if (improvementsAddRow && improvementsTableBody) {
        // Add row functionality
        improvementsAddRow.addEventListener('click', function () {
            // Get the current video details to assign an ID
            const currentVideoDetails = lastDetailsData?.details || [];
            if (currentVideoDetails.length === 0) {
                alert('Please add video details first before adding improvements.');
                return;
            }
            
            // Get the first video detail ID as default
            const firstDetailId = currentVideoDetails[0].id;
            
            const newRow = improvementsTableBody.insertRow(); // adds at the bottom
            newRow.dataset.id = null; // Will be set after saving to database
            
            // Get the next sequential number for display
            const currentRows = improvementsTableBody.querySelectorAll('tr');
            const displayId = currentRows.length; // This will be the next number
            
            // Create dropdown for ID selection using id_fe values from video details
            const idDropdown = createIdDropdown(lastDetailsData?.details || [], '');
            
            newRow.innerHTML = `
                <td>${idDropdown}</td>
                <td><input type="text" class="form-control" name="cycle_number" placeholder="Cycle"></td>
                <td><input type="text" class="form-control" name="improvement" placeholder="Describe improvement"></td>
                <td><input type="text" class="form-control" name="type_of_benefits" placeholder="Benefit"></td>
                <td>
                    <button class="delete-row-btn" title="Delete Row" style="background: #dc3545; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8em;">
                        Delete
                    </button>
                </td>
            `;
        });
    }

    if (improvementsTableBody) {
        // Delete row functionality
        improvementsTableBody.addEventListener('click', function (e) {
            if (e.target.closest('.delete-row-btn')) {
                e.target.closest('tr').remove();
            }
        });
    }

    if (improvementsSaveAll && improvementsTableBody) {
        // Save all functionality
        improvementsSaveAll.addEventListener('click', function () {
            const rows = improvementsTableBody.querySelectorAll('tr');
            const data = [];
            let valid = true;
            
            rows.forEach((row, idx) => {
                // Get video_detail_id from the dropdown select element
                const videoDetailIdSelect = row.querySelector('select[name="video_detail_id"]');
                const videoDetailId = videoDetailIdSelect ? videoDetailIdSelect.value.trim() : '';
                const cycleNumber = row.querySelector('input[name="cycle_number"]')?.value.trim() || '';
                const improvement = row.querySelector('input[name="improvement"]')?.value.trim() || '';
                const typeOfBenefits = row.querySelector('input[name="type_of_benefits"]')?.value.trim() || '';
                
                console.log(`Row ${idx}: videoDetailId=${videoDetailId}, cycleNumber=${cycleNumber}, improvement=${improvement}, typeOfBenefits=${typeOfBenefits}`);
                
                if (!videoDetailId || !cycleNumber || !improvement || !typeOfBenefits) {
                    valid = false;
                    row.style.background = '#fff3cd';
                    console.log(`Row ${idx} validation failed`);
                } else {
                    row.style.background = '';
                    data.push({ 
                        id: row.dataset.id,
                        video_detail_id: videoDetailId,
                        cycle_number: cycleNumber, 
                        improvement, 
                        type_of_benefits: typeOfBenefits,
                        video_id: currentVideoId || null
                    });
                }
            });
            
            if (!valid) {
                alert('Please fill all fields in every row including selecting an ID.');
                return;
            }
            
            if (data.length === 0) {
                alert('No data to save.');
                return;
            }
            
            // Save to backend
            fetch('save_possible_improvement.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ improvements: data })
            })
            .then(r => r.json())
            .then(resp => {
                if (resp.success) {
                    alert('Improvements saved successfully!');
                } else {
                    alert('Error saving: ' + (resp.error || 'Unknown error'));
                }
            })
            .catch(err => {
                console.error('Save error:', err);
                alert('Network error while saving.');
            });
        });
    }

    function updateDetailsRowNumbers() {
        if (detailsTableBody) {
            detailsTableBody.querySelectorAll('tr').forEach((row, idx) => {
                // Update the first cell to show sequential number (1, 2, 3...)
                const firstCell = row.cells[0];
                if (firstCell) {
                    firstCell.textContent = idx + 1;
                }
            });
        }
    }

    function updateImprovementsRowNumbers() {
        if (improvementsTableBody) {
            improvementsTableBody.querySelectorAll('tr').forEach((row, idx) => {
                // Update the first cell to show sequential number (1, 2, 3...)
                const firstCell = row.cells[0];
                if (firstCell) {
                    firstCell.textContent = idx + 1;
                }
            });
        }
    }

    // Function to create ID dropdown for improvements table
    function createIdDropdown(videoDetails, selectedValue = '') {
        if (!videoDetails || videoDetails.length === 0) {
            return '<select class="form-control" name="video_detail_id"><option value="">No details available</option></select>';
        }
        
        let dropdown = '<select class="form-control" name="video_detail_id">';
        dropdown += '<option value="">Select ID</option>';
        
        videoDetails.forEach(detail => {
            const id_fe = detail.id_fe || detail.id; // Use id_fe if available, fallback to id
            const actualId = detail.id; // Use actual database ID for the value
            const isSelected = selectedValue == actualId ? 'selected' : '';
            dropdown += `<option value="${actualId}" ${isSelected}>${id_fe}</option>`;
        });
        
        dropdown += '</select>';
        return dropdown;
    }

    // Helper: when a video detail is deleted, clear selections in improvements that pointed to it
    function rebuildImprovementsIdSelects(deletedDetailId) {
        try {
            // Keep lastDetailsData in sync (remove deleted detail)
            if (lastDetailsData && Array.isArray(lastDetailsData.details)) {
                lastDetailsData.details = lastDetailsData.details.filter(d => String(d.id) !== String(deletedDetailId));
            }
            const selects = document.querySelectorAll('#improvementsTableBody select[name="video_detail_id"]');
            selects.forEach(sel => {
                const currentVal = sel.value;
                const newSelected = (String(currentVal) === String(deletedDetailId)) ? '' : currentVal;
                // Rebuild options
                sel.innerHTML = '';
                const emptyOpt = document.createElement('option');
                emptyOpt.value = '';
                emptyOpt.textContent = 'Select ID';
                sel.appendChild(emptyOpt);
                const details = lastDetailsData && Array.isArray(lastDetailsData.details) ? lastDetailsData.details : [];
                details.forEach(detail => {
                    const id_fe = detail.id_fe || detail.id;
                    const opt = document.createElement('option');
                    opt.value = String(detail.id);
                    opt.textContent = id_fe;
                    if (newSelected && String(newSelected) === String(detail.id)) opt.selected = true;
                    sel.appendChild(opt);
                });
            });
        } catch (e) {
            console.warn('rebuildImprovementsIdSelects error:', e);
        }
    }

    // Minimal robust handler for any form with id="uploadForm" (inline or modal)
    document.addEventListener('submit', function(e) {
        const form = e.target && e.target.matches && e.target.matches('#uploadForm') ? e.target : null;
        if (!form) return;
        e.preventDefault();
        if (!currentVideoId) {
            alert('Please select a video capture first.');
            return;
        }
        const fileInput = form.querySelector('#video') || form.querySelector('input[type="file"]');
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            alert('Please choose a video file.');
            return;
        }
        const formData = new FormData();
        formData.append('video_id', currentVideoId);
        formData.append('video', fileInput.files[0]);
        fetch('upload_existing_video.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data && data.success) {
                    alert('Video uploaded successfully.');
                } else {
                    alert('Upload failed: ' + (data && data.error ? data.error : 'Unknown error'));
                }
            })
            .catch(err => {
                console.error('Upload error:', err);
                alert('An error occurred during upload: ' + err.message);
            });
    });

    function getFileNameFromUrl(urlStr) {
        try {
            const u = new URL(urlStr, window.location.href);
            const last = (u.pathname || '').split('/').pop() || '';
            return decodeURIComponent(last) || 'OneDrive Video';
        } catch (_) {
            return 'OneDrive Video';
        }
    }


    });