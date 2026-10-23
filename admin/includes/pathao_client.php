<?php

class PathaoClient {

    private string $baseUrl;
    private array  $creds;
    private string $tokenFile;

    public function __construct(array $settings) {
        $this->creds = [
            'client_id'     => $settings['pathao_client_id']     ?? '',
            'client_secret' => $settings['pathao_client_secret'] ?? '',
        ];
        $this->baseUrl   = !empty($settings['pathao_sandbox'])
            ? 'https://courier-api-sandbox.pathao.com'
            : 'https://api-hermes.pathao.com';
        $this->tokenFile = dirname(__DIR__, 2) . '/.pathao_token_cache';
    }

    public function isConfigured(): bool {
        return !empty($this->creds['client_id']) && !empty($this->creds['client_secret']);
    }

    // ── Token management ──────────────────────────────────────────────────────

    private function getToken(): string {
        if (file_exists($this->tokenFile)) {
            $cache = json_decode(file_get_contents($this->tokenFile), true) ?? [];
            if (!empty($cache['access_token']) && ($cache['expires_at'] ?? 0) > time() + 60) {
                return $cache['access_token'];
            }
        }

        // Official endpoint: POST /aladdin/api/v1/external/login with {client_id, client_secret} JSON
        $resp = $this->jsonPost('aladdin/api/v1/external/login', $this->creds);

        if (empty($resp['access_token'])) {
            $msg = $resp['message'] ?? $resp['error_description'] ?? $resp['error'] ?? json_encode($resp);
            throw new RuntimeException('Pathao auth failed: ' . $msg);
        }

        file_put_contents($this->tokenFile, json_encode([
            'access_token' => $resp['access_token'],
            'expires_at'   => time() + (int)($resp['expires_in'] ?? 3600),
        ]));

        return $resp['access_token'];
    }

    // ── HTTP helpers ──────────────────────────────────────────────────────────

    private function jsonPost(string $path, array $body, ?string $token = null): array {
        $url     = rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if ($token !== null) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) throw new RuntimeException('cURL error: ' . $err);

        return json_decode($raw, true) ?? ['_raw' => $raw];
    }

    private function jsonGet(string $path): array {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Authorization: Bearer ' . $this->getToken(),
        ]);

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) throw new RuntimeException('cURL error: ' . $err);

        return json_decode($raw, true) ?? ['_raw' => $raw];
    }

    // ── Public API ────────────────────────────────────────────────────────────

    public function getCities(): array {
        $r = $this->jsonGet('aladdin/api/v1/countries/1/city-list');
        return $r['data']['data'] ?? $r['data'] ?? [];
    }

    public function getZones(int $cityId): array {
        $r = $this->jsonGet("aladdin/api/v1/cities/{$cityId}/zone-list");
        return $r['data']['data'] ?? $r['data'] ?? [];
    }

    public function getAreas(int $zoneId): array {
        $r = $this->jsonGet("aladdin/api/v1/zones/{$zoneId}/area-list");
        return $r['data']['data'] ?? $r['data'] ?? [];
    }

    public function getStores(): array {
        $r = $this->jsonGet('aladdin/api/v1/stores');
        return $r['data']['data'] ?? $r['data'] ?? [];
    }

    public function createOrder(array $data): array {
        return $this->jsonPost('aladdin/api/v1/orders', $data, $this->getToken());
    }
}
