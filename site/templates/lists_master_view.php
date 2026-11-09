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
        <h1 class="uk-heading-hero uk-text-center">Мастер Просмотр</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php    
} else {

    $master_id = $input->get->int('idmaster');

    if (!$master_id) {
        echo '<div id="content" style="max-width: 700px;">
            <h1 class="uk-heading-hero uk-text-center">Мастер Просмотр</h1>
            <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
                <h3 class="uk-card-title uk-text-center">Не передан ID мастера</h3>
                <a class="uk-margin-small uk-button uk-button-default" href="/">На главную</a>
            </div>
        </div>';
        return;
    }

    $masterPage = $pages->get("id=$master_id, template=master");

    if (!$masterPage->id) {
        echo '<div id="content" style="max-width: 700px;">
            <h1 class="uk-heading-hero uk-text-center">Мастер Просмотр</h1>
            <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
                <h3 class="uk-card-title uk-text-center">Мастер не найден</h3>
                <a class="uk-margin-small uk-button uk-button-default" href="/">На главную</a>
            </div>
        </div>';
        return;
    }

    if (!function_exists('masterClean')) {
        function masterClean($value) {
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        }
    }

    $master = [
        'id'      => $masterPage->id,
        'title'   => $masterPage->title,
        'post'    => $masterPage->post,
        'percent' => $masterPage->percent
    ];

?>

<div id="content">
    <h1 class="uk-margin-remove uk-heading-hero uk-text-center"><?php echo masterClean($master['title']); ?></h1>
    <div>

        <div>
            <div class="pagemenu uk-width-1-1 uk-flex">
                <a class="menu-link" href="/">На главную</a>
                <a class="menu-link" href="/mastera-spravochnik/">Справочник мастеров</a>
            </div>
        </div>

        <div>
            <div class="uk-card uk-card-default uk-card-body uk-flex uk-flex-column">

                <div class="order-view-top">
                    <div>
                        <div class="order-view-number"><?php echo masterClean($master['title']); ?></div>
                        <div class="order-view-subtitle">Карточка мастера ID <?php echo masterClean($master['id']); ?></div>
                    </div>

                    <div class="order-status-badge" title="Процент от работ">
                        <?php echo (int)$master['percent']; ?> %
                    </div>
                </div>

                <?php if ($input->get->int('saved') === 1) { ?>
                    <div class="uk-alert-success uk-margin-small-top" uk-alert>
                        <a class="uk-alert-close" uk-close></a>
                        <p class="uk-margin-remove">Изменения сохранены</p>
                    </div>
                <?php } elseif ($input->get('saved') !== null && $input->get->int('saved') === 0) { ?>
                    <div class="uk-alert-danger uk-margin-small-top" uk-alert>
                        <a class="uk-alert-close" uk-close></a>
                        <p class="uk-margin-remove">Не удалось сохранить изменения</p>
                    </div>
                <?php } ?>

                <!--БЛОК ПРОСМОТРА-->
                <div id="master_view_block">
                    <div class="order-view-grid uk-margin-small-top">
                        <div class="order-info-box">
                            <div class="order-info-label">ФИО</div>
                            <div class="order-info-value"><?php echo masterClean($master['title']); ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Должность</div>
                            <div class="order-info-value"><?php echo !empty($master['post']) ? masterClean($master['post']) : '—'; ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Процент от работ</div>
                            <div class="order-info-value"><?php echo ($master['percent'] === null || $master['percent'] === '') ? '0 %' : (int)$master['percent'] . ' %'; ?></div>
                        </div>
                    </div>

                    <div class="uk-margin-small-top uk-flex uk-flex-column">
                        <button type="button" class="uk-margin-small-top uk-button uk-button-default" id="toggle_edit_btn">
                            Изменить
                        </button>
                        <a class="uk-margin-small-top uk-button uk-button-default" href="/">Перейти на главную</a>
                    </div>
                </div>
                <!--БЛОК ПРОСМОТРА-->

                <!--БЛОК РЕДАКТИРОВАНИЯ (скрыт по умолчанию)-->
                <form id="master_edit_form" class="uk-flex uk-flex-column" action="/master-redaktirovanie/" method="post" hidden>
                    <input type="hidden" name="master_id" value="<?php echo masterClean($master['id']); ?>">

                    <div class="uk-margin-small-top">
                        <label for="master_title">ФИО</label>
                        <input class="uk-input" id="master_title" type="text" name="master_title" value="<?php echo masterClean($master['title']); ?>" placeholder="Например: Иванов Иван Иванович" autocomplete="off" required>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="master_post">Должность</label>
                        <input class="uk-input" id="master_post" type="text" name="master_post" value="<?php echo masterClean($master['post']); ?>" placeholder="Например: Моторист" autocomplete="off">
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="master_percent">Процент от работ (%)</label>
                        <input class="uk-input" id="master_percent" type="number" name="master_percent" min="0" max="100" step="1" value="<?php echo ($master['percent'] === null || $master['percent'] === '') ? '' : (int)$master['percent']; ?>" placeholder="Например: 40" autocomplete="off">
                    </div>

                    <div class="uk-margin-small-top uk-flex uk-flex-column">
                        <button type="submit" class="uk-margin-small-top uk-button uk-button-default" name="save_master_changes" value="1">
                            Сохранить изменения
                        </button>
                        <button type="button" class="uk-margin-small-top uk-button uk-button-default" id="cancel_edit_btn">
                            Отмена
                        </button>
                    </div>
                </form>
                <!--БЛОК РЕДАКТИРОВАНИЯ-->

            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // --- Показ/скрытие формы редактирования ---
    var viewBlock = document.getElementById('master_view_block');
    var editForm  = document.getElementById('master_edit_form');
    var toggleBtn = document.getElementById('toggle_edit_btn');
    var cancelBtn = document.getElementById('cancel_edit_btn');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            viewBlock.hidden = true;
            editForm.hidden  = false;
            editForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            editForm.hidden  = true;
            viewBlock.hidden = false;
            viewBlock.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }
});
</script>

<?php   
}
?>