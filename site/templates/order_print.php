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
        <h1 class="uk-heading-hero uk-text-center">Печать заказ-наряда</h1>
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
            <h1 class="uk-heading-hero uk-text-center">Заказ-наряд</h1>
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
            <h1 class="uk-heading-hero uk-text-center">Заказ-наряд</h1>
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

    // ---------- Данные заказа ----------
    $orderPage = $pages->get("id=$order_id, template=order_item");

    // Номер заказа из title
    $order_number = $order_id;
    if (preg_match('/(\d+)/u', $orderPage->title, $m)) {
        $order_number = $m[1];
    }

    $order_date    = $orderPage->date_order;
    $client_name   = $orderPage->client;
    $car_full      = $orderPage->auto;
    $total_price   = (float)$orderPage->cost_total;
    $total_words   = num2wordsRub($total_price);

    // Работы
    $works = [];
    if (count($orderPage->works)) {
        foreach ($orderPage->works as $item) {
            $works[] = ['name' => $item->work, 'price' => $item->price];
        }
    }

    // Запчасти
    $parts = [];
    if (count($orderPage->autoparts)) {
        foreach ($orderPage->autoparts as $item) {
            $parts[] = ['name' => $item->autopart, 'price' => $item->price];
        }
    }

    // ---------- Реквизиты автосервиса (заглушки) ----------
    $company_name    = 'Турбина Плюс';
    $company_address = 'Люберцы, Октябрьский проспект 259 стр 1';
    $company_inn     = '502770491281';
    $company_phone   = '8 985 816-10-10';

    // ---------- Клиент: ищем по title в шаблоне owner ----------
    $client_display = $client_name; // по умолчанию — исходная строка (если не найден)
    $client_phone   = '';
    $client_email   = '';
    $client_address = '';
    $client_company = '';
    $client_inn     = '';
    $client_found   = false;

    if ($client_name) {
        $ownerPage = $pages->get("template=owner, title=" . $sanitizer->selectorValue($client_name));

        if ($ownerPage->id) {
            $client_found   = true;
            $client_display = $ownerPage->title;
            $client_phone   = $ownerPage->phone;
            $client_email   = $ownerPage->email;
            $client_address = $ownerPage->owner_address;
            $client_company = $ownerPage->owner_company;
            $client_inn     = $ownerPage->owner_inn;
        }
    }

    $date_priem  = $order_date;
    $date_otpusk = $today;

    // ---------- Автомобиль: ищем по VIN из строки $car_full ----------
    // Строка вида: "BMW X5 (О123ПЕ 58) (VIN:567746464) 2021"
    $car_display = $car_full; // по умолчанию — вся строка целиком (вариант A)
    $car_number  = '—';
    $car_vin     = '—';

    if (preg_match('/VIN:([^)]+)/u', $car_full, $vin_match)) {
        $vin_from_string = trim($vin_match[1]);

        $carPage = $pages->get("template=car, car_vin=" . $sanitizer->selectorValue($vin_from_string));

        if ($carPage->id) {
            // Нашли — собираем из полей
            $car_display = trim($carPage->car_brand . ' ' . $carPage->car_model . ' ' . $carPage->car_year);
            $car_number  = $carPage->car_number ?: '—';
            $car_vin     = $carPage->car_vin    ?: '—';
        }
        // Если не нашли — оставляем всю строку $car_full как есть
    }

    // ---------- HTML документа ----------
    $content = '
    <style type="text/css">
    * {
        font-family: "DejaVu Sans", sans-serif;
        box-sizing: border-box;
    }
    body { font-size: 11px; color: #000; }

    .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }
    .header-table td {
        vertical-align: top;
        padding: 2px 4px;
        font-size: 11px;
    }
    .company-name {
        font-size: 16px;
        font-weight: bold;
        margin: 0 0 3px 0;
    }
    .company-info {
        font-size: 11px;
        line-height: 1.35;
        margin: 0;
    }

    .doc-title {
        text-align: center;
        font-size: 15px;
        font-weight: bold;
        margin: 15px 0 10px 0;
        text-transform: uppercase;
    }

    .section-title {
        text-align: center;
        font-weight: bold;
        font-size: 12px;
        margin: 10px 0 4px 0;
    }

    .info-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 6px;
    }
    .info-table td {
        padding: 2px 4px;
        font-size: 11px;
        vertical-align: top;
    }
    .info-label {
        font-weight: bold;
        white-space: nowrap;
    }

    .works-table {
        width: 100%;
        border-collapse: collapse;
        margin: 4px 0 6px 0;
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
    .works-table td.num { text-align: center; width: 40px; }
    .works-table td.qty { text-align: center; width: 60px; }
    .works-table td.price { text-align: right; width: 100px; }
    .works-table tr.total-row td {
        font-weight: bold;
        text-align: right;
    }

    .summary-block {
        margin-top: 10px;
        font-size: 12px;
    }
    .summary-block p {
        margin: 3px 0;
    }
    .total-final {
        font-size: 13px;
        font-weight: bold;
        text-align: right;
        margin: 8px 0;
    }
    .words {
        font-style: italic;
        margin: 4px 0 10px 0;
    }

    .agreement {
        font-size: 10px;
        text-align: justify;
        margin: 10px 0;
        line-height: 1.35;
    }

    .signatures {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        font-size: 11px;
    }
    .signatures td {
        padding: 15px 4px 2px 4px;
        border-top: 1px solid #000;
        text-align: center;
        width: 50%;
    }
    .dates {
        margin-top: 15px;
        font-size: 11px;
    }
    </style>
    ';

    // Шапка
    $content .= '
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <p class="company-name">' . htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8') . '</p>
                <p class="company-info">' . htmlspecialchars($company_address, ENT_QUOTES, 'UTF-8') . '</p>
                <p class="company-info">ИНН ' . htmlspecialchars($company_inn, ENT_QUOTES, 'UTF-8') . '</p>
                <p class="company-info">тел.: ' . htmlspecialchars($company_phone, ENT_QUOTES, 'UTF-8') . '</p>
            </td>
            <td style="width: 40%;">
                <p class="company-info"><b>Автомобиль:</b> ' . htmlspecialchars($car_display, ENT_QUOTES, 'UTF-8') . '</p>
                <p class="company-info"><b>Гос №:</b> ' . htmlspecialchars($car_number, ENT_QUOTES, 'UTF-8') . '</p>
                <p class="company-info"><b>VIN №:</b> ' . htmlspecialchars($car_vin, ENT_QUOTES, 'UTF-8') . '</p>
                <p class="company-info"><b>Заказчик:</b> ' . htmlspecialchars($client_display, ENT_QUOTES, 'UTF-8') . '</p>';

            if ($client_found) {
                if ($client_phone) {
                    $content .= '<p class="company-info"><b>Тел:</b> ' . htmlspecialchars($client_phone, ENT_QUOTES, 'UTF-8') . '</p>';
                }
                if ($client_email) {
                    $content .= '<p class="company-info"><b>Email:</b> ' . htmlspecialchars($client_email, ENT_QUOTES, 'UTF-8') . '</p>';
                }
                if ($client_address) {
                    $content .= '<p class="company-info"><b>Адрес:</b> ' . htmlspecialchars($client_address, ENT_QUOTES, 'UTF-8') . '</p>';
                }
                if ($client_company) {
                    $content .= '<p class="company-info"><b>Компания:</b> ' . htmlspecialchars($client_company, ENT_QUOTES, 'UTF-8') . '</p>';
                }
                if ($client_inn) {
                    $content .= '<p class="company-info"><b>ИНН:</b> ' . htmlspecialchars($client_inn, ENT_QUOTES, 'UTF-8') . '</p>';
                }
            }

            $content .= '
            </td>
        </tr>
    </table>

    <p class="doc-title">ЗАКАЗ-НАРЯД №' . htmlspecialchars($order_number, ENT_QUOTES, 'UTF-8') . ' от ' . htmlspecialchars($order_date, ENT_QUOTES, 'UTF-8') . '</p>
    ';

    // Таблица работ
    $works_sum = 0;
        $content .= '<p class="section-title">Перечень выполненных работ</p>';
    $content .= '<table class="works-table">
        <tr>
            <th style="width: 40px;">№</th>
            <th>Наименование</th>
            <th style="width: 120px;">Стоимость</th>
        </tr>';

    if (!empty($works)) {
        $i = 1;
        foreach ($works as $w) {
            $price = (float)$w['price'];
            $works_sum += $price;
            $content .= '<tr>
                <td class="num">' . $i . '</td>
                <td>' . htmlspecialchars($w['name'], ENT_QUOTES, 'UTF-8') . '</td>
                <td class="price">' . number_format($price, 2, '.', ' ') . '</td>
            </tr>';
            $i++;
        }
    } else {
        $content .= '<tr><td colspan="3" style="text-align:center; color:#666;">—</td></tr>';
    }

    $content .= '<tr class="total-row">
        <td colspan="2">СУММА:</td>
        <td class="price">' . number_format($works_sum, 2, '.', ' ') . '</td>
    </tr>';
    $content .= '</table>';

    // Таблица запчастей
    $parts_sum = 0;
    $content .= '<p class="section-title">Перечень запасных частей и материалов</p>';
    $content .= '<table class="works-table">
        <tr>
            <th style="width: 40px;">№</th>
            <th>Наименование</th>
            <th style="width: 120px;">Стоимость</th>
        </tr>';

    if (!empty($parts)) {
        $i = 1;
        foreach ($parts as $p) {
            $price = (float)$p['price'];
            $parts_sum += $price;
            $content .= '<tr>
                <td class="num">' . $i . '</td>
                <td>' . htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') . '</td>
                <td class="price">' . number_format($price, 2, '.', ' ') . '</td>
            </tr>';
            $i++;
        }
    } else {
        $content .= '<tr><td colspan="3" style="text-align:center; color:#666;">—</td></tr>';
    }

    $content .= '<tr class="total-row">
        <td colspan="2">СУММА:</td>
        <td class="price">' . number_format($parts_sum, 2, '.', ' ') . '</td>
    </tr>';
    $content .= '</table>';

    // Рекомендованные работы (заглушка)
    $content .= '<p class="section-title">Рекомендованные работы</p>';
    $content .= '<table class="works-table"><tr><td style="height: 30px;">&nbsp;</td></tr></table>';

    // Итого + прописью
    $content .= '
    <p class="total-final">ИТОГО К ОПЛАТЕ: ' . number_format($total_price, 2, '.', ' ') . ' руб.</p>
    <p class="words">Всего получено: (' . number_format($total_price, 2, '.', ' ') . ') ' . $total_words . '</p>
    ';

        // Соглашение
        $content .= '
    <p class="agreement">
    Все перечисленные работы выполнены полностью, претензий к качеству не имею. Автомобиль и комплектация получены.
    Согласен на проведение работ по устранению выявленных в процессе ремонта неисправностей, без устранения которых
    дальнейшая эксплуатация автомобиля ЗАПРЕЩЕНА (постановление правительства РФ от 11.04.01г. №290).
    С правилами предоставления услуг ' . htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8') . ' ознакомлен и согласен.
    Согласен на обработку персональных данных.
    </p>
    ';

    // Подписи
    $content .= '
    <table class="signatures">
        <tr>
            <td>' . htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8') . '</td>
            <td>КЛИЕНТ</td>
        </tr>
    </table>

    <p class="dates">
        Дата приема а/м: ' . htmlspecialchars($date_priem, ENT_QUOTES, 'UTF-8') . ' &nbsp; &nbsp;
        Дата отпуска а/м: ' . htmlspecialchars($date_otpusk, ENT_QUOTES, 'UTF-8') . '
    </p>
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
    // $dompdf->stream('Заказ-наряд-' . $order_number . '.pdf');
?>

<div id="content" style="max-width: 700px;">
    <h1 class="uk-heading-hero uk-text-center">Печать заказ-наряда</h1>
    
    <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
        <p class="operator uk-position-absolute">Оператор: <?php echo $operator; ?></p>
        <h4 class="uk-margin-remove">Заказ-наряд успешно сформирован для печати</h4>
        <h4 class="uk-margin-remove">ID заказ-наряда: <span style="font-weight: 700;"><?php echo $order_id; ?></span></h4>
        <a class="uk-margin-small uk-button uk-button-default" href="/">Перейти на главную</a>
    </div>
</div>

<?php echo $content; ?>

<?php   
}
?>