document.addEventListener('DOMContentLoaded', function () {
    console.log('DOMContentLoaded fired. Initializing script.js...');
    const baseURL = window.location.origin + '/videocapture/';
    // Parse URL parameters for immediate video playback
    const urlParams = new URLSearchParams(window.location.search);
    const initialVideoId = urlParams.get('video_id');
    const initialVideoPath = urlParams.get('video_path');
    const initialVideoName = urlParams.get('video_name');
    console.log('initialVideoId:', initialVideoId, 'initialVideoPath:', initialVideoPath, 'initialVideoName:', initialVideoName);

    const videoPlayer = document.getElementById('videoPlayer');
    const player = new Plyr(videoPlayer); // Initialize Plyr
    const nowPlayingInfo = null;

    let currentVideoId = null; // To store the ID of the currently playing video
    let lastDetailsData = null; // To store the last fetched video details

    // Handle Upload Form submission (for actual video file upload)
    const uploadForm = document.getElementById('uploadForm');
    const uploadModal = document.getElementById('uploadModal');
    const closeUploadModal = document.getElementById('closeUploadModal');

    const fabUpload = document.getElementById('fabUpload');

    if (fabUpload) {
        fabUpload.addEventListener('click', function() {
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
                if (data && data.success) {
                    uploadModal.style.display = 'none';
                    // Reload details and set player source
                    const nameText = (videoCaptureNameDisplay && videoCaptureNameDisplay.textContent) ? videoCaptureNameDisplay.textContent : initialVideoName || '';
                    loadVideoAndDetails(currentVideoId, nameText);
                    setTimeout(() => { try { player.play(); } catch(_) {} }, 300);
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

    const videoCaptureSelection = document.getElementById('videoCaptureSelection');
    const videoCaptureNameDisplay = document.getElementById('videoCaptureNameDisplay');
    const detailsTableBody = document.getElementById('detailsTableBody');

    // Function to load and display videos in the video grid
    function loadVideosForContentPage(sortBy = 'name', sortOrder = 'ASC') {
        console.log('loadVideosForContentPage called.');
        fetch(`fetch_all_videos.php?sortBy=${encodeURIComponent(sortBy)}&sortOrder=${encodeURIComponent(sortOrder)}`)
            .then(response => response.json())
            .then(data => {
                console.log('Videos data received for content page:', data);
                const videoGrid = document.getElementById('videoGrid');
                console.log('videoGrid element:', videoGrid);
                if (videoGrid) {
                    videoGrid.innerHTML = '';
                    if (data.length === 0) {
                        videoGrid.innerHTML = '<p>No videos found.</p>';
                        return;
                    }
                    data.forEach(video => {
                        console.log('Processing video:', video);
                        const videoCard = document.createElement('div');
                        videoCard.className = 'video-card';
                        const sizeText = (typeof video.file_size === 'number' && !isNaN(video.file_size)) ? ` • ${(video.file_size/1024/1024).toFixed(2)} MB` : '';
                        const isCurrent = String(video.id) === String(currentVideoId || initialVideoId || '');
                        const currentMark = isCurrent ? '<span class="material-symbols-outlined current-check" title="Current">check_circle</span>' : '';
                        videoCard.innerHTML = `
                            <a href="content.php?video_id=${video.id}&video_name=${encodeURIComponent(video.name)}">
                                <div class="video-meta">${video.name}${sizeText} ${currentMark}</div>
                            </a>
                        `;
                        videoGrid.appendChild(videoCard);
                    });
                }
            })
            .catch(error => console.error('Error loading videos for content page:', error));
    }

    loadVideosForContentPage();

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

    function loadVideoAndDetails(videoId, videoName) {
        currentVideoId = videoId;
        const videoCaptureNameDisplayElement = document.getElementById('videoCaptureNameDisplay');
        if (videoCaptureNameDisplayElement) {
            videoCaptureNameDisplayElement.textContent = decodeURIComponent(videoName);
        }
        // Apply compact layout for specific files (e.g., V2)
        const normalizedName = (decodeURIComponent(videoName) || '').trim().toLowerCase();
        if (normalizedName === 'v2') {
            document.body.classList.add('v2-compact');
        } else {
            document.body.classList.remove('v2-compact');
        }

        fetch(`fetch_single_video.php?video_id=${videoId}`)
            .then(response => response.json())
            .then(videoData => {
                if (videoData.success && videoData.video) {
                    if (videoData.video.video_path) {
                        console.log('Video path from fetch_single_video.php:', videoData.video.video_path);
                        player.source = {
                            type: 'video',
                            sources: [{ src: `serve_video.php?video_id=${videoId}`, type: 'video/mp4' }],
                        };
                    } else {
                        player.stop();
                    }
                    // Removed Now Playing label text between sections

                    fetch(`fetch_video_details.php?video_id=${videoId}`)
                        .then(response => response.json())
                        .then(detailsData => {
                            lastDetailsData = detailsData;
                            if (detailsData.success) {
                                detailsTableBody.innerHTML = '';
                                let rowIndex = 1;
                                detailsData.details.forEach(detail => {
                                    const newRow = detailsTableBody.insertRow();
                                    newRow.dataset.id = detail.id;
                                    newRow.innerHTML = `
                                        <td>${rowIndex}</td>
                                        <td><input type="text" class="form-control" name="operator" value="${detail.operator}"></td>
                                        <td><input type="text" class="form-control" name="description" value="${detail.description}"></td>
                                        <td>
                                            <select class="form-control" name="va_nva_enva">
                                                <option value="VA" ${detail.va_nva_enva === 'VA' ? 'selected' : ''}>VA</option>
                                                <option value="NVA" ${detail.va_nva_enva === 'NVA' ? 'selected' : ''}>NVA</option>
                                                <option value="ENVA" ${detail.va_nva_enva === 'ENVA' ? 'selected' : ''}>ENVA</option>
                                            </select>
                                        </td>
                                        <td>
                                            <div class="time-input-container">
                                                <input type="text" class="form-control" name="start_time" value="${detail.start_time}">
                                                <button class="btn-get-time" title="Get Current Time"><span class="material-symbols-outlined">schedule</span></button>
                                                <button class="btn-play-time" title="Play from this time"><span class="material-symbols-outlined">play_arrow</span></button>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="time-input-container">
                                                <input type="text" class="form-control" name="end_time" value="${detail.end_time}">
                                                <button class="btn-get-time" title="Get Current Time"><span class="material-symbols-outlined">schedule</span></button>
                                                <button class="btn-play-time" title="Play from this time"><span class="material-symbols-outlined">play_arrow</span></button>
                                            </div>
                                        </td>
                                        <td><button class="btn-danger delete-row-btn" data-id="${detail.id}">Delete</button></td>
                                    `;
                                    rowIndex++;
                                });
                            } else {
                                detailsTableBody.innerHTML = `<tr><td colspan="7">Error loading details: ${detailsData.error}</td></tr>`;
                            }

                            fetch(`fetch_possible_improvements.php?video_id=${videoId}`)
                                .then(response => response.json())
                                .then(improvementsData => {
                                    const improvementsTableBody = document.getElementById('improvementsTableBody');
                                    improvementsTableBody.innerHTML = '';
                                    if (improvementsData.success) {
                                        improvementsData.improvements.forEach(imp => {
                                            const newRow = improvementsTableBody.insertRow();
                                            newRow.dataset.id = imp.id;

                                            let optionsHtml = '';
                                            if (detailsData.success) {
                                                detailsData.details.forEach((detail, index) => {
                                                    const selected = imp.video_detail_id == detail.id ? 'selected' : '';
                                                    optionsHtml += `<option value="${detail.id}" ${selected}>${index + 1}</option>`;
                                                });
                                            }

                                            newRow.innerHTML = `
                                                <td style="width: 10%;">
                                                    <select class="form-control" name="video_detail_id" style="width: 100%;">
                                                        ${optionsHtml}
                                                    </select>
                                                </td>
                                                <td><input type="text" class="form-control" name="cycle_number" value="${imp.cycle_number}"></td>
                                                <td><input type="text" class="form-control" name="improvement" value="${imp.improvement}"></td>
                                                <td><input type="text" class="form-control" name="type_of_benefits" value="${imp.type_of_benefits}"></td>
                                                <td><button class="btn-danger delete-row-btn" data-id="${imp.id}">Delete</button></td>
                                            `;
                                        });
                                    }
                                    adjustPlayerHeight();
                                });
                        });
                } else {
                    alert('Error: Video ID not found or invalid.');
                }
            });
    }

    if (initialVideoId) {
        loadVideoAndDetails(initialVideoId, initialVideoName);
    } else {
        const videoCaptureNameDisplayElement = document.getElementById('videoCaptureNameDisplay');
        if (videoCaptureNameDisplayElement) {
            videoCaptureNameDisplayElement.textContent = ''; // Ensure it's empty if no video is selected
        }
    }

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
        });
    }

    detailsTableBody.addEventListener('click', function(event) {
        const target = event.target;
        const deleteButton = target.closest('.delete-row-btn');
        const getTimeButton = target.closest('.btn-get-time');
        const playButton = target.closest('.btn-play-time');

        if (getTimeButton) {
            if (player.paused) {
                const container = getTimeButton.closest('.time-input-container');
                const input = container.querySelector('input');
                const currentTime = player.currentTime;
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
                            row.remove();
                        } else {
                            alert('Error deleting video detail: ' + data.error);
                        }
                    })
                    .catch(error => console.error('Error:', error));
                }
            } else {
                if (confirm('Are you sure you want to delete this new row?')) {
                    row.remove();
                }
            }
        } else if (playButton) {
            const timeInput = playButton.closest('.time-input-container').querySelector('input');
            const time = timeInput.value;
            const timeParts = time.split(':');
            if (timeParts.length === 3) {
                const totalSeconds = parseInt(timeParts[0], 10) * 3600 + parseInt(timeParts[1], 10) * 60 + parseInt(timeParts[2], 10);
                if (!isNaN(totalSeconds)) {
                    player.currentTime = totalSeconds;
                    player.play();
                }
            }
        }
    });

    const saveAllDetailsBtn = document.getElementById('saveAllDetailsBtn');
    if (saveAllDetailsBtn) {
        saveAllDetailsBtn.addEventListener('click', async function() {
            if (!currentVideoId) {
                alert('Please select a video first.');
                return;
            }

            const allDetails = [];
            const rows = detailsTableBody.querySelectorAll('tr');
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

            rows.forEach(row => {
                allDetails.push({
                    id: row.dataset.id,
                    operator: row.querySelector('input[name="operator"]').value,
                    description: row.querySelector('input[name="description"]').value,
                    va_nva_enva: row.querySelector('select[name="va_nva_enva"]').value,
                    start_time: row.querySelector('input[name="start_time"]').value,
                    end_time: row.querySelector('input[name="end_time"]').value,
                    video_id: currentVideoId
                });
            });

            try {
                const response = await fetch('save_video_detail.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ details: allDetails })
                });
                const data = await response.json();
                if (data.success) {
                    alert('All video details saved successfully!');
                    loadVideoAndDetails(currentVideoId, videoCaptureNameDisplay.value);
                } else {
                    alert('Error saving details: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error saving details:', error);
                alert('An error occurred while saving details.');
            }
        });
    }

    const addRowBenefitsBtn = document.getElementById('addRowBenefitsBtn');
    const improvementsTableBody = document.getElementById('improvementsTableBody');
    if (addRowBenefitsBtn) {
        addRowBenefitsBtn.addEventListener('click', function() {
            const newRow = improvementsTableBody.insertRow();
            newRow.dataset.id = null;

            let optionsHtml = '';
            if (lastDetailsData && lastDetailsData.success) {
                lastDetailsData.details.forEach((detail, index) => {
                    optionsHtml += `<option value="${detail.id}">${index + 1}</option>`;
                });
            }

            newRow.innerHTML = `
                <td>
                    <select class="form-control" name="video_detail_id">
                        ${optionsHtml}
                    </select>
                </td>
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
                    video_detail_id: row.querySelector('select[name="video_detail_id"]').value,
                    cycle_number: row.querySelector('input[name="cycle_number"]').value,
                    improvement: row.querySelector('input[name="improvement"]').value,
                    type_of_benefits: row.querySelector('input[name="type_of_benefits"]').value,
                    video_id: currentVideoId
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

    const reuploadForm = document.getElementById('reuploadForm');
    const reuploadVideoIdInput = document.getElementById('reuploadVideoId');
    const reuploadNewVideoFileInput = document.getElementById('reuploadNewVideoFile');

    if (reuploadNewVideoFileInput) {
        reuploadNewVideoFileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const formData = new FormData(reuploadForm); // reuploadForm is already defined

                fetch('upload_existing_video.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        if (currentVideoId) {
                            loadVideoAndDetails(currentVideoId, videoCaptureNameDisplay.value);
                        }
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error during reupload:', error);
                    alert('An error occurred during video reupload.');
                });
            }
        });
    }

    // Reupload button removed from UI; keep programmatic support if needed

    const sortSelect = document.getElementById('sortSelect');
    const sortAscBtn = document.getElementById('sortAscBtn');
    const sortDescBtn = document.getElementById('sortDescBtn');

    let currentSortBy = sortSelect ? sortSelect.value : 'name';
    let currentSortOrder = 'ASC'; // Default sort order

    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            currentSortBy = this.value;
            loadVideosForContentPage(currentSortBy, currentSortOrder);
        });
    }

    if (sortAscBtn) {
        sortAscBtn.addEventListener('click', function() {
            currentSortOrder = 'ASC';
            loadVideosForContentPage(currentSortBy, currentSortOrder);
        });
    }

    if (sortDescBtn) {
        sortDescBtn.addEventListener('click', function() {
            currentSortOrder = 'DESC';
            loadVideosForContentPage(currentSortBy, currentSortOrder);
        });
    }

    // Initial load with default sorting
    loadVideosForContentPage(currentSortBy, currentSortOrder);

    // Possible Improvements Section Functionality
    const improvementsAddRow = document.getElementById('improvementsAddRow');
    const improvementsSaveAll = document.getElementById('improvementsSaveAll');



    if (improvementsAddRow && improvementsTableBody) {
        // Add row functionality
        improvementsAddRow.addEventListener('click', function () {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <select class="form-control" name="video_detail_id" style="width: 100%;">
                        <option value="">Select ID</option>
                    </select>
                </td>
                <td><input type="text" class="form-control" name="cycle" placeholder="Cycle"></td>
                <td><input type="text" class="form-control" name="improvement" placeholder="Describe improvement"></td>
                <td><input type="text" class="form-control" name="benefit" placeholder="Benefit"></td>
                <td>
                    <button class="delete-row-btn" title="Delete Row" style="background: #dc3545; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8em;">
                        Delete
                    </button>
                </td>
            `;
            improvementsTableBody.appendChild(row);
            
            // Populate the dropdown with video detail IDs
            const dropdown = row.querySelector('select[name="video_detail_id"]');
            populateVideoDetailDropdown(dropdown);
            
            updateImprovementsRowNumbers();
        });
    }

    if (improvementsTableBody) {
        // Delete row functionality
        improvementsTableBody.addEventListener('click', function (e) {
            if (e.target.closest('.delete-row-btn')) {
                e.target.closest('tr').remove();
                updateImprovementsRowNumbers();
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
                const videoDetailId = row.querySelector('select[name="video_detail_id"]')?.value || '';
                const cycle = row.querySelector('input[name="cycle"]')?.value.trim() || '';
                const improvement = row.querySelector('input[name="improvement"]')?.value.trim() || '';
                const benefit = row.querySelector('input[name="benefit"]')?.value.trim() || '';
                
                if (!videoDetailId || !cycle || !improvement || !benefit) {
                    valid = false;
                    row.style.background = '#fff3cd';
                } else {
                    row.style.background = '';
                    data.push({ 
                        video_detail_id: videoDetailId,
                        cycle, 
                        improvement, 
                        benefit,
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

    function updateImprovementsRowNumbers() {
        if (improvementsTableBody) {
            improvementsTableBody.querySelectorAll('tr').forEach((row, idx) => {
                // First column is now a dropdown, so we don't need to update row numbers
                // Row numbers are handled by the table structure
            });
        }
    }

    // Function to populate video detail dropdown
    function populateVideoDetailDropdown(dropdown) {
        if (!dropdown) return;
        
        console.log('Populating video detail dropdown...');
        
        fetch('fetch_video_detail_ids.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Video detail data received:', data);
                if (data.success && data.video_details) {
                    // Clear existing options except the first one
                    dropdown.innerHTML = '<option value="">Select ID</option>';
                    
                    // Add options from fetched data
                    data.video_details.forEach(detail => {
                        const option = document.createElement('option');
                        option.value = detail.id;
                        option.textContent = detail.id;
                        dropdown.appendChild(option);
                    });
                    
                    console.log(`Populated dropdown with ${data.video_details.length} options`);
                } else {
                    console.error('Failed to fetch video detail IDs:', data.error);
                    dropdown.innerHTML = '<option value="">No IDs available</option>';
                }
            })
            .catch(error => {
                console.error('Error fetching video detail IDs:', error);
                dropdown.innerHTML = '<option value="">Error loading IDs</option>';
            });
    }

    });