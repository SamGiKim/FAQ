<?php
$hostname = "localhost";
$username = "root";
$password = "coffee";
$db = "faq";

$dbconnect = mysqli_connect($hostname, $username, $password, $db);

if (!$dbconnect) {
    die("QnA DB 연결 실패: " . mysqli_connect_error());
}else{
    //echo "QnA DB 연결 성공";
}

mysqli_set_charset($dbconnect, "utf8");

// 데이터베이스 연결 확인을 위한 코드 추가
if ($dbconnect->connect_error) {
    die("데이터베이스 연결 실패: " . $dbconnect->connect_error);
}

?>
