// faq.js

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
// Chapter
function handleChapterChange() {
	var chapterSelect = document.getElementById('chapter');
	var isNewChapter = chapterSelect.value === 'new-chapter';

	toggleInputFieldVisibility('new_chapter', isNewChapter);

	if (!isNewChapter) {
		const chapterId = chapterSelect.value;

		fetch('fetch_subchapters.php', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: 'chap_id=' + encodeURIComponent(chapterId),
		})
		.then(response => {
			if (!response.ok) {
				throw new Error('네트워크 응답이 올바르지 않습니다');
			}
			return response.text();
		})
		.then(data => {
			var subChapterSelect = document.getElementById('sub_chapter');
			subChapterSelect.innerHTML = data;
		})
		.catch(error => {
			console.error('fetch 작업에 문제가 있었습니다:', error);
		});
	}
}


/////////////////////////////////////////////////////////////////////////////
// Sub Chapter

// 페이지 로드 시 한 번만 실행되도록 이벤트 리스너를 설정
document.addEventListener('DOMContentLoaded', function() {
	document.addEventListener('change', function(event) {
		// 서브 챕터 선택 드롭다운에서 변경 사항이 발생한 경우
		if (event.target && event.target.id === 'sub_chapter') {
			var isNewSubChapter = event.target.value === 'new-subChapter';
			// 새 서브 챕터 입력의 표시 상태를 조정
			toggleInputFieldVisibility('new_sub_chapter', isNewSubChapter);
		}
	});
});


// function handleSubChapterChange() {
//   var subChapterSelect = document.getElementById('sub_chapter');
//   var sectionSelect = document.getElementById('section');
//   var subChapterId = subChapterSelect.value;

//   if (subChapterId) {
//     fetch('fetch_sections.php', {
//       method: 'POST',
//       headers: {
//         'Content-Type': 'application/x-www-form-urlencoded',
//       },
//       body: 'sub_chap_id=' + encodeURIComponent(subChapterId),
//     })
//     .then(response => response.text())
//     .then(data => {
//       sectionSelect.innerHTML = data;
//     })
//     .catch(error => console.error('Error:', error));
//   }
// }

function handleSubChapterChange() {
	const subChapterSelect = document.getElementById('sub_chapter');
	const sectionSelect = document.getElementById('section');
	const newSubChapterInput = document.getElementById('new_sub_chapter');
	const versionOption = document.querySelector('.version-option');
	const hasVersionCheckbox = document.getElementById('has_version');
	const subChapterId = subChapterSelect.value;

	if (subChapterSelect.value === 'new-subChapter') {
		newSubChapterInput.style.display = 'block';
		versionOption.style.display = 'none';
		newSubChapterInput.focus();
	} else if (subChapterSelect.value !== '') {
		newSubChapterInput.style.display = 'none';
		versionOption.style.display = 'block';
		hasVersionCheckbox.checked = true;
		hasVersionCheckbox.dispatchEvent(new Event('change'));
	} else {
		newSubChapterInput.style.display = 'none';
		versionOption.style.display = 'none';
	}

	if (subChapterId && subChapterId !== 'new-subChapter') {
		fetch('fetch_sections.php', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: 'sub_chap_id=' + encodeURIComponent(subChapterId),
		})
		.then(response => response.text())
		.then(data => {
			sectionSelect.innerHTML = data;
		})
		.catch(error => console.error('Error:', error));
	}
}

/////////////////////////////////////////////////////////////////////////////
// Section
// Section 변경 시 Subsection 데이터 가져오기
function handleSectionChange() {
	const sectionSelect = document.getElementById('section');
	const subSectionSelect = document.getElementById('sub_section');
	const newSectionInput = document.getElementById('new_section');
	const sectionId = sectionSelect.value;

	if (sectionSelect.value === 'new-section') {
		newSectionInput.style.display = 'block';
		subSectionSelect.innerHTML = '<option value="">서브섹션 선택...</option><option value="new-subSection" class="add-new">새 서브섹션 입력</option>';
	} else if (sectionId) {
		newSectionInput.style.display = 'none';
		fetch('fetch_subsections.php', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: 'section_id=' + encodeURIComponent(sectionId)
		})
		.then(response => response.text())
		.then(data => {
			subSectionSelect.innerHTML = data + '<option value="new-subSection" class="add-new">새 서브섹션 입력</option>';
		})
		.catch(error => console.error('Error:', error));
	} else {
		newSectionInput.style.display = 'none';
		subSectionSelect.innerHTML = '<option value="">서브섹션 선택...</option><option value="new-subSection" class="add-new">새 서브섹션 입력</option>';
	}
}



/////////////////////////////////////////////////////////////////////////////
// Sub Section
function handleSubSectionChange() {
	const subSectionSelect = document.getElementById('sub_section');
	const newSubSectionInput = document.getElementById('new_sub_section');
	
	if (subSectionSelect.value === 'new-subSection') {
		newSubSectionInput.style.display = 'block';
		newSubSectionInput.focus();
	} else {
		newSubSectionInput.style.display = 'none';
	}
}

// faq.js의 끝부분에 추가
function validateForm() {
    const mode = document.querySelector('form').getAttribute('data-mode');
    const submitButton = document.querySelector('input[type="submit"]');
    
    // 수정 모드일 때는 항상 버튼 활성화
    if (mode === 'edit') {
        submitButton.disabled = false;
        submitButton.style.backgroundColor = '#007bff';
        submitButton.style.cursor = 'pointer';
        return;
    }

    // 생성 모드일 때 기존 검증 로직
    const chapter = document.getElementById('chapter');
    const newChapter = document.getElementById('new_chapter');
    const subChapter = document.getElementById('sub_chapter');
    const newSubChapter = document.getElementById('new_sub_chapter');
    
    const isChapterValid = (chapter.value && chapter.value !== '') || 
                          (newChapter.value && newChapter.value.trim() !== '');
    const isSubChapterValid = (subChapter.value && subChapter.value !== '') || 
                             (newSubChapter.value && newSubChapter.value.trim() !== '');

    if (isChapterValid && isSubChapterValid) {
        submitButton.disabled = false;
        submitButton.style.backgroundColor = '#007bff';
        submitButton.style.cursor = 'pointer';
    } else {
        submitButton.disabled = true;
        submitButton.style.backgroundColor = '#cccccc';
        submitButton.style.cursor = 'not-allowed';
    }
}

// 이벤트 리스너 설정
document.addEventListener('DOMContentLoaded', function () {
	const form = document.querySelector('form');

	// 필드에 이벤트 리스너 추가
	const chapter = document.getElementById('chapter');
	const newChapter = document.getElementById('new_chapter');
	const subChapter = document.getElementById('sub_chapter');
	const newSubChapter = document.getElementById('new_sub_chapter');

	// 각 필드에 이벤트 리스너 추가
	[chapter, newChapter, subChapter, newSubChapter].forEach(element => {
			if (element) {
					element.addEventListener('change', validateForm);
					element.addEventListener('input', validateForm);
			}
	});

	// 초기 상태 설정
	validateForm();

	// 폼 제출 시 select와 input 동기화
	const fields = [
			{ select: 'chapter', input: 'new_chapter' },
			{ select: 'sub_chapter', input: 'new_sub_chapter' },
			{ select: 'section', input: 'new_section' },
			{ select: 'sub_section', input: 'new_sub_section' }
	];

	form.addEventListener('submit', function () {
			fields.forEach(field => {
					const selectElement = document.getElementById(field.select);
					const inputElement = document.getElementById(field.input);

					// input 값이 있으면 select 값을 input 값으로 설정
					if (inputElement && inputElement.value.trim() !== '') {
							selectElement.value = inputElement.value.trim();
					}
			});
	});
});

function loadFAQContent(chapId, subChapId, position, version) {
    fetch(`get_faq_content.php?chap_id=${chapId}&sub_chap_id=${subChapId}&position=${position}&version=${version}`)
        .then(response => response.json())
        .then(data => {
            // main-frame 내용 업데이트
            const mainContent = document.querySelector('.main-frame main');
            const reference = document.querySelector('.reference');
            
            // 메인 콘텐츠 구성
            let mainHTML = `
                <h1>${data.chapter}</h1>
                <h2>${data.subChapter.name}</h2>
            `;

            // 섹션들 추가
            if (data.sections && data.sections.length > 0) {
                data.sections.forEach(section => {
                    mainHTML += `
                        <h3>${section.SEC_NAME}</h3>
                        <section>
                            ${section.SEC_DESC}
                        </section>
                    `;
                });
            }

            mainContent.innerHTML = mainHTML;

            // reference 섹션 업데이트
            if (data.subChapter.desc) {
                reference.innerHTML = `
                    <section>
                        <h3>${data.subChapter.name}</h3>
                        <div>${data.subChapter.desc}</div>
                    </section>
                `;
            } else {
                reference.innerHTML = ''; // desc가 없으면 비움
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // 에러 발생시 사용자에게 알림
            const mainContent = document.querySelector('.main-frame main');
            mainContent.innerHTML = '<p>콘텐츠를 불러오는 중 오류가 발생했습니다.</p>';
        });
}



