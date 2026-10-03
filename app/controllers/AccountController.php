<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

final class AccountController extends Controller
{
    private function requireAuth(): array
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('auth/login');
        }
        $user = (new User())->findById((int)$_SESSION['user_id']);
        if (!$user) {
            $this->redirect('auth/login');
        }
        return $user;
    }

    /** Profile & Account Settings view dasdasdsadasdasDSADSA*/
    public function settingsAction(): void
    {
        $user = $this->requireAuth();
        
        $success = $_SESSION['account_success'] ?? null;
        $error = $_SESSION['account_error'] ?? null;
        unset($_SESSION['account_success'], $_SESSION['account_error']);

        // Fetch documents
        $docModel = new \App\Models\UserDocument();
        $userDocs = $docModel->tableExists() ? $docModel->findByUserId((int)$user['id']) : [];
        // All certification rows (for multi-cert display)
        $allCerts = $docModel->tableExists() ? $docModel->findAllCertsByUserId((int)$user['id']) : [];

        // Check if there is an active/pending staff application
        $appModel = new \App\Models\StaffApplication();
        $staffApp = $appModel->findByUserId((int)$user['id']);

        $this->view('account/settings', [
            'user'     => $user,
            'success'  => $success,
            'error'    => $error,
            'userDocs' => $userDocs,
            'allCerts' => $allCerts,
            'staffApp' => $staffApp,
        ]);
    }

    /** Handle Document Uploads from Profile & Settings */
    public function uploadDocumentAction(): void
    {
        $user = $this->requireAuth();
        $userId = (int)$user['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('account/settings');
        }

        $docType = $_POST['doc_type'] ?? '';
        $allowedDocs = ['resume', 'certification', 'medical_certificate'];

        if (!in_array($docType, $allowedDocs, true)) {
            $_SESSION['account_error'] = 'Invalid document type specified.';
            $this->redirect('account/settings');
        }

        // Specialization update only (if trainer and certification upload optional)
        if ($docType === 'certification' && isset($_POST['specialization']) && empty($_FILES['document_file']['tmp_name'])) {
            $specialization = trim((string)$_POST['specialization']);
            (new \App\Models\UserDocument())->updateSpecialization($userId, $specialization);
            $_SESSION['account_success'] = 'Specialization updated successfully!';
            $this->redirect('account/settings');
        }

        if (empty($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['account_error'] = 'Please select a valid document file to upload.';
            $this->redirect('account/settings');
        }

        $file = $_FILES['document_file'];
        $maxSizeBytes = 10 * 1024 * 1024; // 10MB

        if ($file['size'] > $maxSizeBytes) {
            $_SESSION['account_error'] = 'File size exceeds 10MB limit.';
            $this->redirect('account/settings');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];

        if (!in_array($ext, $allowedExts, true)) {
            $_SESSION['account_error'] = 'Only PDF, JPG, PNG, DOC, DOCX files allowed.';
            $this->redirect('account/settings');
        }

        // Validate real MIME type (magic bytes)
        $allowedMimes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
        if ($finfo) { finfo_close($finfo); }

        if ($mime && !in_array($mime, $allowedMimes, true)) {
            $_SESSION['account_error'] = 'Invalid file type detected. Please upload a genuine document or image.';
            $this->redirect('account/settings');
        }

        $uploadDir = BASE_PATH . '/public/uploads/user_documents/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $filename = $docType . '_' . $userId . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        $relativeUrl = 'uploads/user_documents/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $_SESSION['account_error'] = 'Failed to save document. Please try again.';
            $this->redirect('account/settings');
        }

        $specialization = isset($_POST['specialization']) ? trim((string)$_POST['specialization']) : null;
        (new \App\Models\UserDocument())->upsert($userId, $docType, $relativeUrl, $specialization);

        $_SESSION['account_success'] = ucfirst(str_replace('_', ' ', $docType)) . ' uploaded successfully!';
        $this->redirect('account/settings');
    }

    /**
     * DELETE a user document by ID (validates ownership, cleans up disk file).
     * Accepts POST with doc_id. Returns JSON when called via XHR, or redirects.
     */
    public function deletedocumentAction(): void
    {
        $user = $this->requireAuth();
        $userId = (int)$user['id'];
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) || ($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'error' => 'Invalid method']); exit; }
            $this->redirect('account/settings');
        }

        $docId = (int)($_POST['doc_id'] ?? 0);
        if ($docId <= 0) {
            if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'error' => 'Invalid document ID']); exit; }
            $_SESSION['account_error'] = 'Invalid document ID.';
            $this->redirect('account/settings');
        }

        $docModel = new \App\Models\UserDocument();
        $docPath = $docModel->deleteById($docId, $userId);

        if ($docPath === null) {
            if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'error' => 'Document not found or access denied']); exit; }
            $_SESSION['account_error'] = 'Document not found or access denied.';
            $this->redirect('account/settings');
        }

        // Remove physical file from disk
        if ($docPath !== '') {
            $fullPath = BASE_PATH . '/public/' . ltrim($docPath, '/');
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Document deleted successfully.']);
            exit;
        }
        $_SESSION['account_success'] = 'Document deleted successfully.';
        $this->redirect('account/settings');
    }

    /** Handle Profile Picture Upload (Camera or File) */
    public function uploadPictureAction(): void
    {
        $user = $this->requireAuth();
        $userId = (int)$user['id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('account/settings');
        }

        if (empty($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['account_error'] = 'Please select a valid image file to upload.';
            $this->redirect('account/settings');
        }

        $file = $_FILES['profile_picture'];
        $maxSizeBytes = 5 * 1024 * 1024; // 5MB

        if ($file['size'] > $maxSizeBytes) {
            $_SESSION['account_error'] = 'File size exceeds 5MB limit. Please upload a smaller image.';
            $this->redirect('account/settings');
        }

        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowedTypes, true)) {
            $_SESSION['account_error'] = 'Invalid file type. Only JPG, PNG, and WEBP images are accepted.';
            $this->redirect('account/settings');
        }

        // Target directory: public/uploads/profile_pictures/
        $uploadDir = BASE_PATH . '/public/uploads/profile_pictures/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = 'jpg'; // We convert/save as JPEG for consistent quality & size
        if ($mime === 'image/png') {
            $extension = 'png';
        } elseif ($mime === 'image/webp') {
            $extension = 'webp';
        }

        $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
        $targetPath = $uploadDir . $filename;
        $relativeUrl = 'uploads/profile_pictures/' . $filename;

        // Process image with GD to square crop & resize to 512x512
        $resized = $this->resizeAndCropImage($file['tmp_name'], $targetPath, $mime, 512, 512);

        if (!$resized) {
            // Fallback move file directly if GD processing fails
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                $_SESSION['account_error'] = 'Failed to save uploaded picture. Please try again.';
                $this->redirect('account/settings');
            }
        }

        // Delete old picture if it exists
        if (!empty($user['profile_picture_url'])) {
            $oldPath = BASE_PATH . '/public/' . ltrim($user['profile_picture_url'], '/');
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        // Save to database
        (new User())->updateProfilePicture($userId, $relativeUrl);

        $_SESSION['account_success'] = 'Profile picture updated successfully!';
        $this->redirect('account/settings');
    }

    /** Utility to center-crop and resize image to square using GD */
    private function resizeAndCropImage(string $srcPath, string $dstPath, string $mime, int $targetW, int $targetH): bool
    {
        if (!function_exists('imagecreatefromjpeg')) {
            return false;
        }

        $srcImg = null;
        if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
            $srcImg = @imagecreatefromjpeg($srcPath);
        } elseif ($mime === 'image/png') {
            $srcImg = @imagecreatefrompng($srcPath);
        } elseif ($mime === 'image/webp') {
            $srcImg = @imagecreatefromwebp($srcPath);
        }

        if (!$srcImg) {
            return false;
        }

        $origW = imagesx($srcImg);
        $origH = imagesy($srcImg);

        if ($origW <= 0 || $origH <= 0) {
            imagedestroy($srcImg);
            return false;
        }

        // Calculate center crop offsets
        if ($origW > $origH) {
            $cropW = $origH;
            $cropH = $origH;
            $srcX  = (int)(($origW - $origH) / 2);
            $srcY  = 0;
        } else {
            $cropW = $origW;
            $cropH = $origW;
            $srcX  = 0;
            $srcY  = (int)(($origH - $origW) / 2);
        }

        $dstImg = imagecreatetruecolor($targetW, $targetH);

        // Preserve transparency if PNG
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($dstImg, false);
            imagesavealpha($dstImg, true);
            $transparent = imagecolorallocatealpha($dstImg, 255, 255, 255, 127);
            imagefilledrectangle($dstImg, 0, 0, $targetW, $targetH, $transparent);
        }

        imagecopyresampled($dstImg, $srcImg, 0, 0, $srcX, $srcY, $targetW, $targetH, $cropW, $cropH);

        $saved = false;
        if ($mime === 'image/png') {
            $saved = imagepng($dstImg, $dstPath, 8);
        } elseif ($mime === 'image/webp') {
            $saved = imagewebp($dstImg, $dstPath, 85);
        } else {
            $saved = imagejpeg($dstImg, $dstPath, 88);
        }

        imagedestroy($srcImg);
        imagedestroy($dstImg);

        return $saved;
    }
}
