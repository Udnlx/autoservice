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
        <h1 class="uk-heading-hero uk-text-center">Печать гарантийного сертификата</h1>
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
            <h1 class="uk-heading-hero uk-text-center">Гарантийный сертификат</h1>
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
            <h1 class="uk-heading-hero uk-text-center">Гарантийный сертификат</h1>
            <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
                <h3 class="uk-card-title uk-text-center">Заказ не найден</h3>
                <a class="uk-margin-small uk-button uk-button-default" href="/">На главную</a>
            </div>
        </div>';
        return;
    }

    // ---------- Номер сертификата (= номер заказ-наряда) ----------
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

    // ---------- Клиент (ФИО для подписи на 2-й странице) ----------
    $client_display = '';
    $ownerPage = $orderPage->client;
    if ($ownerPage && $ownerPage->id) {
        $client_display = $ownerPage->title;
    }

    // ---------- Автомобиль (марка/модель/год + VIN) ----------
    $car_full    = $orderPage->auto;
    $car_display = $car_full;
    $car_vin     = '—';

    if (preg_match('/VIN:([^)]+)/u', $car_full, $vin_match)) {
        $vin_from_string = trim($vin_match[1]);
        $carPage = $pages->get("template=car, car_vin=" . $sanitizer->selectorValue($vin_from_string));
        if ($carPage->id) {
            $car_display = trim($carPage->car_brand . ' ' . $carPage->car_model . ' ' . $carPage->car_year);
            $car_vin     = $carPage->car_vin ?: '—';
        } else {
            $car_vin = $vin_from_string;
        }
    }

    // ---------- Гарантия (из заказа) ----------
    $warranty = $orderPage->warranty ?: '';

    // ---------- Картинка авто в base64 ----------
    $car_img_path = $config->paths->templates . 'imgpdf/order_print_waranty.png';
    $car_img_src  = '';
    if (is_file($car_img_path)) {
        $car_img_data = base64_encode(file_get_contents($car_img_path));
        $car_img_src  = 'data:image/png;base64,' . $car_img_data;
    }

    // ---------- HTML документа ----------
    $content = '
    <style type="text/css">
    * {
        font-family: "DejaVu Sans", sans-serif;
        box-sizing: border-box;
    }
    body { font-size: 10px; color: #000; }

    .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
    }
    .header-table td {
        vertical-align: top;
        padding: 1px 2px;
        font-size: 10px;
    }
    .cert-title {
        font-size: 13px;
        font-weight: bold;
        margin: 0 0 2px 0;
    }
    .company-line {
        margin: 0;
        line-height: 1.3;
    }
    .right-note {
        text-align: right;
        line-height: 1.4;
    }

    .car-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 6px;
    }
    .car-table td {
        border: 1px solid #000;
        padding: 4px 5px;
        font-size: 10px;
        vertical-align: middle;
    }
    .car-label {
        font-weight: bold;
        width: 150px;
        white-space: nowrap;
    }

    .main-grid {
        width: 100%;
        border-collapse: collapse;
    }
    .main-grid > tbody > tr > td {
        vertical-align: top;
        padding: 0;
    }
    .col-left {
        width: 60%;
        padding-right: 6px !important;
    }
    .col-right {
        width: 40%;
    }

    .black-bar {
        background: #000;
        color: #fff;
        text-align: center;
        font-weight: bold;
        padding: 4px 6px;
        font-size: 11px;
        margin: 0;
    }

    .obr-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 4px;
    }
    .obr-table td {
        border: 1px solid #000;
        padding: 4px 5px;
        font-size: 10px;
        vertical-align: top;
    }
    .obr-title {
        font-weight: bold;
    }

    .car-image-box {
        border: 1px solid #000;
        text-align: center;
        padding: 4px;
        margin: 0;
    }
    .car-image-box img {
        max-width: 100%;
        height: auto;
    }

    .corrosion-legend {
        width: 100%;
        border-collapse: collapse;
        margin: 4px 0 0 0;
        font-size: 9px;
    }
    .corrosion-legend td {
        padding: 1px 4px;
        vertical-align: middle;
        border: none;
    }
    .sign-cell {
        width: 30px;
        text-align: center;
        font-size: 13px;
        font-weight: bold;
    }

    .works-section-title {
        /* чёрная полоса "Перечень выполненных работ" */
    }
    .vid-obr-line {
        margin: 4px 0 2px 0;
    }

    .checkbox-table {
        width: 100%;
        border-collapse: collapse;
        margin: 2px 0;
        font-size: 10px;
    }
    .checkbox-table td {
        padding: 2px 4px;
        border: none;
        vertical-align: middle;
    }
    .chk {
        display: inline-block;
        width: 10px;
        height: 10px;
        border: 1px solid #000;
        margin-right: 4px;
        vertical-align: middle;
    }

    .mp {
        text-align: right;
        font-weight: bold;
        margin: 4px 0;
    }

    .srok-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 4px;
    }
    .srok-table td {
        border: 1px solid #000;
        padding: 4px 5px;
        font-size: 10px;
        vertical-align: top;
    }
    .srok-small {
        font-size: 9px;
    }

    .tech-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 4px;
    }
    .tech-table td {
        border: 1px solid #000;
        padding: 4px 5px;
        font-size: 10px;
        height: 22px;
    }
    .tech-label {
        width: 55%;
    }

    .comment-table {
        width: 100%;
        border-collapse: collapse;
    }
    .comment-table td {
        border: 1px solid #000;
        padding: 4px 5px;
        font-size: 10px;
        height: 32px;
    }

    .conditions {
        font-size: 10px;
        line-height: 1.45;
        text-align: justify;
    }
    .conditions ol {
        margin: 6px 0;
        padding-left: 20px;
    }
    .conditions li {
        margin-bottom: 4px;
    }
    .conditions h3 {
        font-size: 11px;
        margin: 10px 0 4px 0;
    }

    .page-break {
        page-break-before: always;
    }

    .final-sign {
        width: 100%;
        border-collapse: collapse;
        margin-top: 25px;
        font-size: 10px;
    }
    .final-sign td {
        text-align: center;
        padding: 20px 10px 2px 10px;
        width: 50%;
    }
    .final-sign .line {
        border-top: 1px solid #000;
        padding-top: 2px;
        font-size: 9px;
    }
    </style>
    ';

    // ---------- ШАПКА (слева: ГАРАНТИЙНЫЙ СЕРТИФИКАТ № + компания; справа: обратная связь) ----------
    $content .= '<table class="header-table"><tr>';
    $content .= '<td style="width: 60%;">';
    $content .= '<p class="cert-title">ГАРАНТИЙНЫЙ СЕРТИФИКАТ № ' . htmlspecialchars($order_number, ENT_QUOTES, 'UTF-8') . '</p>';
    $content .= '<p class="company-line">Антикоррозийной обработки в компании «' . htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8') . '»</p>';
    $content .= '<p class="company-line">' . htmlspecialchars($company_address, ENT_QUOTES, 'UTF-8') . '</p>';
    $content .= '</td>';
    $content .= '<td style="width: 40%;" class="right-note">';
    $content .= '<p class="company-line">Мы надеемся, что Вы остались довольны нашими услугами.</p>';
    $content .= '<p class="company-line">Если у Вас есть какие-либо предложения или замечания,</p>';
    $content .= '<p class="company-line">пожалуйста поделитесь ими с нами</p>';
    $content .= '<p class="company-line">по телефону: ' . htmlspecialchars($company_phone, ENT_QUOTES, 'UTF-8') . '</p>';
    $content .= '</td>';
    $content .= '</tr></table>';

    // ---------- БЛОК АВТОМОБИЛЯ (Марка, VIN, Пробег, Подкрылки, Материалы) ----------
    $content .= '<table class="car-table">';
    $content .= '<tr>
        <td class="car-label">Марка и модель Т/С:</td>
        <td colspan="3">' . htmlspecialchars($car_display, ENT_QUOTES, 'UTF-8') . '</td>
    </tr>';
    $content .= '<tr>
        <td class="car-label">VIN:</td>
        <td colspan="3">' . htmlspecialchars($car_vin, ENT_QUOTES, 'UTF-8') . '</td>
    </tr>';
    $content .= '<tr>
        <td class="car-label">Пробег:</td>
        <td style="width: 30%;">&nbsp;</td>
        <td class="car-label" style="width: 20%;">Наличие подкрылок:</td>
        <td>&nbsp;</td>
    </tr>';
    $content .= '<tr>
        <td class="car-label">Используемые материалы:</td>
        <td colspan="3">&nbsp;</td>
    </tr>';
    $content .= '</table>';

    // ---------- ОБРАБОТКА (дата / ФИО мастера / подпись) ----------
    $content .= '<table class="obr-table">
        <tr>
            <td style="width: 25%;" class="obr-title">Обработка<br><br>дата ___________</td>
            <td>ФИО мастера по обработке:<br>___________________________________<br>подпись: _______________________</td>
        </tr>
    </table>';

    // ---------- ДВЕ КОЛОНКИ: слева схема+чекбоксы, справа комментарий+техосмотры ----------
    $content .= '<table class="main-grid"><tr>';

    // ЛЕВАЯ КОЛОНКА
    $content .= '<td class="col-left">';

    // Чёрная полоса "Заключение предварительного осмотра"
    $content .= '<p class="black-bar">Заключение предварительного осмотра:</p>';

    // Картинка авто
    $content .= '<div class="car-image-box">';
    if ($car_img_src !== '') {
        $content .= '<img src="' . $car_img_src . '" alt="Схема авто">';
    } else {
        $content .= '&nbsp;';
    }
    $content .= '</div>';

    // Легенда коррозии (3 значка)
    $content .= '<table class="corrosion-legend">
        <tr>
            <td style="width: 45%;">Начавшийся процесс коррозии</td>
            <td class="sign-cell">○</td>
            <td>Коррозия в полостях</td>
        </tr>
        <tr>
            <td>Рыхлая коррозия</td>
            <td class="sign-cell">✕</td>
            <td>пороги/двери/крылья</td>
        </tr>
        <tr>
            <td>Сквозная коррозия</td>
            <td class="sign-cell">△</td>
            <td>крышка капота/багажник</td>
        </tr>
    </table>';

    // Чёрная полоса "Перечень выполненных работ"
    $content .= '<p class="black-bar" style="margin-top: 6px;">Перечень выполненных работ:</p>';

    // Вид обработки — свободная линия
    $content .= '<p class="vid-obr-line"><b>Вид обработки</b> ______________________________________________</p>';

    // Чекбоксы: верхняя группа
    $content .= '<table class="checkbox-table">
        <tr>
            <td style="width: 33%;"><span class="chk"></span> Крышка капота</td>
            <td style="width: 33%;"><span class="chk"></span> Крылья</td>
            <td style="width: 34%;"><span class="chk"></span> Иное</td>
        </tr>
        <tr>
            <td><span class="chk"></span> Крышка багажника</td>
            <td><span class="chk"></span> Пороги</td>
            <td>_______________</td>
        </tr>
        <tr>
            <td><span class="chk"></span> Двери</td>
            <td><span class="chk"></span> Под капотом</td>
            <td>_______________</td>
        </tr>
    </table>';

    $content .= '<p style="margin: 4px 0;">______________________________________________________</p>';

    // Чекбоксы: нижняя группа
    $content .= '<table class="checkbox-table">
        <tr>
            <td style="width: 50%;"><span class="chk"></span> Днище</td>
            <td style="width: 50%;"><span class="chk"></span> Колесные арки</td>
        </tr>
        <tr>
            <td><span class="chk"></span> Подвеска</td>
            <td><span class="chk"></span> Иное</td>
        </tr>
        <tr>
            <td><span class="chk"></span> Рама</td>
            <td>&nbsp;</td>
        </tr>
    </table>';

    // М.П.
    $content .= '<p class="mp">М.П.</p>';

    // Срок гарантии + Дата гарантийного осмотра — две колонки в таблице
    $content .= '<table class="srok-table">
        <tr>
            <td style="width: 50%;">
                <b>Срок гарантии</b> ' . htmlspecialchars($warranty, ENT_QUOTES, 'UTF-8') . '
                <div class="srok-small">при ежедневном осмотре</div>
            </td>
            <td style="width: 50%;">
                Дата гарантии осмотра<br>
                _______________________
            </td>
        </tr>
    </table>';

    $content .= '</td>'; // конец col-left

    // ПРАВАЯ КОЛОНКА
    $content .= '<td class="col-right">';

    // Комментарий мастера — заголовок + много пустых строк
    $content .= '<p style="margin: 0 0 2px 0;"><b>Комментарий Мастера:</b></p>';
    $content .= '<table class="comment-table">';
    // 12 пустых строк для записей
    for ($i = 0; $i < 12; $i++) {
        $content .= '<tr><td>&nbsp;</td></tr>';
    }
    $content .= '</table>';

    $content .= '</td>'; // конец col-right

    $content .= '</tr></table>'; // конец main-grid

    // ---------- Даты технических осмотров (на всю ширину, внизу страницы) ----------
    $content .= '<table class="tech-table">';
    for ($i = 1; $i <= 3; $i++) {
        $content .= '<tr>
            <td class="tech-label">Дата технического осмотра №' . $i . '</td>
            <td>&nbsp;</td>
        </tr>';
    }
    $content .= '</table>';

    // ====================== СТРАНИЦА 2 — УСЛОВИЯ ======================
    $content .= '<div class="page-break"></div>';

    $content .= '<div class="conditions">';
    $content .= '<h3>Условия:</h3>';
    $content .= '<ol>';
    $content .= '<li>Компания гарантирует, что все материалы, нанесенные на автомобиль с соблюдением технологии, сохраняют свои химические свойства в течении гарантийного срока.</li>';
    $content .= '<li>Гарантия, предоставляется на полную антикоррозионную обработку, означает, что в течение гарантийного срока защитный материал, нанесенный на поверхность элементов кузова, сохранит свои защитные свойства, не произойдет самопроизвольное отделение покрытия от обработанной поверхности, обработанные поверхности кузова не будут подвергаться коррозии.</li>';
    $content .= '<li>Срок гарантии зависит от возраста и марки автомобиля, состояния кузовных деталей на момент обработки (наличие коррозии; повреждений лакокрасочного покрытия; проведенный ранее кузовной ремонт, связанный со сварочными и покрасочными работами и т.д.). Срок гарантии устанавливает мастер после проведения всех работ.</li>';
    $content .= '<li>Гарантия не устанавливается на автомобили, имеющие повреждения лакокрасочного покрытия или перфорированную (сквозную) коррозию.</li>';
    $content .= '<li>Для автомобилей, использующихся в коммерческих целях или имеющих в год пробег более 40000 км, условия обработки и гарантии следует оговаривать дополнительно.</li>';
    $content .= '<li>Обязательным условием гарантии является регулярное техническое обслуживание покрытия в центрах Компании. Срок очередного техобслуживания устанавливается мастером и указывается в гарантийном сертификате.</li>';
    $content .= '<li>Не является гарантийным случаем нарушение антикоррозионного покрытия в результате естественного износа. Естественный износ возникает в некоторых местах (колесные ниши, пороги, неприкрытые места на днище и т.д.), подвергающихся повышенному воздействию песка, гравия, шуги и др. Для уменьшения естественного износа следует применять пластиковую защиту колесных арок, брызговики, дополнительную обработку специальными мастиками.</li>';
    $content .= '<li>Восстановление покрытия, нарушенного в результате естественного износа, производится за счет владельца автотранспорта.</li>';
    $content .= '<li>Право на гарантийное обслуживание утрачивается в следующих случаях:
        <br>a. Автомобиль побывал в аварии и по истечении 10 дней после ремонта не был предоставлен для обработки замененных или отремонтированных деталей;
        <br>b. Повреждено лакокрасочное покрытие;
        <br>c. Автомобиль был перекрашен;
        <br>d. Автомобиль не был представлен для техобслуживания в установленный срок;
        <br>e. Имеются механические повреждения антикоррозионного защитного покрытия;
        <br>f. На автомобиль не установлены защитные перегородки, брызговики, предусмотренные заводом изготовителем;
        <br>g. Автомобиль был обработан (полностью или частично) другими антикоррозионными материалами;
        <br>h. Утрачен гарантийный сертификат.</li>';
    $content .= '<li>В течении гарантийного срока все недостатки и недоработки, признанные гарантийным случаем устраняются бесплатно в согласованные с клиентом сроки.</li>';
    $content .= '<li>При выдаче гарантийного талона, владельцу автотранспортного средства были сообщены условия эксплуатации автотранспортного средства, соблюдение которых является обязательным для сохранения гарантии:
        <br>a. В течение суток после обработки избегать движения по песку, гравию, пересеченной местности.
        <br>b. В течение 14 дней после обработки не мыть днище автотранспортного средства под давлением.
        <br>c. Не обрабатывать автомобиль антикоррозионными материалами других марок без согласования с Компанией.</li>';
    $content .= '</ol>';

    $content .= '<p style="margin-top: 15px;"><b>С гарантийными условиями и качеством нанесения покрытия согласен:</b></p>';

    // Подписи — ФИО заказчика подставляем, у мастера пусто
    $content .= '<table class="final-sign"><tr>';
    $content .= '<td>';
    $content .= '<div style="margin-bottom: 2px;">_____________ / ' . htmlspecialchars($client_display, ENT_QUOTES, 'UTF-8') . '</div>';
    $content .= '<div class="line">(подпись / ФИО заказчика)</div>';
    $content .= '</td>';
    $content .= '<td>';
    $content .= '<div style="margin-bottom: 2px;">_____________ / _____________________</div>';
    $content .= '<div class="line">(подпись / ФИО мастера)</div>';
    $content .= '</td>';
    $content .= '</tr></table>';

    $content .= '</div>'; // conditions

    // ---------- Генерация PDF ----------
    include_once __DIR__ . '/dompdf/autoload.inc.php';
    $dompdf = new Dompdf\Dompdf();
    $dompdf->set_option('isRemoteEnabled', TRUE);
    $dompdf->set_option('defaultFont', 'DejaVu Sans');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->loadHtml($content, 'UTF-8');
    $dompdf->render();

    // Вывод файла в браузер (раскомментировать когда надо):
    // $dompdf->stream('Гарантийный-сертификат-' . $order_number . '.pdf');
?>

<div id="content" style="max-width: 700px;">
    <h1 class="uk-heading-hero uk-text-center">Печать гарантийного сертификата</h1>
    
    <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
        <p class="operator uk-position-absolute">Оператор: <?php echo $operator; ?></p>
        <h4 class="uk-margin-remove">Гарантийный сертификат успешно сформирован для печати</h4>
        <h4 class="uk-margin-remove">ID заказ-наряда: <span style="font-weight: 700;"><?php echo $order_id; ?></span></h4>
        <a class="uk-margin-small uk-button uk-button-default" href="/">Перейти на главную</a>
    </div>
</div>

<?php echo $content; ?>

<?php   
}
?>