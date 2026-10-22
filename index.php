<?php

// Carrega as credenciais do arquivo .env
$env = parse_ini_file(__DIR__ . '/.env');

$host = $env['DB_HOST'];
$db   = $env['DB_NAME'];
$user = $env['DB_USER'];
$pass = $env['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}

// 1. Busca os sistemas cadastrados
$stmtSistemas = $pdo->query("SELECT * FROM sistemas ORDER BY nome ASC");
$sistemas = $stmtSistemas->fetchAll(PDO::FETCH_ASSOC);

// 2. Busca os últimos 10 eventos/logs
$stmtEventos = $pdo->query("
    SELECT e.*, s.nome AS sistema_nome 
    FROM eventos e 
    JOIN sistemas s ON e.sistema_id = s.id 
    ORDER BY e.registrado_em DESC LIMIT 10
");
$eventos = $stmtEventos->fetchAll(PDO::FETCH_ASSOC);

// 3. Busca as últimas 10 métricas
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
    <title>Bertanzin Sentinel - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .navbar-brand { font-weight: bold; letter-spacing: 1px; }
        .card-header { font-weight: 600; }
        .log-payload { font-size: 0.85em; background: #212529; color: #198754; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">🛡️ Bertanzin Sentinel</a>
        <div class="navbar-nav">
            <a class="nav-link active" href="index.php">Dashboard</a>
            <a class="nav-link" href="novo_sistema.php">Cadastrar Sistema</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="row">
        <!-- Coluna da Esquerda: Sistemas e Métricas -->
        <div class="col-lg-4 mb-4">
            
            <!-- Card de Sistemas -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    Sistemas Monitorados
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach($sistemas as $sis): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($sis['nome']) ?>
                            <?php if($sis['ativo']): ?>
                                <span class="badge bg-success rounded-pill">Ativo</span>
                            <?php else: ?>
                                <span class="badge bg-danger rounded-pill">Inativo</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Card de Métricas Recentes -->
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    Últimas Métricas
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach($metricas as $metrica): ?>
                        <li class="list-group-item">
                            <div class="fw-bold"><?= htmlspecialchars($metrica['nome_metrica']) ?></div>
                            <span class="text-muted small"><?= htmlspecialchars($metrica['sistema_nome']) ?></span> - 
                            <span class="badge bg-info text-dark"><?= htmlspecialchars($metrica['valor_metrica']) ?></span>
                            <br><small class="text-muted"><?= date('d/m/Y H:i:s', strtotime($metrica['registrado_em'])) ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

        </div>

        <!-- Coluna da Direita: Log de Eventos -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    Feed de Eventos e Alertas
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Sistema</th>
                                    <th>Nível</th>
                                    <th>Mensagem</th>
                                    <th>Payload (Detalhes)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($eventos as $evento): ?>
                                    <tr>
                                        <td class="text-nowrap text-muted small">
                                            <?= date('d/m/Y H:i:s', strtotime($evento['registrado_em'])) ?>
                                        </td>
                                        <td class="fw-bold"><?= htmlspecialchars($evento['sistema_nome']) ?></td>
                                        <td>
                                            <?php 
                                                $badgeClass = 'bg-secondary';
                                                if($evento['nivel'] === 'error') $badgeClass = 'bg-danger';
                                                if($evento['nivel'] === 'warning') $badgeClass = 'bg-warning text-dark';
                                                if($evento['nivel'] === 'info') $badgeClass = 'bg-primary';
                                            ?>
                                            <span class="badge <?= $badgeClass ?>"><?= strtoupper(htmlspecialchars($evento['nivel'])) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($evento['mensagem']) ?></td>
                                        <td>
                                            <?php if($evento['payload']): ?>
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#payload-<?= $evento['id'] ?>">
                                                    Ver Dados
                                                </button>
                                                <div class="collapse mt-2" id="payload-<?= $evento['id'] ?>">
                                                    <pre class="log-payload m-0"><?= htmlspecialchars(json_encode(json_decode($evento['payload']), JSON_PRETTY_PRINT)) ?></pre>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
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