<?php
require_once __DIR__ . '/auth/auth.php';

// 현재 페이지의 파일명 가져오기
$current_page = basename($_SERVER['PHP_SELF']);

// 관리자 권한 체크 및 디버깅
// $isAdmin = isAdminEquipment();
$isAdmin = true;
$clientIP = $_SERVER['REMOTE_ADDR'];

// 디버깅용 HTML 주석 (개발 완료 후 제거)
// echo "<!-- Current IP: " . $clientIP . " -->";
// echo "<!-- Is Admin: " . ($isAdmin ? 'true' : 'false') . " -->";
?>

<nav class="nav-container">
    <div class="nav-left">
        <!-- <a href="faq_content.php" class="home-button"> -->
        <a href="index.html" class="home-button">
            목차
        </a>
    </div>
    <div class="nav-right">
        <?php if ($current_page !== 'faq_form.php' && $isAdmin): ?>
            <a href="faq_form.php?mode=create" class="write-button">새글쓰기</a>
        <?php endif; ?>
        <!-- 디버깅용 정보 표시 (개발 완료 후 제거) -->
        <span style="font-size: 12px; color: #999;">
            IP: <?php echo $clientIP; ?> 
            (Admin: <?php echo $isAdmin ? 'Yes' : 'No'; ?>)
        </span>
    </div>
</nav>