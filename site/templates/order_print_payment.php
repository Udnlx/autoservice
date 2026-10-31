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
        <h1 class="uk-heading-hero uk-text-center">Печать счёта</h1>
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
            <h1 class="uk-heading-hero uk-text-center">Счёт</h1>
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
            <h1 class="uk-heading-hero uk-text-center">Счёт</h1>
            <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
                <h3 class="uk-card-title uk-text-center">Заказ не найден</h3>
                <a class="uk-margin-small uk-button uk-button-default" href="/">На главную</a>
            </div>
        </div>';
        return;
    }

    // ---------- Сумма прописью (та же функция, что в акте) ----------
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
                ['миллиард','миллиарда','миллиардов', 0],
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

    // ---------- Исключаемые запчасти (галочка «в печать») ----------
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

    // ---------- Номер документа ----------
    $order_number = $order_id;
    if (preg_match('/(\d+)/u', $orderPage->title, $m)) {
        $order_number = $m[1];
    }

    // ---------- Работы ----------
    $works = [];
    if (count($orderPage->works)) {
        foreach ($orderPage->works as $item) {
            $name = trim((string)$item->work);
            if ($name === '') continue;
            $works[] = [
                'name'  => $name,
                'price' => (float)$item->price,
                'qty'   => max(1, (int)$item->qty),
            ];
        }
    }

    // ---------- Запчасти (с учётом skip) ----------
    $parts = [];
    if (count($orderPage->autoparts)) {
        $idx = 0;
        foreach ($orderPage->autoparts as $item) {
            if (!isset($skip_parts[$idx])) {
                $name = trim((string)$item->autopart);
                if ($name !== '') {
                    $parts[] = [
                        'name'  => $name,
                        'price' => (float)$item->price,
                        'qty'   => max(1, (int)$item->qty),
                    ];
                }
            }
            $idx++;
        }
    }

    // ---------- Реквизиты компании ----------
    $company_name    = '-';
    $company_address = '-';
    $company_inn     = '-';
    $company_phone   = '-';
    $company_kpp     = '';

    $company = $input->get->string('company');
    if ($company == 'turbina_plus') {
        $company_name    = 'Турбина Плюс';
        $company_address = 'Люберцы Октябрьский проспект 259 стр 1';
        $company_inn     = '502770491281';
        $company_phone   = '8 985 816-10-10';
    }
    if ($company == 'antikor_rf') {
        $company_name    = 'Антикор РФ';
        $company_address = 'Люберцы Октябрьский проспект 259 стр 1';
        $company_inn     = '502770491281';
        $company_phone   = '8 985 816-10-10';
    }

    // ---------- Покупатель ----------
    $client_display = '';
    $ownerPage = $orderPage->client;
    if ($ownerPage && $ownerPage->id) {
        $client_display = $ownerPage->title;
    }
    if ($client_display === '') {
        $client_display = '—';
    }

    // ---------- QR в base64 ----------
    $qr_src = '';
    $qr_path = $config->paths->templates . 'imgpdf/order_payment_qr.png';
    if (is_file($qr_path)) {
        $qr_src = 'data:image/png;base64,' . base64_encode(file_get_contents($qr_path));
    }

    // ---------- HTML документа ----------
    $content = '
    <style type="text/css">
    @page { margin: 40px 45px 40px 45px; }
    * {
        font-family: "DejaVu Sans", sans-serif;
        box-sizing: border-box;
    }
    body { font-size: 12px; color: #000; line-height: 1.35; }
    p { margin: 0; }

    /* ---------- рамка с ИНН / КПП / Получателем ---------- */
    .req-box { width: 100%; border-collapse: collapse; }
    .req-box td {
        border: 1px solid #000;
        padding: 4px 7px;
        font-size: 12px;
        vertical-align: middle;
    }
    .req-box td.rb-head { text-align: center; }
    .req-box td.rb-body { padding: 4px 7px 14px 7px; }
    .rb-label { font-size: 12px; }
    .rb-name  { font-size: 13px; font-weight: bold; margin: 1px 0 2px 0; }

    /* ---------- заголовок + QR ---------- */
    .top { width: 100%; border-collapse: collapse; margin-top: 22px; }
    .top > tbody > tr > td { vertical-align: top; padding: 0; }
    .top td.top-left  { width: 78%; padding-right: 10px; }
    .top td.top-right { width: 22%; text-align: right; }
    .top td.top-right img { width: 86px; height: auto; }

    .inv-title {
        font-size: 20px;
        font-weight: bold;
        margin: 0 0 14px 0;
        white-space: nowrap;
    }

    /* ---------- Поставщик / Покупатель ---------- */
    .party { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .party td { padding: 0; font-size: 12px; vertical-align: top; }
    .party td.p-lbl { width: 15%; white-space: nowrap; }
    .party td.p-val { font-weight: bold; }

    /* ---------- таблица позиций ---------- */
    .items {
        width: 100%;
        border-collapse: collapse;
        margin-top: 6px;
        font-size: 12px;
    }
    .items th,
    .items td {
        border: 1px solid #000;
        padding: 4px 6px;
    }
    .items th {
        font-weight: bold;
        text-align: center;
    }
    .items td.c-num   { text-align: center; width: 6%; }
    .items td.c-name  { width: 58%; }
    .items td.c-qty   { text-align: center; width: 9%; }
    .items td.c-unit  { text-align: center; width: 6%; }
    .items td.c-price { text-align: right;  width: 9%; }
    .items td.c-sum   { text-align: right;  width: 12%; }

    /* ---------- итоги под таблицей ---------- */
    .totals { width: 100%; border-collapse: collapse; margin-top: 2px; }
    .totals td { padding: 1px 6px; font-size: 12px; }
    .totals td.t-lbl { text-align: right; }
    .totals td.t-val { text-align: right; width: 12%; }
    .totals tr.t-main td { font-size: 14px; font-weight: bold; padding-top: 3px; }

    /* ---------- нижний текст ---------- */
    .sum-line  { margin-top: 16px; font-size: 12px; }
    .sum-words { margin-top: 2px; font-size: 12px; font-weight: bold; }
    .cond      { margin-top: 8px; font-size: 12px; line-height: 1.5; }

    /* ---------- подписи ---------- */
    .signs { width: 100%; border-collapse: collapse; margin-top: 18px; font-size: 12px; }
    .signs td { padding: 0 0 16px 0; }
    </style>
    ';

    // ---------- Рамка: ИНН, КПП, Получатель, банк. реквизиты ----------
    $content .= '<table class="req-box">';

    $content .= '<tr>';
    $content .= '<td class="rb-head" style="width: 50%;">ИНН ' . htmlspecialchars($company_inn, ENT_QUOTES, 'UTF-8') . '</td>';
    $content .= '<td class="rb-head" style="width: 50%;">КПП ' . htmlspecialchars($company_kpp, ENT_QUOTES, 'UTF-8') . '</td>';
    $content .= '</tr>';

    $content .= '<tr>';
    $content .= '<td class="rb-body" colspan="2">';
    $content .= '<p class="rb-label">Получатель:</p>';
    $content .= '<p class="rb-name">' . htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8') . '</p>';
    $content .= '<p class="rb-label">Банковские реквизиты получателя:</p>';
    $content .= '</td>';
    $content .= '</tr>';

    $content .= '</table>';

    // ---------- Заголовок слева + QR справа ----------
    $content .= '<table class="top"><tr>';

    $content .= '<td class="top-left">';
    $content .= '<p class="inv-title">Счет на оплату №' . htmlspecialchars($order_number, ENT_QUOTES, 'UTF-8') . ' от ' . htmlspecialchars($today, ENT_QUOTES, 'UTF-8') . 'г.</p>';

    // Поставщик
    $content .= '<table class="party"><tr>';
    $content .= '<td class="p-lbl">Поставщик<br>(Исполнитель):</td>';
    $content .= '<td class="p-val">' . htmlspecialchars($company_name . ' ' . $company_address, ENT_QUOTES, 'UTF-8')
             . ' ИНН ' . htmlspecialchars($company_inn, ENT_QUOTES, 'UTF-8')
             . ' тел.:' . htmlspecialchars($company_phone, ENT_QUOTES, 'UTF-8') . '</td>';
    $content .= '</tr></table>';

    // Покупатель
    $content .= '<table class="party"><tr>';
    $content .= '<td class="p-lbl">Покупатель<br>(Заказчик):</td>';
    $content .= '<td class="p-val">' . htmlspecialchars($client_display, ENT_QUOTES, 'UTF-8') . '</td>';
    $content .= '</tr></table>';

    $content .= '</td>';

    $content .= '<td class="top-right">';
    if ($qr_src !== '') {
        $content .= '<img src="' . $qr_src . '" alt="QR">';
    }
    $content .= '</td>';

    $content .= '</tr></table>';

    // ---------- Таблица: работы сверху, запчасти ниже ----------
    $content .= '<table class="items">
        <tr>
            <th style="width: 6%;">№</th>
            <th>Наименование</th>
            <th style="width: 9%;">Кол-во</th>
            <th style="width: 6%;">Ед.</th>
            <th style="width: 9%;">Цена</th>
            <th style="width: 12%;">Сумма</th>
        </tr>';

    $total_price = 0;
    $items_count = 0;
    $row_num     = 1;

    // Работы
    foreach ($works as $w) {
        $price   = (float)$w['price'];
        $qty     = (int)$w['qty'];
        $row_sum = $price * $qty;
        $total_price += $row_sum;
        $items_count++;

        $content .= '<tr>
            <td class="c-num">' . $row_num . '</td>
            <td class="c-name">' . htmlspecialchars($w['name'], ENT_QUOTES, 'UTF-8') . '</td>
            <td class="c-qty">' . $qty . '</td>
            <td class="c-unit">шт.</td>
            <td class="c-price">' . number_format($price, 2, '.', ' ') . '</td>
            <td class="c-sum">' . number_format($row_sum, 2, '.', ' ') . '</td>
        </tr>';
        $row_num++;
    }

    // Запчасти
    foreach ($parts as $p) {
        $price   = (float)$p['price'];
        $qty     = (int)$p['qty'];
        $row_sum = $price * $qty;
        $total_price += $row_sum;
        $items_count++;

        $content .= '<tr>
            <td class="c-num">' . $row_num . '</td>
            <td class="c-name">' . htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') . '</td>
            <td class="c-qty">' . $qty . '</td>
            <td class="c-unit">шт.</td>
            <td class="c-price">' . number_format($price, 2, '.', ' ') . '</td>
            <td class="c-sum">' . number_format($row_sum, 2, '.', ' ') . '</td>
        </tr>';
        $row_num++;
    }

    // Если позиций нет — одна пустая строка
    if ($items_count === 0) {
        $content .= '<tr>
            <td class="c-num">&nbsp;</td>
            <td class="c-name">&nbsp;</td>
            <td class="c-qty">&nbsp;</td>
            <td class="c-unit">&nbsp;</td>
            <td class="c-price">&nbsp;</td>
            <td class="c-sum">&nbsp;</td>
        </tr>';
    }

    $content .= '</table>';

    // ---------- Итоги под таблицей, без рамок ----------
    $content .= '<table class="totals">';
    $content .= '<tr>
        <td class="t-lbl">ИТОГО:</td>
        <td class="t-val">' . number_format($total_price, 2, '.', ' ') . '</td>
    </tr>';
    $content .= '<tr>
        <td class="t-lbl">Без налога (НДС):</td>
        <td class="t-val">-</td>
    </tr>';
    $content .= '<tr class="t-main">
        <td class="t-lbl">ИТОГО К ОПЛАТЕ:</td>
        <td class="t-val">' . number_format($total_price, 2, '.', ' ') . '</td>
    </tr>';
    $content .= '</table>';

    // ---------- Сумма прописью ----------
    $total_words = num2wordsRub($total_price);

    $content .= '<p class="sum-line">Всего наименований ' . (int)$items_count
             . ', на сумму ' . number_format($total_price, 2, '.', ' ') . '</p>';
    $content .= '<p class="sum-words">(' . number_format($total_price, 2, '.', ' ') . ') '
             . htmlspecialchars($total_words, ENT_QUOTES, 'UTF-8') . '</p>';

    // ---------- Условия оплаты ----------
    $content .= '<p class="cond">Условия оплаты:<br>Оплата в течение десяти банковских дней.</p>';

    // ---------- Подписи ----------
    $content .= '<table class="signs">
        <tr>
            <td>Руководитель предприятия_____________________ ()</td>
        </tr>
        <tr>
            <td>Главный бухгалтер____________________________ ()</td>
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
    // $dompdf->stream('Счет-' . $order_number . '.pdf');
?>

<div id="content" style="max-width: 700px;">
    <h1 class="uk-heading-hero uk-text-center">Печать счёта</h1>
    
    <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
        <p class="operator uk-position-absolute">Оператор: <?php echo $operator; ?></p>
        <h4 class="uk-margin-remove">Счёт успешно сформирован для печати</h4>
        <h4 class="uk-margin-remove">ID заказ-наряда: <span style="font-weight: 700;"><?php echo $order_id; ?></span></h4>
        <a class="uk-margin-small uk-button uk-button-default" href="/">Перейти на главную</a>
    </div>
</div>

<?php echo $content; ?>

<?php   
}
?>