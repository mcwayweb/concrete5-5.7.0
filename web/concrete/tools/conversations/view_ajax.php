<? defined('C5_EXECUTE') or die("Access Denied."); ?>
<? 
$cnv = Conversation::getByID($_POST['cnvID']);
if (is_object($cnv)) {
	$displayForm = true;

	$enablePosting = ($_POST['enablePosting'] == 1) ? true : false;
	$paginate = ($_POST['paginate'] == 1) ? true : false;

	switch($_POST['task']) {
		case 'get_messages':
			$displayForm = false;
			break;
	}

	$ml = new ConversationMessageList($cnv);

	switch($_POST['orderBy']) {
		case 'date_desc':
			$ml->sortByDateDescending();
			break;
	}

	if ($paginate && Loader::helper('validation/numbers')->integer($_POST['itemsPerPage'])) {
		$ml->setItemsPerPage($_POST['itemsPerPage']);
	} else {
		$ml->setItemsPerPage(-1);
	}

	$summary = $ml->getSummary();
	$totalPages = $summary->pages;

	$args = array(
		'conversation' => $cnv,
		'messages' => $ml->getPage(),
		'displayForm' => $displayForm,
		'enablePosting' => $enablePosting,
		'currentPage' => 1,
		'totalPages' => $totalPages,
		'orderBy' => $_POST['orderBy']
	);

	Loader::element('conversation/display', $args);
}