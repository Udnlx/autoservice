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
        <h1 class="uk-heading-hero uk-text-center">Работа Просмотр</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php    
} else {

    $work_id = $input->get->int('idwork');

    if (!$work_id) {
        echo '<div id="content" style="max-width: 700px;">
            <h1 class="uk-heading-hero uk-text-center">Работа Просмотр</h1>
            <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
                <h3 class="uk-card-title uk-text-center">Не передан ID работы</h3>
                <a class="uk-margin-small uk-button uk-button-default" href="/">На главную</a>
            </div>
        </div>';
        return;
    }

    $workPage = $pages->get("id=$work_id, template=work_item");

    if (!$workPage->id) {
        echo '<div id="content" style="max-width: 700px;">
            <h1 class="uk-heading-hero uk-text-center">Работа Просмотр</h1>
            <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
                <h3 class="uk-card-title uk-text-center">Работа не найдена</h3>
                <a class="uk-margin-small uk-button uk-button-default" href="/">На главную</a>
            </div>
        </div>';
        return;
    }

    if (!function_exists('workClean')) {
        function workClean($value) {
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        }
    }

    $work = [
        'id'    => $workPage->id,
        'title' => $workPage->title,
        'type'  => $workPage->work_type->title,
        'price' => $workPage->work_price,
        'time'  => $workPage->work_time,
        'notes' => $workPage->work_notes
    ];

?>

<div id="content">
    <h1 class="uk-margin-remove uk-heading-hero uk-text-center"><?php echo workClean($work['title']); ?></h1>
    <div>

        <div>
            <div class="pagemenu uk-width-1-1 uk-flex">
                <a class="menu-link" href="/">На главную</a>
                <a class="menu-link" href="/raboty-spravochnik/">Справочник работ</a>
            </div>
        </div>

        <div>
            <div class="uk-card uk-card-default uk-card-body uk-flex uk-flex-column">

                <div class="order-view-top">
                    <div>
                        <div class="order-view-number"><?php echo workClean($work['title']); ?></div>
                        <div class="order-view-subtitle">Карточка работы ID <?php echo workClean($work['id']); ?></div>
                    </div>

                    <div class="order-status-badge">
                        <?php echo (int)$work['price']; ?> ₽
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
                <div id="work_view_block">
                    <div class="order-view-grid uk-margin-small-top">
                        <div class="order-info-box">
                            <div class="order-info-label">Наименование</div>
                            <div class="order-info-value"><?php echo workClean($work['title']); ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Цена</div>
                            <div class="order-info-value"><?php echo !empty($work['price']) ? (int)$work['price'] . ' ₽' : '—'; ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Нормо-часы</div>
                            <div class="order-info-value"><?php echo !empty($work['time']) ? workClean($work['time']) . ' ч' : '—'; ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Описание</div>
                            <div class="order-info-value"><?php echo !empty($work['notes']) ? workClean($work['notes']) : '—'; ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Тип работы</div>
                            <div class="order-info-value"><?php echo !empty($work['type']) ? workClean($work['type']) : '—'; ?></div>
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
                <form id="work_edit_form" class="uk-flex uk-flex-column" action="/rabota-redaktirovanie/" method="post" hidden>
                    <input type="hidden" name="work_id" value="<?php echo workClean($work['id']); ?>">

                    <div class="uk-margin-small-top">
                        <label for="work_title">Наименование работы</label>
                        <input class="uk-input" id="work_title" type="text" name="work_title" value="<?php echo workClean($work['title']); ?>" placeholder="Например: Замена масла" autocomplete="off" required>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="work_type">Тип работы</label>
                        <select class="uk-select" id="work_type" name="work_type" required>
                            <option value="" disabled <?php echo empty($work['type']) ? 'selected' : ''; ?>>
                                Выберите тип работы
                            </option>

                            <option value="1" <?php echo $work['type'] === 'Антикор' ? 'selected' : ''; ?>>
                                Антикор
                            </option>

                            <option value="2" <?php echo $work['type'] === 'Фильтры' ? 'selected' : ''; ?>>
                                Фильтры
                            </option>

                            <option value="3" <?php echo $work['type'] === 'Турбины' ? 'selected' : ''; ?>>
                                Турбины
                            </option>
                        </select>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="work_price">Цена работы (₽)</label>
                        <input class="uk-input" id="work_price" type="number" name="work_price" value="<?php echo (int)$work['price']; ?>" placeholder="Например: 1500" autocomplete="off" required>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="work_time">Нормо-часы</label>
                        <input class="uk-input" id="work_time" type="text" name="work_time" value="<?php echo workClean($work['time']); ?>" placeholder="Например: 1.5" autocomplete="off">
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="work_notes">Описание работы</label>
                        <textarea class="uk-textarea" id="work_notes" name="work_notes" rows="3" placeholder="Что входит в работу..."><?php echo workClean($work['notes']); ?></textarea>
                    </div>

                    <div class="uk-margin-small-top uk-flex uk-flex-column">
                        <button type="submit" class="uk-margin-small-top uk-button uk-button-default" name="save_work_changes" value="1">
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
    var viewBlock = document.getElementById('work_view_block');
    var editForm  = document.getElementById('work_edit_form');
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