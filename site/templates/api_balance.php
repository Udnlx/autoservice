<?php namespace ProcessWire;

// Только POST, только залогиненные операторы
if (!isset($_SESSION['operator']) || $_SESSION['operator'] === 'no_operator') {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

$token = $pages->get("template=api_point")->token;

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL            => 'https://api.avtovincod.ru/balance',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
    CURLOPT_TIMEOUT        => 10,
]);

$response = curl_exec($curl);
$curl_error = curl_error($curl);
curl_close($curl);

if ($response === false) {
    http_response_code(502);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'curl_error', 'detail' => $curl_error]);
    exit;
}

$data = json_decode($response, true);

// Проверяем что запрос прошёл
if (!isset($data['success']) || $data['success'] != 1) {
    http_response_code(502);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'api_error', 'detail' => 'API вернул ошибку']);
    exit;
}

// Достаём баланс из ответа (он на верхнем уровне)
$balance = isset($data['balance']) ? (float)$data['balance'] : 0;

header('Content-Type: application/json');
echo json_encode(['balance' => $balance]);
exit;