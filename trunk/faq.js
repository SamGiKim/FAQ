// faq.js
import { initSunEditor } from './editor/faq_editor.js';

// 새로 입력하는 창 나올때
function toggleInputFieldVisibility(fieldId, isVisible) {
	var inputField = document.getElementById(fieldId);

	if (isVisible) {
		inputField.classList.add("add-field-active");
		inputField.style.display = 'block';
	} else {
		inputField.classList.remove("add-field-active");
		inputField.style.display = 'none';
	}
}



/////////////////////////////////////////////////////////////////////////////

// 폼 검증 및 버튼 상태 업데이트
// function validateForm() {
// 	const mode = document.querySelector('form').getAttribute('data-mode');
// 	const submitButton = document.querySelector('input[type="submit"]');

// 	// 수정 모드일 때는 항상 버튼 활성화
// 	if (mode === 'edit') {
// 			submitButton.disabled = false;
// 			submitButton.style.backgroundColor = '#007bff';
// 			submitButton.style.cursor = 'pointer';
// 			return;
// 	}

// 	// validateAllFields() 결과에 따라 버튼 상태 업데이트
// 	// const isValid = validateAllFields();
// 	// submitButton.disabled = !isValid;
// 	// submitButton.style.backgroundColor = isValid ? '#007bff' : '#cccccc';
// 	// submitButton.style.cursor = isValid ? 'pointer' : 'not-allowed';
// }

// 폼 초기화 함수
function initializeForm() {
	const form = document.querySelector('form');
	if (!form) return;  // 폼이 없으면 early return

	const submitButton = form.querySelector('input[type="submit"]');
	if (!submitButton) return;  // submit 버튼이 없으면 early return

	// 제출 버튼 활성화
	submitButton.disabled = false;
	submitButton.style.backgroundColor = '#007bff';
	submitButton.style.cursor = 'pointer';

	const fields = [
		{ select: 'chapter', input: 'new_chapter' },
		{ select: 'sub_chapter', input: 'new_sub_chapter' }
	];

	// 폼 제출 시 select와 input 동기화만 처리
	form.addEventListener('submit', (e) => {
		fields.forEach(field => {
			const selectElement = document.getElementById(field.select);
			const inputElement = document.getElementById(field.input);
			if (inputElement?.value.trim()) {
				selectElement.value = inputElement.value.trim();
			}
		});
	});
}


// // 모든 필드 검증 함수
// function validateAllFields() {
// 	try {
// 			// 챕터 검증
// 			const chapter = document.getElementById('chapter');
// 			const newChapter = document.getElementById('new_chapter');
// 			const isChapterValid = (chapter?.value && chapter.value !== 'new-chapter') ||
// 													 (newChapter?.value && newChapter.value.trim() !== '');

// 			// 서브챕터 검증
// 			const subChapter = document.getElementById('sub_chapter');
// 			const newSubChapter = document.getElementById('new_sub_chapter');
// 			const isSubChapterValid = (subChapter?.value && subChapter.value !== 'new-subChapter') ||
// 															 (newSubChapter?.value && newSubChapter.value.trim() !== '');

// 			// 섹션 이름 검증
// 			const sectionName = document.getElementById('section_name');
// 			const isSectionValid = sectionName?.value?.trim() !== '';

// 			// 첫 번째 서브섹션만 검증 (최소 하나만 있으면 됨)
// 			const firstSubsection = document.querySelector('.subsection-group');
// 			const isFirstSubsectionValid = firstSubsection &&
// 					firstSubsection.querySelector('input[name="subsection_names[]"]')?.value?.trim() !== '' &&
// 					firstSubsection.querySelector('.sun-editor-editable')?.value?.trim() !== '';

// 			console.log('Validation Results:', {
// 					chapter: isChapterValid,
// 					subChapter: isSubChapterValid,
// 					section: isSectionValid,
// 					firstSubsection: isFirstSubsectionValid
// 			});

// 			return isChapterValid && isSubChapterValid && isSectionValid && isFirstSubsectionValid;
// 	} catch (error) {
// 			console.error('Validation error:', error);
// 			return false;
// 	}
// }


// 폼 검증 이벤트 설정
// function setupFormValidation() {
// 	const validationFields = ['chapter', 'new_chapter', 'sub_chapter', 'new_sub_chapter', 'section_name'];
// 	validationFields.forEach(fieldId => {
// 			const element = document.getElementById(fieldId);
// 			if (element) {
// 					element.addEventListener('change', validateForm);
// 					element.addEventListener('input', validateForm);
// 			}
// 	});

// 	// 서브섹션 필드 동적으로 추가되는 경우 처리
// 	document.getElementById('sections-container')?.addEventListener('input', validateForm);

// 	// validateForm(); // 초기 상태 설정
// }

// FAQ 내용을 동적으로 로드
function loadSearchFAQContent(chapId, subChapId, position, version) {
    fetch(`get_faq_view.php?chap_id=${chapId}&sub_chap_id=${subChapId}&position=${position}&version=${version}`)
        .then(response => response.json())
        .then(data => {
			// 관리자 버튼들의 hidden input 값 설정
			['current', 'new', 'delete'].forEach(prefix => {
				const element = document.getElementById(`${prefix}_chap_id`);
				if (element) {
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

            const mainContent = document.querySelector('.main-frame article.chapter');
            const reference = document.querySelector('.reference');
			const editContainer = document.querySelector(".edit-button-container");
			const pagination = document.querySelector(".pagenation");
			
			// 수정, 삭제 버튼과 페이지네이션 보이기
			if (editContainer) editContainer.style.display = "flex";
			if (pagination) pagination.style.display = "flex";

            // 메인 콘텐츠 구성
            let mainHTML = ``;

            data.sections.forEach(section => {
                mainHTML += `
                    <div class="section" data-id="${section.SEC_ID}">
                        <h3 class="section-title">섹션: ${section.SEC_NAME}</h3>
                        <div class="section-content">${section.SEC_DESC || ''}</div>
                        ${section.subsections ? section.subsections.map(subsection => {
                            return `
                                <div class="sub-section" data-id="${subsection.SUB_SEC_ID}">
                                    <h5 class="subsection-title">서브섹션: ${subsection.SUB_SEC_NAME}</h5>
                                    <div class="content">${subsection.SUB_SEC_CONTENT || ''}</div>
                                </div>
                            `;
                        }).join('') : ''}
                    </div>
                `;
            });

            mainContent.innerHTML = `<h1>${data.chapter}</h1>
                                    <h2>${data.subChapter.name}</h2>` +
                                `<main class="scrollmini">` + mainHTML + `</main>`;

            // Reference 섹션 업데이트
            reference.innerHTML = `
                <section>
                    <div class="description-content">
                        ${data.subChapter.desc || ''}
                    </div>
                </section>
            `;
			// 검색된 FAQ의 수정, 삭제 버튼 이벤트 등록
            attachEventListeners();

            // 페이지네이션 업데이트
            updatePagination(data.versions, chapId, subChapId, position, version);
        })
        .catch(error => console.error("FAQ 내용 로딩 오류:", error));
}

// 수정, 삭제 버튼 이벤트 등록 함수
function attachEventListeners() {
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            console.log(`수정 버튼 클릭: ${id}`);
            editFAQ(id);
        });
    });

    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            console.log(`삭제 버튼 클릭: ${id}`);
            deleteFAQ(id);
        });
    });
    console.log("이벤트 리스너 재적용 완료!");
}

// 검색된 FAQ의 페이지네이션 업데이트
function updatePagination(versions, chapId, subChapId, position, currentVersion) {
    const pagination = document.querySelector(".pagenation ul");
    if (!pagination || !versions || versions.length === 0) return;

    let paginationHTML = `
        <li><a href="#" class="pagination-link" data-version="${versions[0]}">&laquo;</a></li>
        <li><a href="#" class="pagination-link" data-version="${currentVersion > 1 ? versions[Math.max(0, versions.indexOf(parseInt(currentVersion)) - 1)] : versions[0]}">&lsaquo;</a></li>
    `;

    // 버전 페이지 번호 생성
    versions.forEach((ver, index) => {
        paginationHTML += `
            <li>
                <a href="#" class="pagination-link ${ver == currentVersion ? 'active' : ''}" data-version="${ver}">
                    ${index + 1}
                </a>
            </li>
        `;
    });

    paginationHTML += `
        <li><a href="#" class="pagination-link" data-version="${currentVersion < versions.length ? versions[Math.min(versions.length - 1, versions.indexOf(parseInt(currentVersion)) + 1)] : versions[versions.length - 1]}">&rsaquo;</a></li>
        <li><a href="#" class="pagination-link" data-version="${versions[versions.length - 1]}">&raquo;</a></li>
    `;
    pagination.innerHTML = paginationHTML;

    // 페이지네이션 클릭 이벤트 추가
    document.querySelectorAll(".pagination-link").forEach(link => {
        link.addEventListener("click", function (e) {
            e.preventDefault();
            const newVersion = e.target.dataset.version;
            loadSearchFAQContent(chapId, subChapId, position, newVersion);
        });
    });
}

// 검색
function faqSearch() {
    const word_el = document.querySelector("#search-input"); // 검색 필드에서 값을 가져옴
    let keyword = word_el.value;
    var mainFrame = document.getElementById('id-chapter');
    var reference = document.getElementById('id-reference');

	// 수정,삭제 버튼과 페이지네이션 숨기기
    const editContainer = document.querySelector(".edit-button-container");
    const pagination = document.querySelector(".pagenation");

    if (editContainer) editContainer.style.display = "none";
    if (pagination) pagination.style.display = "none";
	
    // 검색 요청 (AJAX)
    fetch("faq_search.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: `keyword=${encodeURIComponent(keyword)}`
    })
    .then(response => response.json())
    .then(data => {
        mainFrame.innerHTML = ""; // 기존 내용 지우기
        reference.innerHTML = "";

		// 검색 결과가 존재할 경우
        if (data.results.length > 0) {
            let resultHTML = "<h2>검색 결과</h2>";
            data.results.forEach(result => {
                let chapId = result.CHAP_ID;
                let subChapId = result.SUB_CHAP_ID;
                let position = result.POSITION; 
                let version = result.VERSION;

                resultHTML += `
                    <div class="result-item">
                        <a href="#" class="faq-result-link" 
                           data-chap-id="${chapId}"
                           data-sub-chap-id="${subChapId}"
                           data-position="${position}"
                           data-version="${version}">
                           ${result.CHAP_NAME}
                        </a>
                        <p>${result.SUB_CHAP_NAME}</p>
                        <br>
                    </div>
                `;
            });
            mainFrame.innerHTML = resultHTML;
        } else {
            mainFrame.innerHTML = `<p class="no-results">검색 결과가 없습니다.</p>`;
        }

        // 링크 클릭 시 해당 FAQ 내용 로드
        document.querySelectorAll('.faq-result-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const chapId = e.target.dataset.chapId;
                const subChapId = e.target.dataset.subChapId;
                const position = e.target.dataset.position;
                const version = e.target.dataset.version;

                loadSearchFAQContent(chapId, subChapId, position, version);  // 동적으로 FAQ 로드
            });
        });
    })
    .catch(error => console.error("검색 오류:", error));
}


// 이벤트 설정 함수(섹션 추가, 섹션 삭제 버튼)
function setupSectionEvents() {
	// 1. '섹션 추가' 버튼 클릭 시
	document.querySelector('.add-section-btn')?.addEventListener('click', () => sectionManager.addSection());

	// 2. 모든 버튼 클릭 처리를 하나의 이벤트 리스너로 통합
	document.addEventListener('click', (e) => {
			// TODO: 클릭한 엘리먼트를 필터링
			
			// 검색 버튼 클릭시
			if(e.target.getAttribute("id") == "search-button") {
				faqSearch();
			}

			// 섹션 삭제 버튼 클릭 시
			if (e.target.matches('.delete-section-btn') || e.target.closest('.delete-section-btn')) {
					const container = document.getElementById('sections-container');
					const sections = container.querySelectorAll('.section-group');

					if (sections.length > 1) {
							e.target.closest('.section-group').remove();
							sectionManager.updateDeleteButtons();
					}
					return;
			}

			// 서브섹션 추가 버튼 클릭 시
			if (e.target.classList.contains('add-subsection-btn')) {
					sectionManager.addSubsection(e);
					return;
			}

			// 서브섹션 삭제 버튼 클릭 시
			if (e.target.classList.contains('remove-subsection-btn')) {
					sectionManager.removeElement(e);
					return;
			}
	});
}

// 모든 초기화 로직을 하나로 묶기
function initializeAllComponents() {
	initializeForm();
	// setupFormValidation();
	setupSectionEvents();

	// 챕터 매니저 초기화
	chapterManager.initializeEventListeners();
}


// 챕터와 서브챕터 선택 관련 함수들
const chapterManager = {
	   // 새 챕터 입력 필드 표시/숨김 처리
		 toggleNewChapterInput(show) {
			const newChapterInput = document.getElementById('new_chapter');
			if (newChapterInput) {
					newChapterInput.style.display = show ? 'block' : 'none';
					if (show) newChapterInput.focus();
			}
	},
	 // 새 서브챕터 입력 필드 표시/숨김 처리
	toggleNewSubChapterInput(show) {
			const newSubChapterInput = document.getElementById('new_sub_chapter');
			if (newSubChapterInput) {
					newSubChapterInput.style.display = show ? 'block' : 'none';
					if (show) newSubChapterInput.focus();
			}
	},
	 // 챕터 select 변경 이벤트 핸들러
	handleChapterSelectChange(event) {
			console.log('Chapter change event triggered'); // 디버깅
			const isNewChapter = event.target.value === 'new-chapter';
			this.toggleNewChapterInput(isNewChapter);

			if (!isNewChapter) {
					const chapterId = event.target.value;
					fetch('fetch_subchapters.php', {
							method: 'POST',
							headers: {
									'Content-Type': 'application/x-www-form-urlencoded',
							},
							body: 'chap_id=' + encodeURIComponent(chapterId),
					})
					.then(response => response.text())
					.then(data => {
							const subChapterSelect = document.getElementById('sub_chapter');
							if (subChapterSelect) subChapterSelect.innerHTML = data;
					})
					.catch(error => console.error('Error:', error));
			}
	},
	 // 서브챕터 select 변경 이벤트 핸들러
	handleSubChapterSelectChange(event) {
			console.log('SubChapter change event triggered'); // 디버깅
			const subChapterSelect = event.target;
			const isNewSubChapter = subChapterSelect.value === 'new-subChapter';
			this.toggleNewSubChapterInput(isNewSubChapter);

			const versionOption = document.querySelector('.version-option');
			const hasVersionCheckbox = document.getElementById('has_version');

			if (isNewSubChapter) {
					versionOption.style.display = 'none';
			} else if (subChapterSelect.value !== '') {
					versionOption.style.display = 'block';
					hasVersionCheckbox.checked = true;
					hasVersionCheckbox.dispatchEvent(new Event('change'));
			} else {
					versionOption.style.display = 'none';
			}
	},
	 // 이벤트 리스너 초기화
	initializeEventListeners() {
			console.log('Initializing event listeners'); // 디버깅
			 const chapterSelect = document.getElementById('chapter');
			const subChapterSelect = document.getElementById('sub_chapter');
			 if (chapterSelect) {
					chapterSelect.addEventListener('change', (e) => this.handleChapterSelectChange(e));
					console.log('Chapter event listener added');
			}
			 if (subChapterSelect) {
					subChapterSelect.addEventListener('change', (e) => this.handleSubChapterSelectChange(e));
					console.log('SubChapter event listener added');
			}
	}
	}
;

// 섹션 관리 모듈
const sectionManager = {
	// 섹션 추가
	addSection() {
		const container = document.getElementById('sections-container');
		container.insertAdjacentHTML('beforeend', this.createSectionSetTemplate());
		this.updateDeleteButtons();

		// 새로운 섹션의 에디터 초기화
		const newTextarea = container.querySelector('.section-group:last-child .sun-editor-editable');
		if (newTextarea) {
				initSunEditor(newTextarea);
		}
},

 // 섹션 템플릿 생성
 createSectionSetTemplate() {
	const sectionIndex = document.querySelectorAll('.section-group').length;
	return `
			<div class="section-group">
					<div class="section-header">
							<div class="section-header-content">
									<label>섹션<span>Section</span></label>
									<input type="text" name="section_names[${sectionIndex}]" placeholder="섹션 이름">
									<button type="button" class="delete-section-btn delete-button" style="display: none;">
											섹션 삭제
									</button>
							</div>
					</div>
					<div class="subsections-container">
							<div class="subsection-group">
									<div class="subsection-header">
											<label>서브섹션<span>Sub Section</span></label>
											<input type="text" name="subsection_names[${sectionIndex}][]" placeholder="서브섹션 이름">
									</div>
									<div class="subsection-content">
											<label>서브섹션 내용<span>Sub Section Content</span></label>
											<textarea name="subsection_contents[${sectionIndex}][]" class="sun-editor-editable"></textarea>
									</div>
							</div>
							<button type="button" class="add-subsection-btn">+ 서브섹션 추가</button>
					</div>
			</div>
	`;
},

// 섹션 삭제 버튼 상태 업데이트
updateDeleteButtons() {
		const container = document.getElementById('sections-container');
		const sections = container.querySelectorAll('.section-group');

		sections.forEach(section => {
				const deleteBtn = section.querySelector('.delete-section-btn');
				if (deleteBtn) {
						deleteBtn.style.display = sections.length > 1 ? 'inline-block' : 'none';
				}
		});
},

	 // 서브섹션 추가
	 addSubsection(event) {
		if (!event.target.classList.contains('add-subsection-btn')) return;

		const subsectionsContainer = event.target.closest('.subsections-container');
		const sectionGroup = event.target.closest('.section-group');
		const sectionIndex = Array.from(document.querySelectorAll('.section-group')).indexOf(sectionGroup);

		if (!subsectionsContainer) {
				console.error('subsections-container를 찾을 수 없습니다');
				return;
		}

		const timestamp = new Date().getTime();
		subsectionsContainer.insertAdjacentHTML('beforeend', this.createSubsectionTemplate(sectionIndex));

		// Initialize editor for the new subsection
		const newTextarea = subsectionsContainer.querySelector('.subsection-group:last-child .sun-editor-editable');
		if (newTextarea) {
				// Ensure unique ID is set
				if (!newTextarea.id) {
						newTextarea.id = `sun-editor-${timestamp}`;
				}
				const editor = initSunEditor(newTextarea);

				// Add specific onChange handler for this editor
				if (editor) {
						editor.onChange = function(contents) {
								newTextarea.value = contents;
						};
				}
		}
},

// 서브섹션 템플릿 생성
createSubsectionTemplate(sectionIndex) {
		const timestamp = new Date().getTime();
		return `
				<div class="subsection-group">
						<div class="subsection-header">
								<label>서브섹션<span>Sub Section</span></label>
								<input type="text" name="subsection_names[${sectionIndex}][]" placeholder="서브섹션 이름">
								<button type="button" class="remove-subsection-btn">서브섹션 삭제</button>
						</div>
						<div class="subsection-content">
								<label>서브섹션 내용<span>Sub Section Content</span></label>
								<textarea name="subsection_contents[${sectionIndex}][]"
										class="sun-editor-editable"
										id="sun-editor-${timestamp}"></textarea>
						</div>
				</div>
		`;
},

	 // 요소 삭제
	removeElement(event) {
			if (event.target.classList.contains('remove-subsection-btn')) {
					event.target.closest('.subsection-group').remove();
			} else if (event.target.classList.contains('remove-section-btn')) {
					event.target.closest('.section-group').remove();
			}
	}
}
;


// DOMContentLoaded 이벤트에서 초기화 함수 호출
document.addEventListener('DOMContentLoaded', function() {
	console.log('DOM Content Loaded');
	const form = document.querySelector('form');
	console.log('Form found:', form);
	if (form) {
		const submitButton = form.querySelector('input[type="submit"]');
		console.log('Submit button found:', submitButton);
	}
	initializeAllComponents();
});


