<?php
session_start();
require_once 'db_con.php';
require_once 'auth_check.php';

$allowedEventImageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$maxEventImageSize = 5 * 1024 * 1024;
$eventUploadBase = 'uploads/events/';

function ensureDirectoryPath($path) {
    if (!file_exists($path)) {
        mkdir($path, 0755, true);
    }
}

function uploadEventImageFile($file, $subfolder = '') {
    global $allowedEventImageTypes, $maxEventImageSize, $eventUploadBase;

    if (empty($file) || !isset($file['tmp_name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Image upload error code ' . $file['error']);
    }

    if ($file['size'] > $maxEventImageSize) {
        throw new Exception("File {$file['name']} exceeds " . ($maxEventImageSize / 1024 / 1024) . "MB limit");
    }

    $tmpPath = $file['tmp_name'];
    $mime = mime_content_type($tmpPath);
    if (!in_array($mime, $allowedEventImageTypes)) {
        throw new Exception("Unsupported image format for {$file['name']}");
    }

    $subfolder = trim($subfolder, '/');
    $targetDir = $eventUploadBase . ($subfolder ? $subfolder . '/' : '');
    ensureDirectoryPath($targetDir);

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $extension = $extension ?: 'jpg';
    $filename = uniqid('event_' . ($subfolder ?: 'asset') . '_') . '.' . $extension;
    $destination = $targetDir . $filename;

    if (!move_uploaded_file($tmpPath, $destination)) {
        throw new Exception("Failed to save uploaded image {$file['name']}");
    }

    return $destination;
}

function uploadEventGalleryFiles($files) {
    $paths = [];
    if (empty($files) || empty($files['name'])) {
        return $paths;
    }

    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $fileInfo = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i]
        ];

        $paths[] = uploadEventImageFile($fileInfo, 'gallery');
    }

    return $paths;
}

function deleteFileIfExists($path) {
    if ($path && file_exists($path)) {
        @unlink($path);
    }
}

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'add':
            try {
                $featuredImagePath = uploadEventImageFile($_FILES['featured_image'] ?? null, 'featured');
                $galleryPaths = uploadEventGalleryFiles($_FILES['gallery_files'] ?? null);
                $galleryJson = !empty($galleryPaths) ? json_encode($galleryPaths) : null;

                $data = [
                    $_SESSION['admin_id'],
                    $_POST['title'],
                    $_POST['description'] ?: null,
                    $_POST['event_type'],
                    $_POST['start_date'],
                    $_POST['end_date'],
                    $_POST['start_time'] ?: null,
                    $_POST['end_time'] ?: null,
                    $_POST['venue'] ?: null,
                    $_POST['address'] ?: null,
                    isset($_POST['is_online']) ? 1 : 0,
                    $_POST['meeting_link'] ?: null,
                    isset($_POST['registration_required']) ? 1 : 0,
                    $_POST['registration_deadline'] ?: null,
                    $_POST['max_attendees'] ?: null,
                    $_POST['registration_fee'] ?: 0.00,
                    $_POST['status'],
                    $featuredImagePath,
                    $galleryJson
                ];
                
                query("INSERT INTO events (admin_id, title, description, event_type, start_date, end_date, start_time, end_time, venue, address, is_online, meeting_link, registration_required, registration_deadline, max_attendees, registration_fee, status, featured_image, gallery) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", $data);
                
                echo json_encode(['success' => true, 'message' => 'Event added successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'edit':
            try {
                $eventId = $_POST['id'];
                $event = fetchRow("SELECT featured_image, gallery FROM events WHERE id = ?", [$eventId]);
                if (!$event) {
                    throw new Exception('Event not found');
                }

                $featuredImagePath = $event['featured_image'];
                $currentGallery = json_decode($event['gallery'] ?? '[]', true);
                $currentGallery = is_array($currentGallery) ? $currentGallery : [];

                $removedGallery = $_POST['gallery_remove'] ?? [];
                if (!is_array($removedGallery)) {
                    $removedGallery = [$removedGallery];
                }
                foreach ($removedGallery as $removePath) {
                    $removePath = trim($removePath);
                    if ($removePath === '') {
                        continue;
                    }
                    foreach ($currentGallery as $index => $existingPath) {
                        if ($existingPath === $removePath) {
                            unset($currentGallery[$index]);
                            deleteFileIfExists($existingPath);
                            break;
                        }
                    }
                }
                $currentGallery = array_values($currentGallery);

                $uploadedGallery = uploadEventGalleryFiles($_FILES['gallery_files'] ?? null);
                $updatedGallery = array_merge($currentGallery, $uploadedGallery);
                $galleryJson = !empty($updatedGallery) ? json_encode($updatedGallery) : null;

                $uploadedFeatured = uploadEventImageFile($_FILES['featured_image'] ?? null, 'featured');
                $removeFeatured = !empty($_POST['remove_featured_image']);
                if ($uploadedFeatured) {
                    if ($featuredImagePath) {
                        deleteFileIfExists($featuredImagePath);
                    }
                    $featuredImagePath = $uploadedFeatured;
                    $removeFeatured = false;
                } elseif ($removeFeatured && $featuredImagePath) {
                    deleteFileIfExists($featuredImagePath);
                    $featuredImagePath = null;
                }

                $data = [
                    $_POST['title'],
                    $_POST['description'] ?: null,
                    $_POST['event_type'],
                    $_POST['start_date'],
                    $_POST['end_date'],
                    $_POST['start_time'] ?: null,
                    $_POST['end_time'] ?: null,
                    $_POST['venue'] ?: null,
                    $_POST['address'] ?: null,
                    isset($_POST['is_online']) ? 1 : 0,
                    $_POST['meeting_link'] ?: null,
                    isset($_POST['registration_required']) ? 1 : 0,
                    $_POST['registration_deadline'] ?: null,
                    $_POST['max_attendees'] ?: null,
                    $_POST['registration_fee'] ?: 0.00,
                    $_POST['status'],
                    $featuredImagePath,
                    $galleryJson,
                    $eventId
                ];
                
                query("UPDATE events SET title=?, description=?, event_type=?, start_date=?, end_date=?, start_time=?, end_time=?, venue=?, address=?, is_online=?, meeting_link=?, registration_required=?, registration_deadline=?, max_attendees=?, registration_fee=?, status=?, featured_image=?, gallery=? WHERE id=?", $data);
                
                echo json_encode(['success' => true, 'message' => 'Event updated successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'delete':
            try {
                $eventToDelete = fetchRow("SELECT featured_image, gallery FROM events WHERE id = ?", [$_POST['id']]);
                if ($eventToDelete) {
                    deleteFileIfExists($eventToDelete['featured_image']);
                    $galleryItems = json_decode($eventToDelete['gallery'] ?? '[]', true);
                    if (is_array($galleryItems)) {
                        foreach ($galleryItems as $galleryPath) {
                            deleteFileIfExists($galleryPath);
                        }
                    }
                }
                query("DELETE FROM events WHERE id = ?", [$_POST['id']]);
                echo json_encode(['success' => true, 'message' => 'Event deleted successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'get':
            $event = fetchRow("SELECT * FROM events WHERE id = ?", [$_POST['id']]);
            echo json_encode($event);
            exit;
    }
}

// Get events with registration count
$events = fetchAll("
    SELECT e.*, a.full_name as admin_name, COUNT(er.id) as registration_count
    FROM events e 
    JOIN admins a ON e.admin_id = a.id
    LEFT JOIN event_registrations er ON e.id = er.event_id
    GROUP BY e.id 
    ORDER BY e.start_date DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Event Management - SCI Alumni System</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="default/logo.png" type="image/x-icon" />
    
     
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
      WebFont.load({
        google: { families: ["Public Sans:300,400,500,600,700"] },
        custom: {
          families: [
            "Font Awesome 5 Solid",
            "Font Awesome 5 Regular", 
            "Font Awesome 5 Brands",
            "simple-line-icons",
          ],
          urls: ["assets/css/fonts.min.css"],
        },
        active: function () {
          sessionStorage.fonts = true;
        },
      });
    </script>

    
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
</head>
<body>
    <div class="wrapper">
             <?php include("include/sidebar.php"); ?>
      

        <div class="main-panel">
            <div class="main-header">
                <?php include("include/main-header.php"); ?>  
                
                
             <?php include("include/navbar.php"); ?>
              
            </div>

            <div class="container">
                <div class="page-inner">
                    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
                        <div>
                            <h3 class="fw-bold mb-3">Event Management</h3>
                            <h6 class="op-7 mb-2">Manage alumni events and activities</h6>
                        </div>
                        <div class="ms-md-auto py-2 py-md-0">
                            <button class="btn btn-primary btn-round" data-bs-toggle="modal" data-bs-target="#eventModal" onclick="openAddModal()">
                                <i class="fa fa-plus"></i> Add Event
                            </button>
                        </div>
                    </div>

                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Events (<?= count($events) ?>)</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Event Title</th>
                                                    <th>Type</th>
                                                    <th>Date & Time</th>
                                                    <th>Venue</th>
                                                    <th>Registrations</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($events as $event): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlspecialchars($event['title']) ?></strong>
                                                        <?php if ($event['description']): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars(substr($event['description'], 0, 80)) ?>...</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><span class="badge badge-info"><?= htmlspecialchars($event['event_type']) ?></span></td>
                                                    <td>
                                                        <?= date('M j, Y', strtotime($event['start_date'])) ?>
                                                        <?php if ($event['start_time']): ?>
                                                        <br><small class="text-muted"><?= date('g:i A', strtotime($event['start_time'])) ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($event['is_online']): ?>
                                                        <span class="badge badge-primary">Online</span>
                                                        <?php else: ?>
                                                        <?= htmlspecialchars($event['venue'] ?: 'TBA') ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($event['registration_required']): ?>
                                                        <span class="badge badge-success"><?= $event['registration_count'] ?></span>
                                                        <?php if ($event['max_attendees']): ?>
                                                        <small class="text-muted">/ <?= $event['max_attendees'] ?></small>
                                                        <?php endif; ?>
                                                        <?php else: ?>
                                                        <span class="text-muted">No registration</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $status_class = [
                                                            'Draft' => 'secondary',
                                                            'Published' => 'success',
                                                            'Cancelled' => 'danger',
                                                            'Completed' => 'info'
                                                        ];
                                                        ?>
                                                        <span class="badge badge-<?= $status_class[$event['status']] ?? 'secondary' ?>">
                                                            <?= htmlspecialchars($event['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-sm btn-info" onclick="editEvent(<?= $event['id'] ?>)" title="Edit">
                                                                <i class="fa fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger" onclick="deleteEvent(<?= $event['id'] ?>)" title="Delete">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

<?php include("include/footer.php"); ?>
        </div>
    </div>

     
    <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalLabel">Add Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="eventForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="event_id" name="id">
                        <input type="hidden" id="form_action" name="action" value="add">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Event Title *</label>
                                    <input type="text" class="form-control" name="title" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Event Type *</label>
                                    <select class="form-select" name="event_type" required>
                                        <option value="">Select Type</option>
                                        <option value="Reunion">Reunion</option>
                                        <option value="Conference">Conference</option>
                                        <option value="Workshop">Workshop</option>
                                        <option value="Social">Social</option>
                                        <option value="Career Fair">Career Fair</option>
                                        <option value="Webinar">Webinar</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Event description"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Start Date *</label>
                                    <input type="date" class="form-control" name="start_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">End Date *</label>
                                    <input type="date" class="form-control" name="end_date" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Start Time</label>
                                    <input type="time" class="form-control" name="start_time">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">End Time</label>
                                    <input type="time" class="form-control" name="end_time">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_online" id="is_online" onchange="toggleOnlineFields()">
                                <label class="form-check-label" for="is_online">
                                    Online Event
                                </label>
                            </div>
                        </div>
                        
                        <div id="venue_fields">
                            <div class="mb-3">
                                <label class="form-label">Venue</label>
                                <input type="text" class="form-control" name="venue" placeholder="Event venue">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2" placeholder="Complete address"></textarea>
                            </div>
                        </div>
                        
                        <div id="online_fields" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Meeting Link</label>
                                <input type="url" class="form-control" name="meeting_link" placeholder="https://zoom.us/j/...">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="registration_required" id="registration_required" onchange="toggleRegistrationFields()">
                                <label class="form-check-label" for="registration_required">
                                    Registration Required
                                </label>
                            </div>
                        </div>
                        
                        <div id="registration_fields" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Registration Deadline</label>
                                        <input type="date" class="form-control" name="registration_deadline">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Max Attendees</label>
                                        <input type="number" class="form-control" name="max_attendees" min="1" placeholder="Leave blank for unlimited">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Registration Fee</label>
                                <input type="number" class="form-control" name="registration_fee" step="0.01" min="0" value="0.00">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Featured Image</label>
                            <div id="featuredPreview" class="d-flex flex-wrap gap-2 mb-2"></div>
                            <input type="hidden" name="remove_featured_image" id="remove_featured_image" value="0">
                            <input type="file" class="form-control" id="featured_image_input" name="featured_image" accept="image/*">
                            <small class="text-muted">Optional. JPG, PNG, GIF, or WebP. Max 5MB.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Event Gallery</label>
                            <div id="existingGallery" class="d-flex flex-wrap gap-2 mb-2"></div>
                            <div id="selectedGalleryPreview" class="d-flex flex-wrap gap-2 mb-2"></div>
                            <input type="file" class="form-control" id="galleryFilesInput" multiple accept="image/*">
                            <small class="text-muted">You can select multiple photos; they will be appended to the gallery.</small>
                            <div id="galleryRemoveInputs"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status *</label>
                            <select class="form-select" name="status" required>
                                <option value="Draft">Draft</option>
                                <option value="Published">Published</option>
                                <option value="Cancelled">Cancelled</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>

    <script>
        const featuredImageInput = document.getElementById('featured_image_input');
        const galleryFilesInput = document.getElementById('galleryFilesInput');
        const featuredPreview = document.getElementById('featuredPreview');
        const existingGalleryContainer = document.getElementById('existingGallery');
        const selectedGalleryPreview = document.getElementById('selectedGalleryPreview');
        const galleryRemoveInputs = document.getElementById('galleryRemoveInputs');
        let currentFeaturedImage = null;
        let existingGallery = [];
        let removedGalleryPaths = [];
        let selectedGalleryFiles = [];

        function renderFeaturedPreview() {
            if (!featuredPreview) {
                return;
            }

            featuredPreview.innerHTML = '';
            const removeField = document.getElementById('remove_featured_image');

            if (featuredImageInput && featuredImageInput.files.length) {
                const file = featuredImageInput.files[0];
                const previewUrl = URL.createObjectURL(file);
                const wrapper = document.createElement('div');
                wrapper.className = 'border rounded';
                wrapper.style.width = '140px';
                wrapper.style.height = '90px';
                wrapper.style.overflow = 'hidden';
                wrapper.innerHTML = `<img src="${previewUrl}" alt="Featured preview" style="width:100%;height:100%;object-fit:cover;">`;
                featuredPreview.appendChild(wrapper);
                const note = document.createElement('small');
                note.className = 'text-muted d-block mt-1';
                note.textContent = 'New thumbnail will replace the current image.';
                featuredPreview.appendChild(note);

                if (removeField) {
                    removeField.value = '0';
                }
                return;
            }

            if (currentFeaturedImage) {
                const wrapper = document.createElement('div');
                wrapper.className = 'border rounded position-relative';
                wrapper.style.width = '140px';
                wrapper.style.height = '90px';
                wrapper.style.overflow = 'hidden';
                wrapper.innerHTML = `<img src="${currentFeaturedImage}" alt="Featured image" style="width:100%;height:100%;object-fit:cover;">`;

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-sm btn-danger position-absolute';
                removeBtn.style.top = '4px';
                removeBtn.style.right = '4px';
                removeBtn.textContent = 'Remove';
                removeBtn.addEventListener('click', () => {
                    if (removeField) {
                        removeField.value = '1';
                    }
                    currentFeaturedImage = null;
                    if (featuredImageInput) {
                        featuredImageInput.value = '';
                    }
                    renderFeaturedPreview();
                });

                wrapper.appendChild(removeBtn);
                featuredPreview.appendChild(wrapper);
                return;
            }

            const placeholder = document.createElement('small');
            placeholder.className = 'text-muted';
            placeholder.textContent = 'No featured image selected.';
            featuredPreview.appendChild(placeholder);

            if (removeField) {
                removeField.value = '0';
            }
        }

        function renderExistingGallery() {
            if (!existingGalleryContainer) {
                return;
            }

            existingGalleryContainer.innerHTML = '';

            if (!existingGallery.length) {
                const placeholder = document.createElement('small');
                placeholder.className = 'text-muted';
                placeholder.textContent = 'No gallery images uploaded.';
                existingGalleryContainer.appendChild(placeholder);
                return;
            }

            existingGallery.forEach(src => {
                const thumb = document.createElement('div');
                thumb.className = 'border rounded position-relative';
                thumb.style.width = '110px';
                thumb.style.height = '70px';
                thumb.style.overflow = 'hidden';
                thumb.innerHTML = `<img src="${src}" alt="Gallery image" style="width:100%;height:100%;object-fit:cover;">`;

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-sm btn-danger position-absolute';
                removeBtn.style.top = '4px';
                removeBtn.style.right = '4px';
                removeBtn.textContent = 'X';
                removeBtn.addEventListener('click', () => {
                    removedGalleryPaths.push(src);
                    const index = existingGallery.indexOf(src);
                    if (index > -1) {
                        existingGallery.splice(index, 1);
                    }
                    renderExistingGallery();
                    updateGalleryRemoveInputs();
                });

                thumb.appendChild(removeBtn);
                existingGalleryContainer.appendChild(thumb);
            });
        }

        function renderSelectedGalleryPreview() {
            if (!selectedGalleryPreview) {
                return;
            }

            selectedGalleryPreview.innerHTML = '';

            if (!selectedGalleryFiles.length) {
                const placeholder = document.createElement('small');
                placeholder.className = 'text-muted';
                placeholder.textContent = 'No new gallery images selected.';
                selectedGalleryPreview.appendChild(placeholder);
                return;
            }

            selectedGalleryFiles.forEach((file, index) => {
                const item = document.createElement('div');
                item.className = 'border rounded p-2 position-relative';
                item.style.minWidth = '140px';
                item.innerHTML = `
                    <strong class="d-block">${file.name}</strong>
                    <small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                `;

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-sm btn-outline-danger position-absolute';
                removeBtn.style.top = '4px';
                removeBtn.style.right = '4px';
                removeBtn.textContent = 'Remove';
                removeBtn.addEventListener('click', () => {
                    selectedGalleryFiles.splice(index, 1);
                    renderSelectedGalleryPreview();
                });

                item.appendChild(removeBtn);
                selectedGalleryPreview.appendChild(item);
            });
        }

        function updateGalleryRemoveInputs() {
            if (!galleryRemoveInputs) {
                return;
            }

            galleryRemoveInputs.innerHTML = '';
            removedGalleryPaths.forEach(path => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'gallery_remove[]';
                input.value = path;
                galleryRemoveInputs.appendChild(input);
            });
        }

        function resetImageSections() {
            currentFeaturedImage = null;
            existingGallery = [];
            removedGalleryPaths = [];
            selectedGalleryFiles = [];
            if (featuredImageInput) {
                featuredImageInput.value = '';
            }
            if (galleryFilesInput) {
                galleryFilesInput.value = '';
            }
            const removeField = document.getElementById('remove_featured_image');
            if (removeField) {
                removeField.value = '0';
            }
            renderFeaturedPreview();
            renderExistingGallery();
            renderSelectedGalleryPreview();
            updateGalleryRemoveInputs();
        }

        if (featuredImageInput) {
            featuredImageInput.addEventListener('change', function () {
                const removeField = document.getElementById('remove_featured_image');
                if (removeField) {
                    removeField.value = '0';
                }
                renderFeaturedPreview();
            });
        }

        if (galleryFilesInput) {
            galleryFilesInput.addEventListener('change', function () {
                const files = Array.from(this.files || []);
                if (files.length) {
                    selectedGalleryFiles = selectedGalleryFiles.concat(files);
                    renderSelectedGalleryPreview();
                }
                this.value = '';
            });
        }

        function openAddModal() {
            document.getElementById('eventModalLabel').textContent = 'Add Event';
            document.getElementById('form_action').value = 'add';
            document.getElementById('eventForm').reset();
            document.getElementById('event_id').value = '';
            resetImageSections();
            toggleOnlineFields();
            toggleRegistrationFields();
        }
        
        function editEvent(id) {
            $.post('manage_events.php', {action: 'get', id: id}, function(data) {
                if (data) {
                    document.getElementById('eventModalLabel').textContent = 'Edit Event';
                    document.getElementById('form_action').value = 'edit';
                    document.getElementById('event_id').value = data.id;
                    
                    Object.keys(data).forEach(key => {
                        const field = document.querySelector(`[name="${key}"]`);
                        if (!field) {
                            return;
                        }

                        if (field.type === 'checkbox') {
                            field.checked = data[key] == 1;
                            return;
                        }

                        if (field.type === 'file') {
                            return;
                        }

                        field.value = data[key] || '';
                    });

                    currentFeaturedImage = data.featured_image || null;
                    document.getElementById('remove_featured_image').value = '0';
                    existingGallery = [];
                    removedGalleryPaths = [];
                    selectedGalleryFiles = [];
                    if (data.gallery) {
                        try {
                            const parsedGallery = JSON.parse(data.gallery);
                            existingGallery = Array.isArray(parsedGallery) ? parsedGallery : [];
                        } catch (error) {
                            existingGallery = [];
                        }
                    }
                    renderFeaturedPreview();
                    renderExistingGallery();
                    renderSelectedGalleryPreview();
                    updateGalleryRemoveInputs();
                    
                    toggleOnlineFields();
                    toggleRegistrationFields();
                    
                    $('#eventModal').modal('show');
                }
            }, 'json');
        }
        
        function deleteEvent(id) {
            swal({
                title: "Are you sure?",
                text: "This will permanently delete the event!",
                type: "warning",
                buttons: {
                    confirm: {
                        text: "Yes, delete it!",
                        className: "btn btn-success",
                    },
                    cancel: {
                        visible: true,
                        className: "btn btn-danger",
                    },
                },
            }).then((Delete) => {
                if (Delete) {
                    $.post('manage_events.php', {action: 'delete', id: id}, function(response) {
                        if (response.success) {
                            swal("Deleted!", response.message, "success").then(() => {
                                location.reload();
                            });
                        } else {
                            swal("Error!", response.message, "error");
                        }
                    }, 'json');
                }
            });
        }
        
        function toggleOnlineFields() {
            const isOnline = document.getElementById('is_online').checked;
            document.getElementById('venue_fields').style.display = isOnline ? 'none' : 'block';
            document.getElementById('online_fields').style.display = isOnline ? 'block' : 'none';
        }
        
        function toggleRegistrationFields() {
            const registrationRequired = document.getElementById('registration_required').checked;
            document.getElementById('registration_fields').style.display = registrationRequired ? 'block' : 'none';
        }
        
        $('#eventForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            selectedGalleryFiles.forEach(file => {
                formData.append('gallery_files[]', file);
            });

            $.ajax({
                url: 'manage_events.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        swal("Success!", response.message, "success").then(() => {
                            $('#eventModal').modal('hide');
                            location.reload();
                        });
                    } else {
                        swal("Error!", response.message, "error");
                    }
                },
                error: function() {
                    swal("Error!", "Failed to save event. Please try again.", "error");
                }
            });
        });
    </script>
</body>
</html>
