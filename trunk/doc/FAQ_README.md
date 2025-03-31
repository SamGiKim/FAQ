# FAQ 시스템

## 디렉토리 구조
├── **/home/nstek/h2_system/patch_active/FAQ**
├── index.html               # FAQ 메인 페이지
│- PHP와 HTML이 혼합된 메인 페이지
│- 주요 기능:
│    - FAQ 메뉴 구조 (FAQ/Menu 탭 시스템)
│    - 실시간 FAQ 콘텐츠 로딩 (loadFAQContent 함수)
│    - 관리자 전용 수정/삭제 버튼 관리
│    - 버전별 페이지네이션 UI
│- 특이사항:
│    - 동적으로 생성되는 hidden input 필드로 버전 관리
│    - 첫 번째 FAQ 항목 자동 로드 기능
│
├── nav.php                  # 네비게이션 컴포넌트
│- 공통 네비게이션 컴포넌트
│- 시스템 전역 메뉴 제공
│
├── faq.css                  # FAQ 스타일시트
│- 공통 스타일시트
│
├── faq.js                   # FAQ 핵심 관리 모듈
│   - FAQ 관리 시스템의 프론트엔드 핵심
│   - 주요 모듈:
│   - chapterManager: 챕터/서브챕터 선택 관리
│   - sectionManager: 섹션/서브섹션 동적 관리
│   - 이벤트 처리:
│   - 폼 초기화 및 검증
│   - 섹션/서브섹션 추가/삭제
│   - 에디터 초기화
│   
├── faq_form.php        # FAQ 폼 PHP
│    - FAQ 작성/수정 폼
│    - 다중 섹션/서브섹션 지원
│    - 버전 관리 인터페이스
│   
├── faq_content.js           # FAQ 콘텐츠 관리 모듈
│    - FAQ 콘텐츠 관리 핵심 스크립트
│    - 주요 함수:
│    - toggleInputFieldVisibility: 입력 필드 표시/숨김 처리
│    - handleChapterChange: 챕터 변경 시 서브챕터 동적 로드
│    - handleSubChapterChange: 서브챕터 변경 및 버전 옵션 처리
│    - loadFAQContent: AJAX 기반 FAQ 콘텐츠 로드
│    - 버전 관리:
│     - 버전별 UI 업데이트
│     - 페이지네이션 이벤트 처리
│     - 버전 전환 시 콘텐츠 동적 갱신
│
├── faq_submit.php      # FAQ 제출 
│    - FAQ 데이터 저장
│    - 새 버전 생성 처리
│
├── faq_delete.php      # FAQ 삭제 
│    - FAQ 삭제 처리
│    - 관련 이미지 정리
│
├── **데이터 관리**
├── faq_db.php          # DB 연결 및 공통 함수
├── faq_content.php     # FAQ 목록 관리
│  - FAQ 목록을 불러와서 표시
│  - 서브챕터 조회 및 관련 데이터 로드
│  - AJAX를 활용한 동적 콘텐츠 업데이트
│  - 서브챕터 버전 그룹화 쿼리
│  - 버전별 UI 생성
│  
├── faq_view.php        # FAQ 상세 조회
│  - 버전 유효성 검증, 버전별 데이터 처리
│  - createVersionMapping(): UI/DB 버전 매핑
│  - 섹션/서브섹션 계층 조회
│  
├── faq_update.php      # FAQ 업데이트 처리
│- 트랜잭션 기반 데이터 처리:
│  - 서브챕터 정보 업데이트
│  - 섹션/서브섹션 동시 업데이트
│- 버전 관리:
│  - 현재 버전 데이터만 수정
│  - 섹션/서브섹션 버전 동기화+
│
├── fetch_sections.php  # 섹션 정보 가져오는 PHP
│     - 섹션 데이터 조회 API
│     - AJAX 요청 처리
│  
├── fetch_subchapters.php # 하위 챕터 정보 가져오는 PHP
│    - 서브챕터 데이터 조회 API
│    - 버전별 데이터 처리
│  
├──  fetch_subsections.php # 하위 섹션 정보 가져오는 PHP
│    - 서브섹션 데이터 조회 API
│   
├──  get_faq_view.php    # FAQ 상세 내용 조회 API
│     - FAQ 상세 내용 조회 API
│     - 버전별 데이터 처리
│
├── **에디터**
├── editor/
│   ├── faq_editor.js   # SunEditor 설정
│   │    - 이미지 업로드 처리
│   │    - 다중 에디터 인스턴스
│   ├── img_upload.php  # 이미지 처리
│   │    - 파일 업로드 검증
│   │    - 자동 파일명 생성
│
├── **정적 자원**
├── css/                # 스타일시트 디렉토리
├── js/
│   ├── toggleDetails.js # 메뉴 토글 기능
│   ├── uploads/            # FAQ 이미지 저장소
│
├── **인증**
├── auth/
│   ├── auth.php        # 인증 및 권한 관리

## 시스템 개요
이 FAQ 시스템은 계층적 구조(챕터 > 서브챕터 > 섹션 > 서브섹션)로 구성된 FAQ를 관리하는 웹 애플리케이션입니다. PHP와 JavaScript를 사용하여 개발되었으며, 버전 관리 기능을 포함합니다.

## 주요 기능
- 계층적 FAQ 구조 관리
- FAQ 버전 관리 (여러 버전의 FAQ 유지)
- 실시간 콘텐츠 로딩 (AJAX)
- 관리자 전용 편집/삭제 기능
- 동적 메뉴 시스템 (FAQ/Menu 탭)

## 기술 스택
- 프론트엔드: 
  - HTML5
  - CSS3
  - JavaScript (ES6+)
  - SunEditor (리치 텍스트 에디터)
- 백엔드:
  - PHP 
  - MySQL
- 특징:
  - AJAX를 통한 비동기 데이터 로딩
  - 세션 기반 인증
  - 반응형 디자인

## 데이터베이스 구조
- CHAPTERS: 최상위 카테고리
- SUBCHAPTERS: 하위 카테고리 (버전 관리 포함)
- SECTIONS: FAQ 섹션
- SUBSECTIONS: FAQ 상세 내용

## 보안 기능
- 세션 기반 인증
- SQL 인젝션 방지
- XSS 방지를 위한 출력 이스케이프

## 주요 기능
- 계층적 FAQ 구조 관리
- 버전 관리 기능
- 실시간 콘텐츠 로딩
- 관리자 전용 편집/삭제 기능                  