<?php

$today = date("d-m-Y");

error_reporting(E_ALL);
ini_set('display_errors', 'Off');

if(isset($_SESSION['operator'])){
    $operator = $_SESSION['operator'];
} else {
    $operator = 'no_operator';
}

if ($operator == 'no_operator') {
?>
    <div id="content" style="max-width: 700px;">
        <h1 class="uk-heading-hero uk-text-center">Печать дефектовки турбокомпрессора</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php
} else {

    $order_id = $input->get->int('idorder');

    if (!$order_id) {
        echo '<div id="content" style="max-width: 700px;">
            <h1 class="uk-heading-hero uk-text-center">Дефектовка турбокомпрессора</h1>
            <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
                <h3 class="uk-card-title uk-text-center">Не передан ID заказа</h3>
                <a class="uk-margin-small uk-button uk-button-default" href="/">На главную</a>
            </div>
        </div>';
        return;
    }

    $orderPage = $pages->get("id=$order_id, template=order_item");

    if (!$orderPage->id) {
        echo '<div id="content" style="max-width: 700px;">
            <h1 class="uk-heading-hero uk-text-center">Дефектовка турбокомпрессора</h1>
            <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
                <h3 class="uk-card-title uk-text-center">Заказ не найден</h3>
                <a class="uk-margin-small uk-button uk-button-default" href="/">На главную</a>
            </div>
        </div>';
        return;
    }

    // ---------- Номер заказ-наряда (для имени файла) ----------
    $order_number = $order_id;
    if (preg_match('/(\d+)/u', $orderPage->title, $m)) {
        $order_number = $m[1];
    }

    // ---------- Клиент ----------
    $client_display = '';
    $ownerPage = $orderPage->client;
    if ($ownerPage && $ownerPage->id) {
        $client_display = $ownerPage->title;
    }

    // ---------- Автомобиль (марка/модель/год) ----------
    $car_full    = $orderPage->auto;
    $car_display = $car_full;

    if (preg_match('/VIN:([^)]+)/u', $car_full, $vin_match)) {
        $vin_from_string = trim($vin_match[1]);
        $carPage = $pages->get("template=car, car_vin=" . $sanitizer->selectorValue($vin_from_string));
        if ($carPage->id) {
            $car_display = trim($carPage->car_brand . ' ' . $carPage->car_model . ' ' . $carPage->car_year);
        }
    }

    // ---------- Серийный номер турбины ----------
    $turbo_sn = trim((string)$orderPage->num_turbo);

    // ---------- Строки таблицы ----------
    // Колонки независимы: слева 14 неисправностей, справа 16 видов ремонта.
    $defects = [
        'Механические повреждения',
        'Клин клапана вестгейт',
        'износ втулки клапана вестгейт',
        'повреждение геометрии',
        'Клин геометрии',
        'неисправность актуатора',
        'неисправность сектора',
        'неисправность электромотора',
        'трещины горячей части',
        'повреждение холодной части',
        'разрушение холод. крыльчатки',
        'разрушение гор. крыльчатки',
        'течь картриджа',
        'сорвана резьба/болт'
    ];

    $repairs = [
        'замена картриджа',
        'разработка подвижности кл.',
        'замена клапана вестгейт',
        'замена геометрии',
        'замена актуатора',
        'замена сектора',
        'замена электромотора',
        'регулировка актуатора',
        'замена горячей части',
        'замена холодной части',
        'шлифовка холод. части',
        'чистка ультразвук',
        'востановление резьбовых соед.',
        'замена чашки геометрии',
        'Замена шнека',
        'Замена байпаса'
    ];

    $rows_count = max(count($defects), count($repairs));

    // ---------- HTML документа ----------
    $content = '
    <style type="text/css">
    * {
        font-family: "DejaVu Sans", sans-serif;
        box-sizing: border-box;
    }
    body { font-size: 10px; color: #000; }

    .doc-title {
        font-size: 15px;
        font-weight: bold;
        text-align: center;
        margin: 0 0 10px 0;
    }

    .head-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }
    .head-table td {
        border: 1px solid #000;
        padding: 5px 6px;
        font-size: 10px;
        vertical-align: middle;
        height: 22px;
    }
    .head-label {
        width: 42%;
        font-weight: bold;
    }

    .def-table {
        width: 100%;
        border-collapse: collapse;
    }
    .def-table td, .def-table th {
        border: 1px solid #000;
        padding: 4px 5px;
        font-size: 9.5px;
        vertical-align: middle;
        height: 20px;
    }
    .def-table th {
        font-weight: bold;
        text-align: center;
        font-size: 10px;
    }
	.col-chk    { width: 5%; text-align: center; }
    .col-defect { width: 32%; }
    .col-repair { width: 32%; }
    .col-sn     { width: 13%; }
    .col-price  { width: 13%; }

    .chk {
        display: inline-block;
        width: 10px;
        height: 10px;
        border: 1px solid #000;
        vertical-align: middle;
    }

    .foot-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }
    .foot-table td {
        border: 1px solid #000;
        padding: 6px;
        font-size: 10px;
        height: 26px;
        vertical-align: middle;
    }
    .foot-label { font-weight: bold; }
    </style>
    ';

    $content .= '<p class="doc-title">Дефектовка турбокомпрессора</p>';

    // ---------- ШАПКА ----------
    $content .= '<table class="head-table">';
    $content .= '<tr>
        <td class="head-label">Дата:</td>
        <td>' . htmlspecialchars($today, ENT_QUOTES, 'UTF-8') . '</td>
    </tr>';
    $content .= '<tr>
        <td class="head-label">Автомобиль:</td>
        <td>' . ($car_display !== '' ? htmlspecialchars($car_display, ENT_QUOTES, 'UTF-8') : '&nbsp;') . '</td>
    </tr>';
    $content .= '<tr>
        <td class="head-label">Заказчик:</td>
        <td>' . ($client_display !== '' ? htmlspecialchars($client_display, ENT_QUOTES, 'UTF-8') : '&nbsp;') . '</td>
    </tr>';
    $content .= '<tr>
        <td class="head-label">Каталог номер турбокомпрессора P/N:</td>
        <td>&nbsp;</td>
    </tr>';
    $content .= '<tr>
        <td class="head-label">Серийный номер турбокомпрессора S/N:</td>
        <td>' . ($turbo_sn !== '' ? htmlspecialchars($turbo_sn, ENT_QUOTES, 'UTF-8') : '&nbsp;') . '</td>
    </tr>';
    $content .= '</table>';

    // ---------- ОСНОВНАЯ ТАБЛИЦА ----------
    $content .= '<table class="def-table">';
    $content .= '<tr>
        <th colspan="2">Неисправности</th>
        <th colspan="2">Ремонт</th>
        <th class="col-sn">S/N</th>
        <th class="col-price">Цена</th>
    </tr>';

    for ($i = 0; $i < $rows_count; $i++) {
        $defect_text = $defects[$i] ?? '';
        $repair_text = $repairs[$i] ?? '';

        $content .= '<tr>';
        $content .= '<td class="col-chk">' . ($defect_text !== '' ? '<span class="chk"></span>' : '&nbsp;') . '</td>';
        $content .= '<td class="col-defect">' . ($defect_text !== '' ? htmlspecialchars($defect_text, ENT_QUOTES, 'UTF-8') : '&nbsp;') . '</td>';
        $content .= '<td class="col-chk">' . ($repair_text !== '' ? '<span class="chk"></span>' : '&nbsp;') . '</td>';
        $content .= '<td class="col-repair">' . ($repair_text !== '' ? htmlspecialchars($repair_text, ENT_QUOTES, 'UTF-8') : '&nbsp;') . '</td>';
        $content .= '<td class="col-sn">&nbsp;</td>';
        $content .= '<td class="col-price">&nbsp;</td>';
        $content .= '</tr>';
    }

    $content .= '</table>';

    // ---------- ПОДВАЛ ----------
    $content .= '<table class="foot-table">
        <tr>
            <td class="foot-label" style="width: 42%;">Курьер:</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td class="foot-label">Общая стоимость ремонта:</td>
            <td style="text-align: right;">руб.</td>
        </tr>
    </table>';

    // ---------- Генерация PDF ----------
    include_once __DIR__ . '/dompdf/autoload.inc.php';
    $dompdf = new Dompdf\Dompdf();
    $dompdf->set_option('isRemoteEnabled', TRUE);
    $dompdf->set_option('defaultFont', 'DejaVu Sans');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->loadHtml($content, 'UTF-8');
    $dompdf->render();

    // Вывод файла в браузер (раскомментировать когда надо):
    $dompdf->stream('Дефектовка-турбокомпрессора-' . $order_number . '.pdf');
?>

<div id="content" style="max-width: 700px;">
    <h1 class="uk-heading-hero uk-text-center">Печать дефектовки турбокомпрессора</h1>

    <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
        <p class="operator uk-position-absolute">Оператор: <?php echo $operator; ?></p>
        <h4 class="uk-margin-remove">Дефектовка турбокомпрессора успешно сформирована для печати</h4>
        <h4 class="uk-margin-remove">ID заказ-наряда: <span style="font-weight: 700;"><?php echo $order_id; ?></span></h4>
        <a class="uk-margin-small uk-button uk-button-default" href="/">Перейти на главную</a>
    </div>
</div>

<?php echo $content; ?>

<?php
}
?>