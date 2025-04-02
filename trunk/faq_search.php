<?php
require_once "faq_db.php";  

mysqli_set_charset($dbconnect, "utf8");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $keyword = isset($_POST["keyword"]) ? trim($_POST["keyword"]) : "";

    // 검색어가 비어 있으면
    if (strlen($keyword) === 0) {
        echo json_encode(["results" => [], "error" => "검색어를 입력하세요."]);
        exit;
    }

    $likeKeyword = "%" . $keyword . "%";
    $results = [];

    $query = "
        SELECT 
            c.CHAP_ID, 
            c.CHAP_NAME, 
            s.SUB_CHAP_ID, 
            s.SUB_CHAP_NAME, 
            s.POSITIONS AS POSITION,
            s.VERSIONS AS VERSION,
            sec.SEC_ID, 
            sec.SEC_NAME, 
            subsec.SUB_SEC_NAME
        FROM CHAPTERS c
        LEFT JOIN SUBCHAPTERS s ON c.CHAP_ID = s.CHAP_ID
        LEFT JOIN SECTIONS sec ON s.SUB_CHAP_ID = sec.SUB_CHAP_ID
        LEFT JOIN SUBSECTIONS subsec ON sec.SEC_ID = subsec.SEC_ID
        WHERE 
            s.SUB_CHAP_NAME LIKE ? 
            OR sec.SEC_NAME LIKE ? 
            OR subsec.SUB_SEC_NAME LIKE ?
    ";

    $stmt = $dbconnect->prepare($query);
    $stmt->bind_param("sss", $likeKeyword, $likeKeyword, $likeKeyword);
    $stmt->execute();
    $result = $stmt->get_result();

    // 결과 배열에 섹션을 그룹화하여 저장
    $groupedResults = [];
    
    while ($row = $result->fetch_assoc()) {
        $secName = $row["SEC_NAME"];

        // 섹션이 이미 그룹에 존재하면 서브섹션 추가
        if (!isset($groupedResults[$secName])) {
            $groupedResults[$secName] = [
                "CHAP_ID" => $row["CHAP_ID"],  
                "CHAP_NAME" => $row["CHAP_NAME"],
                "SUB_CHAP_ID" => $row["SUB_CHAP_ID"],  
                "SUB_CHAP_NAME" => $row["SUB_CHAP_NAME"],
                "POSITION" => $row["POSITION"],  
                "VERSION" => $row["VERSION"],  
                "SEC_NAME" => $secName,
                "SUB_SECTIONS" => []
            ];
        }

        // 서브섹션 추가
        if (!empty($row["SUB_SEC_NAME"])) {
            $groupedResults[$secName]["SUB_SECTIONS"][] = $row["SUB_SEC_NAME"];
        }
    }

    // 최종 결과 배열 생성
    foreach ($groupedResults as $sec) {
        $results[] = [
            "CHAP_ID" => $sec["CHAP_ID"],  
            "CHAP_NAME" => $sec["CHAP_NAME"],
            "SUB_CHAP_ID" => $sec["SUB_CHAP_ID"],  
            "SUB_CHAP_NAME" => $sec["SUB_CHAP_NAME"],
            "POSITION" => $sec["POSITION"],  
            "VERSION" => $sec["VERSION"],  
            "SEC_NAME" => $sec["SEC_NAME"],
            "SUB_SEC_NAMES" => $sec["SUB_SECTIONS"]
        ];
    }

    // JSON 반환
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["results" => $results], JSON_UNESCAPED_UNICODE);
}
?>
