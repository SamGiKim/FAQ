<?php

require_once "faq_db.php";
require_once "debug.php"; 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// echo "<pre>";
// print_r($_POST);
// echo "</pre>";
// exit;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // POST 데이터 디버깅
        error_log("===== POST DATA DEBUG =====");
        error_log(print_r($_POST, true));

        // 배열 데이터 확인
        error_log("Section names: " . print_r($_POST['section_names'], true));
        error_log("Subsection names: " . print_r($_POST['subsection_names'], true));
        error_log("Subsection contents: " . print_r($_POST['subsection_contents'], true));

        $dbconnect->begin_transaction();

        // 데이터 처리 전 배열 확인
        $section_names = $_POST['section_names'] ?? [];
        $section_descs = $_POST['section_descs'] ?? [];
        $subsection_names = $_POST['subsection_names'] ?? [];
        $subsection_contents = $_POST['subsection_contents'] ?? [];

        error_log("Processed arrays:");
        error_log("Section names (count): " . count($section_names));
        error_log("Subsection names (count): " . count($subsection_names));

        // 데이터 가져오기 - 기존 유지
        $newChapterName = $_POST['new_chapter'] ?? null;
        $chapterOption = $_POST['chapter'] ?? null;
        $newSubChapterName = $_POST['new_sub_chapter'] ?? null;
        $subChapterOption = $_POST['sub_chapter'] ?? null;
        $section_names = $_POST['section_names'] ?? [];
        $section_descs = $_POST['section_descs'] ?? [];  // 섹션 설명 배열 추가
        $subsection_names = $_POST['subsection_names'] ?? [];
        $subsection_contents = $_POST['subsection_contents'] ?? [];

        // Add this near the top where other POST variables are collected
        $subChapterDesc = $_POST['subchapter_description'] ?? null;  // Get the description from the form

        // POST 데이터 디버깅
        error_log("POST Data: " . print_r($_POST, true));

        // 배열이 아닌 경우 배열로 변환
        $section_names = is_array($section_names) ? $section_names : [$section_names];
        $section_descs = is_array($section_descs) ? $section_descs : [$section_descs];
        $subsection_names = is_array($subsection_names) ? $subsection_names : [$subsection_names];
        $subsection_contents = is_array($subsection_contents) ? $subsection_contents : [$subsection_contents];

        // 1. 챕터 처리
        if (!empty($newChapterName)) {
            // 챕터 이름 중복 검사
            $stmt = $dbconnect->prepare("
                SELECT COUNT(*) as count
                FROM CHAPTERS
                WHERE CHAP_NAME = ?
            ");
            $stmt->bind_param("s", $newChapterName);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()['count'] > 0) {
                throw new Exception("이미 존재하는 챕터 이름입니다: " . $newChapterName);
            }

            // 새 챕터 추가
            $stmt = $dbconnect->prepare("INSERT INTO CHAPTERS (CHAP_NAME) VALUES (?)");
            $stmt->bind_param("s", $newChapterName);
            if (!$stmt->execute()) {
                throw new Exception("새 챕터 추가 실패: " . $stmt->error);
            }
            $chapId = $dbconnect->insert_id;
        } elseif (!empty($chapterOption)) {
            $chapId = $chapterOption;
        } else {
            throw new Exception("챕터를 입력하거나 선택하세요.");
        }

       // 2. 서브챕터 처리 및 버전 관리
        if (!empty($newSubChapterName)) {
            // 같은 챕터 내 서브챕터 이름 중복 검사
            $stmt = $dbconnect->prepare("
                SELECT COUNT(*) as count
                FROM SUBCHAPTERS
                WHERE CHAP_ID = ? AND SUB_CHAP_NAME = ?
            ");
            $stmt->bind_param("is", $chapId, $newSubChapterName);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()['count'] > 0) {
                throw new Exception("이미 같은 챕터 내에 동일한 이름의 서브챕터가 존재합니다: " . $newSubChapterName);
            }

        // 새로운 서브챕터 생성 시
        $stmt = $dbconnect->prepare("
            SELECT POSITIONS
            FROM SUBCHAPTERS
            WHERE CHAP_ID = ? AND SUB_CHAP_NAME = ?
            LIMIT 1
        ");
        $stmt->bind_param("is", $chapId, $newSubChapterName);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // 같은 챕터 내에 이미 존재하는 SUB_CHAP_NAME인 경우 해당 POSITION 사용
            $position = $result->fetch_assoc()['POSITIONS'];
        } else {
            // 새로운 SUB_CHAP_NAME인 경우 해당 챕터 내에서 새로운 POSITION 생성(챕터가 다른 경우 독립적으로 POSITION 값 할당)
            $stmt = $dbconnect->prepare("
                SELECT MAX(POSITIONS) as max_position
                FROM SUBCHAPTERS
                WHERE CHAP_ID = ?
            ");
            $stmt->bind_param("i", $chapId);
            $stmt->execute();
            $position = ($stmt->get_result()->fetch_assoc()['max_position'] ?? 0) + 1;
        }

        // 새 서브챕터 추가 (VERSION은 1로 시작)
        $stmt = $dbconnect->prepare("
            INSERT INTO SUBCHAPTERS
            (CHAP_ID, SUB_CHAP_NAME, SUB_CHAP_DESC, POSITIONS, VERSIONS)
            VALUES (?, ?, ?, ?, 1)
        ");
        $stmt->bind_param("issi", $chapId, $newSubChapterName, $subChapterDesc, $position);
        if (!$stmt->execute()) {
            throw new Exception("새로운 서브챕터 추가 실패: " . $stmt->error);
        }
        $subChapId = $dbconnect->insert_id;
        $version = 1;

    } elseif (!empty($subChapterOption)) {
        // 기존 서브챕터 선택 시
        $subChapId = $subChapterOption;

        // 현재 서브챕터의 정보 조회
        $stmt = $dbconnect->prepare("
            SELECT POSITIONS, SUB_CHAP_NAME
            FROM SUBCHAPTERS
            WHERE SUB_CHAP_ID = ? AND CHAP_ID = ?
        ");
        $stmt->bind_param("ii", $subChapId, $chapId);
        $stmt->execute();
        $subChapInfo = $stmt->get_result()->fetch_assoc();

        // 같은 챕터, 같은 POSITION 내에서 최대 VERSION 조회
        $stmt = $dbconnect->prepare("
            SELECT MAX(VERSIONS) as max_version
            FROM SUBCHAPTERS
            WHERE CHAP_ID = ? AND POSITIONS = ?
        ");
        $stmt->bind_param("ii", $chapId, $subChapInfo['POSITIONS']);
        $stmt->execute();
        $maxVersion = $stmt->get_result()->fetch_assoc()['max_version'];

        // 새로운 버전으로 서브챕터 추가
        $newVersion = $maxVersion + 1;
        $stmt = $dbconnect->prepare("
            INSERT INTO SUBCHAPTERS
            (CHAP_ID, SUB_CHAP_NAME, SUB_CHAP_DESC, POSITIONS, VERSIONS)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issii", $chapId, $subChapInfo['SUB_CHAP_NAME'], $subChapterDesc, $subChapInfo['POSITIONS'], $newVersion);
        if (!$stmt->execute()) {
            throw new Exception("새 버전 추가 실패: " . $stmt->error);
        }
        $subChapId = $dbconnect->insert_id;
        $version = $newVersion;
    } else {
        throw new Exception("서브챕터를 입력하거나 선택하세요.");
    }
         // 3. 섹션 처리
        foreach ($section_names as $index => $name) {
            if (empty($name)) continue;

            // Fix error logging
            error_log("Processing section index: " . $index);
            error_log("Processing section name: " . (is_array($name) ? json_encode($name) : $name));

            // 섹션 생성
            $stmt = $dbconnect->prepare("
                INSERT INTO SECTIONS
                (CHAP_ID, SUB_CHAP_ID, SEC_NAME, SEC_DESC, VERSION)
                VALUES (?, ?, ?, ?, ?)
            ");
            $sectionDesc = $section_descs[$index] ?? '';
            $nameToInsert = is_array($name) ? $name[0] : $name;
            $descToInsert = is_array($sectionDesc) ? $sectionDesc[0] : $sectionDesc;

            $stmt->bind_param("iissi", $chapId, $subChapId, $nameToInsert, $descToInsert, $version);
            if (!$stmt->execute()) {
                throw new Exception("섹션 추가 실패: " . $stmt->error);
            }
            $secId = $dbconnect->insert_id;

            // 4. 해당 섹션의 서브섹션 처리
            if (!empty($subsection_names[$index])) {
                foreach ($subsection_names[$index] as $subIndex => $subSectionName) {
                    if (empty($subSectionName)) continue;

                    // Get and validate content
                    $subSectionContent = $subsection_contents[$index][$subIndex] ?? '';
                    error_log("Processing subsection content: " . substr($subSectionContent, 0, 100) . "...");

                    if (empty($subSectionContent)) {
                        error_log("Warning: Empty content for subsection {$subSectionName}");
                    }

                    // 서브섹션 중복 검사
                    $stmt = $dbconnect->prepare("
                        SELECT COUNT(*) as count
                        FROM SUBSECTIONS ss
                        JOIN SECTIONS s ON ss.SEC_ID = s.SEC_ID
                        WHERE s.CHAP_ID = ?
                        AND s.SUB_CHAP_ID = ?
                        AND s.SEC_ID = ?
                        AND ss.SUB_SEC_NAME = ?
                    ");
                    $stmt->bind_param("iiis", $chapId, $subChapId, $secId, $subSectionName);
                    $stmt->execute();
                    if ($stmt->get_result()->fetch_assoc()['count'] > 0) {
                        throw new Exception("이미 같은 섹션 내에 동일한 이름의 서브섹션이 존재합니다: " . $subSectionName);
                    }

                    // 서브섹션 생성
                    $stmt = $dbconnect->prepare("
                        INSERT INTO SUBSECTIONS
                        (SEC_ID, SUB_SEC_NAME, SUB_SEC_CONTENT, VERSION)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->bind_param("issi", $secId, $subSectionName, $subSectionContent, $version);
                    if (!$stmt->execute()) {
                        throw new Exception("서브섹션 추가 실패: " . $stmt->error);
                    }
                }
            }
        }
         // 트랜잭션 커밋 - 기존 유지
        $dbconnect->commit();

        // 성공 메시지 설정
        session_start();
        $_SESSION['success_message'] = "내용이 성공적으로 추가되었습니다.";

        header("Location: index.html");
        exit;
   } catch (Exception $e) {
    $dbconnect->rollback();

    // 에러 로그 기록
    error_log("FAQ 제출 오류: " . $e->getMessage());

    // 개발 환경에서 상세한 에러 정보 표시
    echo "<div style='background: #ffebee; padding: 20px; margin: 20px; border: 1px solid #ef9a9a;'>";
    echo "<h2>오류가 발생했습니다</h2>";
    echo "<p><strong>에러 메시지:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>에러 코드:</strong> " . $e->getCode() . "</p>";
    echo "<p><strong>에러 발생 위치:</strong> " . htmlspecialchars($e->getFile()) . " (라인: " . $e->getLine() . ")</p>";
    echo "<p><strong>스택 트레이스:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "<p><strong>POST 데이터:</strong></p>";
    echo "<pre>" . htmlspecialchars(print_r($_POST, true)) . "</pre>";

    // 이전 페이지로 돌아가기 버튼
    echo "<button onclick='history.back()' style='padding: 10px 20px; margin-top: 20px; cursor: pointer;'>
            이전 페이지로 돌아가기
          </button>";
    echo "</div>";
    exit;
}
}

// Add these debug logs at the start of the script
error_log("=== Form Data Debug ===");
error_log("Section Names: " . json_encode($section_names));
error_log("Section Descs: " . json_encode($section_descs));
error_log("Subsection Names: " . json_encode($subsection_names));
error_log("Subsection Contents: " . json_encode($subsection_contents));

?>
