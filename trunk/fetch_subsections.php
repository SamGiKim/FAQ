<?php
require_once "faq_db.php";

if (isset($_POST['chap_id']) && isset($_POST['position']) && isset($_POST['version'])) {
    $chap_id = $_POST['chap_id'];
    $position = $_POST['position'];
    $version = $_POST['version'];
    
    $stmt = $dbconnect->prepare("
        SELECT s.SEC_ID, s.SEC_NAME 
        FROM SECTIONS s
        JOIN SUBCHAPTERS sc ON s.CHAP_ID = sc.CHAP_ID 
            AND s.VERSION = sc.VERSIONS
        WHERE sc.CHAP_ID = ? 
            AND sc.POSITIONS = ?
            AND sc.VERSIONS = ?
    ");
    $stmt->bind_param("iii", $chap_id, $position, $version);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $options = "<option value=''>섹션 선택...</option>";
    while ($row = $result->fetch_assoc()) {
        $options .= "<option value='{$row['SEC_ID']}'>{$row['SEC_NAME']}</option>";
    }
    $options .= "<option value='new-section' class='new-option'>새로운 섹션 입력</option>";
    echo $options;
}
?>