<?php
// faq_form.php

// 에러 로깅 설정
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "faq_db.php";
require_once "debug.php"; 

// 기본값 설정
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'create';  // Only 'create' or 'edit_current'
$current_chap_id = isset($_GET['chap_id']) ? intval($_GET['chap_id']) : null;
$current_position = isset($_GET['position']) ? intval($_GET['position']) : null;
$current_version = isset($_GET['version']) ? intval($_GET['version']) : null;

// SDP - Add version validation right after the basic settings
if ($mode === 'edit_current') {
    // Get all valid versions for this subchapter
    $versions_query = "
        SELECT DISTINCT VERSIONS
        FROM SUBCHAPTERS
        WHERE CHAP_ID = ? AND POSITIONS =?
        ORDER BY VERSIONS ASC";

    $stmt = $dbconnect->prepare($versions_query);
    $stmt->bind_param("ii", $current_chap_id, $current_position);
    $stmt->execute();
    $versions_result = $stmt->get_result();

    $valid_versions = [];
    while ($row = $versions_result->fetch_assoc()) {
        $valid_versions[] = $row['VERSIONS'];
    }

    // Validate that current_version exists in valid_versions
    if (!in_array($current_version, $valid_versions)) {
        die("Invalid version requested: {$current_version}. Valid versions are: " . implode(", ", $valid_versions));
    }
}

$edit_data = [];
$sections_data = [];

try {
    // 데이터베이스 연결 확인
    if (!$dbconnect) {
        throw new Exception("데이터베이스 연결 실패: " . mysqli_connect_error());
    }

    if ($mode === 'edit_current') {
        echo "<!-- Debug Info:\n";
        echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
        echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
        echo "GET params: " . print_r($_GET, true) . "\n";
        echo "Current Version: " . $current_version . "\n";
        echo "Valid Versions: " . implode(", ", $valid_versions) . "\n";
        echo "-->\n";

        // 버전 검증도 valid_versions 사용
        if (!in_array($current_version, $valid_versions)) {
            die("요청하신 버전이 존재하지 않습니다.<br>" .
                "- 요청한 버전: {$current_version}<br>" .
                "- 존재하는 버전: " . implode(", ", $valid_versions));
        }

        // 데이터 조회
        $check_query = "
            SELECT sc.*, c.CHAP_NAME
            FROM SUBCHAPTERS sc
            LEFT JOIN CHAPTERS c ON c.CHAP_ID = sc.CHAP_ID
            WHERE sc.CHAP_ID = ?
            AND sc.POSITIONS = ?
            AND sc.VERSIONS = ?";
        $stmt = $dbconnect->prepare($check_query);
        $stmt->bind_param("iii", $current_chap_id, $current_position, $current_version);
        $stmt->execute();
        $check_result = $stmt->get_result();

        if ($check_result->num_rows === 0) {
            die("데이터를 찾을 수 없습니다. (CHAP_ID: {$current_chap_id}, POSITION: {$current_position}, VERSION: {$current_version})");
        }

        $edit_data = $check_result->fetch_assoc();

        // 섹션 데이터 가져오기
        $sections_query = "
        SELECT
            s.SEC_ID,
            s.SEC_NAME,
            s.SEC_DESC
        FROM SECTIONS s
        WHERE s.CHAP_ID = ?
            AND s.SUB_CHAP_ID = (
                SELECT SUB_CHAP_ID
                FROM SUBCHAPTERS
                WHERE CHAP_ID = ?
                AND POSITIONS = ?
                AND VERSIONS = ?
            )
            AND s.VERSION = ?
        ORDER BY s.SEC_ID";

        $stmt = $dbconnect->prepare($sections_query);
        $stmt->bind_param("iiiii",
            $current_chap_id,
            $current_chap_id,
            $current_position,
            $current_version,
            $current_version
        );
        $stmt->execute();
        $sections_result = $stmt->get_result();

        while ($section = $sections_result->fetch_assoc()) {
            // 3. 각 섹션에 대한 서브섹션 데이터 가져오기
            $subsections_query = "
                SELECT
                    SUB_SEC_ID,
                    SUB_SEC_NAME,
                    SUB_SEC_CONTENT
                FROM SUBSECTIONS
                WHERE SEC_ID = ?
                AND VERSION = ?";

            $sub_stmt = $dbconnect->prepare($subsections_query);
            $sub_stmt->bind_param("ii", $section['SEC_ID'], $current_version);
            $sub_stmt->execute();
            $subsections_result = $sub_stmt->get_result();

            $section['subsections'] = [];
            while ($subsection = $subsections_result->fetch_assoc()) {
                $section['subsections'][] = $subsection;
            }

            $sections_data[] = $section;
        }
    }
} catch (Exception $e) {
    error_log("Error in faq_form.php: " . $e->getMessage());
    die("오류가 발생했습니다: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DB input form</title>
    <link rel="stylesheet" href="faq.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script type="module" src="faq.js"></script>
    <!-- SunEditor -->
    <link href="https://cdn.jsdelivr.net/npm/suneditor@latest/dist/css/suneditor.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/suneditor@latest/dist/suneditor.min.js"></script>
    <!-- <script src="editor/faq_editor.js"></script>-->
     <script type="module" src="./editor/faq_editor.js"></script>
    <!-- /SunEditor -->
</head>

<?php
// 섹션 데이터 디버깅
// echo "<!-- DEBUG START: SECTIONS DATA -->\n";
// echo "<pre>";
// echo htmlspecialchars(print_r($sections_data, true)); // HTML에서 보이는 방식으로 데이터 출력
// echo "</pre>";
// echo "<!-- DEBUG END: SECTIONS DATA -->\n";
?>

<body>
    <?php include 'nav.php'; ?>
    <div class="input-form">
    <h1><?php
    switch($mode) {
            case 'edit_current':
                echo 'FAQ 현재 버전 수정';
                break;
            case 'create':
                echo 'FAQ 작성';
                break;
            default:
                echo 'FAQ 입력';
        }
    ?></h1>
        <form action="<?php echo ($mode === 'edit_current') ? 'faq_update.php' : 'faq_submit.php'; ?>" method="POST">
            <!-- Add mode as hidden input for edit_current -->
            <?php if ($mode === 'edit_current'): ?>
                <input type="hidden" name="mode" value="edit_current">
                <input type="hidden" name="sub_chap_id" value="<?php echo $edit_data['SUB_CHAP_ID']; ?>">
            <?php endif; ?>

      <!-- Chapter -->
    <label for="chapter">챕터<span>Chapter</span></label>
    <?php if ($mode === 'edit_current'): ?>
        <!-- 현재 버전 수정시: 읽기 전용 -->
        <input type="text"
            id="chapter"
            name="chapter_name"
            value="<?php echo htmlspecialchars($edit_data['CHAP_NAME']); ?>"
            readonly
            class="edit-field readonly">
        <input type="hidden" name="chapter_id" value="<?php echo $edit_data['CHAP_ID']; ?>">
    <?php else: ?>
        <!-- create mode -->
        <select id="chapter" name="chapter">
            <option value="">챕터 선택...</option>
            <?php
            try {
                $query = "SELECT CHAP_ID, CHAP_NAME FROM CHAPTERS";
                $result = mysqli_query($dbconnect, $query);
                if (!$result) {
                    throw new Exception("쿼리 실행 실패: " . mysqli_error($dbconnect));
                }
            } catch (Exception $e) {
                error_log($e->getMessage());
                echo "데이터를 가져오는 중 오류가 발생했습니다.";
            }
            while ($row = mysqli_fetch_assoc($result)) {
                echo '<option value="' . $row['CHAP_ID'] . '">'
                    . htmlspecialchars($row['CHAP_NAME']) . '</option>';
            }
            ?>
            <option value="new-chapter" class="add-new">새 챕터 추가</option>
        </select>
    <?php endif; ?>
    <input type="text" id="new_chapter" name="new_chapter" class="add-field" style="display:none;" placeholder="새 챕터 이름">

    <!-- Sub Chapter -->
    <label for="sub_chapter">
        서브 챕터<span>Sub Chapter</span>
    </label>
    <?php if ($mode === 'edit_current'): ?>
            <!-- 현재 버전 수정시: 읽기 전용 -->
            <input type="text"
            id="sub_chapter"
            name="sub_chapter_name"
            value="<?php echo htmlspecialchars($edit_data['SUB_CHAP_NAME']); ?>"
            readonly
            class="edit-field readonly">
        <input type="hidden" name="position" value="<?php echo $edit_data['POSITIONS']; ?>">
    <?php else: ?>
        <!-- create mode -->
        <select id="sub_chapter" name="sub_chapter">
            <option value="">서브 챕터 선택...</option>
            <option value="new-subChapter" class="add-new">새 서브 챕터 추가</option>
        </select>
    <?php endif; ?>

    <input type="text"
        id="new_sub_chapter"
        name="new_sub_chapter"
        class="add-field"
        style="display:none;"
        placeholder="새 서브 챕터 이름">

    <!-- 버전 추가 옵션 (create mode와 edit_new에서만 표시) -->
    <?php if ($mode === 'create'): ?>
        <div class="version-option" style="display:none; margin-top: 10px;">
            <div class="version-checkbox-group">
                <input type="checkbox" id="has_version" name="has_version">
                <label for="has_version">서브챕터 #버전 추가</label>
                <span id="version_number_display" class="version-number"></span>
            </div>
            <input type="hidden" id="sub_chapter_number" name="sub_chapter_number">
        </div>
    <?php endif; ?>

<!-- Sections Container 시작 -->
<div id="sections-container">
    <?php if ($mode === 'edit_current'): ?>
        <?php foreach ($sections_data as $index => $section): ?>
            <div class="section-group">
                <!-- 섹션 -->
                <div class="section-header">
                    <label>섹션<span>Section</span></label>
                    <input type="text"
                        name="section_names[]"
                        value="<?php echo htmlspecialchars($section['SEC_NAME']); ?>"
                        class="edit-field">
                    <input type="hidden"
                        name="section_ids[]"
                        value="<?php echo $section['SEC_ID']; ?>">
                </div>

                <!-- 서브섹션 컨테이너 -->
                <div class="subsections-container">
                    <?php if (!empty($section['subsections'])): ?>
                        <?php foreach ($section['subsections'] as $subIndex => $subsection): ?>
                            <div class="subsection-group">
                                <div class="subsection-header">
                                    <label>서브섹션<span>Sub Section</span></label>
                                    <input type="text"
                                        name="subsection_names[<?php echo $index; ?>][]"
                                        value="<?php echo htmlspecialchars($subsection['SUB_SEC_NAME']); ?>"
                                        placeholder="서브섹션 이름">
                                    <input type="hidden"
                                        name="subsection_ids[<?php echo $index; ?>][]"
                                        value="<?php echo $subsection['SUB_SEC_ID']; ?>">
                                </div>

                                <div class="subsection-content">
                                    <label>서브섹션 내용<span>Sub Section Content</span></label>
                                    <textarea
                                        name="subsection_contents[<?php echo $index; ?>][]"
                                        class="sun-editor-editable"
                                        id="sun-editor-<?php echo $section['SEC_ID']; ?>-<?php echo $subsection['SUB_SEC_ID']; ?>">
                                        <?php echo htmlspecialchars($subsection['SUB_SEC_CONTENT']); ?>
                                    </textarea>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <button type="button" class="add-subsection-btn">+ 서브섹션 추가</button>
                </div>
            </div>
        <?php endforeach; ?>

        <?php else: ?>
            <!-- 새 FAQ 작성 시 기본 구조 -->
            <div class="section-group" data-section-index="0">
                <div class="section-header">
                    <div class="section-header-content">
                        <label>섹션<span>Section</span></label>
                        <input type="text" name="section_names[0]" placeholder="섹션 이름">
                        <button type="button" class="delete-section-btn" style="display: none;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <div class="subsections-container">
                    <div class="subsection-group" data-section-index="0" data-subsection-index="0">
                        <div class="subsection-header">
                            <label>서브섹션<span>Sub Section</span></label>
                            <input type="text" name="subsection_names[0][]" placeholder="서브섹션 이름">
                        </div>

                        <div class="subsection-content">
                            <label>서브섹션 내용<span>Sub Section Content</span></label>
                            <textarea name="subsection_contents[0][]" class="sun-editor-editable"></textarea>
                        </div>
                    </div>
                    <button type="button" class="add-subsection-btn">+ 서브섹션 추가</button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <!-- Sections Container 끝 -->

    <?php if ($mode === 'edit_current'): ?>
        <!-- Template for new section (hidden) -->
        <template id="new-section-template">
            <div class="section-group">
                <div class="section-header">
                    <label>섹션<span>Section</span></label>
                    <input type="text" name="section_names[]" placeholder="섹션 이름" class="edit-field">
                    <input type="hidden" name="section_ids[]" value="new">
                    <button type="button" class="remove-section-btn">섹션 삭제</button>
                </div>

                <div class="subsections-container">
                    <div class="subsection-controls">
                        <button type="button" class="add-subsection-btn">새 서브섹션 추가</button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Template for new subsection (hidden) -->
        <template id="new-subsection-template">
            <div class="subsection-group">
                <div class="subsection-header">
                    <label>서브섹션<span>Sub Section</span></label>
                    <input type="text" name="subsection_names[{SECTION_INDEX}][]" placeholder="서브섹션 이름">
                    <input type="hidden" name="subsection_ids[{SECTION_INDEX}][]" value="new">
                    <button type="button" class="remove-subsection-btn">서브섹션 삭제</button>
                </div>
                <div class="subsection-content">
                    <label>서브섹션 내용<span>Sub Section Content</span></label>
                    <textarea name="subsection_contents[{SECTION_INDEX}][]" class="sun-editor-editable"></textarea>
                </div>
            </div>
        </template>
    <?php endif; ?>

    <!-- Single add section button for all modes -->
    <button type="button" class="add-section-btn">+ 섹션 추가</button>

    <!-- 우측 박스(위지위그 편집기) Content -->
    <label for="subchapter_description">서브챕터 설명 <span>SubChapter Description</span></label>
    <textarea id="subchapter_description" name="subchapter_description"><?php
       if (($mode === 'edit_current') && $edit_data && isset($edit_data['SUB_CHAP_DESC'])) {
        echo htmlspecialchars($edit_data['SUB_CHAP_DESC']);
        }  else {
                echo '';
        }
    ?></textarea>

    <input type="submit" value="<?php
        echo ($mode === 'edit_current') ? '현재 버전 수정' : '등록';
    ?>" disabled style="background-color: #cccccc; cursor: not-allowed;">
    </form>
    </div>

    <!-- 섹션 템플릿 -->
    <template id="section-template">
        <div class="section-group" data-section-index="{index}">
            <div class="section-header">
                <input type="text" name="section_names[{index}]" placeholder="섹션 이름" required>
                <input type="text" name="section_descs[{index}]" placeholder="섹션 설명">
            </div>
            <div class="subsections-container">
                <div class="subsection-group" data-section-index="{index}" data-subsection-index="0">
                    <input type="text" name="subsection_names[{index}][]" placeholder="서브섹션 이름" required>
                    <div class="editor-container">
                        <textarea name="subsection_contents[{index}][]" class="editor" required></textarea>
                    </div>
                </div>
                <button type="button" class="add-subsection-btn">+ 서브섹션 추가</button>
            </div>
        </div>
    </template>

    <!-- JavaScript -->
    <script>
    function addNewSection() {
        const template = document.getElementById('section-template');
        const container = document.getElementById('sections-container');
        const sectionCount = container.children.length;

        // 템플릿 복제 및 인덱스 설정
        const newSection = template.content.cloneNode(true);
        newSection.querySelector('.section-container').dataset.sectionIndex = sectionCount;

        // name 속성 설정
        const inputs = newSection.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            const baseName = input.getAttribute('name').replace('[]', '');
            input.setAttribute('name', `${baseName}[${sectionCount}]`);
        });

        container.appendChild(newSection);

        // 에디터 초기화
        const newEditor = newSection.querySelector('.editor');
        if (newEditor) {
            initializeEditor(newEditor);
        }
    }
    </script>

</body>
</html>
