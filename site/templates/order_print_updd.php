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
        <h1 class="uk-heading-hero uk-text-center">Печать УПДД</h1>
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
            <h1 class="uk-heading-hero uk-text-center">УПДД</h1>
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
            <h1 class="uk-heading-hero uk-text-center">УПДД</h1>
            <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
                <h3 class="uk-card-title uk-text-center">Заказ не найден</h3>
                <a class="uk-margin-small uk-button uk-button-default" href="/">На главную</a>
            </div>
        </div>';
        return;
    }

    // ---------- Сумма прописью ----------
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

    // ---------- Исключаемые запчасти ----------
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
    $client_display = '—';
    $client_address = '';
    $client_inn     = '';
    $client_kpp     = '';
    $client_found   = false;

    $ownerPage = $orderPage->client;
    if ($ownerPage && $ownerPage->id) {
        $client_found   = true;
        $client_display = $ownerPage->title;
        $client_address = (string)$ownerPage->owner_address;
        $client_inn     = (string)$ownerPage->owner_inn;
    }

    // ---------- HTML документа ----------
    $content = '
    <style type="text/css">
    @page { margin: 22px 20px 22px 20px; }
    * {
        font-family: "DejaVu Sans", sans-serif;
        box-sizing: border-box;
    }
    body { font-size: 7px; color: #000; line-height: 1.25; }
    p { margin: 0; }

    /* ================= ВЕРХНИЙ БЛОК ================= */
    .upd-top { width: 100%; border-collapse: collapse; }
    .upd-top > tbody > tr > td { vertical-align: top; padding: 0; }
    .upd-top td.ut-left  { width: 13.5%; padding-right: 6px; }
    .upd-top td.ut-right { width: 86.5%; }

    .ul-title { font-size: 8.5px; line-height: 1.3; margin-bottom: 10px; }

    .status-tbl { border-collapse: collapse; margin-bottom: 3px; }
    .status-tbl td { padding: 0; vertical-align: middle; }
    .status-tbl td.st-lbl { font-size: 8.5px; padding-right: 5px; }
    .status-tbl td.st-num {
        border: 1px solid #000;
        font-size: 9.5px;
        font-weight: bold;
        text-align: center;
        width: 22px;
        height: 15px;
    }
    .st-legend { font-size: 6.5px; line-height: 1.35; }

    .ut-inner { width: 100%; border-collapse: collapse; }
    .ut-inner > tbody > tr > td { vertical-align: top; padding: 0; }
    .ut-inner td.ut-main { width: 68%; }
    .ut-inner td.ut-note { width: 32%; text-align: right; font-size: 6.5px; line-height: 1.3; }

    .inv-title { font-size: 12px; font-weight: bold; margin-bottom: 1px; }
    .inv-sub   { font-size: 10px; font-weight: bold; margin-bottom: 5px; }

    .fld { font-size: 7.5px; line-height: 1.45; margin-bottom: 1px; }
    .fld .cap { font-size: 6px; }
    .fld-b { font-weight: bold; }

    /* ================= ТАБЛИЦА ПОЗИЦИЙ ================= */
    .items {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
        table-layout: fixed;
    }
    .items th, .items td {
        border: 1px solid #000;
        padding: 1px 2px;
        font-size: 6px;
        line-height: 1.2;
        vertical-align: middle;
    }
    .items th {
        font-weight: normal;
        text-align: center;
        vertical-align: middle;
    }
    .items tr.lit td {
        text-align: center;
        font-weight: bold;
        padding: 0;
        height: 9px;
    }
    .items td.c-num    { text-align: center; }
    .items td.c-name   { text-align: left; }
    .items td.c-center { text-align: center; }
    .items td.c-right  { text-align: right; }
    .items tr.total td { font-weight: bold; text-align: right; }
    .items tr.total td.t-lbl { text-align: left; font-weight: normal; }

    /* ================= НИЖНИЙ БЛОК ================= */
    .bottom { width: 100%; border-collapse: collapse; margin-top: 6px; }
    .bottom > tbody > tr > td { vertical-align: top; padding: 0; }
    .bottom td.b-narrow { width: 13.5%; padding-right: 6px; font-size: 7px; line-height: 1.35; }
    .bottom td.b-wide   { width: 86.5%; }

    /* сетка подписей руководителя */
    .sign-grid { width: 100%; border-collapse: collapse; }
    .sign-grid td { padding: 0; vertical-align: bottom; }
    .sign-grid td.sg-lbl { font-size: 6.5px; line-height: 1.25; width: 20%; padding-right: 4px; vertical-align: middle; }
    .sign-grid td.sg-cell { width: 30%; padding: 0 3px; }

    .uline { border-bottom: 1px solid #000; height: 9px; }
    .ucap  { text-align: center; font-size: 5.5px; line-height: 1.1; }

    /* полноширинные строки с подчёркиванием */
    .fline { width: 100%; border-collapse: collapse; margin-top: 5px; }
    .fline td { padding: 0; vertical-align: bottom; }
    .fline td.fl-lbl { white-space: nowrap; font-size: 7px; padding-right: 4px; }

    /* нижняя двухколоночная часть */
    .duo { width: 100%; border-collapse: collapse; margin-top: 6px; }
    .duo > tbody > tr > td { vertical-align: top; padding: 0; }
    .duo td.d-left  { width: 50%; padding-right: 6px; border-right: 1px solid #000; }
    .duo td.d-right { width: 50%; padding-left: 6px; }

    .d-title { font-size: 7px; margin-bottom: 2px; }

    .sig-row { width: 100%; border-collapse: collapse; margin-top: 4px; }
    .sig-row td { padding: 0 3px; vertical-align: bottom; }

    .mp-sign { font-size: 7px; margin-top: 6px; }
    </style>
    ';

    // ======================= ВЕРХ =======================
    $content .= '<table class="upd-top"><tr>';

    // Левая колонка
    $content .= '<td class="ut-left">';
    $content .= '<p class="ul-title">Универсальный<br>передаточный<br>документ</p>';
    $content .= '<table class="status-tbl"><tr>';
    $content .= '<td class="st-lbl">Статус:</td>';
    $content .= '<td class="st-num">2</td>';
    $content .= '</tr></table>';
    $content .= '<p class="st-legend">1 – счет-фактура и передаточный документ (акт)<br>2 – передаточный документ (акт)</p>';
    $content .= '</td>';

    // Правая колонка
    $content .= '<td class="ut-right">';

    $content .= '<table class="ut-inner"><tr>';

    $content .= '<td class="ut-main">';
    $content .= '<p class="inv-title">Счет-фактура № ' . htmlspecialchars($order_number, ENT_QUOTES, 'UTF-8') . ' от ' . htmlspecialchars($today, ENT_QUOTES, 'UTF-8') . 'г.</p>';
    $content .= '<p class="inv-sub">Исправление № -- от --</p>';

    $content .= '<p class="fld">Продавец: ' . htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8') . '</p>';
    $content .= '<p class="fld">Адрес: ' . htmlspecialchars($company_address, ENT_QUOTES, 'UTF-8') . '</p>';
    $content .= '<p class="fld">ИНН/КПП продавца: ' . htmlspecialchars($company_inn, ENT_QUOTES, 'UTF-8') . '/' . htmlspecialchars($company_kpp, ENT_QUOTES, 'UTF-8') . '</p>';
    $content .= '<p class="fld">Грузоотправитель и его адрес: он же</p>';
    $content .= '<p class="fld">Грузополучатель и его адрес: ' . htmlspecialchars($client_display, ENT_QUOTES, 'UTF-8') . (($client_address !== '') ? ', ' . htmlspecialchars($client_address, ENT_QUOTES, 'UTF-8') : '') . '</p>';
    $content .= '<p class="fld">К платежно-расчетному документу № -- от --</p>';
    $content .= '<p class="fld"><span class="fld-b">Документ об отгрузке</span> <span class="fld-b">Универсальный передаточный документ №' . htmlspecialchars($order_number, ENT_QUOTES, 'UTF-8') . ' от ' . htmlspecialchars($today, ENT_QUOTES, 'UTF-8') . 'г.</span></p>';
    $content .= '<p class="fld">Покупатель: ' . htmlspecialchars($client_display, ENT_QUOTES, 'UTF-8') . '</p>';
    $content .= '<p class="fld">Адрес: ' . htmlspecialchars($client_address, ENT_QUOTES, 'UTF-8') . '</p>';
    $content .= '<p class="fld">ИНН/КПП покупателя: ' . htmlspecialchars($client_inn, ENT_QUOTES, 'UTF-8') . '/' . htmlspecialchars($client_kpp, ENT_QUOTES, 'UTF-8') . '</p>';
    $content .= '<p class="fld">Валюта: <span class="cap">наименование, код</span> Российский рубль, 643</p>';
    $content .= '<p class="fld">Идентификатор государственного контракта, договора (соглашения) (при наличии):</p>';
    $content .= '</td>';

    $content .= '<td class="ut-note">Приложение № 1 к постановлению Правительства Российской Федерации от 26 декабря 2011 г. № 1137<br>(в редакции постановления Правительства Российской Федерации от 16 августа 2024 г. № 1096)</td>';

    $content .= '</tr></table>';

    $content .= '</td>';

    $content .= '</tr></table>';

    // ======================= ТАБЛИЦА =======================
    $content .= '<table class="items">';

    // Ширины колонок
    $content .= '<colgroup>
        <col style="width: 2.5%;">
        <col style="width: 5.5%;">
        <col style="width: 20%;">
        <col style="width: 5%;">
        <col style="width: 3.5%;">
        <col style="width: 4.5%;">
        <col style="width: 5%;">
        <col style="width: 5.5%;">
        <col style="width: 7%;">
        <col style="width: 4.5%;">
        <col style="width: 4.5%;">
        <col style="width: 6%;">
        <col style="width: 7%;">
        <col style="width: 4%;">
        <col style="width: 5%;">
        <col style="width: 7%;">
    </colgroup>';

    // Шапка, ряд 1
    $content .= '<tr>
        <th rowspan="2">№</th>
        <th rowspan="2">Код товара/<br>работ, услуг</th>
        <th rowspan="2">Наименование товара (описание выполненных<br>работ, оказанных услуг), имущественного права</th>
        <th rowspan="2">Код вида<br>товара</th>
        <th colspan="2">Единица<br>измерения</th>
        <th rowspan="2">Количе<br>ство<br>(объем)</th>
        <th rowspan="2">Цена<br>(тариф) за<br>ед.<br>измерения</th>
        <th rowspan="2">Стоимость<br>товаров<br>(работ, услуг),<br>имущественн<br>ых прав без<br>налога - всего</th>
        <th rowspan="2">В том<br>числе<br>сумма<br>акциза</th>
        <th rowspan="2">Налого<br>вая<br>ставка</th>
        <th rowspan="2">Сумма<br>налога,<br>предъявляе<br>мая<br>покупателю</th>
        <th rowspan="2">Стоимость<br>товаров<br>(работ, услуг),<br>имущественн<br>ых прав с<br>налогом -<br>всего</th>
        <th colspan="2">Страна<br>происхождения<br>товара</th>
        <th rowspan="2">Регистрацион<br>ный №<br>декларации<br>на товары<br>подлежащего<br>прослеживае<br>мости</th>
    </tr>';

    // Шапка, ряд 2
    $content .= '<tr>
        <th>Код</th>
        <th>условное<br>обозначе<br>ние<br>(национа<br>льное)</th>
        <th>циф<br>рово<br>й<br>код</th>
        <th>краткое<br>наимено<br>вание</th>
    </tr>';

    // Литерные коды
    $content .= '<tr class="lit">
        <td>А</td>
        <td>Б</td>
        <td>1</td>
        <td>1a</td>
        <td>2</td>
        <td>2a</td>
        <td>3</td>
        <td>4</td>
        <td>5</td>
        <td>6</td>
        <td>7</td>
        <td>8</td>
        <td>9</td>
        <td>10</td>
        <td>10a</td>
        <td>11</td>
    </tr>';

    // Позиции
    $total_price = 0;
    $items_count = 0;
    $row_num     = 1;

    $all_items = array_merge($works, $parts);

    foreach ($all_items as $it) {
        $price   = (float)$it['price'];
        $qty     = (int)$it['qty'];
        $row_sum = $price * $qty;
        $total_price += $row_sum;
        $items_count++;

        $content .= '<tr>
            <td class="c-num">' . $row_num . '</td>
            <td class="c-center">&nbsp;</td>
            <td class="c-name">' . htmlspecialchars($it['name'], ENT_QUOTES, 'UTF-8') . '</td>
            <td class="c-center">&nbsp;</td>
            <td class="c-center">&nbsp;</td>
            <td class="c-center">шт.</td>
            <td class="c-center">' . $qty . '</td>
            <td class="c-right">' . number_format($price, 2, '.', ' ') . '</td>
            <td class="c-right">' . number_format($row_sum, 2, '.', ' ') . '</td>
            <td class="c-center">&nbsp;</td>
            <td class="c-center">&nbsp;</td>
            <td class="c-right">&nbsp;</td>
            <td class="c-right">' . number_format($row_sum, 2, '.', ' ') . '</td>
            <td class="c-center">&nbsp;</td>
            <td class="c-center">&nbsp;</td>
            <td class="c-center">&nbsp;</td>
        </tr>';
        $row_num++;
    }

    // Всего к оплате
    $content .= '<tr class="total">
        <td class="t-lbl" colspan="8">Всего к оплате</td>
        <td>' . number_format($total_price, 2, '.', ' ') . '</td>
        <td>X</td>
        <td>&nbsp;</td>
        <td>' . number_format($total_price, 2, '.', ' ') . '</td>
        <td>' . number_format($total_price, 2, '.', ' ') . '</td>
        <td colspan="3">&nbsp;</td>
    </tr>';

    $content .= '</table>';

    // ======================= НИЗ =======================
    $content .= '<table class="bottom"><tr>';

    $content .= '<td class="b-narrow">Документ<br>составлен на<br><b>1</b> листе</td>';

    $content .= '<td class="b-wide">';

    // --- Подписи руководителя ---
    $content .= '<table class="sign-grid">';

    $content .= '<tr>
        <td class="sg-lbl">Руководитель организации<br>или иное уполномоченное лицо</td>
        <td class="sg-cell"><div class="uline"></div><div class="ucap">(подпись)</div></td>
        <td class="sg-cell"><div class="uline"></div><div class="ucap">(ф.и.о.)</div></td>
    </tr>';

    $content .= '<tr>
        <td class="sg-lbl">Главный бухгалтер<br>или иное уполномоченное лицо</td>
        <td class="sg-cell"><div class="uline"></div><div class="ucap">(подпись)</div></td>
        <td class="sg-cell"><div class="uline"></div><div class="ucap">(ф.и.о.)</div></td>
    </tr>';

    $content .= '<tr>
        <td class="sg-lbl">Индивидуальный предприниматель<br>или иное уполномоченное лицо</td>
        <td class="sg-cell"><div class="uline"></div><div class="ucap">(подпись)</div></td>
        <td class="sg-cell"><div class="uline"></div><div class="ucap">(ф.и.о.)</div></td>
    </tr>';

    $content .= '<tr>
        <td class="sg-lbl">&nbsp;</td>
        <td class="sg-cell" colspan="2"><div class="uline"></div><div class="ucap">(реквизиты свидетельства о государственной регистрации индивидуального предпринимателя)</div></td>
    </tr>';

    $content .= '</table>';

    // --- Основание передачи ---
    $content .= '<table class="fline"><tr>
        <td class="fl-lbl">Основание передачи (сдачи) / получения (приемки)</td>
        <td><div class="uline"></div><div class="ucap">(договор; доверенность и др.)</div></td>
    </tr></table>';

    // --- Данные о транспортировке ---
    $content .= '<table class="fline"><tr>
        <td class="fl-lbl">Данные о транспортировке и грузе</td>
        <td><div class="uline"></div><div class="ucap">(транспортная накладная, поручение экспедитору, экспедиторская / складская расписка и др. / масса нетто/ брутто груза, если не приведены ссылки на транспортные документы, содержащие эти сведения)</div></td>
    </tr></table>';

    $content .= '</td>';

    $content .= '</tr></table>';

    // --- Двухколоночная часть ---
    $content .= '<table class="duo"><tr>';

    // ЛЕВО
    $content .= '<td class="d-left">';
    $content .= '<p class="d-title">Товар (груз) передал / услуги, результаты работ, права сдал</p>';

    $content .= '<table class="sig-row"><tr>
        <td style="width: 45%;"><div class="uline"></div><div class="ucap">(должность)</div></td>
        <td style="width: 27%;"><div class="uline"></div><div class="ucap">(подпись)</div></td>
        <td style="width: 28%;"><div class="uline"></div><div class="ucap">(ф.и.о.)</div></td>
    </tr></table>';

    $content .= '<table class="fline"><tr>
        <td class="fl-lbl">Дата отгрузки, передачи (сдачи)</td>
        <td style="width: 55%;"><div class="uline"><span style="font-size:7px;">' . htmlspecialchars($today, ENT_QUOTES, 'UTF-8') . 'г.</span></div></td>
    </tr></table>';

    $content .= '<table class="fline"><tr>
        <td class="fl-lbl">Иные сведения об отгрузке, передаче</td>
        <td><div class="uline"></div><div class="ucap">(ссылки на неотъемлемые приложения, сопутствующие документы, иные документы и т.п.)</div></td>
    </tr></table>';

    $content .= '</td>';

    // ПРАВО
    $content .= '<td class="d-right">';
    $content .= '<p class="d-title">Товар (груз) получил / услуги, результаты работ, права принял</p>';

    $content .= '<table class="sig-row"><tr>
        <td style="width: 45%;"><div class="uline"></div><div class="ucap">(должность)</div></td>
        <td style="width: 27%;"><div class="uline"></div><div class="ucap">(подпись)</div></td>
        <td style="width: 28%;"><div class="uline"></div><div class="ucap">(ф.и.о.)</div></td>
    </tr></table>';

    $content .= '<table class="fline"><tr>
        <td class="fl-lbl">Дата получения (приемки)</td>
        <td><div class="uline"></div></td>
    </tr></table>';

    $content .= '<table class="fline"><tr>
        <td class="fl-lbl">Иные сведения об отгрузке, передаче</td>
        <td><div class="uline"></div><div class="ucap">(информация о наличии/отсутствии претензии; ссылки на неотъемлемые приложения, и другие документы и т.п.)</div></td>
    </tr></table>';

    $content .= '</td>';

    $content .= '</tr></table>';

    // --- Ответственный за оформление ---
    $content .= '<table class="duo" style="margin-top: 8px;"><tr>';

    $content .= '<td class="d-left">';
    $content .= '<p class="d-title">Ответственный за правильность оформления факта хозяйственной жизни</p>';
    $content .= '<table class="sig-row"><tr>
        <td style="width: 45%;"><div class="uline"></div><div class="ucap">(должность)</div></td>
        <td style="width: 27%;"><div class="uline"></div><div class="ucap">(подпись)</div></td>
        <td style="width: 28%;"><div class="uline"></div><div class="ucap">(ф.и.о.)</div></td>
    </tr></table>';
    $content .= '</td>';

    $content .= '<td class="d-right">';
    $content .= '<p class="d-title">Ответственный за правильность оформления факта хозяйственной жизни</p>';
    $content .= '<table class="sig-row"><tr>
        <td style="width: 45%;"><div class="uline"></div><div class="ucap">(должность)</div></td>
        <td style="width: 27%;"><div class="uline"></div><div class="ucap">(подпись)</div></td>
        <td style="width: 28%;"><div class="uline"></div><div class="ucap">(ф.и.о.)</div></td>
    </tr></table>';
    $content .= '</td>';

    $content .= '</tr></table>';

    // --- Наименование экономического субъекта ---
    $content .= '<table class="duo" style="margin-top: 8px;"><tr>';

    $content .= '<td class="d-left">';
    $content .= '<p class="d-title">Наименование экономического субъекта – составителя документа (в т.ч. комиссионера / агента)</p>';
    $content .= '<div class="uline"><span style="font-size:7px;">' . htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8') . ', ' . htmlspecialchars($company_inn, ENT_QUOTES, 'UTF-8') . '</span></div>';
    $content .= '<div class="ucap" style="text-align:left;">(может не заполняться при проставлении печати в М.П., может быть указан ИНН / КПП)</div>';
    $content .= '<p class="mp-sign">М.П.</p>';
    $content .= '</td>';

    $content .= '<td class="d-right">';
    $content .= '<p class="d-title">Наименование экономического субъекта – составителя документа</p>';
    $content .= '<div class="uline"><span style="font-size:7px;">' . htmlspecialchars($client_display, ENT_QUOTES, 'UTF-8') . '</span></div>';
    $content .= '<div class="ucap" style="text-align:left;">(может не заполняться при проставлении печати в М.П., может быть указан ИНН / КПП)</div>';
    $content .= '<p class="mp-sign">М.П.</p>';
    $content .= '</td>';

    $content .= '</tr></table>';

    // ---------- Генерация PDF ----------
    include_once __DIR__ . '/dompdf/autoload.inc.php';
    $dompdf = new Dompdf\Dompdf();
    $dompdf->set_option('isRemoteEnabled', TRUE);
    $dompdf->set_option('defaultFont', 'DejaVu Sans');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->loadHtml($content, 'UTF-8');
    $dompdf->render();

    // Вывод файла в браузер (раскомментировать когда надо):
    // $dompdf->stream('УПД-' . $order_number . '.pdf');
?>

<div id="content" style="max-width: 700px;">
    <h1 class="uk-heading-hero uk-text-center">Печать УПДД</h1>
    
    <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
        <p class="operator uk-position-absolute">Оператор: <?php echo $operator; ?></p>
        <h4 class="uk-margin-remove">УПДД успешно сформирован для печати</h4>
        <h4 class="uk-margin-remove">ID заказ-наряда: <span style="font-weight: 700;"><?php echo $order_id; ?></span></h4>
        <a class="uk-margin-small uk-button uk-button-default" href="/">Перейти на главную</a>
    </div>
</div>

<?php echo $content; ?>

<?php   
}
?>