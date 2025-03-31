<?php
require_once "faq_db.php";

try {
    // POST 데이터 검증
    if (!isset($_POST['sub_chap_id'])) {
        throw new Exception("서브챕터 ID가 없습니다.");
    }

    $sub_chap_id = $_POST['sub_chap_id'];
    $chapter_name = $_POST['chapter_name'] ?? '';
    $sub_chapter_name = $_POST['sub_chapter_name'] ?? '';
    $subchapter_desc = $_POST['subchapter_description'] ?? '';
    $mode = $_POST['mode'] ?? 'edit_current';

    // 트랜잭션 시작
    $dbconnect->begin_transaction();

    // 서브챕터 업데이트
    if (!empty($sub_chapter_name)) {
        // 데이터 유효성 검사
        if (!isset($_POST['chapter_id'])) {
            throw new Exception("챕터 ID가 없습니다.");
        }

        // // 서브챕터 중복 검사
        // $stmt = $dbconnect->prepare("
        //     SELECT COUNT(*) as count
        //     FROM SUBCHAPTERS
        //     WHERE CHAP_ID = ?
        //     AND SUB_CHAP_NAME = ?
        //     AND SUB_CHAP_ID != ?
        // ");
        // $stmt->bind_param("isi", $_POST['chapter_id'], $sub_chapter_name, $sub_chap_id);
        // $stmt->execute();
        // if ($stmt->get_result()->fetch_assoc()['count'] > 0) {
        //     throw new Exception("이미 같은 챕터 내에 동일한 이름의 서브챕터가 존재합니다.");
        // }

        // 현재 서브챕터 정보 가져오기
        $stmt = $dbconnect->prepare("
            SELECT SUB_CHAP_ID, POSITIONS, VERSIONS, SUB_CHAP_NAME
            FROM SUBCHAPTERS
            WHERE SUB_CHAP_ID = ? AND CHAP_ID = ?
        ");
        $stmt->bind_param("ii", $sub_chap_id, $_POST['chapter_id']);
        $stmt->execute();
        $current_subchapter = $stmt->get_result()->fetch_assoc();

        if (!$current_subchapter) {
            throw new Exception("해당 서브챕터를 찾을 수 없습니다.");
        }

        // 현재 버전 수정
        $stmt = $dbconnect->prepare("
            UPDATE SUBCHAPTERS
            SET SUB_CHAP_NAME = ?,
                SUB_CHAP_DESC = ?
            WHERE SUB_CHAP_ID = ?
            AND VERSIONS = ?
        ");
        $stmt->bind_param("ssii",
            $sub_chapter_name,
            $subchapter_desc,
            $sub_chap_id,
            $current_subchapter['VERSIONS']
        );

        if (!$stmt->execute()) {
            throw new Exception("서브챕터 수정 실패: " . $stmt->error);
        }

        $new_sub_chap_id = $sub_chap_id;

        // Fix section handling
        if (isset($_POST['section_names'])) {
            foreach ($_POST['section_names'] as $index => $section_name) {
                if (empty($section_name)) continue;

                $section_id = $_POST['section_ids'][$index] ?? 'new';

                if ($section_id === 'new') {
                    // Insert new section
                    $stmt = $dbconnect->prepare("
                        INSERT INTO SECTIONS
                        (CHAP_ID, SUB_CHAP_ID, SEC_NAME, VERSION)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->bind_param("iisi",
                        $_POST['chapter_id'],
                        $sub_chap_id,
                        $section_name,
                        $current_subchapter['VERSIONS']
                    );
                    $stmt->execute();
                    $section_id = $dbconnect->insert_id;
                } else {
                    // Update existing section
                    $stmt = $dbconnect->prepare("
                        UPDATE SECTIONS
                        SET SEC_NAME = ?
                        WHERE SEC_ID = ? AND VERSION = ?
                    ");
                    $stmt->bind_param("sii",
                        $section_name,
                        $section_id,
                        $current_subchapter['VERSIONS']
                    );
                    $stmt->execute();
                }

                // Handle subsections
                if (isset($_POST['subsection_names'][$index])) {
                    foreach ($_POST['subsection_names'][$index] as $sub_index => $subsection_name) {
                        $subsection_id = $_POST['subsection_ids'][$index][$sub_index] ?? 'new';
                        $subsection_content = $_POST['subsection_contents'][$index][$sub_index];

                        if ($subsection_id === 'new') {
                            // Insert new subsection
                            $stmt = $dbconnect->prepare("
                                INSERT INTO SUBSECTIONS (SEC_ID, SUB_SEC_NAME, SUB_SEC_CONTENT, VERSION)
                                VALUES (?, ?, ?, ?)
                            ");
                            $stmt->bind_param("issi",
                                $section_id,
                                $subsection_name,
                                $subsection_content,
                                $current_subchapter['VERSIONS']
                            );
                        } else {
                            // Update existing subsection
                            $stmt = $dbconnect->prepare("
                                UPDATE SUBSECTIONS
                                SET SUB_SEC_NAME = ?,
                                    SUB_SEC_CONTENT = ?
                                WHERE SUB_SEC_ID = ? AND VERSION = ?
                            ");
                            $stmt->bind_param("ssii",
                                $subsection_name,
                                $subsection_content,
                                $subsection_id,
                                $current_subchapter['VERSIONS']
                            );
                        }
                        $stmt->execute();
                    }
                }
            }
        }
    }

    // 트랜잭션 커밋
    $dbconnect->commit();

    // 성공 메시지 설정
    session_start();
    $_SESSION['success_message'] = "FAQ가 성공적으로 수정되었습니다.";
    header("Location: index.html");
    exit;

} catch (Exception $e) {
    if ($dbconnect->connect_errno === 0) {
        $dbconnect->rollback();
    }

    echo "<script>
        alert('FAQ 수정 중 오류가 발생했습니다: " . addslashes($e->getMessage()) . "');
        history.back();
    </script>";
    exit;
}

$dbconnect->close();
?>
