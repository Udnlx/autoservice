<?php namespace ProcessWire;

if (isset($_SESSION['operator'])) {
    $operator = $_SESSION['operator'];
} else {
    $operator = 'no_operator';
}

if ($operator == 'no_operator') {
    $session->redirect('/login/');
}

if (!function_exists('orderParseDate')) {
    function orderParseDate($value) {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        foreach (['d-m-Y', 'd.m.Y', 'Y-m-d', 'd/m/Y'] as $fmt) {
            $dt = \DateTime::createFromFormat('!' . $fmt, $value);
            if ($dt && $dt->format($fmt) === $value) {
                return $dt->getTimestamp();
            }
        }

        return null;
    }
}

// --- СПРАВОЧНИК ТИПОВ РАБОТ ---
$work_type_labels = [
    1 => 'Антикор',
    2 => 'Фильтры',
    3 => 'Турбины',
];

// --- КАРТА ВСЕХ РАБОТ ИЗ СПРАВОЧНИКА: [название => тип] ---
$works_catalog = [];
foreach ($pages->find("template=work_item, include=all") as $workPage) {
    $wtObj = $workPage->work_type;
    $wt = 0;

    if ($wtObj) {
        if (is_object($wtObj) && isset($wtObj->id)) {
            $wt = (int)$wtObj->id;
        } elseif ($wtObj instanceof \ProcessWire\WireArray && count($wtObj)) {
            $wt = (int)$wtObj->first()->id;
        }
    }

    if ($wt > 0) {
        $works_catalog[(string)$workPage->title] = $wt;
    }
}

// --- ВХОДНЫЕ ПАРАМЕТРЫ (те же, что на экранной странице) ---
$search_client    = trim($input->get->text('search_client'));
$search_auto      = trim($input->get->text('search_auto'));
$search_date_from = trim($input->get->text('search_date_from'));
$search_date_to   = trim($input->get->text('search_date_to'));

$all_statuses = ['Новая', 'В работе', 'Ожидает запчасти', 'Завершена', 'Отменена'];

$active_statuses = [];
foreach ((array)$input->get->array('status_filter') as $st) {
    $st = (string)$st;
    if (in_array($st, $all_statuses, true)) {
        $active_statuses[] = $st;
    }
}

$active_types = [];
foreach ((array)$input->get->array('type_filter') as $tp) {
    $tp = (int)$tp;
    if (isset($work_type_labels[$tp])) {
        $active_types[] = $tp;
    }
}

$all_types_active = (count($active_types) === count($work_type_labels));

// --- ПЕРИОД ---
$date_from_ts = orderParseDate($search_date_from);
$date_to_ts   = orderParseDate($search_date_to);

// Без корректного периода выгружать нечего — возвращаем на страницу отчёта
if ($date_from_ts === null || $date_to_ts === null || $date_from_ts > $date_to_ts) {
    $session->redirect('/otchet-po-zakaz-nariadam/');
}

$date_to_ts = strtotime('tomorrow', $date_to_ts) - 1;

// --- ВЫБОРКА ---
$orders_page = $pages->get('template=orders');
$selector = "template=order_item, parent=$orders_page";

if ($search_client !== '') {
    $value  = $sanitizer->selectorValue($search_client);
    $digits = $sanitizer->digits($search_client);

    $ownerIds = [];
    foreach ($pages->find("template=owner, title%=$value") as $ownerItem) {
        $ownerIds[$ownerItem->id] = true;
    }
    foreach ($pages->find("template=owner, phone%=$value") as $ownerItem) {
        $ownerIds[$ownerItem->id] = true;
    }
    if (strlen($digits) >= 3) {
        foreach ($pages->find("template=owner, phone%=$digits") as $ownerItem) {
            $ownerIds[$ownerItem->id] = true;
        }
    }

    if (count($ownerIds)) {
        $selector .= ", client=" . implode('|', array_keys($ownerIds));
    } else {
        $selector .= ", id=-1";
    }
}

if ($search_auto !== '') {
    $selector .= ", auto%=" . $sanitizer->selectorValue($search_auto);
}

if (empty($active_statuses) || empty($active_types)) {
    $selector .= ", id=-1";
}

$found = $pages->find($selector);

$rows = [];

foreach ($found as $candidate) {

    $row_status = (string)$candidate->status_order;
    if (!in_array($row_status, $active_statuses, true)) {
        continue;
    }

    $row_ts = orderParseDate($candidate->date_order);
    if ($row_ts === null || $row_ts < $date_from_ts || $row_ts > $date_to_ts) {
        continue;
    }

    $row_type_ids = [];
    foreach ($candidate->works as $workRow) {
        $wname = (string)$workRow->work;
        if ($wname === '' || !isset($works_catalog[$wname])) {
            continue;
        }
        $row_type_ids[(int)$works_catalog[$wname]] = true;
    }

    if (!$all_types_active) {
        $type_ok = false;
        foreach (array_keys($row_type_ids) as $tid) {
            if (in_array($tid, $active_types, true)) {
                $type_ok = true;
                break;
            }
        }
        if (!$type_ok) {
            continue;
        }
    }

    // --- ПРИБЫЛЬ ПО ЗАПЧАСТЯМ ---
    $parts_profit  = 0;
    $parts_skipped = 0;

    foreach ($candidate->autoparts as $partRow) {
        $pid = (int)$partRow->part_id;

        if ($pid <= 0) {
            $parts_skipped++;
            continue;
        }

        $partRef = $pages->get("id=$pid, template=part");
        if (!$partRef->id) {
            $parts_skipped++;
            continue;
        }

        $purchase = (int)$partRef->getUnformatted('part_price_purchase');
        $sale     = (int)$partRow->price;
        $qty      = max(1, (int)$partRow->qty);

        $parts_profit += ($sale - $purchase) * $qty;
    }

    $rows[] = [
        'page'          => $candidate,
        'ts'            => $row_ts,
        'status'        => $row_status,
        'types'         => array_keys($row_type_ids),
        'parts_profit'  => $parts_profit,
        'parts_skipped' => $parts_skipped,
        'profit_total'  => (int)$candidate->cost_works + $parts_profit
    ];
}

usort($rows, function ($a, $b) {
    if ($a['ts'] === $b['ts']) {
        return $b['page']->id - $a['page']->id;
    }
    return $b['ts'] - $a['ts'];
});

// --- ИТОГИ ---
$works_sum_all        = 0;
$parts_cost_sum_all   = 0;
$total_sum            = 0;
$parts_profit_sum_all = 0;
$profit_sum_all       = 0;

foreach ($rows as $row) {
    $works_sum_all        += (int)$row['page']->cost_works;
    $parts_cost_sum_all   += (int)$row['page']->cost_autoparts;
    $total_sum            += (int)$row['page']->cost_total;
    $parts_profit_sum_all += (int)$row['parts_profit'];
    $profit_sum_all       += (int)$row['profit_total'];
}

// --- ОТДАЧА CSV ---
$filename = 'otchet-zakaz-naryady-'
          . date('d-m-Y', $date_from_ts) . '_'
          . date('d-m-Y', $date_to_ts) . '.csv';

// Чистим буфер: любой лишний байт до заголовков ломает файл
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

// BOM — без него русский Excel покажет кириллицу кракозябрами
fwrite($out, "\xEF\xBB\xBF");

$statuses_text = implode(', ', $active_statuses);

$types_text = [];
foreach ($active_types as $tid) {
    $types_text[] = $work_type_labels[$tid];
}
$types_text = implode(', ', $types_text);

// --- ШАПКА ПАРАМЕТРОВ ---
fputcsv($out, ['Отчёт по заказ-нарядам'], ';');
fputcsv($out, ['Период', date('d-m-Y', $date_from_ts) . ' — ' . date('d-m-Y', $date_to_ts)], ';');
fputcsv($out, ['Статусы', $statuses_text !== '' ? $statuses_text : '—'], ';');
fputcsv($out, ['Типы работ', $types_text !== '' ? $types_text : '—'], ';');

if ($search_client !== '') {
    fputcsv($out, ['Клиент', $search_client], ';');
}
if ($search_auto !== '') {
    fputcsv($out, ['Автомобиль', $search_auto], ';');
}

fputcsv($out, ['Найдено заявок', count($rows)], ';');
fputcsv($out, ['Сформирован', date('d-m-Y H:i') . ', оператор ' . $operator], ';');
fputcsv($out, [], ';');

// --- ЗАГОЛОВКИ ТАБЛИЦЫ ---
fputcsv($out, [
    '№',
    'Дата',
    'Заказ',
    'Клиент',
    'Автомобиль',
    'Типы работ',
    'Работы',
    'Запчасти',
    'Итого',
    'Запчасти прибыль',
    'Итого прибыль',
    'Статус'
], ';');

// --- СТРОКИ ---
$n = 0;
foreach ($rows as $row) {
    $rowPage = $row['page'];
    $n++;

    $types_list = [];
    foreach ($row['types'] as $tid) {
        $types_list[] = $work_type_labels[$tid];
    }

    fputcsv($out, [
        $n,
        (string)$rowPage->date_order,
        (string)$rowPage->title,
        $rowPage->client->id ? (string)$rowPage->client->title : '—',
        (string)$rowPage->auto,
        implode(', ', $types_list),
        (int)$rowPage->cost_works,
        (int)$rowPage->cost_autoparts,
        (int)$rowPage->cost_total,
        (int)$row['parts_profit'],
        (int)$row['profit_total'],
        $row['status']
    ], ';');
}

// --- ИТОГО ---
fputcsv($out, [], ';');
fputcsv($out, [
    '',
    '',
    '',
    '',
    '',
    'ИТОГО',
    $works_sum_all,
    $parts_cost_sum_all,
    $total_sum,
    $parts_profit_sum_all,
    $profit_sum_all,
    ''
], ';');

fclose($out);
exit;