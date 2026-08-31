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
        <h1 class="uk-heading-hero uk-text-center">Автомобиль Просмотр</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php    
} else {

    $car_id = $input->get->int('idcar');

    if (!$car_id) {
        echo '<div id="content" style="max-width: 700px;">
            <h1 class="uk-heading-hero uk-text-center">Автомобиль Просмотр</h1>
            <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
                <h3 class="uk-card-title uk-text-center">Не передан ID автомобиля</h3>
                <a class="uk-margin-small uk-button uk-button-default" href="/">На главную</a>
            </div>
        </div>';
        return;
    }

    $carPage = $pages->get("id=$car_id, template=car");

    if (!$carPage->id) {
        echo '<div id="content" style="max-width: 700px;">
            <h1 class="uk-heading-hero uk-text-center">Автомобиль Просмотр</h1>
            <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
                <h3 class="uk-card-title uk-text-center">Автомобиль не найден</h3>
                <a class="uk-margin-small uk-button uk-button-default" href="/">На главную</a>
            </div>
        </div>';
        return;
    }

    if (!function_exists('carClean')) {
        function carClean($value) {
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        }
    }

    $ownerPage = $carPage->car_owner;

    $car = [
        'id'           => $carPage->id,
        'title'        => $carPage->title,
        'brand'        => $carPage->car_brand,
        'model'        => $carPage->car_model,
        'number'       => $carPage->car_number,
        'vin'          => $carPage->car_vin,
        'year'         => $carPage->car_year,
        'owner_id'     => ($ownerPage && $ownerPage->id) ? $ownerPage->id    : 0,
        'owner_title'  => ($ownerPage && $ownerPage->id) ? $ownerPage->title : '',
        'notes'        => $carPage->car_notes
    ];

    $car_title_display = trim(carClean($car['brand']) . ' ' . carClean($car['model']));
    if ($car_title_display === '') {
        $car_title_display = carClean($car['title']);
    }

    // Все клиенты для select в форме редактирования
    $all_owners = $pages->find("template=owner, sort=title, limit=500");

?>

<div id="content">
    <h1 class="uk-margin-remove uk-heading-hero uk-text-center"><?php echo $car_title_display; ?></h1>
    <div>

        <div>
            <div class="pagemenu uk-width-1-1 uk-flex">
                <a class="menu-link" href="/">На главную</a>
                <a class="menu-link" href="/avtomobili-spravochnik/">Справочник авто</a>
            </div>
        </div>

        <div>
            <div class="uk-card uk-card-default uk-card-body uk-flex uk-flex-column">

                <div class="order-view-top">
                    <div>
                        <div class="order-view-number"><?php echo $car_title_display; ?></div>
                        <div class="order-view-subtitle">Карточка автомобиля ID <?php echo carClean($car['id']); ?></div>
                    </div>

                    <?php if (!empty($car['number'])) { ?>
                        <div class="order-status-badge">
                            <?php echo carClean($car['number']); ?>
                        </div>
                    <?php } ?>
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
                <div id="car_view_block">
                    <div class="order-view-grid uk-margin-small-top">
                        <div class="order-info-box">
                            <div class="order-info-label">Марка</div>
                            <div class="order-info-value"><?php echo !empty($car['brand']) ? carClean($car['brand']) : '—'; ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Модель</div>
                            <div class="order-info-value"><?php echo !empty($car['model']) ? carClean($car['model']) : '—'; ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Гос. номер</div>
                            <div class="order-info-value"><?php echo !empty($car['number']) ? carClean($car['number']) : '—'; ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">VIN</div>
                            <div class="order-info-value"><?php echo !empty($car['vin']) ? carClean($car['vin']) : '—'; ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Год выпуска</div>
                            <div class="order-info-value"><?php echo !empty($car['year']) ? carClean($car['year']) : '—'; ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Клиент</div>
                            <div class="order-info-value">
                                <?php if ($car['owner_id']) { ?>
                                    <a href="/klient-prosmotr/?idowner=<?php echo (int)$car['owner_id']; ?>">
                                        <?php echo carClean($car['owner_title']); ?>
                                    </a>
                                <?php } else { ?>
                                    —
                                <?php } ?>
                            </div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Примечания</div>
                            <div class="order-info-value"><?php echo !empty($car['notes']) ? carClean($car['notes']) : '—'; ?></div>
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
                <form id="car_edit_form" class="uk-flex uk-flex-column" action="/avtomobil-redaktirovanie/" method="post" hidden>
                    <input type="hidden" name="car_id" value="<?php echo carClean($car['id']); ?>">

                    <div class="uk-margin-small-top">
                        <label for="car_brand">Марка</label>
                        <input class="uk-input" id="car_brand" type="text" name="car_brand" value="<?php echo carClean($car['brand']); ?>" placeholder="Например: Renault" autocomplete="off" required>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="car_model">Модель</label>
                        <input class="uk-input" id="car_model" type="text" name="car_model" value="<?php echo carClean($car['model']); ?>" placeholder="Например: Sandero" autocomplete="off" required>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="car_number">Гос. номер</label>
                        <input class="uk-input" id="car_number" type="text" name="car_number" value="<?php echo carClean($car['number']); ?>" placeholder="Например: О123ХХ58" autocomplete="off" required>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="car_vin">VIN</label>
                        <input class="uk-input" id="car_vin" type="text" name="car_vin" value="<?php echo carClean($car['vin']); ?>" placeholder="17 символов" autocomplete="off" required>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="car_year">Год выпуска</label>
                        <input class="uk-input" id="car_year" type="text" name="car_year" value="<?php echo carClean($car['year']); ?>" placeholder="Например: 2022" autocomplete="off">
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="car_owner">Клиент</label>
                        <select class="uk-select" id="car_owner" name="car_owner">
                            <option value="0">— Не выбран —</option>
                            <?php foreach ($all_owners as $ownerOption) { ?>
                                <option value="<?php echo (int)$ownerOption->id; ?>"
                                    <?php echo ($ownerOption->id === $car['owner_id']) ? 'selected' : ''; ?>>
                                    <?php echo carClean($ownerOption->title); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="car_notes">Примечания</label>
                        <textarea class="uk-textarea" id="car_notes" name="car_notes" rows="3" placeholder="Особенности, замечания..."><?php echo carClean($car['notes']); ?></textarea>
                    </div>

                    <div class="uk-margin-small-top uk-flex uk-flex-column">
                        <button type="submit" class="uk-margin-small-top uk-button uk-button-default" name="save_car_changes" value="1">
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
document.addEventListener('DOMContentLoaded', function() {
    var viewBlock = document.getElementById('car_view_block');
    var editForm  = document.getElementById('car_edit_form');
    var toggleBtn = document.getElementById('toggle_edit_btn');
    var cancelBtn = document.getElementById('cancel_edit_btn');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            viewBlock.hidden = true;
            editForm.hidden  = false;
            editForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
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