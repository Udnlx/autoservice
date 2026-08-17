<?php

namespace ProcessWire;

require_once '../index.php';

ini_set('max_execution_time', 0);
ini_set('memory_limit', '4096M');

// УДАЛЕНИЕ ПАКЕТОМ
$del_books = $pages->find('template=book_itm, author_book^=Х, sort=author_book');
$books = '';
echo count($del_books);

foreach ($del_books as $item) {
$books .= $item->title . '<br>';
// $item->delete();
}

// // УДАЛЕНИЕ ПО ОДНОЙ
// $del_book = $pages->get('template=book_itm, id=8502');
// $books = $del_book->id;
// $del_book->delete();

?>







<div id="content">
	<div class="uk-001">
		<div class="uk-001-content uk-margin-auto" style="min-height: 400px;">
			<h1>REMOVE BOOKS</h1>
			<?php echo $books ?>
		</div>
	</div>
</div>