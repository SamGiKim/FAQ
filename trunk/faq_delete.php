<?php
// faq_delete.php
require_once "faq_db.php";
require_once "debug.php"; 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['chap_id'], $_POST['sub_chap_id'], $_POST['position'], $_POST['version'])) {
    $chap_id = $_POST['chap_id'];
    $sub_chap_id = $_POST['sub_chap_id'];
    $position = $_POST['position'];
    $version = $_POST['version'];

    // 변수 값 출력 (디버깅용)
    var_dump($chap_id, $sub_chap_id, $position, $version);

    $dbconnect->begin_transaction();

    try {
        // 1. SUBSECTIONS 삭제
        $query = "DELETE ss FROM SUBSECTIONS ss
                  INNER JOIN SECTIONS s ON ss.SEC_ID = s.SEC_ID
                  INNER JOIN SUBCHAPTERS sc ON s.SUB_CHAP_ID = sc.SUB_CHAP_ID
                  WHERE sc.CHAP_ID = ? AND sc.POSITIONS = ? AND ss.VERSION = ?";
        $stmt = $dbconnect->prepare($query);
        $stmt->bind_param("iii", $chap_id, $position, $version);
        $stmt->execute();
        echo "삭제된 SUBSECTIONS 행 수: " . $stmt->affected_rows . "\n";

        // 2. SECTIONS 삭제
        $query = "DELETE s FROM SECTIONS s
                  INNER JOIN SUBCHAPTERS sc ON s.SUB_CHAP_ID = sc.SUB_CHAP_ID
                  WHERE sc.CHAP_ID = ? AND sc.POSITIONS = ? AND s.VERSION = ?";
        $stmt = $dbconnect->prepare($query);
        $stmt->bind_param("iii", $chap_id, $position, $version);
        $stmt->execute();
        echo "삭제된 SECTIONS 행 수: " . $stmt->affected_rows . "\n";

        // 3. SUBCHAPTERS 삭제
        $query = "DELETE FROM SUBCHAPTERS 
                  WHERE CHAP_ID = ? AND POSITIONS = ? AND VERSIONS = ?";
        $stmt = $dbconnect->prepare($query);
        $stmt->bind_param("iii", $chap_id, $position, $version);
        $stmt->execute();
        echo "삭제된 SUBCHAPTERS 행 수: " . $stmt->affected_rows . "\n";

        // 4. CHAPTERS 삭제 (해당 CHAP_ID에 더 이상 SUBCHAPTERS가 없으면 삭제)
        $query = "DELETE FROM CHAPTERS 
                  WHERE CHAP_ID = ? 
                  AND NOT EXISTS (
                      SELECT 1 FROM SUBCHAPTERS 
                      WHERE CHAP_ID = ?
                  )";
        $stmt = $dbconnect->prepare($query);
        $stmt->bind_param("ii", $chap_id, $chap_id);
        $stmt->execute();
        echo "삭제된 CHAPTERS 행 수: " . $stmt->affected_rows . "\n";

        $dbconnect->commit();
        header("Location: index.html");
        exit();

    } catch (Exception $e) {
        $dbconnect->rollback();
        error_log("Error in faq_delete.php: " . $e->getMessage());
        echo "삭제 중 오류가 발생했습니다: " . $e->getMessage();
    }
} else {
    header("Location: index.html");
    exit();
}
?>