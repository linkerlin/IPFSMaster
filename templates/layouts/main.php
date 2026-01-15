<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPFS Master - 更好的IPFS客户端</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --ipfs-primary: #469ea2;
            --ipfs-secondary: #6acad1;
            --ipfs-dark: #083b54;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            margin: 20px auto;
            max-width: 1400px;
            overflow: hidden;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--ipfs-primary) 0%, var(--ipfs-secondary) 100%);
            padding: 1rem 2rem;
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: white !important;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 500;
            margin: 0 0.5rem;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            color: white !important;
            transform: translateY(-2px);
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            margin-bottom: 1.5rem;
        }
        
        .card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            transform: translateY(-5px);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--ipfs-primary) 0%, var(--ipfs-secondary) 100%);
            color: white;
            font-weight: bold;
            border: none;
            padding: 1rem 1.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--ipfs-primary) 0%, var(--ipfs-secondary) 100%);
            border: none;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(70, 158, 162, 0.4);
        }
        
        .stat-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-card.blue {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .stat-card.green {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        .stat-card.orange {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin: 0.5rem 0;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .cid-badge {
            background: #f8f9fa;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            word-break: break-all;
            border-left: 4px solid var(--ipfs-primary);
        }
        
        .upload-zone {
            border: 3px dashed var(--ipfs-primary);
            border-radius: 15px;
            padding: 3rem;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .upload-zone:hover {
            background: #e9ecef;
            border-color: var(--ipfs-secondary);
        }
        
        .upload-zone.dragover {
            background: rgba(70, 158, 162, 0.1);
            border-color: var(--ipfs-secondary);
        }
        
        .table-hover tbody tr:hover {
            background: rgba(70, 158, 162, 0.05);
        }
        
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }
        
        .content-area {
            padding: 2rem;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="/">
                    <i class="bi bi-hdd-network"></i> IPFS Master
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $_SERVER['REQUEST_URI'] === '/' ? 'active' : ''; ?>" href="/">
                                <i class="bi bi-speedometer2"></i> 仪表盘
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/upload') === 0 ? 'active' : ''; ?>" href="/upload">
                                <i class="bi bi-cloud-upload"></i> 上传
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/pins') === 0 ? 'active' : ''; ?>" href="/pins">
                                <i class="bi bi-pin-angle"></i> Pin管理
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/settings') === 0 ? 'active' : ''; ?>" href="/settings">
                                <i class="bi bi-gear"></i> 设置
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <div class="content-area">
            <?php echo $content; ?>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- htmx -->
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    
    <script>
        // Add fade-in animation to all cards
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.card').forEach(function(card, index) {
                setTimeout(function() {
                    card.classList.add('fade-in');
                }, index * 100);
            });
        });
        
        // Toast notifications
        function showToast(message, type = 'success') {
            const toastHtml = `
                <div class="toast align-items-center text-white bg-${type} border-0" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
                    <div class="d-flex">
                        <div class="toast-body">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            const toastEl = document.createElement('div');
            toastEl.innerHTML = toastHtml;
            document.body.appendChild(toastEl);
            const toast = new bootstrap.Toast(toastEl.querySelector('.toast'));
            toast.show();
            setTimeout(() => toastEl.remove(), 5000);
        }
        
        // htmx event handlers
        document.body.addEventListener('htmx:afterSwap', function(event) {
            // Re-apply fade-in animation after htmx swap
            event.detail.target.querySelectorAll('.card').forEach(function(card) {
                card.classList.add('fade-in');
            });
        });
    </script>
</body>
</html>
