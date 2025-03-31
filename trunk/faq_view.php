<?php
require_once "faq_db.php";
require_once __DIR__ . '/auth/auth.php';
// $isAdmin = isAdminEquipment();
$isAdmin = true;

// 기본값 설정
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'view';
$current_chap_id = isset($_GET['chap_id']) ? intval($_GET['chap_id']) : 1;
$current_sub_chap_id = isset($_GET['sub_chap_id']) ? intval($_GET['sub_chap_id']) : 1;
$current_position = isset($_GET['position']) ? intval($_GET['position']) : 0;

// 먼저 유효한 버전들을 가져옴
$versions_query = "
    SELECT DISTINCT VERSIONS AS version
    FROM SUBCHAPTERS
    WHERE CHAP_ID = ? AND POSITIONS = ?
    ORDER BY VERSIONS ASC
";
$stmt = $dbconnect->prepare($versions_query);
$stmt->bind_param("ii", $current_chap_id, $current_position);
$stmt->execute();
$versions_result = $stmt->get_result();

$valid_versions = [];
while ($version_row = $versions_result->fetch_assoc()) {
    $valid_versions[] = $version_row['version'];
}

// 이렇게 수정
$current_version = isset($_GET['version']) ? intval($_GET['version']) : $valid_versions[0];

// 디버깅 정보를 페이지 상단에 추가
echo "<!-- Debug Info:\n";
echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "GET params: " . print_r($_GET, true) . "\n";
echo "Current Version (before processing): " . $current_version . "\n";
echo "Valid Versions: " . implode(", ", $valid_versions) . "\n";
echo "-->\n";

// 유효성 검사 추가
if (!in_array($current_version, $valid_versions)) {
    die("유효하지 않은 버전입니다.");
}

// 1. 서브챕터 정보 확인
$query = "
    SELECT sc.*, c.CHAP_NAME
    FROM SUBCHAPTERS sc
    JOIN CHAPTERS c ON sc.CHAP_ID = c.CHAP_ID
    WHERE sc.SUB_CHAP_ID = ?
";
$stmt = $dbconnect->prepare($query);
$stmt->bind_param("i", $current_sub_chap_id);
$stmt->execute();
$current_subchapter = $stmt->get_result()->fetch_assoc();

if (!$current_subchapter) {
    die("존재하지 않는 서브챕터입니다.");
}

// 3. 섹션 및 서브섹션 정보 가져오기 (버전에 따라 제한)
$query = "
    SELECT s.*
    FROM SECTIONS s
    WHERE s.SUB_CHAP_ID = ? AND s.VERSION = ?
";
$stmt = $dbconnect->prepare($query);
$stmt->bind_param("ii", $current_sub_chap_id, $current_version);
$stmt->execute();
$sections = $stmt->get_result();

$sections_data = [];
while ($section = $sections->fetch_assoc()) {
    $section_id = $section['SEC_ID'];
    $subsections_query = "
        SELECT *
        FROM SUBSECTIONS
        WHERE SEC_ID = ? AND VERSION = ?
    ";
    $sub_stmt = $dbconnect->prepare($subsections_query);
    $sub_stmt->bind_param("ii", $section_id, $current_version);
    $sub_stmt->execute();
    $subsections = $sub_stmt->get_result();

    $subsections_data = [];
    while ($subsection = $subsections->fetch_assoc()) {
        $subsections_data[] = $subsection;
    }

    $section['subsections'] = $subsections_data;
    $sections_data[] = $section;
}

// 버전 매핑을 위한 함수
function createVersionMapping($db_versions) {
    $mapping = [];
    $ui_version = 1;  // UI에 보여줄 버전 번호는 항상 1부터 시작

    // DB 버전을 순서대로 정렬
    sort($db_versions);

    // 각 DB 버전에 대해 UI 버전 매핑
    foreach ($db_versions as $db_version) {
        $mapping[$ui_version] = $db_version;
        $ui_version++;
    }

    return $mapping;
}

// 역매핑 함수 (DB 버전 -> UI 버전)
function getUIVersion($db_version, $version_mapping) {
    return array_search($db_version, $version_mapping);
}

// 버전 매핑 생성
$version_mapping = createVersionMapping($valid_versions);

// 페이지네이션에서 사용
foreach ($version_mapping as $ui_ver => $db_ver) {
    echo "<a href='...&version={$db_ver}'>#{$ui_ver}</a>";
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title><?php echo $current_subchapter['SUB_CHAP_NAME']; ?> - FAQ</title>
    <link rel="stylesheet" href="faq.css">
</head>
<body>
<?php include 'nav.php'; ?>
    <div class="view-container">
        <h1><?php echo $current_subchapter['SUB_CHAP_NAME']; ?> (#<?php echo $current_version; ?>)</h1>

        <!-- 섹션 출력 -->
        <?php foreach ($sections_data as $section): ?>
            <div class="section">
                <h3 class="section-title">섹션: <?php echo $section['SEC_NAME']; ?></h3>
                <p><?php echo $section['SEC_DESC']; ?></p>

                <!-- 서브섹션 출력 -->
                <?php if (!empty($section['subsections'])): ?>
                    <?php foreach ($section['subsections'] as $subsection): ?>
                        <div class="sub-section">
                            <h5 class="subsection-title">서브섹션: <?php echo $subsection['SUB_SEC_NAME']; ?></h5>
                            <div class="content">
                                <?php echo $subsection['SUB_SEC_CONTENT']; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

         <!-- 서브챕터 설명 -->
        <?php if (!empty($current_subchapter['SUB_CHAP_DESC'])): ?>
        <div class="subchapter-description">
            <h3>서브챕터 설명</h3>
            <div class="description-content">
                <?php echo $current_subchapter['SUB_CHAP_DESC']; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 페이지네이션 -->
        <div class="pagination">
            <?php foreach ($version_mapping as $ui_ver => $db_ver): ?>
                <a href="?mode=view&chap_id=<?php echo $current_chap_id; ?>&sub_chap_id=<?php echo $current_sub_chap_id; ?>&position=<?php echo $current_position; ?>&version=<?php echo $db_ver; ?>"
                class="page-number <?php echo ($db_ver == $current_version) ? 'active' : ''; ?>">
                    #<?php echo $ui_ver; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- 수정/삭제 버튼 (관리자만 보임) -->
        <?php if ($isAdmin): ?>
        <div class="edit-button-container">
            <!-- 현재 버전 수정 -->
            <form action="faq_form.php" method="GET" style="margin-right: 10px;" onsubmit="return confirm('수정하시겠습니까?');">
                <input type="hidden" name="mode" value="edit_current">
                <input type="hidden" name="chap_id" value="<?php echo $current_chap_id; ?>">
                <input type="hidden" name="sub_chap_id" value="<?php echo $current_sub_chap_id; ?>">
                <input type="hidden" name="position" value="<?php echo $current_position; ?>">
                <input type="hidden" name="version" value="<?php echo $current_version; ?>">
                <button type="submit" class="edit-current-button">수정</button>
            </form>

            <!-- 새 버전 생성 -->
            <form action="faq_form.php" method="GET" style="margin-right: 10px;" onsubmit="return confirm('새로운 버전을 생성하시겠습니까?');">
                <input type="hidden" name="mode" value="edit_new">
                <input type="hidden" name="chap_id" value="<?php echo $current_chap_id; ?>">
                <input type="hidden" name="sub_chap_id" value="<?php echo $current_sub_chap_id; ?>">
                <input type="hidden" name="position" value="<?php echo $current_position; ?>">
                <input type="hidden" name="version" value="<?php echo $current_version; ?>">
                <button type="submit" class="edit-new-button">복제</button>
            </form>

            <!-- 삭제 버튼 -->
            <form action="faq_delete.php" method="POST" onsubmit="return confirm('삭제하시겠습니까?');">
                <input type="hidden" name="chap_id" value="<?php echo $current_chap_id; ?>">
                <input type="hidden" name="sub_chap_id" value="<?php echo $current_sub_chap_id; ?>">
                <input type="hidden" name="position" value="<?php echo $current_position; ?>">
                <input type="hidden" name="version" value="<?php echo $current_version; ?>">
                <button type="submit" class="delete-button">삭제</button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- 디버깅 정보 -->
    <div style="display:none">
        <?php
        echo "current_version: " . $current_version . "<br>";
        echo "valid_versions: " . implode(", ", $valid_versions) . "<br>";
        echo "array_search result: " . array_search($current_version, $valid_versions) . "<br>";
        echo "실제 전달될 버전값: " . $valid_versions[array_search($current_version, $valid_versions)] . "<br>";
        ?>
    </div>
</body>
</html>
