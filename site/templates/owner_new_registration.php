<?php namespace ProcessWire;

$today = date("d-m-Y");

if(isset($_SESSION['operator'])){
    $operator = $_SESSION['operator'];
} else {
    $operator = 'no_operator';
}

if ($operator == 'no_operator') {
?>
    <div id="content" style="max-width: 700px;">
        <h1 class="uk-heading-hero uk-text-center">Новый клиент Регистрация</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php    
} else {

    $owner_name  = !empty($_POST['owner_name'])  ? trim($_POST['owner_name'])  : NULL;
    $owner_phone = !empty($_POST['owner_phone']) ? trim($_POST['owner_phone']) : NULL;
    $owner_email = !empty($_POST['owner_email']) ? trim($_POST['owner_email']) : NULL;

    $success = '';

    if ($owner_name && $owner_phone && $operator != 'no_operator') {

        $owners_page = $pages->get('template=owners');
        $ownerPage = $pages->add('owner', $owners_page);

        $ownerPage->of(false);

        $ownerPage->title = $owner_name;
        $ownerPage->phone = $owner_phone;
        $ownerPage->email = $owner_email;

        $ownerPage->save();

        $success = 'Клиент успешно зарегистрирован';
        $session->redirect('/klient-prosmotr/?idowner=' . $ownerPage->id);

    } else {
        $success = 'Клиент не зарегистрирован!<br>Ошибка в данных';
    }

    $info = '';
    if ($success == 'Клиент успешно зарегистрирован') {
        $info .= '
            <p class="uk-margin-remove">Клиент успешно зарегистрирован<br>Сейчас произойдёт переход на страницу клиента</p>
        ';
    } else {
        $info .= '
            <p class="uk-margin-remove">Клиент не зарегистрирован!<br>Произошла ошибка, возможно некорректные данные или не заполнены обязательные поля.<br>Попробуйте позже или обратитесь в техподдержку</p>
            <a class="uk-margin-small-top uk-button uk-button-default" href="/klienty-spravochnik/">Вернуться в справочник</a>
        ';
    }

}

?>

<div id="content">
    <h1 class="uk-margin-remove uk-heading-hero uk-text-center"><?php echo $success; ?></h1>
    <div>

        <div>
            <div class="pagemenu uk-width-1-1 uk-flex">
                <a class="menu-link" href="/">На главную</a>
                <a class="menu-link" href="/klienty-spravochnik/">Справочник клиентов</a>
            </div>
        </div>

        <div>
            <div class="uk-card uk-card-default uk-card-body uk-flex uk-flex-column">
                <?php echo $info; ?>
            </div>
        </div>

    </div>
</div>
