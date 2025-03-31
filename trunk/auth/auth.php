<?php
function isAdminEquipment() {
    // 관리자 IP 목록
    $adminIPs = array(
        '192.168.100.111',  // 관리자 IP
        '192.168.100.159', 
        '192.168.100.1' 
    );
    
    $clientIP = $_SERVER['REMOTE_ADDR'];
    return in_array($clientIP, $adminIPs);
}

function enforceAdminPrivileges() {
    if (!isAdminEquipment()) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(['error' => '권한이 없습니다.']);
        exit();
    }
}
?>