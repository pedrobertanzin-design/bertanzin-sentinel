<?php
$url = 'http://localhost/bertanzin-sentinel/api.php?endpoint=metrics';
$token = 'gerir-token-secreto-123';

$payload = json_encode([
    'nome_metrica' => 'storage_used_bytes',
    'valor_metrica' => 2048.50
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json',
    'Content-Length: ' . strlen($payload)
]);

$resposta = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h3>Status HTTP: {$http_status}</h3>";
echo "<pre>" . print_r(json_decode($resposta, true), true) . "</pre>";