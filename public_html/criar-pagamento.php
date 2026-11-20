<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function reply(int $status, array $body): void {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}

function httpsHost(string $url): ?string {
    $parts = parse_url($url);
    if ($parts === false || !filter_var($url, FILTER_VALIDATE_URL)
        || strtolower($parts['scheme'] ?? '') !== 'https'
        || isset($parts['user']) || isset($parts['pass'])) {
        return null;
    }
    return strtolower($parts['host'] ?? '');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    reply(405, ['error' => 'Método inválido.']);
}
if (strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') {
    reply(415, ['error' => 'Formato inválido.']);
}

$token = getenv('GATEWAY_API_TOKEN');
$endpoint = 'https://cryptopayr.com/api/v1/payment/create';
$checkoutHost = strtolower(trim(getenv('GATEWAY_CHECKOUT_HOST') ?: 'cryptopayr.com'));
$webhookUrl = getenv('GATEWAY_WEBHOOK_URL');
if (!$token || !function_exists('curl_init') || ($webhookUrl && !httpsHost($webhookUrl))) {
    reply(503, ['error' => 'Pagamento indisponível.']);
}

if (!session_start([
    'use_strict_mode' => 1,
    'use_only_cookies' => 1,
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
])) {
    reply(503, ['error' => 'Pagamento indisponível.']);
}

$order = $_SESSION['payment'] ?? [];
if (($order['expires'] ?? 0) <= time()) {
    $order = ['expires' => time() + 300];
    $_SESSION['payment'] = $order;
}

if (isset($order['url']) && httpsHost($order['url']) === $checkoutHost) {
    session_write_close();
    reply(200, ['checkout_url' => $order['url']]);
}

$payload = ['amount' => 49, 'currency' => 'USD'];
if ($webhookUrl) {
    $payload['webhook_url'] = $webhookUrl;
}

$request = curl_init($endpoint);
curl_setopt_array($request, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 12,
    CURLOPT_FOLLOWLOCATION => false,
]);
$body = curl_exec($request);
$code = curl_getinfo($request, CURLINFO_HTTP_CODE);
curl_close($request);

$data = is_string($body) ? json_decode($body, true) : null;
$url = $data['checkout_url'] ?? null;
if ($code < 200 || $code >= 300 || !is_string($url) || httpsHost($url) !== $checkoutHost) {
    session_write_close();
    reply(502, ['error' => 'O gateway não retornou um link válido.']);
}

$_SESSION['payment']['url'] = $url;
session_write_close();
reply(200, ['checkout_url' => $url]);
