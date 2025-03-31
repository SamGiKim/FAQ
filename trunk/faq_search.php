<?php
require_once "faq_db.php";  

mysqli_set_charset($dbconnect, "utf8");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $keyword = $_POST["keyword"];

    // CHAPTERS와 SUBCHAPTERS에서 검색어를 포함한 항목을 찾기 위한 쿼리
    $chaptersQuery = "SELECT * FROM CHAPTERS WHERE CHAP_NAME LIKE ?";
    $stmt = $dbconnect->prepare($chaptersQuery);
    $likeKeyword = "%" . $keyword . "%";
    $stmt->bind_param("s", $likeKeyword);
    $stmt->execute();
    $chaptersResult = $stmt->get_result();

    $results = [];
    while ($chapter = $chaptersResult->fetch_assoc()) {
        // 해당 챕터의 서브챕터를 찾기 위한 쿼리
        $subChaptersQuery = "
            SELECT
                MIN(SUB_CHAP_ID) AS SUB_CHAP_ID,
                SUB_CHAP_NAME,
                POSITIONS,
                GROUP_CONCAT(VERSIONS ORDER BY VERSIONS ASC) AS VERSIONS_GROUP
            FROM SUBCHAPTERS
            WHERE CHAP_ID = {$chapter['CHAP_ID']} AND (SUB_CHAP_NAME LIKE ? OR SUB_CHAP_DESC LIKE ?)
            GROUP BY POSITIONS, SUB_CHAP_NAME
            ORDER BY POSITIONS ASC
        ";
        $stmt = $dbconnect->prepare($subChaptersQuery);
        $stmt->bind_param("ss", $likeKeyword, $likeKeyword);
        $stmt->execute();
        $subChaptersResult = $stmt->get_result();

        while ($subChapter = $subChaptersResult->fetch_assoc()) {
            $results[] = [
                'name' => $subChapter['SUB_CHAP_NAME'],
                'desc' => $subChapter['SUB_CHAP_DESC'],
                'chap_id' => $chapter['CHAP_ID'],
                'sub_chap_id' => $subChapter['SUB_CHAP_ID'],
                'positions' => $subChapter['POSITIONS'],
                'versions_group' => $subChapter['VERSIONS_GROUP']
            ];
        }
    }

    // 결과를 JSON 형식으로 반환
    echo json_encode($results);
}
?>
