<?php
$env = parse_ini_file(__DIR__ . '/.env');

try {
    $pdo = new PDO("mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4", $env['DB_USER'], $env['DB_PASS']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

$mensagem = '';
$novaKey = '';

// Processa o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'])) {
    $nome = trim($_POST['nome']);
    
    if (!empty($nome)) {
        // Gera uma API Key segura de 32 caracteres com um prefixo personalizado
        $novaKey = 'brtz_' . bin2hex(random_bytes(16));
        
        try {
            $stmt = $pdo->prepare("INSERT INTO sistemas (nome, api_key, ativo) VALUES (?, ?, 1)");
            $stmt->execute([$nome, $novaKey]);
            $mensagem = "Sistema/Empresa cadastrado com sucesso!";
        } catch (PDOException $e) {
            $mensagem = "Erro ao cadastrar. Tente outro nome.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Sistema - Bertanzin Sentinel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">🛡️ Bertanzin Sentinel</a>
        <div class="navbar-nav">
            <a class="nav-link" href="index.php">Dashboard</a>
            <a class="nav-link active" href="novo_sistema.php">Cadastrar Sistema</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white fw-bold">
                    Cadastrar Nova Empresa / Sistema
                </div>
                <div class="card-body">
                    
                    <?php if ($mensagem && $novaKey): ?>
                        <div class="alert alert-success">
                            <?= $mensagem ?><br><br>
                            <strong>Copie a API Key gerada:</strong><br>
                            <div class="input-group mt-2">
                                <input type="text" class="form-control bg-white" value="<?= $novaKey ?>" readonly id="apiKeyInput">
                                <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('apiKeyInput').value); alert('Copiado!')">Copiar</button>
                            </div>
                            <small class="text-danger mt-2 d-block">Guarde esta chave. Você precisará dela para configurar o SDK no sistema cliente.</small>
                        </div>
                    <?php elseif ($mensagem): ?>
                        <div class="alert alert-warning"><?= $mensagem ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome da Empresa ou Sistema</label>
                            <input type="text" class="form-control" id="nome" name="nome" placeholder="Ex: GERIR, Cliente X, DDS..." required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Gerar API Key e Cadastrar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>