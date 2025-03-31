<?php
if ($_FILES['file']['name']) {
    if (!is_dir('uploads')) {
        mkdir('uploads', 0777, true);
    }
    
    $filename = uniqid() . '_' . $_FILES['file']['name'];
    $filepath = 'uploads/' . $filename;
    
    // 파일 크기 제한 (예: 5MB)
    if ($_FILES['file']['size'] > 5000000) {
        echo json_encode(['error' => '파일 크기는 5MB를 초과할 수 없습니다.']);
        exit;
    }
    
    // 이미지 파일 타입 확인
    $allowed = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($_FILES['file']['type'], $allowed)) {
        echo json_encode(['error' => '허용되지 않는 파일 형식입니다.']);
        exit;
    }
    
    // 이미지 최적화 코드 추가
    if (in_array($_FILES['file']['type'], ['image/jpeg', 'image/png'])) {
        $image = null;
        if ($_FILES['file']['type'] == 'image/jpeg') {
            $image = imagecreatefromjpeg($_FILES['file']['tmp_name']);
        } else {
            $image = imagecreatefrompng($_FILES['file']['tmp_name']);
        }
        
        // 이미지 크기 조정 (최대 너비 1024px)
        $maxWidth = 1024;
        $width = imagesx($image);
        $height = imagesy($image);
        
        if ($width > $maxWidth) {
            $newHeight = ($height / $width) * $maxWidth;
            $tmp = imagecreatetruecolor($maxWidth, $newHeight);
            imagecopyresampled($tmp, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
            $image = $tmp;
        }
        
        // 품질 80%로 저장
        imagejpeg($image, $filepath, 80);
        imagedestroy($image);
    } else {
        move_uploaded_file($_FILES['file']['tmp_name'], $filepath);
    }
} 