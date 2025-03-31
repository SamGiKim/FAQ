<?php
// faq_content.php
require_once "faq_db.php";
require_once __DIR__ . '/auth/auth.php';

// 에러 디버깅 설정
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (isset($_SESSION['success_message'])) {
    echo "<script>
        alert('" . htmlspecialchars($_SESSION['success_message']) . "');
    </script>";
    unset($_SESSION['success_message']); // 메시지 표시 후 삭제
}

// CHAPTERS 테이블에서 모든 챕터를 가져오기
$chaptersQuery = "SELECT * FROM CHAPTERS";
$chaptersResult = $dbconnect->query($chaptersQuery);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>FAQ 목록</title>
    <link rel="stylesheet" href="faq.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php include 'nav.php'; ?>
    <h1>FAQ 목록</h1>

    <?php if ($chaptersResult->num_rows > 0) : ?>
        <div id="faq-list">
        <?php while ($chapter = $chaptersResult->fetch_assoc()) : ?>
            <div class="chapter">
                <h2><?php echo htmlspecialchars($chapter['CHAP_NAME']); ?></h2>
                <?php
                // 동일 CHAP_ID와 POSITIONS의 SUBCHAPTERS 가져오기
                $subChaptersQuery = "
                SELECT 
                    s1.SUB_CHAP_NAME,
                    s1.POSITIONS,
                    (
                        SELECT MAX(VERSIONS)
                        FROM SUBCHAPTERS s2
                        WHERE s2.CHAP_ID = s1.CHAP_ID
                        AND s2.POSITIONS = s1.POSITIONS
                    ) as MAX_VERSION
                FROM SUBCHAPTERS s1
                WHERE s1.CHAP_ID = {$chapter['CHAP_ID']}
                GROUP BY s1.POSITIONS, s1.SUB_CHAP_NAME
                ORDER BY s1.POSITIONS ASC
            ";
                $subChaptersResult = $dbconnect->query($subChaptersQuery);
                ?>
                <?php if ($subChaptersResult->num_rows > 0) : ?>
                    <ul>
                        <?php while ($subChapter = $subChaptersResult->fetch_assoc()) : ?>
                            <li>
                                <a href="faq_view.php?mode=view&chap_id=<?php echo $chapter['CHAP_ID']; ?>&sub_chap_id=<?php echo $subChapter['SUB_CHAP_ID']; ?>&position=<?php echo $subChapter['POSITIONS']; ?>&version=1">
                                    <?php echo htmlspecialchars($subChapter['SUB_CHAP_NAME']); ?>
                                </a>

                                <?php if ($subChapter['MAX_VERSION'] > 1): ?>
                                    <span class="versions">
                                        (
                                        <?php 
                                        $isFirst = true;
                                        for ($i = 1; $i <= $subChapter['MAX_VERSION']; $i++): 
                                            // 해당 버전이 실제로 존재하는지 확인
                                            $checkVersionQuery = "SELECT 1 FROM SUBCHAPTERS 
                                                WHERE CHAP_ID = {$chapter['CHAP_ID']} 
                                                AND POSITIONS = {$subChapter['POSITIONS']} 
                                                AND VERSIONS = {$i}
                                                LIMIT 1";
                                            $versionExists = $dbconnect->query($checkVersionQuery)->num_rows > 0;
                                            
                                            if ($versionExists):
                                                if (!$isFirst) {
                                                    echo "&nbsp;";
                                                }
                                                $isFirst = false;
                                        ?>
                                                <a href="faq_view.php?mode=view&chap_id=<?php echo $chapter['CHAP_ID']; ?>&position=<?php echo $subChapter['POSITIONS']; ?>&version=<?php echo $i; ?>">
                                                    #<?php echo $i; ?>
                                                </a>
                                        <?php 
                                            endif;
                                        endfor; 
                                        ?>
                                        )
                                    </span>
                                <?php endif; ?>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else : ?>
                    <p>서브챕터가 없습니다.</p>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
        </div>
    <?php else : ?>
        <p>FAQ 항목이 없습니다.</p>
    <?php endif; ?>
</body>
</html> 