<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => t('invalid_request_method')]);
    exit;
}

$action = $_POST['action'] ?? 'create';
$id = intval($_POST['id'] ?? 0);
$typeName = sanitizeInput($_POST['type_name'] ?? '');
$typeNameUrdu = sanitizeInput($_POST['type_name_urdu'] ?? '');
$description = sanitizeInput($_POST['description'] ?? '');

try {
    $db = getDB();
    
    if ($action == 'delete') {
        // Validate ID for delete
        if (empty($id) || $id <= 0) {
            echo json_encode(['success' => false, 'message' => t('invalid_id')]);
            exit;
        }
        // Check if user type is being used
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM accounts WHERE user_type_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            echo json_encode(['success' => false, 'message' => t('cannot_delete_user_type_in_use')]);
            exit;
        }
        
        $stmt = $db->prepare("DELETE FROM user_types WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => t('user_type_deleted_success')]);
    } elseif ($action == 'update') {
        // Validate for update
        if (empty($id) || $id <= 0) {
            echo json_encode(['success' => false, 'message' => t('invalid_id')]);
            exit;
        }
        
        if (empty($typeName)) {
            echo json_encode(['success' => false, 'message' => t('type_name_required')]);
            exit;
        }
        
        $stmt = $db->prepare("UPDATE user_types SET type_name = ?, type_name_urdu = ?, description = ? WHERE id = ?");
        $stmt->execute([$typeName, $typeNameUrdu, $description, $id]);
        
        echo json_encode(['success' => true, 'message' => t('user_type_updated_success')]);
    } else {
        // Create - validate required fields
        if (empty($typeName)) {
            echo json_encode(['success' => false, 'message' => t('type_name_required')]);
            exit;
        }
        
        $stmt = $db->prepare("INSERT INTO user_types (type_name, type_name_urdu, description) VALUES (?, ?, ?)");
        $stmt->execute([$typeName, $typeNameUrdu, $description]);
        
        echo json_encode(['success' => true, 'message' => t('user_type_added_success')]);
    }
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'UNIQUE') !== false) {
        echo json_encode(['success' => false, 'message' => t('type_already_exists')]);
    } else {
        $errorMsg = $action == 'delete' ? t('error_deleting_user_type') : ($action == 'update' ? t('error_updating_user_type') : t('error_adding_user_type'));
        echo json_encode(['success' => false, 'message' => $errorMsg . ': ' . $e->getMessage()]);
    }
}
?>

