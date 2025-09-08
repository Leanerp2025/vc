document.addEventListener('DOMContentLoaded', function () {
    console.log('dashboard_script.js: DOMContentLoaded fired.');

    // Elements
    const fabAddItem = document.getElementById('fabAddItem');
    const addItemModal = document.getElementById('addItemModal');
    const closeAddItemModal = document.getElementById('closeAddItemModal');
    const itemTypeSelect = document.getElementById('itemType');
    const organizationFormDiv = document.getElementById('organizationForm');
    const folderFormDiv = document.getElementById('folderForm');
    const videoFormDiv = document.getElementById('videoForm');

    const addOrganizationForm = document.getElementById('addOrganizationForm');
    const addFolderForm = document.getElementById('addFolderForm');
    const addVideoForm = document.getElementById('addVideoForm');

    const organizationsList = document.getElementById('organizationsList');
    const foldersList = document.getElementById('foldersList');
    const videoCapturesList = document.getElementById('videoCapturesList');

    const folderOrganizationSelect = document.getElementById('folderOrganization');

    // Modal helpers
    function openModal(modal) {
        modal.classList.add('show');
    }
    function closeModal(modal) {
        modal.classList.remove('show');
        // Reset forms and select when closing
        itemTypeSelect.value = '';
        organizationFormDiv.style.display = 'none';
        folderFormDiv.style.display = 'none';
        videoFormDiv.style.display = 'none';
        addOrganizationForm.reset();
        addFolderForm.reset();
        addVideoForm.reset();
    }

    // FAB add item
    fabAddItem.onclick = () => openModal(addItemModal);
    closeAddItemModal.onclick = () => closeModal(addItemModal);

    window.onclick = function(event) {
        if (event.target === addItemModal) closeModal(addItemModal);
    };

    // Show/hide forms based on selection and populate dropdowns
    itemTypeSelect.addEventListener('change', function() {
        organizationFormDiv.style.display = 'none';
        folderFormDiv.style.display = 'none';
        videoFormDiv.style.display = 'none';

        if (this.value === 'organization') {
            organizationFormDiv.style.display = 'block';
        } else if (this.value === 'folder') {
            folderFormDiv.style.display = 'block';
        } else if (this.value === 'video') {
            videoFormDiv.style.display = 'block';
        }
    });

    // Handle Add Organization form submission
    addOrganizationForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'add_organization');

        fetch('upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                closeModal(addItemModal);
                loadOrganizations(); // Refresh organizations list
            } else {
                alert(data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while adding the organization.');
        });
    });

    // Handle Add Folder form submission
    addFolderForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'add_folder');

        fetch('upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                closeModal(addItemModal);
                loadFolders(); // Refresh folders list
                loadOrganizations();
            } else {
                alert(data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while adding the folder.');
        });
    });

    // Handle Add Video form submission
    addVideoForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Prevent multiple submissions
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn.disabled) {
            return;
        }
        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating...';
        
        const formData = new FormData();
        formData.append('action', 'add_video');
        // Create name-only entry; no file included
        const videoNameInput = document.getElementById('videoName');
        if (!videoNameInput || !videoNameInput.value.trim()) {
            alert('Please enter a video name.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Add Video Name';
            return;
        }
        formData.append('videoName', videoNameInput.value.trim());

        fetch('upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                closeModal(addItemModal);
                // Refresh video list before redirecting
                loadVideoCaptures();
                // Redirect to content.php to upload the video file
                window.location.href = `content.php?video_id=${data.video_id}&video_name=${encodeURIComponent(data.video_name)}`;
            } else {
                alert('Error adding video: ' + data.error);
                submitBtn.disabled = false;
                submitBtn.textContent = 'Add Video Name';
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('An error occurred while adding the video. Check console for details: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.textContent = 'Add Video Name';
        });
    });

    // Functions to load and display data
    function loadOrganizations() {
    fetch('upload.php?action=list_organizations')
        .then(response => response.json())
        .then(data => {
            console.log('Organizations data received:', data);
            organizationsList.innerHTML = '';
            if (data.length === 0) {
                organizationsList.innerHTML = '<p>No organizations added yet.</p>';
                return;
            }
            data.forEach(org => {
                const div = document.createElement('div');
                div.className = 'item-card';
                div.innerHTML = `
                    <div class="item-card-header">
                        <span class="file-name">${org.name}</span>
                        <div class="item-card-actions">
                            <a href="organizations_list.php" class="arrow-icon" title="Open"><span class="material-symbols-outlined">arrow_forward</span></a>
                            <button class="delete-btn" data-id="${org.id}" data-type="organization" title="Delete">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>
                    </div>
                `;
                organizationsList.appendChild(div);
            });
            addDeleteEventListeners();
        })
        .catch(error => console.error('Error loading organizations:', error));
}

    function loadFolders() {
    fetch('upload.php?action=list_folders')
        .then(response => response.json())
        .then(data => {
            foldersList.innerHTML = '';
            if (!data.success) {
                foldersList.innerHTML = `<p>Error loading folders: ${data.error}</p>`;
                return;
            }
            if (data.data.length === 0) {
                foldersList.innerHTML = '<p>No folders added yet.</p>';
                return;
            }
            data.data.forEach(folder => {
                const div = document.createElement('div');
                div.className = 'item-card';
                div.innerHTML = `
                    <div class="item-card-header">
                        <span class="file-name">${folder.name}</span>
                        <div class="item-card-actions">
                            <a href="folders_list.php" class="arrow-icon" title="Open"><span class="material-symbols-outlined">arrow_forward</span></a>
                            <button class="delete-btn" data-id="${folder.id}" data-type="folder" title="Delete">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>
                    </div>
                `;
                foldersList.appendChild(div);
            });
            addDeleteEventListeners();
        })
        .catch(error => console.error('Error loading folders:', error));
}

    function loadVideoCaptures() {
        console.log('loadVideoCaptures called.');
        fetch('fetch_all_videos.php?sortBy=id&sortOrder=ASC')
            .then(response => response.json())
            .then(data => {
                console.log('Video Captures data received:', data);
                videoCapturesList.innerHTML = '';

                // Handle potential error response from PHP
                if (data && typeof data === 'object' && data.success === false && data.error) {
                    videoCapturesList.innerHTML = `<p>Error loading video captures: ${data.error}</p>`;
                    return;
                }

                // Ensure data is an array before proceeding
                if (!Array.isArray(data)) {
                    videoCapturesList.innerHTML = '<p>Error: Unexpected data format received for video captures.</p>';
                    return;
                }

                if (data.length === 0) {
                    videoCapturesList.innerHTML = '<p>No video captures added yet.</p>';
                    return;
                }

                data.forEach(video => {
                    const div = document.createElement('div');
                    div.className = 'item-card';
                    const videoDisplayName = video.name;

                    const hasFile = video.file_size && video.file_size > 0;
                    const fileStatus = hasFile ? 
                        '<span class="material-symbols-outlined" style="color: #28a745; font-size: 16px;" title="Video file uploaded">check_circle</span>' : 
                        '';
                    
                    div.innerHTML = `
                        <div class="item-card-header">
                            <span class="file-name">${videoDisplayName} ${fileStatus}</span>
                            <div class="item-card-actions">
                                <a href="content.php?video_id=${video.id}&video_name=${encodeURIComponent(video.name)}" class="arrow-icon" title="Open"><span class="material-symbols-outlined">arrow_forward</span></a>
                                <button class="delete-btn" data-id="${video.id}" data-type="video" title="Delete">
                                    <span class="material-symbols-outlined">close</span>
                                </button>
                            </div>
                        </div>
                    `;
                    videoCapturesList.appendChild(div);
                });
                addDeleteEventListeners();
            })
            .catch(error => console.error('Error loading video captures:', error));
    }

    function addDeleteEventListeners() {
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.onclick = function(event) {
                event.stopPropagation(); // Prevent card click if it's a video card
                const id = this.dataset.id;
                const type = this.dataset.type;
                if (confirm(`Are you sure you want to delete this ${type}?`)) {
                    deleteItem(id, type);
                }
            };
        });
    }

    function deleteItem(id, type) {
        fetch('delete_item.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}&type=${type}`,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                if (type === 'organization') {
                    loadOrganizations();
                } else if (type === 'folder') {
                    loadFolders();
                } else if (type === 'video') {
                    loadVideoCaptures();
                }
            } else {
                alert('Error deleting item: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the item.');
        });
    }

    // Initial load of dashboard content
    loadOrganizations();
    loadFolders();
    loadVideoCaptures();
});
