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
        <h1 class="uk-heading-hero uk-text-center">Мастера Справочник</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php    
} else {

    if (!function_exists('masterClean')) {
        function masterClean($value) {
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        }
    }

    $search_query = trim((string)$input->get('q'));
    $search_active = $search_query !== '';

    $found_masters = [];

    if ($search_active) {
        // Поиск по ФИО и должности
        $safe_query = $sanitizer->selectorValue($search_query);
        $masters_result = $pages->find("template=master, title|post%=$safe_query, limit=100");
    } else {
        // Последние 30 мастеров, новые вверху
        $masters_result = $pages->find("template=master, sort=-created, limit=30");
    }

    foreach ($masters_result as $masterPage) {
        $found_masters[] = [
            'id'      => $masterPage->id,
            'title'   => $masterPage->title,
            'post'    => $masterPage->post,
            'percent' => $masterPage->percent
        ];
    }

?>

<div id="content">
    <h1 class="uk-margin-remove uk-heading-hero uk-text-center">Мастера Справочник</h1>
    <div>

        <div>
            <div class="pagemenu uk-width-1-1 uk-flex">
                <a class="menu-link" href="/">На главную</a>
                <a class="menu-link" href="#new_master_modal" uk-toggle>Новый мастер</a>
            </div>
        </div>

        <div>
            <div class="uk-card uk-card-default uk-card-body uk-flex uk-flex-column">

                <form class="uk-flex uk-flex-column" action="/mastera-spravochnik/" method="get" style="margin:0;">
                    <label for="q">Поиск мастера</label>

                    <div class="uk-flex uk-flex-middle order-add-row" style="gap: 10px;">
                        <input class="uk-input" id="q" type="text" name="q" value="<?php echo masterClean($search_query); ?>" placeholder="ФИО или должность" autocomplete="off">

                        <button type="submit" class="uk-button uk-button-default" style="margin: 0 !important;">
                            Найти
                        </button>
                    </div>
                </form>

                <?php if (empty($found_masters)) { ?>
                    <div class="order-cart-empty uk-margin-small-top uk-text-center">
                        <?php if ($search_active) { ?>
                            Ничего не найдено по запросу «<?php echo masterClean($search_query); ?>»
                        <?php } else { ?>
                            Пока нет мастеров в справочнике
                        <?php } ?>
                    </div>
                <?php } else { ?>
                    <div class="orders-list uk-flex uk-flex-column uk-margin-small-top">
                        <?php foreach ($found_masters as $master) { ?>
                            <a class="order-list-item" href="/master-prosmotr/?idmaster=<?php echo (int)$master['id']; ?>">
                                <div class="order-list-item-top">
                                    <div class="order-list-item-title"><?php echo masterClean($master['title']); ?></div>
                                    <div class="order-status-badge status-new" title="Процент от работ"><?php echo (int)$master['percent']; ?> %</div>
                                </div>

                                <div class="order-list-item-grid">
                                    <div class="order-list-item-cell">
                                        <div class="order-list-item-label">ФИО</div>
                                        <div class="order-list-item-value"><?php echo masterClean($master['title']); ?></div>
                                    </div>

                                    <div class="order-list-item-cell">
                                        <div class="order-list-item-label">Должность</div>
                                        <div class="order-list-item-value"><?php echo !empty($master['post']) ? masterClean($master['post']) : '—'; ?></div>
                                    </div>

                                    <div class="order-list-item-cell">
                                        <div class="order-list-item-label">Процент</div>
                                        <div class="order-list-item-value"><?php echo ($master['percent'] === null || $master['percent'] === '') ? '—' : (int)$master['percent'] . ' %'; ?></div>
                                    </div>
                                </div>
                            </a>
                        <?php } ?>
                    </div>
                <?php } ?>

            </div>
        </div>

    </div>

<!--МОДАЛЬНОЕ ОКНО НОВЫЙ МАСТЕР-->
<div id="new_master_modal" uk-modal>
    <div class="uk-modal-dialog uk-modal-body">
        <button class="uk-modal-close-default" type="button" uk-close></button>

        <h3 class="uk-card-title uk-text-center">Новый мастер</h3>

        <form class="uk-flex uk-flex-column" action="/master-registratciia/" method="post">

            <div class="uk-margin-small-top">
                <label for="master_title">ФИО</label>
                <input class="uk-input" id="master_title" type="text" name="master_title" placeholder="Например: Иванов Иван Иванович" autocomplete="off" required>
            </div>

            <div class="uk-margin-small-top">
                <label for="master_post">Должность</label>
                <input class="uk-input" id="master_post" type="text" name="master_post" placeholder="Например: Моторист" autocomplete="off">
            </div>

            <div class="uk-margin-small-top">
                <label for="master_percent">Процент от работ (%)</label>
                <input class="uk-input" id="master_percent" type="number" name="master_percent" min="0" max="100" step="1" placeholder="Например: 40" autocomplete="off">
            </div>

            <div class="uk-margin-small-top uk-flex uk-flex-column">
                <button type="submit" class="uk-margin-small-top uk-button uk-button-default" name="save_new_master" value="1">
                    Зарегистрировать
                </button>
            </div>

        </form>
    </div>
</div>
<!--МОДАЛЬНОЕ ОКНО НОВЫЙ МАСТЕР-->

</div>

<?php   
}
?>