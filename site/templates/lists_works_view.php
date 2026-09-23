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
        <h1 class="uk-heading-hero uk-text-center">Работы Справочник</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php    
} else {

    if (!function_exists('workClean')) {
        function workClean($value) {
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        }
    }

    $search_query = trim((string)$input->get('q'));
    $search_active = $search_query !== '';

    $found_works = [];

    if ($search_active) {
        // Поиск по запросу
        $safe_query = $sanitizer->selectorValue($search_query);
        $works_result = $pages->find("template=work_item, title%=$safe_query, limit=100");
    } else {
        // Последние 30 работ, новые вверху
        $works_result = $pages->find("template=work_item, sort=-created, limit=30");
    }

    foreach ($works_result as $workPage) {
        $found_works[] = [
            'id'         => $workPage->id,
            'title'      => $workPage->title,
            'price'      => $workPage->work_price,
            'time'       => $workPage->work_time,
            'notes'      => $workPage->work_notes
        ];
    }

?>

<div id="content">
    <h1 class="uk-margin-remove uk-heading-hero uk-text-center">Работы Справочник</h1>
    <div>

        <div>
            <div class="pagemenu uk-width-1-1 uk-flex">
                <a class="menu-link" href="/">На главную</a>
                <a class="menu-link" href="#new_work_modal" uk-toggle>Новая работа</a>
            </div>
        </div>

        <div>
            <div class="uk-card uk-card-default uk-card-body uk-flex uk-flex-column">

                <form class="uk-flex uk-flex-column" action="/raboty-spravochnik/" method="get" style="margin:0;">
                    <label for="q">Поиск работы</label>

                    <div class="uk-flex uk-flex-middle order-add-row" style="gap: 10px;">
                        <input class="uk-input" id="q" type="text" name="q" value="<?php echo workClean($search_query); ?>" placeholder="Введите название работы" autocomplete="off">

                        <button type="submit" class="uk-button uk-button-default" style="margin: 0 !important;">
                            Найти
                        </button>
                    </div>
                </form>

                <?php if (empty($found_works)) { ?>
                    <div class="order-cart-empty uk-margin-small-top uk-text-center">
                        <?php if ($search_active) { ?>
                            Ничего не найдено по запросу «<?php echo workClean($search_query); ?>»
                        <?php } else { ?>
                            Пока нет работ в справочнике
                        <?php } ?>
                    </div>
                <?php } else { ?>
                    <div class="orders-list uk-flex uk-flex-column uk-margin-small-top">
                        <?php foreach ($found_works as $work) { ?>
                            <a class="order-list-item" href="/rabota-prosmotr/?idwork=<?php echo (int)$work['id']; ?>">
                                <div class="order-list-item-top">
                                    <div class="order-list-item-title"><?php echo workClean($work['title']); ?></div>
                                    <div class="order-status-badge status-new"><?php echo (int)$work['price']; ?> ₽</div>
                                </div>

                                <div class="order-list-item-grid">
                                    <div class="order-list-item-cell">
                                        <div class="order-list-item-label">Цена</div>
                                        <div class="order-list-item-value"><?php echo !empty($work['price']) ? (int)$work['price'] . ' ₽' : '—'; ?></div>
                                    </div>

                                    <div class="order-list-item-cell">
                                        <div class="order-list-item-label">Нормо-часы</div>
                                        <div class="order-list-item-value"><?php echo !empty($work['time']) ? workClean($work['time']) . ' ч' : '—'; ?></div>
                                    </div>

                                    <div class="order-list-item-cell">
                                        <div class="order-list-item-label">Описание</div>
                                        <div class="order-list-item-value"><?php echo !empty($work['notes']) ? workClean($work['notes']) : '—'; ?></div>
                                    </div>
                                </div>
                            </a>
                        <?php } ?>
                    </div>
                <?php } ?>

            </div>
        </div>

    </div>

<!--МОДАЛЬНОЕ ОКНО НОВАЯ РАБОТА-->
<div id="new_work_modal" uk-modal>
    <div class="uk-modal-dialog uk-modal-body">
        <button class="uk-modal-close-default" type="button" uk-close></button>

        <h3 class="uk-card-title uk-text-center">Новая работа</h3>

        <form class="uk-flex uk-flex-column" action="/rabota-registratciia/" method="post">

            <div class="uk-margin-small-top">
                <label for="work_title">Наименование работы</label>
                <input class="uk-input" id="work_title" type="text" name="work_title" placeholder="Например: Замена масла" autocomplete="off" required>
            </div>

            <div class="uk-margin-small-top">
                <label for="work_price">Цена работы (₽)</label>
                <input class="uk-input" id="work_price" type="number" name="work_price" placeholder="Например: 1500" autocomplete="off" required>
            </div>

            <div class="uk-margin-small-top">
                <label for="work_time">Нормо-часы</label>
                <input class="uk-input" id="work_time" type="text" name="work_time" placeholder="Например: 1.5" autocomplete="off">
            </div>

            <div class="uk-margin-small-top">
                <label for="work_notes">Описание работы</label>
                <textarea class="uk-textarea" id="work_notes" name="work_notes" rows="3" placeholder="Что входит в работу..."></textarea>
            </div>

            <div class="uk-margin-small-top uk-flex uk-flex-column">
                <button type="submit" class="uk-margin-small-top uk-button uk-button-default" name="save_new_work" value="1">
                    Зарегистрировать
                </button>
            </div>

        </form>
    </div>
</div>
<!--МОДАЛЬНОЕ ОКНО НОВАЯ РАБОТА-->

</div>

<?php   
}
?>