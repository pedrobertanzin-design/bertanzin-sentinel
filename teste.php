<?php
require_once 'Sentinel.php';

// 1. Inicializa o SDK
$url = 'http://localhost/bertanzin-sentinel/api.php';
$token = 'gerir-token-secreto-123';
$sentinel = new BertanzinSentinel($url, $token);

// 2. Testando o Heartbeat
echo "<h3>1. Heartbeat</h3>";
$resHeartbeat = $sentinel->heartbeat();
echo "<pre>" . print_r($resHeartbeat, true) . "</pre>";

// 3. Testando uma Métrica
echo "<h3>2. Métrica</h3>";
$resMetrica = $sentinel->reportMetric('cpu_usage_percent', 45.2);
echo "<pre>" . print_r($resMetrica, true) . "</pre>";

// 4. Testando um Evento
echo "<h3>3. Evento</h3>";
$resEvento = $sentinel->reportEvent('error', 'Falha ao conectar no banco do GERIR', ['tabela' => 'usuarios']);
echo "<pre>" . print_r($resEvento, true) . "</pre>";