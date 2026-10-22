<?php

class BertanzinSentinel {
    private $apiUrl;
    private $apiKey;

    public function __construct($apiUrl, $apiKey) {
        
        $this->apiUrl = rtrim($apiUrl, '/'); 
        $this->apiKey = $apiKey;
    }

    private function request($endpoint, $dados = null) {
        $url = $this->apiUrl . '?endpoint=' . $endpoint;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];

        if ($dados !== null) {
            $payload = json_encode($dados);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            $headers[] = 'Content-Length: ' . strlen($payload);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $resposta = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'http_status' => $http_status,
            'dados' => json_decode($resposta, true) ?: $resposta
        ];
    }

    public function heartbeat() {
        return $this->request('heartbeat');
    }

    public function reportMetric($nomeMetrica, $valorMetrica) {
        return $this->request('metrics', [
            'nome_metrica' => $nomeMetrica,
            'valor_metrica' => $valorMetrica
        ]);
    }

    public function reportEvent($nivel, $mensagem, $payload = null) {
        $dados = [
            'nivel' => $nivel,
            'mensagem' => $mensagem
        ];
        if ($payload !== null) {
            $dados['payload'] = $payload;
        }
        return $this->request('events', $dados);
    }
}