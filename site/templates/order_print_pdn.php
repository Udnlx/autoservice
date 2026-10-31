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
        <h1 class="uk-heading-hero uk-text-center">Печать ПДН</h1>
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
            <h1 class="uk-heading-hero uk-text-center">ПДН</h1>
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
            <h1 class="uk-heading-hero uk-text-center">ПДН</h1>
            <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
                <h3 class="uk-card-title uk-text-center">Заказ не найден</h3>
                <a class="uk-margin-small uk-button uk-button-default" href="/">На главную</a>
            </div>
        </div>';
        return;
    }

    // ---------- Реквизиты компании ----------
    $company_name    = '-';
    $company_address = '-';
    $company_inn     = '-';
    $company_kpp     = '';

    $company = $input->get->string('company');
    if ($company == 'turbina_plus') {
        $company_name    = 'Турбина Плюс';
        $company_address = 'Люберцы Октябрьский проспект 259 стр 1';
        $company_inn     = '502770491281';
    }
    if ($company == 'antikor_rf') {
        $company_name    = 'Антикор РФ';
        $company_address = 'Люберцы Октябрьский проспект 259 стр 1';
        $company_inn     = '502770491281';
    }

    // ---------- Клиент ----------
    $client_display = '';
    $ownerPage = $orderPage->client;
    if ($ownerPage && $ownerPage->id) {
        $client_display = $ownerPage->title;
    }
    if ($client_display === '') {
        $client_display = '_______________________';
    }

    // ---------- HTML документа ----------
    $content = '
    <style type="text/css">
    @page { margin: 30px 40px 30px 40px; }
    * {
        font-family: "DejaVu Sans", sans-serif;
        box-sizing: border-box;
    }
    body { font-size: 10.5px; color: #000; line-height: 1.5; }
    p { margin: 0 0 8px 0; }

    .co-name { font-size: 12px; margin-bottom: 2px; }
    .co-line { font-size: 9.5px; line-height: 1.4; margin: 0; }

    .doc-title {
        text-align: center;
        font-size: 12px;
        font-weight: bold;
        margin: 18px 0 12px 0;
    }

    .pdn-list {
        margin: 4px 0 10px 0;
        padding-left: 16px;
    }
    .pdn-list li {
        margin-bottom: 1px;
    }

    .sign-line {
        margin-top: 22px;
        font-size: 10.5px;
    }
    .sign-fio { font-weight: bold; }
    </style>
    ';

    // ---------- Шапка компании ----------
    $content .= '<p class="co-name">' . htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8') . '</p>';
    $content .= '<p class="co-line">ИНН: ' . htmlspecialchars($company_inn, ENT_QUOTES, 'UTF-8') . ' КПП: ' . htmlspecialchars($company_kpp, ENT_QUOTES, 'UTF-8') . '</p>';
    $content .= '<p class="co-line">' . htmlspecialchars($company_address, ENT_QUOTES, 'UTF-8') . '</p>';

    // ---------- Заголовок ----------
    $content .= '<p class="doc-title">СОГЛАСИЕ НА ОБРАБОТКУ ПЕРСОНАЛЬНЫХ ДАННЫХ</p>';

    // ---------- Текст ----------
    $content .= '<p>Настоящим я, <b>' . htmlspecialchars($client_display, ENT_QUOTES, 'UTF-8') . '</b>, действуя от своего имени, добровольно и не находясь под влиянием заблуждения, даю согласие ' . htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8') . ' ИНН:' . htmlspecialchars($company_inn, ENT_QUOTES, 'UTF-8') . ' расположенному по адресу ' . htmlspecialchars($company_address, ENT_QUOTES, 'UTF-8') . ' (далее – «Оператор»), на передачу и последующую обработку моих персональных данных, которые перечислены в настоящем согласии ниже:</p>';

    $content .= '<ul class="pdn-list">
        <li>Фамилия, имя, отчество</li>
        <li>Контактный телефон</li>
        <li>Адрес проживания</li>
        <li>Адрес электронной почты</li>
    </ul>';

    $content .= '<p>Согласие дано Оператору для совершения следующих действий с моими персональными данными с использованием средств автоматизации и/или без использования таких средств: сбор, систематизация, накопление, хранение, уточнение (обновление, изменение), использование, обезличивание, передана третьим лицам для указанных ниже цепей, а также осуществление любых иных действий, предусмотренных действующим законодательством РФ как неавтоматизированными, так и автоматизированными способами.</p>';

    $content .= '<p>Данное согласие дается Оператору для обработки моих персональных данных в следующих целях:</p>';

    $content .= '<ul class="pdn-list">
        <li>Предоставление мне услуг/работ;</li>
        <li>Направление в мой адрес уведомлений касающихся предоставляемых услуг работ;</li>
        <li>Подготовка и направление ответов на мои запросы;</li>
        <li>Направление в мой адрес информации в том числе рекламной, о мероприятиях/товарах/услугах/работах Оператора;</li>
    </ul>';

    $content .= '<p>Настоящее согласие действует до момента его отзыва путем направления соответствующего уведомления на почтовый адрес ' . htmlspecialchars($company_address, ENT_QUOTES, 'UTF-8') . '. В случае отзыва мною согласия на обработку персональных данных Оператор вправе продолжить обработку персональных данных без моего согласия при наличии оснований указанных в пунктах 2 — 11 части 1 статьи 6, части 2 статьи 10 и части 2 статьи 11 Федерального закона № 152-Ф3 «0 персональных данных» от 26.06. 2006 г.</p>';

    // ---------- Подпись ----------
    $content .= '<table style="width: 100%; border-collapse: collapse; margin-top: 22px;">
        <tr>
            <td style="width: 50%;">/ <span class="sign-fio">' . htmlspecialchars($client_display, ENT_QUOTES, 'UTF-8') . '</span> /</td>
            <td style="width: 30%; text-align: center;">__________________________</td>
            <td style="width: 20%; text-align: right;">( ' . htmlspecialchars($today, ENT_QUOTES, 'UTF-8') . 'г. )</td>
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
    // $dompdf->stream('ПДН-' . $order_id . '.pdf');
?>

<div id="content" style="max-width: 700px;">
    <h1 class="uk-heading-hero uk-text-center">Печать ПДН</h1>
    
    <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
        <p class="operator uk-position-absolute">Оператор: <?php echo $operator; ?></p>
        <h4 class="uk-margin-remove">ПДН успешно сформирован для печати</h4>
        <h4 class="uk-margin-remove">ID заказ-наряда: <span style="font-weight: 700;"><?php echo $order_id; ?></span></h4>
        <a class="uk-margin-small uk-button uk-button-default" href="/">Перейти на главную</a>
    </div>
</div>

<?php echo $content; ?>

<?php   
}
?>