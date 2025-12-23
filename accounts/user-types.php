<?php
require_once '../config/config.php';
requireLogin();

$pageTitle = 'add_user_type_title';

// Get all user types
try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM user_types ORDER BY id DESC");
    $userTypes = $stmt->fetchAll();
} catch (PDOException $e) {
    $userTypes = [];
}

include '../includes/header.php';
?>

<style>
/* Remove all animations from user types page */
.table tbody tr {
    transition: none !important;
}
.table tbody tr:hover {
    transform: none !important;
}
.card {
    transition: none !important;
}
.card:hover {
    transform: none !important;
}
.card-header::before {
    animation: none !important;
    display: none !important;
}
.btn {
    transition: none !important;
}
.btn:hover {
    transform: none !important;
}
.btn-primary::before {
    display: none !important;
}
</style>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-tags"></i> <?php echo t('add_user_type_title'); ?></h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newUserTypeModal">
            <i class="fas fa-plus"></i> <?php echo t('new_user_type'); ?>
        </button>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo t('all_user_types'); ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?php echo t('type_name'); ?></th>
                                <th><?php echo t('type_name_urdu'); ?></th>
                                <th><?php echo t('description'); ?></th>
                                <th><?php echo t('date'); ?></th>
                                <th><?php echo t('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($userTypes)): ?>
                                <tr>
                                    <td colspan="6" class="text-center"><?php echo t('no_records'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($userTypes as $index => $type): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo displayTypeName($type); ?></td>
                                        <td><?php echo getLang() == 'ur' ? htmlspecialchars($type['type_name'] ?? '-') : htmlspecialchars($type['type_name_urdu'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($type['description'] ?? '-'); ?></td>
                                        <td><?php echo formatDate($type['created_at']); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-warning" onclick="editUserType(<?php echo $type['id']; ?>, '<?php echo htmlspecialchars($type['type_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($type['type_name_urdu'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($type['description'] ?? '', ENT_QUOTES); ?>')">
                                                <i class="fas fa-edit"></i> <?php echo t('edit'); ?>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteUserType(<?php echo $type['id']; ?>, '<?php echo htmlspecialchars($type['type_name'], ENT_QUOTES); ?>', event)">
                                                <i class="fas fa-trash"></i> <?php echo t('delete'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New User Type Modal -->
<div class="modal fade" id="newUserTypeModal" tabindex="-1" aria-labelledby="newUserTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newUserTypeModalLabel">
                    <i class="fas fa-tags"></i> <?php echo t('new_user_type'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="userTypeFormMessage"></div>
                <form id="newUserTypeForm">
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('type_name_english'); ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="type_name" id="type_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('type_name_urdu_label'); ?></label>
                        <input type="text" class="form-control" name="type_name_urdu" id="type_name_urdu">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('description'); ?></label>
                        <textarea class="form-control" name="description" id="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                </button>
                <button type="button" class="btn btn-primary" onclick="saveNewUserType()">
                    <i class="fas fa-save"></i> <?php echo t('save'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function editUserType(id, typeName, typeNameUrdu, description) {
    document.getElementById('user_type_id').value = id;
    document.getElementById('type_name').value = typeName;
    document.getElementById('type_name_urdu').value = typeNameUrdu;
    document.getElementById('description').value = description;
    document.getElementById('newUserTypeModalLabel').innerHTML = '<i class="fas fa-edit"></i> <?php echo t('edit'); ?> <?php echo t('user_type'); ?>';
    
    const modal = new bootstrap.Modal(document.getElementById('newUserTypeModal'));
    modal.show();
}

function deleteUserType(id, typeName, event) {
    // Validate inputs
    if (!id || id <= 0) {
        showNotification('<?php echo t('invalid_id'); ?>', 'error', 5000);
        return;
    }
    
    // Show confirmation dialog
    if (!confirm('<?php echo t('confirm'); ?>: <?php echo t('delete'); ?> "' + typeName + '"?')) {
        return;
    }
    
    // Find the delete button that was clicked to show loading state
    let targetButton = null;
    
    // Use event target if available
    if (event && event.target) {
        targetButton = event.target.closest('button.btn-danger');
    }
    
    // Fallback: find button by onclick attribute
    if (!targetButton) {
        const deleteButtons = document.querySelectorAll('button.btn-danger');
        deleteButtons.forEach(btn => {
            const onclickAttr = btn.getAttribute('onclick');
            if (onclickAttr && onclickAttr.includes('deleteUserType(' + id + ',')) {
                targetButton = btn;
            }
        });
    }
    
    // Show loading state
    const originalButtonHTML = targetButton ? targetButton.innerHTML : '';
    const originalButtonDisabled = targetButton ? targetButton.disabled : false;
    
    if (targetButton) {
        targetButton.disabled = true;
        targetButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo t('deleting'); ?>...';
    }
    
    // Make delete request
    fetch('<?php echo BASE_URL; ?>accounts/user-type-ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=delete&id=' + id
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Show success notification
            showNotification(data.message, 'success');
            
            // Reload after 2 seconds to show notification
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            // Show error notification (auto-dismiss after 5 seconds)
            showNotification(data.message || '<?php echo t('error_deleting_user_type'); ?>', 'error', 5000);
            
            // Restore button state
            if (targetButton) {
                targetButton.disabled = originalButtonDisabled;
                targetButton.innerHTML = originalButtonHTML;
            }
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        // Show error notification (auto-dismiss after 5 seconds)
        showNotification('<?php echo t('error'); ?>: <?php echo t('error_deleting_user_type'); ?>', 'error', 5000);
        
        // Restore button state
        if (targetButton) {
            targetButton.disabled = originalButtonDisabled;
            targetButton.innerHTML = originalButtonHTML;
        }
    });
}

function saveNewUserType() {
    const form = document.getElementById('newUserTypeForm');
    const formData = new FormData(form);
    const messageDiv = document.getElementById('userTypeFormMessage');
    
    // Clear previous messages
    messageDiv.innerHTML = '';
    
    // Validate required field
    if (!formData.get('type_name').trim()) {
        messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo t('type_name_required'); ?></div>';
        return;
    }
    
    // Show loading
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo t('save'); ?>...';
    
    fetch('<?php echo BASE_URL; ?>accounts/user-type-ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';
            form.reset();
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('newUserTypeModal'));
                modal.hide();
                location.reload();
            }, 1500);
        } else {
            messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' + data.message + '</div>';
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo t('error_adding_user_type'); ?></div>';
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}

// Reset form when modal is closed
document.getElementById('newUserTypeModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('newUserTypeForm').reset();
    document.getElementById('userTypeFormMessage').innerHTML = '';
});
</script>

<?php include '../includes/footer.php'; ?>

