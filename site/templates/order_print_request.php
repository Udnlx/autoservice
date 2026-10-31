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
        <h1 class="uk-heading-hero uk-text-center">Печать заявки и приёмки</h1>
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
            <h1 class="uk-heading-hero uk-text-center">Заявка + Приёмка</h1>
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
            <h1 class="uk-heading-hero uk-text-center">Заявка + Приёмка</h1>
            <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
                <h3 class="uk-card-title uk-text-center">Заказ не найден</h3>
                <a class="uk-margin-small uk-button uk-button-default" href="/">На главную</a>
            </div>
        </div>';
        return;
    }

    // ---------- Номер документа (= номер заказ-наряда) ----------
    $order_number = $order_id;
    if (preg_match('/(\d+)/u', $orderPage->title, $m)) {
        $order_number = $m[1];
    }

    // ---------- Реквизиты компании ----------
    $company_name    = '-';
    $company_address = '-';
    $company_inn     = '-';
    $company_phone   = '-';

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

    // ---------- Клиент (владелец) ----------
    $client_display = '';
    $client_phone   = '';

    $ownerPage = $orderPage->client;
    if ($ownerPage && $ownerPage->id) {
        $client_display = $ownerPage->title;
        $client_phone   = $ownerPage->phone;
    }

    // ---------- Автомобиль ----------
    $car_full    = $orderPage->auto;
    $car_display = '';
    $car_vin     = '';
    $car_number  = '';

    if (preg_match('/VIN:([^)]+)/u', $car_full, $vin_match)) {
        $vin_from_string = trim($vin_match[1]);
        $carPage = $pages->get("template=car, car_vin=" . $sanitizer->selectorValue($vin_from_string));
        if ($carPage->id) {
            $car_display = trim($carPage->car_brand . ' ' . $carPage->car_model . ' ' . $carPage->car_year);
            $car_vin     = (string)$carPage->car_vin;
            $car_number  = (string)$carPage->car_number;
        } else {
            $car_vin = $vin_from_string;
        }
    }

    // ---------- Вид наряда ----------
    $payment_type = $orderPage->payment_type;
    if (is_object($payment_type) && $payment_type->id) {
        $payment_type = $payment_type->title;
    }
    $payment_type = trim((string)$payment_type);

    // ---------- Работы (для «Перечня планируемых работ») ----------
    $works = [];
    if (count($orderPage->works)) {
        foreach ($orderPage->works as $item) {
            $name = trim((string)$item->work);
            if ($name !== '') {
                $works[] = $name;
            }
        }
    }

    // ---------- Рекомендации для клиента ----------
    $recomend_text = trim((string)$orderPage->recomend);

    // ---------- Изображения в base64 ----------
    function wReqImgToBase64($path) {
        if (is_file($path)) {
            return 'data:image/png;base64,' . base64_encode(file_get_contents($path));
        }
        return '';
    }

    $img_dir   = $config->paths->templates . 'imgpdf/';
    $img_rst   = wReqImgToBase64($img_dir . 'order_print_request_rst.png');
    $img_qr    = wReqImgToBase64($img_dir . 'order_print_request_qr.png');
    $img_car   = wReqImgToBase64($img_dir . 'order_print_request.png');

    // ---------- HTML документа ----------
    $content = '
    <style type="text/css">
    @page { margin: 26px 30px 26px 30px; }
    * {
        font-family: "DejaVu Sans", sans-serif;
        box-sizing: border-box;
    }
    body { font-size: 10px; color: #000; }

    p { margin: 0; }

    /* ---------- шапка ---------- */
    .hdr { width: 100%; border-collapse: collapse; }
    .hdr > tbody > tr > td { vertical-align: top; padding: 0; }
    .company-name { font-size: 11px; margin-bottom: 4px; }
    .company-line { font-size: 10px; line-height: 1.4; }

    .fields { width: 100%; border-collapse: collapse; }
    .fields td {
        border-bottom: 1px solid #000;
        padding: 2px 3px;
        font-size: 10px;
    }
    .fields td.fl { white-space: nowrap; width: 1%; }

    /* ---------- заголовок с РСТ и QR ---------- */
    .title-tbl { width: 100%; border-collapse: collapse; margin: 12px 0 6px 0; }
    .title-tbl > tbody > tr > td { vertical-align: middle; padding: 0; }
    .title-tbl td.t-rst { width: 90px; }
    .title-tbl td.t-qr  { width: 90px; text-align: right; }
    .doc-title { text-align: center; font-size: 15px; font-weight: bold; }

    /* ---------- рамка «заполняется клиентом» ---------- */
    .client-box { border: 1px solid #000; padding: 4px 6px 6px 6px; }
    .note-right {
        text-align: right;
        font-style: italic;
        font-size: 9px;
        padding-bottom: 2px;
    }
    .line-row {
        border-bottom: 1px solid #000;
        height: 15px;
    }

    .parts-chk { width: 100%; border-collapse: collapse; margin-top: 4px; }
    .parts-chk td {
        border: none;
        padding: 3px 4px;
        font-size: 9.5px;
        vertical-align: middle;
    }
    .parts-chk td.ptxt { font-weight: bold; }
    .parts-chk td.pchk { width: 60px; text-align: center; }
    .parts-chk td.psign { width: 130px; text-align: right; font-style: italic; }
    .chk-wide {
        display: inline-block;
        width: 44px;
        height: 11px;
        border: 1px solid #000;
    }

    /* ---------- акт приёмки ---------- */
    .act-title {
        text-align: center;
        font-size: 13px;
        font-weight: bold;
        letter-spacing: 1px;
        margin: 10px 0 4px 0;
    }
    .act-box { border: 1px solid #000; padding: 4px; }
    .act-sub {
        text-align: center;
        font-style: italic;
        font-weight: bold;
        font-size: 10px;
        margin-bottom: 2px;
    }
    .act-box img { width: 100%; height: auto; }

    /* ---------- ЛКП ---------- */
    .lkp-box {
        border: 1px solid #000;
        padding: 5px 7px;
        font-size: 11px;
        line-height: 1.45;
        margin-top: 8px;
    }

    /* ---------- комплектация / примечания ---------- */
    .two-tbl { width: 100%; border-collapse: collapse; margin-top: 8px; }
    .two-tbl > tbody > tr > td { vertical-align: top; padding: 0; }
    .two-tbl td.left  { width: 52%; padding-right: 5px !important; }

    .comp-table { width: 100%; border-collapse: collapse; }
    .comp-table td {
        border: 1px solid #000;
        padding: 2px 4px;
        font-size: 9px;
        height: 15px;
    }
    .comp-table td.head {
        text-align: center;
        font-weight: bold;
        font-style: italic;
        font-size: 10px;
    }
    .comp-table td.daynot {
        text-align: center;
        font-weight: bold;
        font-style: italic;
        font-size: 9px;
        width: 45px;
    }
    .comp-table td.item-name { font-weight: bold; font-style: italic; }

    .note-table { width: 100%; border-collapse: collapse; }
    .note-table td {
        border: 1px solid #000;
        padding: 2px 4px;
        font-size: 9px;
        height: 15px;
    }
    .note-table td.head {
        text-align: center;
        font-weight: bold;
        font-style: italic;
        font-size: 10px;
    }
    .note-table td.bag { font-weight: bold; font-style: italic; }

    /* ---------- мелкий текст и подписи ---------- */
    .small-note {
        font-size: 7.5px;
        line-height: 1.35;
        margin-top: 6px;
    }
    .signs { width: 100%; border-collapse: collapse; margin-top: 14px; }
    .signs td {
        width: 50%;
        font-size: 10px;
        font-weight: bold;
        padding: 0;
    }
    .signs td.right { text-align: right; }

    /* ---------- вторая страница ---------- */
    .page-break { page-break-before: always; }

    .works-title {
        font-size: 12px;
        font-weight: bold;
        margin: 12px 0 4px 0;
    }
    .works-table { width: 100%; border-collapse: collapse; }
    .works-table th {
        border: 1px solid #000;
        padding: 4px;
        font-size: 10px;
        font-weight: bold;
        text-align: center;
    }
    .works-table td {
        border: 1px solid #000;
        padding: 3px 4px;
        font-size: 10px;
        height: 15px;
    }
    .works-table td.c-exec { width: 24%; }
    .works-table td.c-date { width: 18%; }

    .recomend-title {
        font-size: 11px;
        font-weight: bold;
        margin: 14px 0 3px 0;
    }
    .recomend-text { font-size: 10px; line-height: 1.4; }
    </style>
    ';

    // ====================== СТРАНИЦА 1 ======================

    // ---------- Шапка ----------
    $content .= '<table class="hdr"><tr>';

    // Левая колонка — реквизиты
    $content .= '<td style="width: 45%;">';
    $content .= '<p class="company-name">' . htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8') . '</p>';
    $content .= '<p class="company-line">' . htmlspecialchars($company_address, ENT_QUOTES, 'UTF-8') . ' ИНН</p>';
    $content .= '<p class="company-line">' . htmlspecialchars($company_inn, ENT_QUOTES, 'UTF-8') . ' тел.:' . htmlspecialchars($company_phone, ENT_QUOTES, 'UTF-8') . '</p>';
    $content .= '</td>';

    // Правая колонка — поля
    $content .= '<td style="width: 55%;">';
    $content .= '<table class="fields">';
    $content .= '<tr><td class="fl">Дата:</td><td>' . htmlspecialchars($today, ENT_QUOTES, 'UTF-8') . '</td></tr>';
    $content .= '<tr><td class="fl">Владелец:</td><td>' . htmlspecialchars($client_display, ENT_QUOTES, 'UTF-8') . '</td></tr>';
    $content .= '<tr><td class="fl">Автомобиль:</td><td>' . htmlspecialchars($car_display, ENT_QUOTES, 'UTF-8') . '</td></tr>';
    $content .= '<tr>
        <td class="fl">Гос.номер:</td><td>' . htmlspecialchars($car_number, ENT_QUOTES, 'UTF-8') . '</td>
        <td class="fl">Пробег:</td><td style="width: 25%;">&nbsp;</td>
    </tr>';
    $content .= '<tr><td class="fl">VIN:</td><td>' . htmlspecialchars($car_vin, ENT_QUOTES, 'UTF-8') . '</td></tr>';
    $content .= '<tr><td class="fl">Шасси</td><td>&nbsp;</td></tr>';
    $content .= '<tr><td class="fl">тел:</td><td>' . htmlspecialchars($client_phone, ENT_QUOTES, 'UTF-8') . '</td></tr>';
    $content .= '<tr><td class="fl">Вид наряда:</td><td>' . htmlspecialchars($payment_type, ENT_QUOTES, 'UTF-8') . '</td></tr>';
    $content .= '</table>';
    $content .= '</td>';

    $content .= '</tr></table>';

    // ---------- Заголовок: РСТ + название + QR ----------
    $content .= '<table class="title-tbl"><tr>';
    $content .= '<td class="t-rst">';
    if ($img_rst !== '') {
        $content .= '<img src="' . $img_rst . '" style="width: 70px; height: auto;">';
    }
    $content .= '</td>';
    $content .= '<td><p class="doc-title">ЗАЯВКА НА РЕМОНТ № ' . htmlspecialchars($order_number, ENT_QUOTES, 'UTF-8') . '</p></td>';
    $content .= '<td class="t-qr">';
    if ($img_qr !== '') {
        $content .= '<img src="' . $img_qr . '" style="width: 62px; height: auto;">';
    }
    $content .= '</td>';
    $content .= '</tr></table>';

    // ---------- Блок «заполняется клиентом» ----------
    $content .= '<div class="client-box">';
    $content .= '<p class="note-right">заполняется клиентом</p>';
    for ($i = 0; $i < 4; $i++) {
        $content .= '<div class="line-row"></div>';
    }

    $content .= '<table class="parts-chk">';
    $content .= '<tr>
        <td class="ptxt">Старые запчасти</td>
        <td class="pchk"><span class="chk-wide"></span></td>
        <td class="psign" rowspan="3">подпись клиента</td>
    </tr>';
    $content .= '<tr>
        <td class="ptxt">Запчасти приобретаются Техцентром</td>
        <td class="pchk"><span class="chk-wide"></span></td>
    </tr>';
    $content .= '<tr>
        <td class="ptxt">Запчасти приобретаются Клиентом</td>
        <td class="pchk"><span class="chk-wide"></span></td>
    </tr>';
    $content .= '</table>';
    $content .= '</div>';

    // ---------- Акт приёмки автомобиля ----------
    $content .= '<p class="act-title">АКТ ПРИЕМКИ АВТОМОБИЛЯ</p>';
    $content .= '<div class="act-box">';
    $content .= '<p class="act-sub">Повреждения на автомобиле</p>';
    if ($img_car !== '') {
        $content .= '<img src="' . $img_car . '" alt="Схема автомобиля">';
    }
    $content .= '<p class="note-right">заполняется мастером-приемщиком</p>';
    $content .= '</div>';

    // ---------- Состояние ЛКП ----------
    $content .= '<div class="lkp-box">';
    $content .= 'Автомобиль: грязный, пыльный, мокрый, чистый, Состояние дефектов ЛКП:<br>';
    $content .= 'Ц-царапины, С-сколы, В- вмятины, З- нарушение зазоров, О-отклеин, отломан, отходит, К-краска';
    $content .= '</div>';

    // ---------- Комплектация + Примечания ----------
    $content .= '<table class="two-tbl"><tr>';

    // Комплектация
    $content .= '<td class="left">';
    $content .= '<table class="comp-table">';
    $content .= '<tr><td class="head">Комплектация</td><td class="daynot">да/нет</td></tr>';

    $comp_items = [
        'щетки, поводки',
        'зал. колесо',
        'литые диски',
        'а/магнитола(наим.)',
        'панель а/магнитолы',
        'колонки(ситм.)',
        'антенна',
        'усилитель (наим.)',
        'мех. протир., устр-во (наим.)',
        'мех. протир., устр-во (наим.)',
        'марш. компьютер,',
    ];
    foreach ($comp_items as $ci) {
        $content .= '<tr><td class="item-name">' . htmlspecialchars($ci, ENT_QUOTES, 'UTF-8') . '</td><td class="daynot">&nbsp;</td></tr>';
    }
    $content .= '</table>';
    $content .= '<p class="note-right">заполняется мастером-приёмщиком</p>';
    $content .= '</td>';

    // Примечания
    $content .= '<td>';
    $content .= '<table class="note-table">';
    $content .= '<tr><td class="head">Примечания</td></tr>';
    $content .= '<tr><td class="bag">В багажнике:</td></tr>';
    for ($i = 0; $i < 9; $i++) {
        $content .= '<tr><td>&nbsp;</td></tr>';
    }
    $content .= '</table>';
    $content .= '</td>';

    $content .= '</tr></table>';

    // ---------- Мелкий текст ----------
    $content .= '<p class="small-note">
    Автомобиль принят в (грязном виде, не на ходу, с эвакуатора, севшим АКБ, ДВС не заводится), без осмотра ЛКП и салона, без проверки эл. оборудования и работы ДВС.<br>
    Заказчик уведомлен, что за имеющиеся повреждения вышеперечисленных узлов, агрегатов и деталей а/м исполнитель ответственности не несет.
    </p>';

    // ---------- Подписи ----------
    $content .= '<table class="signs"><tr>';
    $content .= '<td>Подпись мастера приемщика ______________________</td>';
    $content .= '<td class="right">Подпись клиента ______________________</td>';
    $content .= '</tr></table>';

    // ====================== СТРАНИЦА 2 ======================
    $content .= '<div class="page-break"></div>';

    // Шапка второй страницы
    $content .= '<p class="company-name" style="font-size: 12px;">' . htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8') . '</p>';

    $content .= '<table class="fields" style="margin-top: 6px;">';
    $content .= '<tr><td class="fl">Автомобиль:</td><td>' . htmlspecialchars($car_display, ENT_QUOTES, 'UTF-8') . '</td></tr>';
    $content .= '<tr>
        <td class="fl">Гос №:</td><td>' . htmlspecialchars($car_number, ENT_QUOTES, 'UTF-8') . '</td>
        <td class="fl">Пробег:</td><td style="width: 25%;">&nbsp;</td>
    </tr>';
    $content .= '<tr><td class="fl">VIN № :</td><td>' . htmlspecialchars($car_vin, ENT_QUOTES, 'UTF-8') . '</td></tr>';
    $content .= '</table>';

    // Заголовок
    $content .= '<p class="doc-title" style="margin: 16px 0 6px 0;">ЗАЯВКА НА РЕМОНТ № ' . htmlspecialchars($order_number, ENT_QUOTES, 'UTF-8') . ' от ' . htmlspecialchars($today, ENT_QUOTES, 'UTF-8') . '</p>';

    // Перечень планируемых работ
    $content .= '<p class="works-title">Перечень планируемых работ</p>';
    $content .= '<table class="works-table">';
    $content .= '<tr>
        <th>Наименование</th>
        <th style="width: 24%;">Исполнитель</th>
        <th style="width: 18%;">Дата</th>
    </tr>';

    // Работы из заказа
    foreach ($works as $w_name) {
        $content .= '<tr>
            <td>' . htmlspecialchars($w_name, ENT_QUOTES, 'UTF-8') . '</td>
            <td class="c-exec">&nbsp;</td>
            <td class="c-date">&nbsp;</td>
        </tr>';
    }

    // 10 пустых строк под ручку
    for ($i = 0; $i < 10; $i++) {
        $content .= '<tr>
            <td>&nbsp;</td>
            <td class="c-exec">&nbsp;</td>
            <td class="c-date">&nbsp;</td>
        </tr>';
    }

    $content .= '</table>';

    // Рекомендации
    $content .= '<p class="recomend-title">Рекомендации:</p>';
    if ($recomend_text !== '') {
        $content .= '<p class="recomend-text">' . nl2br(htmlspecialchars($recomend_text, ENT_QUOTES, 'UTF-8')) . '</p>';
    } else {
        $content .= '<p class="recomend-text">&nbsp;</p>';
    }

    // ---------- Генерация PDF ----------
    include_once __DIR__ . '/dompdf/autoload.inc.php';
    $dompdf = new Dompdf\Dompdf();
    $dompdf->set_option('isRemoteEnabled', TRUE);
    $dompdf->set_option('defaultFont', 'DejaVu Sans');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->loadHtml($content, 'UTF-8');
    $dompdf->render();

    // Вывод файла в браузер (раскомментировать когда надо):
    $dompdf->stream('Заявка-приемка-' . $order_number . '.pdf');
?>

<div id="content" style="max-width: 700px;">
    <h1 class="uk-heading-hero uk-text-center">Печать заявки и приёмки</h1>
    
    <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
        <p class="operator uk-position-absolute">Оператор: <?php echo $operator; ?></p>
        <h4 class="uk-margin-remove">Заявка и приёмка успешно сформированы для печати</h4>
        <h4 class="uk-margin-remove">ID заказ-наряда: <span style="font-weight: 700;"><?php echo $order_id; ?></span></h4>
        <a class="uk-margin-small uk-button uk-button-default" href="/">Перейти на главную</a>
    </div>
</div>

<?php echo $content; ?>

<?php   
}
?>