# FAQ 시스템

## 디렉토리 구조
├── **/home/nstek/h2_system/patch_active/FAQ**
├── index.html # 메인 인덱스 HTML
├── FAQ_db.puml # DB 연결 및 공통 함수
├── auth
│   └── auth.php  # 인증 및 권한 관리
├── css
│   └── style.css # 스타일시티(by 이응석팀장)
├── editor
│   ├── faq_editor.js # SunEditor 설정
│   ├── img_upload.php # 이미지 처리
│   └── install.sh # 설치 스크립트
├── faq.css # FAQ 스타일시트
├── faq.js # FAQ 핵심 관리 모듈
├── faq_content.js # FAQ 콘텐츠 관리 모듈
├── faq_content.php # FAQ 목록 관리
├── faq_db.php # DB 연결 및 공통 함수
├── faq_delete.php # FAQ 삭제 처리
├── faq_form.php # FAQ 폼 관리
├── faq_img -> /var/faq_img # 이미지 저장소
├── faq_submit.php # FAQ 제출 처리
├── faq_update.php # FAQ 업데이트 처리
├── faq_view.php # FAQ 상세 조회
├── fetch_sections.php # 섹션 조회
├── fetch_subchapters.php # 서브챕터 조회
├── fetch_subsections.php # 서브섹션 조회
├── get_faq_view.php # FAQ 뷰 조회
├── img # 이미지 저장소

├── js # 자바스크립트 디렉토리
│   └── toggleDetails.js # 메뉴 토글 기능
├── nav.php # 네비게이션 컴포넌트
├── upload_image.php # 이미지 업로드 처리
└── uploads
