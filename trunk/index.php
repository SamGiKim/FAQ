<?php
require_once "faq_db.php";
require_once __DIR__ . '/auth/auth.php';

// 에러 디버깅 설정
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <script src="js/toggleDetails.js" defer></script>
    <script type="module" src="faq.js" defer></script>
    <script>
    function loadFAQContent(chapId, subChapId, position, version) {
        fetch(`get_faq_view.php?chap_id=${chapId}&sub_chap_id=${subChapId}&position=${position}&version=${version}`)
            .then(response => response.json())
            .then(data => {
                // 관리자 버튼들의 hidden input 값 설정
                ['current', 'new', 'delete'].forEach(prefix => {
                    const element = document.getElementById(`${prefix}_chap_id`);
                    if (element) {  // Check if element exists before setting value
                        element.value = chapId;
                    }
                    const subElement = document.getElementById(`${prefix}_sub_chap_id`);
                    if (subElement) {
                        subElement.value = subChapId;
                    }
                    const posElement = document.getElementById(`${prefix}_position`);
                    if (posElement) {
                        posElement.value = position;
                    }
                    const verElement = document.getElementById(`${prefix}_version`);
                    if (verElement) {
                        verElement.value = version;
                    }
                });

                // 데이터 확인을 위한 로그
                console.log('Received data:', data);

                const mainContent = document.querySelector('.main-frame main');
                const reference = document.querySelector('.reference');

                // 메인 콘텐츠 구성
                let mainHTML = `
                    <h1>${data.chapter}</h1>
                    <h2>${data.subChapter.name}</h2>
                `;

                // 섹션 데이터 확인
                console.log('Sections:', data.sections);

                // 섹션들 추가
                data.sections.forEach(section => {
                    console.log('Processing section:', section);
                    mainHTML += `
                        <div class="section">
                            <h3 class="section-title">섹션: ${section.SEC_NAME}</h3>
                            <div class="section-content">${section.SEC_DESC || ''}</div>

                            <!-- 서브섹션 출력 -->
                            ${section.subsections ? section.subsections.map(subsection => {
                                console.log('Processing subsection:', subsection);
                                return `
                                    <div class="sub-section">
                                        <h5 class="subsection-title">서브섹션: ${subsection.SUB_SEC_NAME}</h5>
                                        <div class="content">
                                            ${subsection.SUB_SEC_CONTENT || ''}
                                        </div>
                                    </div>
                                `;
                            }).join('') : ''}
                        </div>
                    `;
                });

                mainContent.innerHTML = mainHTML;

                // reference 섹션 업데이트
                reference.innerHTML = `
                    <section>
                        <h3>${data.subChapter.name}</h3>
                        <div class="description-content">
                            ${data.subChapter.desc || ''}
                        </div>
                    </section>
                `;

                // 페이지네이션 업데이트
                const pagination = document.querySelector('.pagenation ul');
                if (data.versions && data.versions.length > 0) {
                    let paginationHTML = `
                        <li><a href="#" class="pagination-link" data-version="${data.versions[0]}">&laquo;</a></li>
                        <li><a href="#" class="pagination-link" data-version="${version > 1 ? data.versions[Math.max(0, data.versions.indexOf(parseInt(version)) - 1)] : data.versions[0]}">&lsaquo;</a></li>
                    `;

                    // 버전 번호 생성
                    data.versions.forEach((ver, index) => {
                        paginationHTML += `
                            <li>
                                <a href="#"
                                    class="pagination-link ${ver == version ? 'active' : ''}"
                                    data-version="${ver}"
                                >${index + 1}</a>
                            </li>
                        `;
                    });

                    paginationHTML += `
                        <li><a href="#" class="pagination-link" data-version="${version < data.versions.length ? data.versions[Math.min(data.versions.length - 1, data.versions.indexOf(parseInt(version)) + 1)] : data.versions[data.versions.length - 1]}">&rsaquo;</a></li>
                        <li><a href="#" class="pagination-link" data-version="${data.versions[data.versions.length - 1]}">&raquo;</a></li>
                    `;

                    pagination.innerHTML = paginationHTML;

                    // 페이지네이션 클릭 이벤트 추가
                    document.querySelectorAll('.pagination-link').forEach(link => {
                        link.addEventListener('click', (e) => {
                            e.preventDefault();
                            const newVersion = e.target.dataset.version;
                            loadFAQContent(chapId, subChapId, position, newVersion);
                        });
                    });
                }
            })
            .catch(error => console.error('Error:', error));
    }

    // FAQ 링크들에 이벤트 리스너 추가
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('#first-group a').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const url = new URL(e.target.href);
                const params = new URLSearchParams(url.search);

                loadFAQContent(
                    params.get('chap_id'),
                    params.get('sub_chap_id'),
                    params.get('position'),
                    params.get('version')
                );
            });
        });

        // 페이지 로드 시 첫 번째 FAQ 내용 자동 로드
        const firstFAQLink = document.querySelector('#first-group a');
        if (firstFAQLink) {
            const url = new URL(firstFAQLink.href);
            const params = new URLSearchParams(url.search);

            loadFAQContent(
                params.get('chap_id'),
                params.get('sub_chap_id'),
                params.get('position'),
                params.get('version')
            );
        }

        // 검색
        const searchForm = document.querySelector('#search-form'); // 검색 폼
        const searchResultsContainer = document.querySelector('.scrollmini'); // 기존 FAQ 내용을 지우고 검색 결과 표시

        searchForm.addEventListener('submit', (e) => {
            e.preventDefault(); // 폼 제출 기본 동작 막기

            const formData = new FormData(searchForm); // 폼 데이터 추출

            fetch('faq_search.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // 기존 내용 삭제 대신 초기화 (기존 FAQ는 유지)
                searchResultsContainer.innerHTML = '';

                if (data.length > 0) {
                    let resultHTML = '<h2>검색 결과</h2>';
                    data.forEach(result => {
                        resultHTML += `
                            <div class="result-item">
                                <h3>${result.name}</h3>
                                <p>${result.desc}</p>
                            </div>
                        `;
                    });
                    searchResultsContainer.innerHTML = resultHTML;
                } else {
                    alert("검색 결과가 없습니다.");
                }
            })
            .catch(error => console.error('검색 오류:', error));
        });
    });
    </script>
    <title>Help</title>
</head>
<body>
    <!-- Side menu -->
    <div class="nav-frame">
        <div class="main-frame">
        <!-- Chapter -->
        <article class="chapter">
            <main class="scrollmini">
            <!-- 여기에 동적 콘텐츠가 로드됩니다 -->
            </main>

            <!-- 수정/삭제 버튼 (관리자만 보임) -->
            <div class="edit-button-container">
                <!-- 현재 버전 수정 -->
                <form action="faq_form.php" method="GET" style="margin-right: 10px;" onsubmit="return confirm('현재 버전을 수정하시겠습니까?');">
                    <input type="hidden" name="mode" value="edit_current">
                    <input type="hidden" name="chap_id" id="current_chap_id">
                    <input type="hidden" name="sub_chap_id" id="current_sub_chap_id">
                    <input type="hidden" name="position" id="current_position">
                    <input type="hidden" name="version" id="current_version">
                    <button type="submit" class="edit-current-button">현재 버전 수정</button>
                </form>

                <!-- 삭제 버튼 -->
                <form action="faq_delete.php" method="POST" onsubmit="return confirm('삭제하시겠습니까?');">
                    <input type="hidden" name="chap_id" id="delete_chap_id">
                    <input type="hidden" name="sub_chap_id" id="delete_sub_chap_id">
                    <input type="hidden" name="position" id="delete_position">
                    <input type="hidden" name="version" id="delete_version">
                    <button type="submit" class="delete-button">삭제</button>
                </form>
            </div>

            <nav class="pagenation">
            <ul>
                <li><a href="first.html">‹</a></li>
                <li><a href="before.html">«</a></li>
                <li><a href="1.html" class="active">1</a></li>
                <li><a href="2.html">2</a></li>
                <li><a href="3.html">3</a></li>
                <li><a href="4.html">4</a></li>
                <li><a href="5.html">5</a></li>
                <li><a href="6.html">6</a></li>
                <li><a href="7.html">7</a></li>
                <li><a href="8.html">8</a></li>
                <li><a href="9.html">9</a></li>
                <li><a href="after.html">›</a></li>
                <li><a href="last.html">»</a></li>
            </ul>
            </nav>
        </article>

        <aside class="reference">
            <!-- 여기에 동적 reference 콘텐츠가 로드됩니다 -->
        </aside>
        </div>

        <nav class="help-menu">
        <div class="menu-header">
            <a href="faq_form.php?mode=create" class="write-button">새글쓰기</a>
            <button class="close" title="닫기">X</button>
        </div>

        <div class="select-menu">
            <input id="first-group-check" class="input-check" type="radio" name="switch"  checked />
            <label class="tabs line" for="first-group-check">FAQ</label>
            <input id="second-group-check" class="input-check" type="radio" name="switch" /><label class="tabs" for="second-group-check">Menu</label>

            <div class="inner-width">
                <form action="faq_search.php" id="search-form" method="POST">
                    <div>
                        <input type="text" id="search-input" name="keyword" placeholder="Search" required>  
                        <button type="submit" id="search-button" class="btn search-icon" title="검색"></button>
                        <input type="checkbox" id="toggle-check" onchange="toggleDetails()">
                        <label class="btn full" for="toggle-check"></label>
                    </div>
                </form>
            </div>


            <div id="first-group" class="inner-width">
                <?php
                // CHAPTERS 테이블에서 모든 챕터를 가져오기
                $chaptersQuery = "SELECT * FROM CHAPTERS";
                $chaptersResult = $dbconnect->query($chaptersQuery);

                if ($chaptersResult->num_rows > 0) {
                    while ($chapter = $chaptersResult->fetch_assoc()) {
                        ?>
                        <details name="only">
                            <summary class="btn full over gray"><?php echo htmlspecialchars($chapter['CHAP_NAME']); ?></summary>
                            <?php
                            // 해당 챕터의 서브챕터 가져오기
                            $subChaptersQuery = "
                                SELECT
                                    MIN(SUB_CHAP_ID) AS SUB_CHAP_ID,
                                    SUB_CHAP_NAME,
                                    POSITIONS,
                                    GROUP_CONCAT(VERSIONS ORDER BY VERSIONS ASC) AS VERSIONS_GROUP
                                FROM SUBCHAPTERS
                                WHERE CHAP_ID = {$chapter['CHAP_ID']}
                                GROUP BY POSITIONS, SUB_CHAP_NAME
                                ORDER BY POSITIONS ASC
                            ";
                            $subChaptersResult = $dbconnect->query($subChaptersQuery);

                            if ($subChaptersResult->num_rows > 0) {
                                ?>
                                <ul>
                                    <?php
                                    while ($subChapter = $subChaptersResult->fetch_assoc()) {
                                        ?>
                                        <li>
                                            <a href="faq_view.php?mode=view&chap_id=<?php echo $chapter['CHAP_ID']; ?>&sub_chap_id=<?php echo $subChapter['SUB_CHAP_ID']; ?>&position=<?php echo $subChapter['POSITIONS']; ?>&version=<?php echo explode(',', $subChapter['VERSIONS_GROUP'])[0]; ?>">
                                                <?php echo htmlspecialchars($subChapter['SUB_CHAP_NAME']); ?>
                                            </a>
                                            <?php
                                            $versions = explode(',', $subChapter['VERSIONS_GROUP']);
                                            if (count($versions) > 1) {
                                                ?>
                                                <span class="versions">
                                                    (
                                                    <?php
                                                    foreach ($versions as $uiIndex => $actualVersion) {
                                                        ?>
                                                        <a href="faq_view.php?mode=view&chap_id=<?php echo $chapter['CHAP_ID']; ?>&sub_chap_id=<?php echo $subChapter['SUB_CHAP_ID']; ?>&position=<?php echo $subChapter['POSITIONS']; ?>&version=<?php echo $actualVersion; ?>">
                                                            #<?php echo $uiIndex + 1; ?>
                                                        </a>
                                                        <?php
                                                        if ($uiIndex < count($versions) - 1) {
                                                            echo '&nbsp;';
                                                        }
                                                    }
                                                    ?>
                                                    )
                                                </span>
                                                <?php
                                            }
                                            ?>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                </ul>
                                <?php
                            }
                            ?>
                        </details>
                        <?php
                    }
                }
                ?>
            </div>

            <div id="second-group" class="inner-width">
            <details name="only">
                <summary class="btn full over gray">메뉴1</summary>
                <ul>
                    <li><a href="">실기간 장비 현황 및 분석 로그 그래프가 안 나와요</a></li>
                    <li><a href="">L7 실시간 모니터링 테이터가 안 나와요</a></li>
                    <li><a href="">INLINE 및 정책 재생하면 네트워크 지연 현상이 생겨요</a></li>
                </ul>
            </details>
            <details name="only">
                <summary class="btn full over gray">메뉴2</summary>
                <ul>
                    <li><a href="">실기간 장비 현황 및 분석 로그 그래프가 안 나와요</a></li>
                    <li><a href="">L7 실시간 모니터링 테이터가 안 나와요</a></li>
                    <li><a href="">INLINE 및 정책 재생하면 네트워크 지연 현상이 생겨요</a></li>
                </ul>
            </details>
            <details name="only">
                <summary class="btn full over gray">메뉴3</summary>
                <ul>
                    <li><a href="">실기간 장비 현황 및 분석 로그 그래프가 안 나와요</a></li>
                    <li><a href="">L7 실시간 모니터링 테이터가 안 나와요</a></li>
                    <li><a href="">INLINE 및 정책 재생하면 네트워크 지연 현상이 생겨요</a></li>
                </ul>
            </details>
            <details name="only">
                <summary class="btn full over gray">메뉴4</summary>
                <ul>
                    <li><a href="">실기간 장비 현황 및 분석 로그 그래프가 안 나와요</a></li>
                    <li><a href="">L7 실시간 모니터링 테이터가 안 나와요</a></li>
                    <li><a href="">INLINE 및 정책 재생하면 네트워크 지연 현상이 생겨요</a></li>
                </ul>
            </details>
            <details name="only">
                <summary class="btn full over gray">메뉴5</summary>
                <ul>
                    <li><a href="">실기간 장비 현황 및 분석 로그 그래프가 안 나와요</a></li>
                    <li><a href="">L7 실시간 모니터링 테이터가 안 나와요</a></li>
                    <li><a href="">INLINE 및 정책 재생하면 네트워크 지연 현상이 생겨요</a></li>
                </ul>
            </details>
            </div>
        </div>




        </nav>
    </div>
</body>
</html>
