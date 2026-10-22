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
    ORDER BY e.registrado_em DESC LIMIT 10
");
$eventos = $stmtEventos->fetchAll(PDO::FETCH_ASSOC);

$stmtMetricas = $pdo->query("
    SELECT m.*, s.nome AS sistema_nome 
    FROM metricas m 
    JOIN sistemas s ON m.sistema_id = s.id 
    ORDER BY m.registrado_em DESC LIMIT 10
");
$metricas = $stmtMetricas->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Bertanzin Sentinel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { 
            background-color: #f4f7f6; 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        /* Novo Cabeçalho Grande e Arredondado */
        .main-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 24px;
            padding: 2.5rem 3rem;
            color: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        .main-header h1 {
            font-weight: 800;
            letter-spacing: -0.5px;
            font-size: 2.2rem;
        }
        .main-header p {
            color: #94a3b8;
            font-size: 1.1rem;
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            transition: transform 0.2s ease;
            margin-bottom: 1.5rem;
        }
        .card:hover { transform: translateY(-3px); }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #f0f0f0;
            font-weight: 600;
            border-top-left-radius: 20px !important;
            border-top-right-radius: 20px !important;
            padding: 1.25rem 1.5rem;
        }
        
        .status-dot {
            height: 12px;
            width: 12px;
            background-color: #10b981;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.6);
        }
        .status-dot.offline {
            background-color: #ef4444;
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.6);
        }

        .log-payload { 
            font-size: 0.85rem; 
            background: #1e293b; 
            color: #10b981; 
            padding: 15px; 
            border-radius: 12px; 
            border: 1px solid #334155;
        }
        
        .table > :not(caption) > * > * { padding: 1.2rem 1rem; vertical-align: middle; }
        .btn-custom { border-radius: 50px; font-weight: 600; padding: 0.6rem 1.5rem; }
    </style>
</head>
<body>

<div class="container-fluid px-4 mt-4">
    <!-- Cabeçalho Principal -->
    <div class="main-header d-flex flex-column flex-md-row justify-content-between align-items-md-center">
        <div>
            <h1 class="mb-1">
                <i class="bi bi-shield-check text-success me-2"></i>Bertanzin Sentinel
            </h1>
            <p class="mb-0">Centro Integrado de Monitoramento e Telemetria</p>
        </div>
        <div class="mt-4 mt-md-0 d-flex gap-3">
            <a href="index.php" class="btn btn-light btn-custom">
                <i class="bi bi-grid-1x2-fill me-2"></i>Dashboard
            </a>
            <a href="novo_sistema.php" class="btn btn-outline-light btn-custom">
                <i class="bi bi-plus-lg me-2"></i>Novo Sistema
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Coluna da Esquerda -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-hdd-network text-primary me-2"></i> Sistemas Conectados</span>
                    <span class="badge bg-primary rounded-pill"><?= count($sistemas) ?></span>
                </div>
                <div class="list-group list-group-flush rounded-bottom">
                    <?php foreach($sistemas as $sis): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <div>
                                <h6 class="mb-0 fw-bold"><?= htmlspecialchars($sis['nome']) ?></h6>
                                <small class="text-muted">Adicionado em <?= date('d/m/y', strtotime($sis['criado_em'])) ?></small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small fw-medium"><?= $sis['ativo'] ? 'Online' : 'Offline' ?></span>
                                <span class="status-dot <?= $sis['ativo'] ? '' : 'offline' ?>"></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="bi bi-graph-up-arrow text-info me-2"></i> Métricas Recentes
                </div>
                <div class="list-group list-group-flush rounded-bottom">
                    <?php foreach($metricas as $metrica): ?>
                        <div class="list-group-item py-3 px-4">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="fw-bold text-dark"><?= htmlspecialchars($metrica['nome_metrica']) ?></span>
                                <span class="badge bg-light text-primary border rounded-pill px-3"><?= htmlspecialchars($metrica['valor_metrica']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <small class="text-muted fw-medium"><i class="bi bi-box me-1"></i><?= htmlspecialchars($metrica['sistema_nome']) ?></small>
                                <small class="text-muted"><?= date('H:i - d/m', strtotime($metrica['registrado_em'])) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Coluna da Direita -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-terminal text-dark me-2"></i> Feed de Eventos e Logs</span>
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-arrow-clockwise me-1"></i>Atualizar</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 text-muted fw-semibold">Data/Hora</th>
                                    <th class="text-muted fw-semibold">Sistema</th>
                                    <th class="text-muted fw-semibold">Nível</th>
                                    <th class="text-muted fw-semibold">Mensagem</th>
                                    <th class="pe-4 text-end text-muted fw-semibold">Detalhes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($eventos as $evento): ?>
                                    <tr>
                                        <td class="ps-4 text-nowrap text-muted small fw-medium">
                                            <?= date('d/m/Y H:i:s', strtotime($evento['registrado_em'])) ?>
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
                                        <td class="text-secondary"><?= htmlspecialchars($evento['mensagem']) ?></td>
                                        <td class="pe-4 text-end">
                                            <?php if($evento['payload']): ?>
                                                <button class="btn btn-sm btn-light border rounded-circle" type="button" data-bs-toggle="collapse" data-bs-target="#payload-<?= $evento['id'] ?>" title="Ver Payload">
                                                    <i class="bi bi-braces"></i>
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
                                                <div class="p-4 bg-light border-bottom">
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