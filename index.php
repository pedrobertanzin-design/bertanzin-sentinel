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
    ORDER BY e.registrado_em DESC LIMIT 15
");
$eventos = $stmtEventos->fetchAll(PDO::FETCH_ASSOC);

$stmtMetricas = $pdo->query("
    SELECT m.*, s.nome AS sistema_nome 
    FROM metricas m 
    JOIN sistemas s ON m.sistema_id = s.id 
    ORDER BY m.registrado_em DESC LIMIT 8
");
$metricas = $stmtMetricas->fetchAll(PDO::FETCH_ASSOC);

$sistemasAtivos = count(array_filter($sistemas, function($s) { return $s['ativo'] == 1; }));

$stmtErrosHoje = $pdo->query("SELECT COUNT(*) FROM eventos WHERE nivel = 'error' AND DATE(registrado_em) = CURDATE()");
$qtdErrosHoje = $stmtErrosHoje->fetchColumn();

$stmtMetricasHoje = $pdo->query("SELECT COUNT(*) FROM metricas WHERE DATE(registrado_em) = CURDATE()");
$qtdMetricasHoje = $stmtMetricasHoje->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bertanzin Sentinel - Telemetria</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            /* Design System estilo Apple */
            --color-bg: #F5F5F7; /* Cinza Apple padrão do macOS/iOS */
            --color-surface: #FFFFFF;
            --color-border: rgba(0, 0, 0, 0.05); /* Bordas super sutis */
            --color-text-main: #1D1D1F; /* Quase preto da Apple */
            --color-text-muted: #86868B;
            
            --color-error: #FF3B30; /* Red iOS */
            --color-warning: #FF9500; /* Orange iOS */
            --color-info: #007AFF; /* Blue iOS */
            --color-success: #34C759; /* Green iOS */

            /* Raios arredondados (squircle effect) */
            --radius-lg: 24px;
            --radius-md: 16px;
            --radius-sm: 10px;
        }

        body { 
            background-color: var(--color-bg); 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--color-text-main);
            -webkit-font-smoothing: antialiased;
            letter-spacing: -0.01em; /* Aperta levemente para imitar SF Pro */
        }
        
        .container-custom {
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 32px;
        }

        .text-main { color: var(--color-text-main); }
        .text-muted-custom { color: var(--color-text-muted); }
        .fw-medium { font-weight: 500; }
        .fw-semibold { font-weight: 600; }
        .fw-bold { font-weight: 700; }
        .fw-extrabold { font-weight: 800; }

        /* Cabeçalho Hero */
        .main-header {
            background: linear-gradient(135deg, #ef4444 0%, #991b1b 100%);
            border-radius: 28px; /* Mais arredondado */
            padding: 2.5rem 3.5rem;
            color: white;
            box-shadow: 0 14px 34px rgba(239, 68, 68, 0.2);
            margin: 32px 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: flex-start;
            border: 1px solid rgba(255,255,255,0.1); /* Efeito de luz na borda */
        }
        @media (min-width: 768px) {
            .main-header { flex-direction: row; align-items: center; }
        }
        
        .page-title { 
            font-size: 2.4rem; 
            letter-spacing: -0.05em; /* Título grande mais apertado (Apple style) */
            margin-bottom: 4px; 
        }
        .page-subtitle { font-size: 1.15rem; color: rgba(255,255,255,0.8); margin: 0; font-weight: 400; }
        
        .btn-custom { 
            border-radius: 50px; 
            padding: 0.65rem 1.5rem; 
            font-weight: 600; 
            letter-spacing: -0.01em;
            border: none;
            backdrop-filter: blur(10px); /* Glassmorphism leve */
        }
        .btn-light-glass {
            background: rgba(255, 255, 255, 0.9);
            color: #991b1b;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .btn-outline-glass {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Cartões estilo Apple (UI Blocks) */
        .ui-block {
            background: var(--color-surface);
            border-radius: var(--radius-lg);
            padding: 28px;
            height: 100%;
            /* Sombra ultra difusa */
            box-shadow: 0 4px 24px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.02);
            border: 1px solid var(--color-border);
        }
        
        /* Indicadores */
        .indicator-value { 
            font-size: 2.5rem; 
            font-weight: 700; 
            letter-spacing: -0.04em; 
            line-height: 1.1; 
            margin-bottom: 6px; 
        }
        .indicator-label { 
            font-size: 0.85rem; 
            font-weight: 600; 
            color: var(--color-text-muted); 
            text-transform: uppercase; 
            letter-spacing: 0.04em; /* Letras pequenas separadas */
        }
        .indicator-critical { color: var(--color-error); }

        /* Cabeçalhos de Seção */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .section-title { 
            font-size: 1.2rem; 
            font-weight: 700; 
            letter-spacing: -0.02em; 
            margin: 0; 
        }

        /* Tabelas Clean */
        .table-custom th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--color-text-muted);
            border-bottom: 1px solid var(--color-border);
            padding-bottom: 12px;
            font-weight: 600;
        }
        .table-custom td {
            padding: 16px 0;
            vertical-align: middle;
            border-bottom: 1px solid var(--color-border);
            font-size: 0.9rem;
        }
        /* Remove borda da última linha */
        .table-custom tr:last-child td { border-bottom: none; }

        /* Badges e Status */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.02em;
        }
        /* Cores de fundo bem suaves para os badges (estilo iOS) */
        .badge-error { background: rgba(255, 59, 48, 0.12); color: var(--color-error); }
        .badge-warning { background: rgba(255, 149, 0, 0.12); color: var(--color-warning); }
        .badge-info { background: rgba(0, 122, 255, 0.12); color: var(--color-info); }
        
        .system-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid var(--color-border);
        }
        .system-row:last-child { border-bottom: none; padding-bottom: 0; }
        
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 12px;
        }
        .dot-active { background-color: var(--color-success); box-shadow: 0 0 10px rgba(52, 199, 89, 0.4); }
        .dot-inactive { background-color: var(--color-error); }

        .log-payload {
            background: #1C1C1E; /* Quase preto iOS */
            color: #34C759; /* Verde terminal limpo */
            padding: 16px;
            border-radius: var(--radius-md);
            font-family: SFMono-Regular, Consolas, monospace;
            font-size: 0.85rem;
            margin-top: 8px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        
        .btn-icon-soft {
            background: rgba(0,0,0,0.03);
            border: none;
            border-radius: 50px;
            padding: 6px 12px;
            color: var(--color-text-main);
            transition: background 0.2s;
        }
        .btn-icon-soft:hover { background: rgba(0,0,0,0.08); }
    </style>
</head>
<body>

<div class="container-custom">
    
    <!-- Cabeçalho Hero -->
    <header class="main-header">
        <div>
            <h1 class="page-title fw-bold"><i class="bi bi-shield-check text-white me-2"></i>Bertanzin Sentinel</h1>
            <p class="page-subtitle">Visão geral do ambiente de telemetria</p>
        </div>
        <div class="d-flex gap-3 mt-4 mt-md-0">
            <button onclick="location.reload();" class="btn btn-custom btn-light-glass">
                <i class="bi bi-arrow-clockwise me-1"></i>Atualizar Painel
            </button>
            <a href="novo_sistema.php" class="btn btn-custom btn-outline-glass">
                <i class="bi bi-plus-lg me-1"></i>Novo Sistema
            </a>
        </div>
    </header>

    <!-- Indicadores -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="ui-block">
                <div class="indicator-label mb-2">Sistemas Habilitados</div>
                <div class="indicator-value text-main"><?= $sistemasAtivos ?> <span class="fs-4 text-muted-custom fw-medium">/ <?= count($sistemas) ?></span></div>
                <div class="text-muted-custom mt-2" style="font-size: 0.8rem;">Total de cadastros ativos no banco</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="ui-block">
                <div class="indicator-label mb-2">Eventos (Hoje)</div>
                <div class="indicator-value text-main"><?= $qtdMetricasHoje ?></div>
                <div class="text-muted-custom mt-2" style="font-size: 0.8rem;">Métricas coletadas nas últimas 24h</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="ui-block">
                <div class="indicator-label mb-2">Erros Críticos (Hoje)</div>
                <div class="indicator-value indicator-critical"><?= $qtdErrosHoje ?></div>
                <div class="text-muted-custom mt-2" style="font-size: 0.8rem;">Ocorrências que exigem atenção imediata</div>
            </div>
        </div>
    </div>

    <!-- Grid Principal (8 + 4) -->
    <div class="row g-4 mb-5">
        
        <!-- Feed de Eventos -->
        <div class="col-lg-8">
            <div class="ui-block">
                <div class="section-header">
                    <h2 class="section-title">Eventos Recentes</h2>
                    <span class="text-muted-custom fw-medium" style="font-size: 0.8rem;">Últimas 15 ocorrências</span>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th class="ps-2">Horário</th>
                                <th>Sistema</th>
                                <th>Status</th>
                                <th>Ocorrência</th>
                                <th class="text-end pe-2">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($eventos as $evento): ?>
                                <tr>
                                    <td class="text-muted-custom ps-2"><?= date('H:i - d/m', strtotime($evento['registrado_em'])) ?></td>
                                    <td class="fw-bold text-main"><?= htmlspecialchars($evento['sistema_nome']) ?></td>
                                    <td>
                                        <?php 
                                            $badgeClass = 'badge-info';
                                            if($evento['nivel'] === 'error') $badgeClass = 'badge-error';
                                            if($evento['nivel'] === 'warning') $badgeClass = 'badge-warning';
                                        ?>
                                        <span class="status-badge <?= $badgeClass ?>">
                                            <?= strtoupper(htmlspecialchars($evento['nivel'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-muted-custom fw-medium"><?= htmlspecialchars($evento['mensagem']) ?></td>
                                    <td class="text-end pe-2">
                                        <?php if($evento['payload']): ?>
                                            <button class="btn-icon-soft" type="button" data-bs-toggle="collapse" data-bs-target="#payload-<?= $evento['id'] ?>">
                                                <i class="bi bi-chevron-down"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted-custom">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php if($evento['payload']): ?>
                                <tr>
                                    <td colspan="5" class="p-0 border-0">
                                        <div class="collapse" id="payload-<?= $evento['id'] ?>">
                                            <pre class="log-payload mb-4 mx-2"><?= htmlspecialchars(json_encode(json_decode($evento['payload']), JSON_PRETTY_PRINT)) ?></pre>
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

        <!-- Coluna de Contexto -->
        <div class="col-lg-4">
            
            <div class="ui-block mb-4">
                <div class="section-header">
                    <h2 class="section-title">Sistemas</h2>
                </div>
                <div>
                    <?php foreach($sistemas as $sis): ?>
                        <div class="system-row">
                            <div class="d-flex align-items-center">
                                <span class="status-dot <?= $sis['ativo'] ? 'dot-active' : 'dot-inactive' ?>"></span>
                                <span class="fw-semibold text-main" style="font-size: 0.95rem;"><?= htmlspecialchars($sis['nome']) ?></span>
                            </div>
                            <span class="text-muted-custom fw-medium" style="font-size: 0.8rem;">
                                <?= $sis['ativo'] ? 'Habilitado' : 'Desativado' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="ui-block">
                <div class="section-header">
                    <h2 class="section-title">Métricas Isoladas</h2>
                </div>
                <div>
                    <?php foreach($metricas as $metrica): ?>
                        <div class="system-row flex-column align-items-start">
                            <div class="d-flex justify-content-between w-100 mb-1">
                                <span class="fw-semibold text-main" style="font-size: 0.85rem;"><?= htmlspecialchars($metrica['nome_metrica']) ?></span>
                                <span class="fw-bold" style="font-size: 0.9rem; color: var(--color-text-main);"><?= htmlspecialchars($metrica['valor_metrica']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between w-100 text-muted-custom" style="font-size: 0.8rem;">
                                <span><?= htmlspecialchars($metrica['sistema_nome']) ?></span>
                                <span><?= date('H:i', strtotime($metrica['registrado_em'])) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>