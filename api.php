<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

$host = 'localhost';
$db   = 'bertanzin_sentinel';
$user = 'root';
$pass = ''; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha na conexão com o banco de dados do Sentinel.']);
    exit;
}

function getBearerToken() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }
    return null;
}

$token = getBearerToken();

if (!$token) {
    http_response_code(401);
    echo json_encode(['erro' => 'Acesso negado. Token de autorização ausente.']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, nome FROM sistemas WHERE api_key = ? AND ativo = 1");
$stmt->execute([$token]);
$sistema = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sistema) {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado. Token inválido ou sistema desativado.']);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';

// Rota: POST /heartbeat
if ($metodo === 'POST' && $endpoint === 'heartbeat') {
    http_response_code(200);
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Conexão estabelecida com sucesso! Olá, ' . $sistema['nome'],
        'sistema_id' => $sistema['id']
    ]);
    exit;
}

// Rota: POST /metrics
if ($metodo === 'POST' && $endpoint === 'metrics') {
    $dados = json_decode(file_get_contents('php://input'), true);

    if (!isset($dados['nome_metrica']) || !isset($dados['valor_metrica'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'Parâmetros nome_metrica e valor_metrica são obrigatórios.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO metricas (sistema_id, nome_metrica, valor_metrica) VALUES (?, ?, ?)");
        $stmt->execute([
            $sistema['id'], 
            $dados['nome_metrica'], 
            $dados['valor_metrica']
        ]);

        http_response_code(201);
        echo json_encode(['sucesso' => true, 'mensagem' => 'Métrica registrada com sucesso.']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao salvar métrica no banco.']);
    }
    exit;
}

// Rota: POST /events
if ($metodo === 'POST' && $endpoint === 'events') {
    $dados = json_decode(file_get_contents('php://input'), true);

    if (!isset($dados['nivel']) || !isset($dados['mensagem'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'Parâmetros nivel e mensagem são obrigatórios.']);
        exit;
    }

    // O payload é opcional e deve ser convertido em string JSON para salvar no MySQL
    $payload = isset($dados['payload']) ? json_encode($dados['payload']) : null;

    try {
        $stmt = $pdo->prepare("INSERT INTO eventos (sistema_id, nivel, mensagem, payload) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $sistema['id'], 
            $dados['nivel'], 
            $dados['mensagem'],
            $payload
        ]);

        http_response_code(201);
        echo json_encode(['sucesso' => true, 'mensagem' => 'Evento registrado com sucesso.']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao salvar evento no banco.']);
    }
    exit;
}

http_response_code(404);
echo json_encode(['erro' => 'Endpoint não encontrado.']);