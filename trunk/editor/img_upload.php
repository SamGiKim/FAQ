<?php
// JSON 헤더 설정
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/debug.php';

// Debug log
error_log("Upload request received");
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

// For macOS Sites folder
$BASEPATH = dirname(__DIR__) . "/uploads";  // Physical path
$URL_BASE = "/FAQ/uploads";  // Web path matching your current URL structure

try {
    // Log the paths being used
    error_log("Base path: " . $BASEPATH);
    error_log("URL base: " . $URL_BASE);

    // Create uploads directory if it doesn't exist
    if (!file_exists($BASEPATH)) {
        if (!mkdir($BASEPATH, 0755, true)) {
            throw new Exception("Failed to create uploads directory");
        }
    }

    // Check if directory is writable
    if (!is_writable($BASEPATH)) {
        throw new Exception("Upload directory is not writable");
    }

    // 파일 업로드 확인 - Changed to check for file-0
    if (!isset($_FILES['file-0'])) {
        throw new Exception("업로드된 파일이 없습니다.");
    }

    // Check for upload errors
    if ($_FILES['file-0']['error'] === UPLOAD_ERR_INI_SIZE) {
        throw new Exception("파일이 PHP 설정의 최대 업로드 크기를 초과했습니다.");
    }

    // 기본 파일 정보 가져오기 - Changed to use file-0
    $filename = $_FILES['file-0']['name'];
    $tmp_file = $_FILES['file-0']['tmp_name'];

    if ($_FILES['file-0']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("파일 업로드 에러: " . $_FILES['file-0']['error']);
    }

    // POST로 전달된 ID들 가져오기
    $chap_id = isset($_POST['chap_id']) ? $_POST['chap_id'] : '0';
    $sub_chap_id = isset($_POST['sub_chap_id']) ? $_POST['sub_chap_id'] : '0';
    $version = isset($_POST['version']) ? $_POST['version'] : '1';

    // 파일 확장자 추출
    $file_extension = pathinfo($filename, PATHINFO_EXTENSION);

    // 새로운 파일명 생성
    $timestamp = date("Ymd_His");
    $new_filename = "chap{$chap_id}_sub{$sub_chap_id}_v{$version}_{$timestamp}.{$file_extension}";
    $store_filepath = "{$BASEPATH}/{$new_filename}";

    // 파일이 이미 존재하는 경우 처리
    if (file_exists($store_filepath)) {
        $is_samefile = (md5_file($store_filepath) == md5_file($tmp_file));
        if (!$is_samefile) {
            $counter = 1;
            while (file_exists($store_filepath)) {
                $new_filename = "chap{$chap_id}_sub{$sub_chap_id}_v{$version}_{$timestamp}_{$counter}.{$file_extension}";
                $store_filepath = "{$BASEPATH}/{$new_filename}";
                $counter++;
            }
        }
    }

    // Log the file save attempt
    error_log("Attempting to save file to: " . $store_filepath);

    // 파일 저장
    if (!move_uploaded_file($tmp_file, $store_filepath)) {
        error_log("Failed to move uploaded file");
        throw new Exception("파일 저장 실패");
    }

    error_log("File successfully saved");

    $response = array(
        "result" => array(
            array(
                "url" => "{$URL_BASE}/{$new_filename}",
                "name" => $new_filename,
                "size" => $_FILES['file-0']['size']
            )
        )
    );

    error_log("Sending response: " . print_r($response, true));
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Upload error: " . $e->getMessage());
    // 에러 로깅
    error_log("Image upload error: " . $e->getMessage());

    // SunEditor 에러 응답 형식
    echo json_encode(array(
        "result" => false,
        "error" => $e->getMessage()
    ));
}
?>
