<?php
$env = parse_ini_file(__DIR__ . '/.env');

try {
    $pdo = new PDO("mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4", $env['DB_USER'], $env['DB_PASS']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}

$stmtSistemas = $pdo->query("SELECT * FROM sistemas ORDER BY nome ASC");
$sistemas = $stmtSistemas->fetchAll(PDO::FETCH_ASSOC);

$stmtEventos = $pdo->query("
    SELECT e.*, s.nome AS sistema_nome 
    FROM eventos e 
    JOIN sistemas s ON e.sistema_id = s.id 
    ORDER BY e.registrado_em DESC LIMIT 12
");
$eventos = $stmtEventos->fetchAll(PDO::FETCH_ASSOC);

$stmtMetricas = $pdo->query("
    SELECT m.*, s.nome AS sistema_nome 
    FROM metricas m 
    JOIN sistemas s ON m.sistema_id = s.id 
    ORDER BY m.registrado_em DESC LIMIT 8
");
$metricas = $stmtMetricas->fetchAll(PDO::FETCH_ASSOC);

// Contadores para os Quick Stats
$totalSistemas = count($sistemas);
$sistemasOnline = count(array_filter($sistemas, function($s) { return $s['ativo'] == 1; }));
$qtdErros = count(array_filter($eventos, function($e) { return $e['nivel'] == 'error'; }));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Bertanzin Sentinel</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        body { 
            background-color: #f8fafc; 
            font-family: 'Inter', sans-serif;
            color: #334155;
        }
        
        .fw-medium { font-weight: 500 !important; }
        .fw-semibold { font-weight: 600 !important; }
        .fw-bold { font-weight: 700 !important; }
        .fw-extrabold { font-weight: 800 !important; }
        
        /* Cabeçalho Hero */
        .main-header {
            background: linear-gradient(135deg, #ef4444 0%, #991b1b 100%);
            border-radius: 20px;
            padding: 2.5rem 3rem;
            color: white;
            box-shadow: 0 10px 25px rgba(239, 68, 68, 0.15);
            margin-bottom: 2rem;
            transition: box-shadow 0.4s ease-in-out;
        }
        .main-header:hover {
            box-shadow: 0 0 35px rgba(239, 68, 68, 0.35);
        }
        
        /* Quick Stats */
        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid #f1f5f9;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        /* Cards de Dados */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            margin-bottom: 2rem;
            border: 1px solid #f8fafc;
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #f1f5f9;
            padding: 1.25rem 1.75rem;
            border-top-left-radius: 16px !important;
            border-top-right-radius: 16px !important;
        }
        
        /* Listas e Tabelas */
        .list-group-item { padding: 1rem 1.5rem; border-color: #f1f5f9; }
        .table > :not(caption) > * > * { padding: 1rem 1.5rem; vertical-align: middle; border-bottom-color: #f1f5f9; }
        .table thead th { font-size: 0.75rem; text-transform: uppercase; color: #64748b !important; letter-spacing: 0.05em; background: #f8fafc; }
        
        /* Elementos UI */
        .status-dot { height: 10px; width: 10px; background-color: #10b981; border-radius: 50%; display: inline-block; box-shadow: 0 0 10px rgba(16, 185, 129, 0.4); }
        .status-dot.offline { background-color: #ef4444; box-shadow: 0 0 10px rgba(239, 68, 68, 0.4); }
        .badge { padding: 0.5em 0.85em; font-weight: 600; }
        
        .log-payload { 
            font-size: 0.85rem; 
            background: #1e293b; 
            color: #34d399; 
            padding: 1.25rem; 
            border-radius: 12px; 
            font-family: monospace;
            border: 1px solid #334155;
        }
        .btn-custom { border-radius: 50px; padding: 0.6rem 1.5rem; transition: all 0.2s; }
    </style>
</head>
<body>

<div class="container-fluid px-4 mt-4 mb-5">
    
    <!-- Cabeçalho Principal -->
    <div class="main-header d-flex flex-column flex-md-row justify-content-between align-items-md-center">
        <div>
            <h1 class="mb-1 fw-extrabold fs-2">
                <i class="bi bi-shield-check text-white me-2"></i>Bertanzin Sentinel
            </h1>
            <p class="mb-0 text-white-50 fs-6">Centro Integrado de Monitoramento e Telemetria</p>
        </div>
        <div class="mt-4 mt-md-0 d-flex gap-3">
            <button onclick="location.reload();" class="btn btn-light fw-semibold btn-custom shadow-sm text-danger">
                <i class="bi bi-arrow-clockwise me-2"></i>Atualizar
            </button>
            <a href="novo_sistema.php" class="btn btn-outline-light fw-semibold btn-custom">
                <i class="bi bi-plus-lg me-2"></i>Novo Sistema
            </a>
        </div>
    </div>

    <!-- Quick Stats Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-hdd-network"></i></div>
                <div>
                    <h4 class="fw-bold mb-0 text-dark"><?= $sistemasOnline ?> <span class="fs-6 fw-medium text-muted">/ <?= $totalSistemas ?></span></h4>
                    <small class="text-muted fw-medium text-uppercase letter-spacing">Sistemas Online</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-graph-up-arrow"></i></div>
                <div>
                    <h4 class="fw-bold mb-0 text-dark"><?= count($metricas) ?></h4>
                    <small class="text-muted fw-medium text-uppercase letter-spacing">Métricas Coletadas</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-exclamation-triangle"></i></div>
                <div>
                    <h4 class="fw-bold mb-0 text-dark"><?= $qtdErros ?></h4>
                    <small class="text-muted fw-medium text-uppercase letter-spacing">Erros Críticos</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Dados Principais -->
    <div class="row">
        <!-- Coluna da Esquerda (Sistemas e Métricas) -->
        <div class="col-lg-4">
            
            <!-- Sistemas -->
            <div class="card">
                <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-hdd-network text-primary me-2"></i> Monitoramento</span>
                </div>
                <div class="list-group list-group-flush rounded-bottom">
                    <?php foreach($sistemas as $sis): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($sis['nome']) ?></h6>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small fw-medium"><?= $sis['ativo'] ? 'Online' : 'Offline' ?></span>
                                <span class="status-dot <?= $sis['ativo'] ? '' : 'offline' ?>"></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Métricas -->
            <div class="card">
                <div class="card-header fw-bold">
                    <i class="bi bi-graph-up-arrow text-info me-2"></i> Últimas Métricas
                </div>
                <div class="list-group list-group-flush rounded-bottom">
                    <?php foreach($metricas as $metrica): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="fw-semibold text-dark fs-6"><?= htmlspecialchars($metrica['nome_metrica']) ?></span>
                                <span class="badge bg-light text-primary border rounded-pill"><?= htmlspecialchars($metrica['valor_metrica']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <small class="text-muted fw-medium"><i class="bi bi-box me-1"></i><?= htmlspecialchars($metrica['sistema_nome']) ?></small>
                                <small class="text-muted fw-medium"><?= date('H:i', strtotime($metrica['registrado_em'])) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <!-- Coluna da Direita (Logs) -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header fw-bold">
                    <i class="bi bi-terminal text-dark me-2"></i> Feed de Eventos e Logs
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Data/Hora</th>
                                    <th>Sistema</th>
                                    <th>Status</th>
                                    <th>Mensagem</th>
                                    <th class="pe-4 text-end">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($eventos as $evento): ?>
                                    <tr>
                                        <td class="ps-4 text-nowrap text-muted small fw-medium">
                                            <?= date('d/m/Y H:i', strtotime($evento['registrado_em'])) ?>
                                        </td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($evento['sistema_nome']) ?></td>
                                        <td>
                                            <?php 
                                                $bg = 'bg-secondary';
                                                $icon = 'bi-info-circle';
                                                if($evento['nivel'] === 'error') { $bg = 'bg-danger'; $icon = 'bi-x-circle'; }
                                                if($evento['nivel'] === 'warning') { $bg = 'bg-warning text-dark'; $icon = 'bi-exclamation-triangle'; }
                                                if($evento['nivel'] === 'info') { $bg = 'bg-info text-dark'; $icon = 'bi-info-circle'; }
                                            ?>
                                            <span class="badge <?= $bg ?> rounded-pill px-3"><i class="bi <?= $icon ?> me-1"></i><?= strtoupper(htmlspecialchars($evento['nivel'])) ?></span>
                                        </td>
                                        <td class="text-secondary fw-medium"><?= htmlspecialchars($evento['mensagem']) ?></td>
                                        <td class="pe-4 text-end">
                                            <?php if($evento['payload']): ?>
                                                <button class="btn btn-sm btn-light border rounded-circle shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#payload-<?= $evento['id'] ?>">
                                                    <i class="bi bi-code-slash text-secondary"></i>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php if($evento['payload']): ?>
                                    <tr>
                                        <td colspan="5" class="p-0 border-0">
                                            <div class="collapse" id="payload-<?= $evento['id'] ?>">
                                                <div class="p-3 bg-light border-bottom">
                                                    <pre class="log-payload m-0"><?= htmlspecialchars(json_encode(json_decode($evento['payload']), JSON_PRETTY_PRINT)) ?></pre>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>