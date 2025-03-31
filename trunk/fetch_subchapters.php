<?php
require_once "faq_db.php";

if (isset($_POST['chap_id'])) {
    $chap_id = $_POST['chap_id'];
    // POSITIONS를 기준으로 서브챕터 조회
    $stmt = $dbconnect->prepare("
        SELECT DISTINCT SUB_CHAP_ID, SUB_CHAP_NAME, POSITIONS, MAX(VERSIONS) as MAX_VERSION
        FROM SUBCHAPTERS
        WHERE CHAP_ID = ?
        GROUP BY SUB_CHAP_ID, SUB_CHAP_NAME, POSITIONS
        ORDER BY POSITIONS ASC
    ");
    $stmt->bind_param("i", $chap_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $options = "<option value=''>서브 챕터 선택...</option>";
    while ($row = $result->fetch_assoc()) {
        $options .= "<option value='{$row['SUB_CHAP_ID']}'>{$row['SUB_CHAP_NAME']} (Position: {$row['POSITIONS']}, Max Version: {$row['MAX_VERSION']})</option>";
    }
    $options .= "<option value='new-subChapter' class='new-option'>새로운 서브챕터 입력</option>";
    echo $options;
}
?>
