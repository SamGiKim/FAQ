<?php
require_once "faq_db.php";

header('Content-Type: application/json');

$chap_id = isset($_GET['chap_id']) ? (int)$_GET['chap_id'] : 0;
$position = isset($_GET['position']) ? (int)$_GET['position'] : 0;
$version = isset($_GET['version']) ? (int)$_GET['version'] : 1;

// 챕터 정보 가져오기
$chapterQuery = "SELECT CHAP_NAME FROM CHAPTERS WHERE CHAP_ID = ?";
$stmt = $dbconnect->prepare($chapterQuery);
$stmt->bind_param("i", $chap_id);
$stmt->execute();
$chapterResult = $stmt->get_result()->fetch_assoc();

// 서브챕터 정보 가져오기 (POSITION 기반으로 수정)
$subChapterQuery = "SELECT SUB_CHAP_NAME, SUB_CHAP_DESC, SUB_CHAP_ID FROM SUBCHAPTERS 
                   WHERE CHAP_ID = ? AND POSITIONS = ? AND VERSIONS = ?";
$stmt = $dbconnect->prepare($subChapterQuery);
$stmt->bind_param("iii", $chap_id, $position, $version);
$stmt->execute();
$subChapterResult = $stmt->get_result()->fetch_assoc();

// 섹션 정보 가져오기 (SUB_CHAP_ID는 위 쿼리에서 가져온 것 사용)
$sectionsQuery = "SELECT SEC_ID, SEC_NAME, SEC_DESC FROM SECTIONS 
                 WHERE SUB_CHAP_ID = ? AND VERSION = ?";
$stmt = $dbconnect->prepare($sectionsQuery);
$stmt->bind_param("ii", $subChapterResult['SUB_CHAP_ID'], $version);
$stmt->execute();
$sections = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 각 섹션에 대한 서브섹션 정보 가져오기
foreach ($sections as &$section) {
    $subsectionsQuery = "SELECT SUB_SEC_ID, SUB_SEC_NAME, SUB_SEC_CONTENT 
                        FROM SUBSECTIONS 
                        WHERE SEC_ID = ? AND VERSION = ?";
    $stmt = $dbconnect->prepare($subsectionsQuery);
    $stmt->bind_param("ii", $section['SEC_ID'], $version);
    $stmt->execute();
    $section['subsections'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// 버전 정보 가져오기
$versions_query = "
    SELECT DISTINCT VERSIONS AS version
    FROM SUBCHAPTERS
    WHERE CHAP_ID = ? AND POSITIONS = ?
    ORDER BY VERSIONS ASC
";
$stmt = $dbconnect->prepare($versions_query);
$stmt->bind_param("ii", $chap_id, $position);
$stmt->execute();
$versions_result = $stmt->get_result();

$valid_versions = [];
while ($version_row = $versions_result->fetch_assoc()) {
    $valid_versions[] = (int)$version_row['version'];
}

$response = [
    'chapter' => $chapterResult['CHAP_NAME'],
    'subChapter' => [
        'name' => $subChapterResult['SUB_CHAP_NAME'],
        'desc' => $subChapterResult['SUB_CHAP_DESC']
    ],
    'sections' => $sections,
    'versions' => $valid_versions
];

echo json_encode($response);