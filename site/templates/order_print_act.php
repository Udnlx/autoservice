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
        <h1 class="uk-heading-hero uk-text-center">Печать акта</h1>
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
            <h1 class="uk-heading-hero uk-text-center">Акт</h1>
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
            <h1 class="uk-heading-hero uk-text-center">Акт</h1>
            <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
                <h3 class="uk-card-title uk-text-center">Заказ не найден</h3>
                <a class="uk-margin-small uk-button uk-button-default" href="/">На главную</a>
            </div>
        </div>';
        return;
    }

    // ---------- Сумма прописью (рубли) ----------
    if (!function_exists('num2wordsRub')) {
        function num2wordsRub($amount) {
            $nul = 'ноль';
            $ten = [
                ['','один','два','три','четыре','пять','шесть','семь','восемь','девять'],
                ['','одна','две','три','четыре','пять','шесть','семь','восемь','девять'],
            ];
            $a20 = ['десять','одиннадцать','двенадцать','тринадцать','четырнадцать','пятнадцать','шестнадцать','семнадцать','восемнадцать','девятнадцать'];
            $tens = [2=>'двадцать','тридцать','сорок','пятьдесят','шестьдесят','семьдесят','восемьдесят','девяносто'];
            $hundred = ['','сто','двести','триста','четыреста','пятьсот','шестьсот','семьсот','восемьсот','девятьсот'];
            $unit = [
                ['копейка','копейки','копеек', 1],
                ['рубль','рубля','рублей', 0],
                ['тысяча','тысячи','тысяч', 1],
                ['миллион','миллиона','миллионов', 0],
                ['миллиард','милиарда','миллиардов', 0],
            ];

            list($rub, $kop) = explode('.', sprintf("%015.2f", (float)$amount));
            $out = [];
            if ((int)$rub > 0) {
                foreach (str_split($rub, 3) as $uk => $v) {
                    if (!(int)$v) continue;
                    $uk = sizeof($unit) - $uk - 1;
                    $gender = $unit[$uk][3];
                    list($i1,$i2,$i3) = array_map('intval', str_split($v, 1));
                    $out[] = $hundred[$i1];
                    if ($i2 > 1) {
                        $out[] = $tens[$i2] . ' ' . $ten[$gender][$i3];
                    } else {
                        $out[] = $i2 > 0 ? $a20[$i3] : $ten[$gender][$i3];
                    }
                    if ($uk > 1) {
                        $out[] = morph($v, $unit[$uk][0], $unit[$uk][1], $unit[$uk][2]);
                    }
                }
            } else {
                $out[] = $nul;
            }
            $out[] = morph((int)$rub, $unit[1][0], $unit[1][1], $unit[1][2]);
            $out[] = $kop . ' ' . morph($kop, $unit[0][0], $unit[0][1], $unit[0][2]);
            return trim(preg_replace('/ {2,}/', ' ', join(' ', $out)));
        }
    }
    if (!function_exists('morph')) {
        function morph($n, $f1, $f2, $f5) {
            $n = abs((int)$n) % 100;
            if ($n > 10 && $n < 20) return $f5;
            $n = $n % 10;
            if ($n > 1 && $n < 5) return $f2;
            if ($n == 1) return $f1;
            return $f5;
        }
    }

    // ---------- Исключаемые запчасти (та же логика, что в заказ-наряде) ----------
    $skip_parts = [];
    $skip_raw   = $input->get->string('skip');
    if ($skip_raw !== '') {
        foreach (explode(',', $skip_raw) as $idx) {
            $idx = trim($idx);
            if (ctype_digit($idx)) {
                $skip_parts[(int)$idx] = true;
            }
        }
    }

    // Номер заказа из title (для «Акт №…» и для «Основание»)
    $order_number = $order_id;
    if (preg_match('/(\d+)/u', $orderPage->title, $m)) {
        $order_number = $m[1];
    }

    $order_date = $orderPage->date_order;

    // Работы
    $works = [];
    if (count($orderPage->works)) {
        foreach ($orderPage->works as $item) {
            $works[] = [
                'name'  => $item->work,
                'price' => (float)$item->price,
                'qty'   => max(1, (int)$item->qty),
            ];
        }
    }

    // Запчасти (с пропуском по skip)
    $parts = [];
    if (count($orderPage->autoparts)) {
        $idx = 0;
        foreach ($orderPage->autoparts as $item) {
            if (!isset($skip_parts[$idx])) {
                $parts[] = [
                    'name'  => $item->autopart,
                    'price' => (float)$item->price,
                    'qty'   => max(1, (int)$item->qty),
                ];
            }
            $idx++;
        }
    }

    // ---------- Реквизиты компании ----------
    $company_name    = '-';
    $company_address = '-';
    $company_inn     = '-';
    $company_phone   = '-';

    $company = $input->get->string('company');
    if ($company == 'turbina_plus') {
        $company_name    = 'Турбина Плюс';
        $company_address = 'Люберцы, Октябрьский проспект 259 стр 1';
        $company_inn     = '502770491281';
        $company_phone   = '8 985 816-10-10';
    }
    if ($company == 'antikor_rf') {
        $company_name    = 'Антикор РФ';
        $company_address = 'Люберцы, Октябрьский проспект 259 стр 1';
        $company_inn     = '502770491281';
        $company_phone   = '8 985 816-10-10';
    }

    // ---------- Клиент ----------
    $client_display = '—';
    $client_phone   = '';
    $client_email   = '';
    $client_address = '';
    $client_company = '';
    $client_inn     = '';
    $client_found   = false;

    $ownerPage = $orderPage->client;
    if ($ownerPage && $ownerPage->id) {
        $client_found   = true;
        $client_display = $ownerPage->title;
        $client_phone   = $ownerPage->phone;
        $client_email   = $ownerPage->email;
        $client_address = $ownerPage->owner_address;
        $client_company = $ownerPage->owner_company;
        $client_inn     = $ownerPage->owner_inn;
    }

    // ---------- Строка «Основание» — заказ-наряд ----------
    $osnovanie = 'Заказ-наряд №' . $order_number . ' от ' . $order_date;

    // ---------- HTML документа ----------
    $content = '
    <style type="text/css">
    * {
        font-family: "DejaVu Sans", sans-serif;
        box-sizing: border-box;
    }
    body { font-size: 11px; color: #000; }

    .doc-title {
        font-size: 15px;
        font-weight: bold;
        margin: 0 0 12px 0;
    }

    .party-block {
        margin: 6px 0;
        font-size: 11px;
        line-height: 1.4;
    }
    .party-label {
        font-weight: bold;
        margin: 0;
    }
    .party-value {
        margin: 0 0 2px 0;
    }

    .works-table {
        width: 100%;
        border-collapse: collapse;
        margin: 10px 0 6px 0;
        font-size: 11px;
    }
    .works-table th,
    .works-table td {
        border: 1px solid #000;
        padding: 4px 6px;
        text-align: left;
    }
    .works-table th {
        background: #eee;
        font-weight: bold;
        text-align: center;
    }
    .works-table td.num   { text-align: center; width: 35px; }
    .works-table td.qty   { text-align: center; width: 55px; }
    .works-table td.unit  { text-align: center; width: 45px; }
    .works-table td.price { text-align: right;  width: 90px; }
    .works-table td.sum   { text-align: right;  width: 100px; }

    .works-table tr.total-row td {
        font-weight: bold;
        text-align: right;
    }
    .works-table tr.tax-row td {
        text-align: right;
    }

    .totals-text {
        margin: 6px 0 2px 0;
        font-size: 11px;
    }
    .words {
        font-style: italic;
        margin: 2px 0 10px 0;
    }

    .agreement {
        font-size: 10px;
        text-align: justify;
        margin: 14px 0 20px 0;
        line-height: 1.4;
    }

    .signatures {
        width: 100%;
        border-collapse: collapse;
        margin-top: 30px;
        font-size: 11px;
    }
    .signatures td {
        padding: 20px 4px 2px 4px;
        border-top: 1px solid #000;
        text-align: center;
        width: 50%;
    }
    .signatures td.mp {
        border: none;
        padding: 10px 4px 0 4px;
        font-weight: bold;
    }
    </style>
    ';

    // Заголовок — номер от заказ-наряда, дата сегодняшняя (дата формирования акта)
    $content .= '<p class="doc-title">Акт №' . htmlspecialchars($order_number, ENT_QUOTES, 'UTF-8') . ' от ' . htmlspecialchars($today, ENT_QUOTES, 'UTF-8') . 'г.</p>';

    // Исполнитель
    $content .= '<div class="party-block">';
    $content .= '<p class="party-label">Исполнитель:</p>';
    $content .= '<p class="party-value">' . htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8')
             . ' ' . htmlspecialchars($company_address, ENT_QUOTES, 'UTF-8')
             . ' ИНН ' . htmlspecialchars($company_inn, ENT_QUOTES, 'UTF-8')
             . ' тел.: ' . htmlspecialchars($company_phone, ENT_QUOTES, 'UTF-8')
             . '</p>';
    $content .= '</div>';

    // Заказчик
    $content .= '<div class="party-block">';
    $content .= '<p class="party-label">Заказчик:</p>';
    $content .= '<p class="party-value">' . htmlspecialchars($client_display, ENT_QUOTES, 'UTF-8');
    if ($client_found) {
        $extras = [];
        if ($client_company) $extras[] = htmlspecialchars($client_company, ENT_QUOTES, 'UTF-8');
        if ($client_inn)     $extras[] = 'ИНН ' . htmlspecialchars($client_inn, ENT_QUOTES, 'UTF-8');
        if ($client_phone)   $extras[] = 'тел.: ' . htmlspecialchars($client_phone, ENT_QUOTES, 'UTF-8');
        if ($client_address) $extras[] = htmlspecialchars($client_address, ENT_QUOTES, 'UTF-8');
        if (!empty($extras)) {
            $content .= ' ' . implode(', ', $extras);
        }
    }
    $content .= '</p>';
    $content .= '</div>';

    // Основание
    $content .= '<div class="party-block">';
    $content .= '<p class="party-label">Основание:</p>';
    $content .= '<p class="party-value">' . htmlspecialchars($osnovanie, ENT_QUOTES, 'UTF-8') . '</p>';
    $content .= '</div>';

    // ---------- Общая таблица: работы, потом запчасти, сквозная нумерация ----------
    $content .= '<table class="works-table">
        <tr>
            <th style="width: 35px;">№</th>
            <th>Наименование</th>
            <th style="width: 55px;">Кол-во</th>
            <th style="width: 45px;">Ед.</th>
            <th style="width: 90px;">Цена</th>
            <th style="width: 100px;">Сумма</th>
        </tr>';

    $total_price = 0;
    $items_count = 0;
    $row_num = 1;

    // Сначала работы
    foreach ($works as $w) {
        $price   = (float)$w['price'];
        $qty     = (int)$w['qty'];
        $row_sum = $price * $qty;
        $total_price += $row_sum;
        $items_count++;

        $content .= '<tr>
            <td class="num">' . $row_num . '</td>
            <td>' . htmlspecialchars($w['name'], ENT_QUOTES, 'UTF-8') . '</td>
            <td class="qty">' . $qty . '</td>
            <td class="unit">шт.</td>
            <td class="price">' . number_format($price, 2, '.', ' ') . '</td>
            <td class="sum">' . number_format($row_sum, 2, '.', ' ') . '</td>
        </tr>';
        $row_num++;
    }

    // Потом запчасти — продолжаем ту же нумерацию
    foreach ($parts as $p) {
        $price   = (float)$p['price'];
        $qty     = (int)$p['qty'];
        $row_sum = $price * $qty;
        $total_price += $row_sum;
        $items_count++;

        $content .= '<tr>
            <td class="num">' . $row_num . '</td>
            <td>' . htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') . '</td>
            <td class="qty">' . $qty . '</td>
            <td class="unit">шт.</td>
            <td class="price">' . number_format($price, 2, '.', ' ') . '</td>
            <td class="sum">' . number_format($row_sum, 2, '.', ' ') . '</td>
        </tr>';
        $row_num++;
    }

    // Если вообще пусто — одна строка-пустышка
    if ($items_count === 0) {
        $content .= '<tr><td colspan="6" style="text-align:center; color:#666;">—</td></tr>';
    }

    // ИТОГО
    $content .= '<tr class="total-row">
        <td colspan="5">ИТОГО:</td>
        <td class="sum">' . number_format($total_price, 2, '.', ' ') . '</td>
    </tr>';

    // Без налога (НДС) — константа
    $content .= '<tr class="tax-row">
        <td colspan="5">Без налога (НДС):</td>
        <td class="sum">—</td>
    </tr>';

    $content .= '</table>';

    // Итоговые строки под таблицей
    $total_words = num2wordsRub($total_price);

    $content .= '<p class="totals-text">Всего наименований ' . (int)$items_count
             . ', на сумму ' . number_format($total_price, 2, '.', ' ') . '</p>';
    $content .= '<p class="words">(' . number_format($total_price, 2, '.', ' ') . ') ' . htmlspecialchars($total_words, ENT_QUOTES, 'UTF-8') . '</p>';

    // Соглашение
    $content .= '<p class="agreement">
    Вышеперечисленные услуги выполнены полностью и в срок. Запчасти установлены. Заказчик претензий по объему, качеству, срокам оказания услуг и количеству запчастей не имеет.
    </p>';

    // Подписи с МП
    $content .= '
    <table class="signatures">
        <tr>
            <td>ИСПОЛНИТЕЛЬ</td>
            <td>ЗАКАЗЧИК</td>
        </tr>
        <tr>
            <td class="mp">МП</td>
            <td class="mp">МП</td>
        </tr>
    </table>
    ';

    // ---------- Генерация PDF ----------
    include_once __DIR__ . '/dompdf/autoload.inc.php';
    $dompdf = new Dompdf\Dompdf();
    $dompdf->set_option('isRemoteEnabled', TRUE);
    $dompdf->set_option('defaultFont', 'DejaVu Sans');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->loadHtml($content, 'UTF-8');
    $dompdf->render();

    // Вывод файла в браузер (раскомментировать когда надо):
    // $dompdf->stream('Акт-' . $order_number . '.pdf');
?>

<div id="content" style="max-width: 700px;">
    <h1 class="uk-heading-hero uk-text-center">Печать акта</h1>
    
    <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
        <p class="operator uk-position-absolute">Оператор: <?php echo $operator; ?></p>
        <h4 class="uk-margin-remove">Акт успешно сформирован для печати</h4>
        <h4 class="uk-margin-remove">ID заказ-наряда: <span style="font-weight: 700;"><?php echo $order_id; ?></span></h4>
        <a class="uk-margin-small uk-button uk-button-default" href="/">Перейти на главную</a>
    </div>
</div>

<?php echo $content; ?>

<?php   
}
?>