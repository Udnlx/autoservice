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

$input_raw = file_get_contents('php://input');
$input_data = json_decode($input_raw, true);

$vin = isset($input_data['vin']) ? trim((string)$input_data['vin']) : '';

// Базовая валидация VIN
if (strlen($vin) !== 17) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'invalid_vin']);
    exit;
}

$token = $pages->get("template=api_point")->token;

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL            => 'https://api.avtovincod.ru/brief?vin=' . urlencode($vin),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
    CURLOPT_TIMEOUT        => 60,
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

// Достаём нужные поля из ответа avtovincod.ru
$mark_model = isset($data['response']['mark_model']) ? (string)$data['response']['mark_model'] : '';
$year       = isset($data['response']['year']) ? (string)$data['response']['year'] : '';
$balance    = isset($data['balance']) ? (string)$data['balance'] : '';

// Разделяем mark_model на brand и model
// Формат: "RЕNАULТ SАNDЕRО SТЕРWАУ 5SRLVG"
$brand = '';
$model = '';

if (!empty($mark_model)) {
    $parts = explode(' ', $mark_model, 2);
    $brand = $parts[0];
    $model = isset($parts[1]) ? $parts[1] : '';
}

header('Content-Type: application/json');
echo json_encode([
    'brand'   => $brand,
    'model'   => $model,
    'year'    => $year,
    'balance' => $balance,
]);
exit;