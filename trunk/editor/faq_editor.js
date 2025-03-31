// faq_editor.js
document.addEventListener('DOMContentLoaded', function() {
    const formTextArea = document.getElementById('sub_section_content');
    if (formTextArea) {
        const editor = SUNEDITOR.create('sub_section_content', {
            height: '400px',
            buttonList: [
                ['undo', 'redo'],
                ['font', 'fontSize', 'formatBlock'],
                ['bold', 'underline', 'italic', 'strike', 'subscript', 'superscript'],
                ['removeFormat'],
                ['fontColor', 'hiliteColor'],
                ['outdent', 'indent'],
                ['align', 'horizontalRule', 'list', 'table'],
                ['link', 'image'],
                ['fullScreen', 'showBlocks', 'codeView'],
            ],
              // 이미지 업로드 설정 추가
              imageUploadUrl: 'editor/img_upload.php',
              imageUploadSizeLimit: 5242880,  // 5MB
              imageAccept: '.jpg, .jpeg, .png, .gif',
              imageUploadParams: {
                chap_id: document.getElementById('chapter')?.value || '0',
                sub_chap_id: document.getElementById('sub_chapter')?.value || '0',
                version: document.getElementById('sub_chapter_number')?.value || '1'
            },
            onImageUploadBefore: function (files, info, uploadHandler) {
                console.log('onImageUploadBefore triggered', {
                    filesLength: files?.length,
                    info: info
                });

                if (files && files[0]) {
                    upload_file(files[0], "editor/img_upload.php", (json) => {
                        console.log('Upload callback received:', json);
                        if (json.result && json.result[0]) {
                            const imageUrl = json.result[0].url;
                            console.log('Inserting image with URL:', imageUrl);
                            this.insertImage(imageUrl);
                            return true;
                        } else {
                            console.error('Invalid response format:', json);
                            return false;
                        }
                    });
                    return false;
                }
            }
        });

        // 에디터 내용이 변경될 때 textarea 값 업데이트
        editor.onChange = function (contents) {
            formTextArea.value = contents;
        };
    }

       // 서브섹션 내용 에디터들 초기화 (추가)
    document.querySelectorAll('.subsection-content .sun-editor-editable').forEach(textarea => {
        if (!textarea.closest('.sun-editor')) {  // 이미 초기화되지 않은 에디터만
            const editor = SUNEDITOR.create(textarea, {
                height: '400px',
                buttonList: [
                    ['undo', 'redo'],
                    ['font', 'fontSize', 'formatBlock'],
                    ['bold', 'underline', 'italic', 'strike', 'subscript', 'superscript'],
                    ['removeFormat'],
                    ['fontColor', 'hiliteColor'],
                    ['outdent', 'indent'],
                    ['align', 'horizontalRule', 'list', 'table'],
                    ['link', 'image'],
                    ['fullScreen', 'showBlocks', 'codeView'],
                ],
                imageUploadUrl: 'editor/img_upload.php',
                imageUploadSizeLimit: 5242880,
                imageAccept: '.jpg, .jpeg, .png, .gif',
                // 이미지 업로드 핸들러 추가
                onImageUploadBefore: function (files, info, uploadHandler) {
                    console.log('onImageUploadBefore triggered', {
                        filesLength: files?.length,
                        info: info
                    });

                    if (files && files[0]) {
                        upload_file(files[0], "editor/img_upload.php", (json) => {
                            console.log('Upload callback received:', json);
                            if (json.result && json.result[0]) {
                                const imageUrl = json.result[0].url;
                                console.log('Inserting image with URL:', imageUrl);
                                this.insertImage(imageUrl);
                                return true;
                            } else {
                                console.error('Invalid response format:', json);
                                return false;
                            }
                        });
                        return false;
                    }
                }
            });

            editor.onChange = function (contents) {
                textarea.value = contents;
            };
        }
    });

    // 폼 페이지의 서브챕터 설명 에디터 초기화
    const descriptionTextArea = document.getElementById('subchapter_description');
    if (descriptionTextArea) {
        const descriptionEditor = SUNEDITOR.create('subchapter_description', {
            height: '300px',
            buttonList: [
                ['undo', 'redo'],
                ['font', 'fontSize', 'formatBlock'],
                ['bold', 'underline', 'italic', 'strike', 'subscript', 'superscript'],
                ['removeFormat'],
                ['fontColor', 'hiliteColor'],
                ['outdent', 'indent'],
                ['align', 'horizontalRule', 'list', 'table'],
                ['link', 'image'],
                ['fullScreen', 'showBlocks', 'codeView'],
            ],
              // 이미지 업로드 설정 추가
              imageUploadUrl: 'editor/img_upload.php',
              imageUploadSizeLimit: 5242880,  // 5MB
              imageAccept: '.jpg, .jpeg, .png, .gif',
              // 이미지 업로드 시 추가 파라미터 설정
              imageUploadParams: {
                chap_id: document.getElementById('chapter')?.value || '0',
                sub_chap_id: document.getElementById('sub_chapter')?.value || '0',
                version: document.getElementById('sub_chapter_number')?.value || '1'
            },
            onImageUploadBefore: function (files, info, uploadHandler) {
                console.log('onImageUploadBefore triggered', {
                    filesLength: files?.length,
                    info: info
                });

                if (files && files[0]) {
                    const formData = new FormData();
                    formData.append('file', files[0]);

                    // 추가 데이터 전송
                    formData.append('chap_id', document.getElementById('chapter')?.value || '0');
                    formData.append('sub_chap_id', document.getElementById('sub_chapter')?.value || '0');
                    formData.append('version', document.getElementById('sub_chapter_number')?.value || '1');

                    // old 버전의 핵심 기능만 추가
                    upload_file(files[0], "editor/img_upload.php", (json) => {
                        console.log('Upload callback received:', json);
                        if (json.result && json.result[0]) {
                            const imageUrl = json.result[0].url;
                            console.log('Inserting image with URL:', imageUrl);
                            this.insertImage(imageUrl);
                            return true;
                        } else {
                            console.error('Invalid response format:', json);
                            return false;
                        }
                    });
                    return false;
                }
            }
        });

        // 에디터 내용이 변경될 때 textarea 값 업데이트
        descriptionEditor.onChange = function (contents) {
            descriptionTextArea.value = contents;
        };
    }

    // 뷰 페이지의 에디터 초기화
    const viewTextArea = document.getElementById('subsection_editor');
    if (viewTextArea) {
        const viewEditor = SUNEDITOR.create('subsection_editor', {
            readOnly: true,
            showToolbar: false,
            height: 'auto',
            buttonList: []
        });

        // 초기 콘텐츠 설정
        if (viewTextArea.value) {
            viewEditor.setContents(viewTextArea.value);
        }
    }

    // Initialize form submission handling
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Synchronize all editors before form submission
            syncAllEditors();
        });
    }
});

// SunEditor 초기화 함수
export function initSunEditor(textarea) {
	if (!textarea) return null;

	const editorConfig = {
		height: '400px',
		buttonList: [
				['undo', 'redo'],
				['font', 'fontSize', 'formatBlock'],
				['bold', 'underline', 'italic', 'strike', 'subscript', 'superscript'],
				['removeFormat'],
				['fontColor', 'hiliteColor'],
				['outdent', 'indent'],
				['align', 'horizontalRule', 'list', 'table'],
				['link', 'image'],
				['fullScreen', 'showBlocks', 'codeView'],
		],
		imageUploadUrl: 'editor/img_upload.php',
		imageUploadSizeLimit: 5242880,
		imageAccept: '.jpg, .jpeg, .png, .gif'
	};

	const editor = SUNEDITOR.create(textarea.id || textarea, {
		...editorConfig,
		onImageUploadBefore: function (files, info, uploadHandler) {
			console.log('onImageUploadBefore triggered', {
				filesLength: files?.length,
				info: info
			});

			if (files && files[0]) {
				upload_file(files[0], "editor/img_upload.php", (json) => {
					console.log('Upload callback received:', json);
					if (json.result && json.result[0]) {
						const imageUrl = json.result[0].url;
						console.log('Inserting image with URL:', imageUrl);
						this.insertImage(imageUrl);
						return true;
					} else {
						console.error('Invalid response format:', json);
						return false;
					}
				});
				return false;
			}
		}
	});

	// Add onChange handler to update textarea value
	editor.onChange = function(contents) {
		textarea.value = contents;
	};

	return editor;
}

function upload_file(file, to_url, callback) {
    console.log('Attempting to upload file:', {
        fileName: file.name,
        fileSize: file.size,
        fileType: file.type,
        uploadUrl: to_url
    });

    var formData = new FormData();
    formData.append("file-0", file);  // Changed from "file" to "file-0" to match what SunEditor sends

    // Add the additional parameters that img_upload.php expects
    formData.append('chap_id', document.getElementById('chapter')?.value || '0');
    formData.append('sub_chap_id', document.getElementById('sub_chapter')?.value || '0');
    formData.append('version', document.getElementById('sub_chapter_number')?.value || '1');

    return fetch(to_url, {
        method: "POST",
        body: formData
    })
    .then((res) => {
        console.log('Upload response status:', res.status);
        if(res.ok) {
            res.json().then((json) => {
                console.log('Upload response:', json);
                callback(json);
            });
        } else {
            console.error("Upload failed with status:", res.status);
            res.text().then(text => console.error('Error response:', text));
        }
    })
    .catch((err) => {
        console.error("Error during file upload:", err);
    });
}

// Add this function to synchronize all editors before form submission
function syncAllEditors() {
    document.querySelectorAll('.sun-editor-editable').forEach(textarea => {
        const editorId = textarea.id;
        const editor = SUNEDITOR.get(editorId);
        if (editor) {
            textarea.value = editor.getContents();
        }
    });
}
