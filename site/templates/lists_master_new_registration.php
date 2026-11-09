<?php namespace ProcessWire;

$today = date("d-m-Y");

$success = '';   // объявляем заранее: нижний HTML читает её даже при потерянной сессии
$info    = '';

if(isset($_SESSION['operator'])){
    $operator = $_SESSION['operator'];
} else {
    $operator = 'no_operator';
}

if ($operator == 'no_operator') {
?>
    <div id="content" style="max-width: 700px;">
        <h1 class="uk-heading-hero uk-text-center">Новый мастер Регистрация</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php    
} else {

    $master_title   = !empty($_POST['master_title']) ? trim($_POST['master_title']) : NULL;
	$master_post    = !empty($_POST['master_post'])  ? trim($_POST['master_post'])  : '';
    $master_percent = $input->post->int('master_percent');   // 0 допустим

    if ($master_title && $master_percent >= 0 && $master_percent <= 100) {

        $masters_page = $pages->get('name=mastera');

        if ($masters_page && $masters_page->id) {

            $masterPage = $pages->add('master', $masters_page);

            if ($masterPage && $masterPage->id) {

                $masterPage->of(false);

                $masterPage->title   = $master_title;
                $masterPage->post    = $master_post;
                $masterPage->percent = $master_percent;

                $masterPage->save();

                $success = 'Мастер успешно зарегистрирован';

                $session->redirect('/master-prosmotr/?idmaster=' . $masterPage->id);

            } else {
                $success = 'Мастер не зарегистрирован!<br>Ошибка при создании страницы';
            }

        } else {
            $success = 'Мастер не зарегистрирован!<br>Не найдена папка «Мастера» (name=mastera)';
        }

    } else {
        $success = 'Мастер не зарегистрирован!<br>Ошибка в данных';
    }

    if ($success == 'Мастер успешно зарегистрирован') {
        $info .= '
            <p class="uk-margin-remove">Мастер успешно зарегистрирован<br>Сейчас произойдёт переход на страницу мастера</p>
        ';
    } else {
        $info .= '
            <p class="uk-margin-remove">' . $success . '<br>Возможно некорректные данные или не заполнены обязательные поля.<br>Попробуйте позже или обратитесь в техподдержку</p>
            <a class="uk-margin-small-top uk-button uk-button-default" href="/mastera-spravochnik/">Вернуться в справочник</a>
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
                <a class="menu-link" href="/mastera-spravochnik/">Справочник мастеров</a>
            </div>
        </div>

        <div>
            <div class="uk-card uk-card-default uk-card-body uk-flex uk-flex-column">
                <?php echo $info; ?>
            </div>
        </div>

    </div>
</div>