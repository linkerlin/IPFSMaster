<div class="row">
    <div class="col-12">
        <h1 class="mb-4"><i class="bi bi-cloud-upload"></i> 上传到IPFS</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-file-earmark-arrow-up"></i> 上传文件
            </div>
            <div class="card-body">
                <form id="uploadFileForm" hx-post="/upload/file" hx-encoding="multipart/form-data" hx-target="#uploadResult">
                    <div class="upload-zone" id="fileDropZone">
                        <i class="bi bi-cloud-upload display-1 text-primary"></i>
                        <h4 class="mt-3">拖放文件到这里</h4>
                        <p class="text-muted">或者点击选择文件</p>
                        <input type="file" name="file" id="fileInput" class="d-none" required>
                        <button type="button" class="btn btn-primary mt-3" onclick="document.getElementById('fileInput').click()">
                            <i class="bi bi-folder2-open"></i> 选择文件
                        </button>
                    </div>
                    <div id="selectedFile" class="mt-3 d-none">
                        <div class="alert alert-info">
                            <i class="bi bi-file-earmark"></i> 已选择: <strong id="fileName"></strong>
                            <button type="button" class="btn-close float-end" onclick="clearFileSelection()"></button>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-upload"></i> 上传并Pin到IPFS
                        </button>
                    </div>
                </form>
                <div id="uploadResult" class="mt-3"></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-folder-plus"></i> 导入CID
            </div>
            <div class="card-body">
                <form hx-post="/pins/add" hx-target="#cidResult">
                    <div class="mb-3">
                        <label for="cidInput" class="form-label">IPFS CID</label>
                        <input type="text" class="form-control" id="cidInput" name="cid" 
                               placeholder="QmXx... 或 bafybeig..." required>
                        <div class="form-text">输入要固定的IPFS内容标识符（CID）</div>
                    </div>
                    <div class="mb-3">
                        <label for="nameInput" class="form-label">名称（可选）</label>
                        <input type="text" class="form-control" id="nameInput" name="name" 
                               placeholder="给这个内容起个名字">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-pin-angle"></i> Pin到IPFS（递归）
                    </button>
                </form>
                <div id="cidResult" class="mt-3"></div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> 说明
            </div>
            <div class="card-body">
                <h6><i class="bi bi-check-circle text-success"></i> 自动递归Pin</h6>
                <p class="small text-muted">
                    所有导入的内容都会自动进行递归Pin操作，确保所有链接的内容都被固定，防止被垃圾回收（GC）清理。
                </p>
                
                <h6 class="mt-3"><i class="bi bi-shield-check text-primary"></i> 数据安全</h6>
                <p class="small text-muted">
                    Pin后的内容将永久保存在本地IPFS节点中，不会因为垃圾回收而丢失。
                </p>
                
                <h6 class="mt-3"><i class="bi bi-lightning text-warning"></i> 快速访问</h6>
                <p class="small text-muted">
                    上传成功后，可以通过IPFS网关或在Pin管理中查看和访问您的内容。
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    // File drag and drop
    const dropZone = document.getElementById('fileDropZone');
    const fileInput = document.getElementById('fileInput');
    const selectedFile = document.getElementById('selectedFile');
    const fileName = document.getElementById('fileName');
    
    dropZone.addEventListener('click', function() {
        fileInput.click();
    });
    
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });
    
    dropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        dropZone.classList.remove('dragover');
    });
    
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        
        if (e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            showSelectedFile();
        }
    });
    
    fileInput.addEventListener('change', function() {
        showSelectedFile();
    });
    
    function showSelectedFile() {
        if (fileInput.files.length > 0) {
            fileName.textContent = fileInput.files[0].name;
            selectedFile.classList.remove('d-none');
        }
    }
    
    function clearFileSelection() {
        fileInput.value = '';
        selectedFile.classList.add('d-none');
    }
    
    // Handle form submission results
    document.body.addEventListener('htmx:afterRequest', function(event) {
        if (event.detail.successful) {
            try {
                const response = JSON.parse(event.detail.xhr.response);
                if (response.success) {
                    showToast('操作成功！内容已Pin到IPFS', 'success');
                    
                    if (response.cid) {
                        const resultHtml = `
                            <div class="alert alert-success">
                                <h6><i class="bi bi-check-circle"></i> 上传成功！</h6>
                                <p class="mb-2"><strong>CID:</strong></p>
                                <div class="cid-badge mb-2">${response.cid}</div>
                                ${response.gateway_url ? `
                                    <a href="${response.gateway_url}" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                                        <i class="bi bi-box-arrow-up-right"></i> 在网关中查看
                                    </a>
                                ` : ''}
                            </div>
                        `;
                        event.detail.target.innerHTML = resultHtml;
                    }
                    
                    // Clear form
                    if (event.detail.target.id === 'uploadResult') {
                        clearFileSelection();
                    }
                } else if (response.error) {
                    showToast('错误: ' + response.error, 'danger');
                    event.detail.target.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> ${response.error}
                        </div>
                    `;
                }
            } catch (e) {
                console.error('Error parsing response:', e);
            }
        }
    });
</script>
