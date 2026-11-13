<?php
/** Phase 1C-D regressions, included by run.php with isolated WordPress/store doubles. */
use Papijo\H5PThemerWordPress\Admin\Admin_Service;
use Papijo\H5PThemerWordPress\Admin\Admin_View;
use Papijo\H5PThemerWordPress\Admin\Admin_Controller;

$uxStart = $checks;
$uxStore = new Admin_Test_Store();
$uxService = new Admin_Service($uxStore);
$uxUrl = 'http://isolated.invalid/wp-admin/admin.php?page=pjh5pwp-themes';
$uxView = new Admin_View($uxUrl, $preview);
ok($uxService, post_for($uxStore, 'initialize'));
foreach (array('ux_a'=>'UX A', 'ux_b'=>'UX B') as $id=>$label) {
	ok($uxService, post_for($uxStore, 'create', '', extra($id, $label)));
}

// Exercise the real controller's save/redirect, then render with isolated preview CSS.
$uxController = new Admin_Controller(static function() use ($uxService) { return $uxService; });
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = post_for($uxStore, 'edit', 'custom:ux_a', extra('ux_a', 'UX A'));
try { $uxController->load(); throw new RuntimeException('Expected theme-save redirect'); }
catch (Pjh5pwp_Test_Redirect $e) {}
same(303, $GLOBALS['pjh5pwp_test_redirect'][1], 'Theme save uses PRG');
parse_str(parse_url($GLOBALS['pjh5pwp_test_redirect'][0], PHP_URL_QUERY), $_GET);
same('edit', $_GET['pjh5pwp_view'], 'Save remains on editing page');
same('custom:ux_a', $_GET['pjh5pwp_source'], 'Save retains edited identity');
same('1', $_GET['pjh5pwp_saved'], 'Save confirmation marker');
$uxHtml = $uxView->render($uxService->status(), $_GET['pjh5pwp_view'], $_GET['pjh5pwp_source'], array(), array(), '1' === $_GET['pjh5pwp_saved']);
check(false !== strpos($uxHtml, '<div class="notice notice-success"><p>Theme saved successfully.</p></div>'), 'Exact WordPress-style theme save confirmation');
same(33, substr_count($uxHtml, 'data-pjh5pwp-color='), 'Saved theme keeps editable colors');
check(false !== strpos($uxHtml, 'name="label" value="UX A" required maxlength="255">'), 'Saved name remains editable');
check(false !== strpos($uxHtml, 'name="pjh5pwp_op" value="edit"'), 'Saved form remains an edit operation');
ok($uxService, post_for($uxStore, 'edit', 'custom:ux_a', extra('ux_a', 'UX A revised')));
same('UX A revised', $uxStore->state['definitions']['ux_a']['label'], 'Further edits can be saved after successful save');
$uxNotice = $uxView->render($uxService->status(), 'overview', '', array(), array(), true);
check(false !== strpos($uxNotice, 'Theme library saved.'), 'Overview operations retain their general confirmation');
check(false === strpos($uxNotice, 'Theme saved successfully.'), 'Overview does not claim a theme edit was saved');

foreach (array(array('edit','custom:ux_a'), array('create',''), array('duplicate','builtin:dark')) as $route) {
	$uxHtml = $uxView->render($uxService->status(), $route[0], $route[1]);
	$found = preg_match('~<button[^>]*>Save theme</button>\s*<a class="button" href="([^"]+)">Back to theme overview</a>~', $uxHtml, $match);
	check(1 === $found, 'Non-submit navigation anchor immediately follows Save on ' . $route[0]);
	if (1 === $found) { same($uxUrl, html_entity_decode($match[1], ENT_QUOTES, 'UTF-8'), 'Navigation targets clean overview URL'); }
	same(1, substr_count($uxHtml, '>Back to theme overview</a>'), 'Single prominent overview navigation');
	same(0, substr_count($uxHtml, '>Theme overview</a>'), 'No duplicate old overview navigation on editor');
}
$uxBad = post_for($uxStore, 'edit', 'custom:ux_a', extra('ux_a', 'Unsaved name'));
$uxBad['colors']['--h5p-theme-background'] = 'invalid-color';
$uxResult = rejects($uxService, $uxBad, 'invalid_hex_color');
$uxHtml = $uxView->render($uxService->status(), 'edit', 'custom:ux_a', $uxBad, $uxResult['errors']);
check(false !== strpos($uxHtml, 'notice notice-error'), 'Validation error remains visible');
check(false !== strpos($uxHtml, 'value="Unsaved name"'), 'Unsaved name retained');
check(false !== strpos($uxHtml, 'value="invalid-color"'), 'Invalid color retained for correction');
check(false === strpos($uxHtml, 'Theme saved successfully.'), 'Failed save has no success confirmation');
same('UX A revised', $uxStore->state['definitions']['ux_a']['label'], 'Failed save does not alter saved name');

function ux_catalog_row($html, $token) {
	$found = preg_match('~<tr>(?:(?!</tr>).)*<code>' . preg_quote($token, '~') . '</code>(?:(?!</tr>).)*</tr>~s', $html, $match);
	check(1 === $found, 'Catalog row exists: ' . $token);
	return 1 === $found ? $match[0] : '';
}
foreach (array('ux_a','ux_b') as $defaultId) {
	ok($uxService, post_for($uxStore, 'default', '', array('default_theme'=>'custom:' . $defaultId)));
	$uxWrites = $uxStore->writes;
	$uxHtml = $uxView->render($uxService->status());
	same($uxWrites, $uxStore->writes, 'Catalog rendering does not write');
	check(false !== strpos($uxHtml, '<p>Manage H5P color themes, publication settings, and the global default theme. Learners can configure their Theme and Density preferences in their WordPress profiles when enabled.</p>'), 'Current administration description');
	check(false === strpos($uxHtml, 'playback styling and Density application are not implemented'), 'Obsolete administration description removed');
	foreach (array('ux_a','ux_b') as $id) {
		$row = ux_catalog_row($uxHtml, 'custom:' . $id);
		$labels = $id === $defaultId ? array('Edit / rename', 'Duplicate', 'Export colors') : array('Edit / rename', 'Duplicate', 'Delete', 'Export colors');
		preg_match('~<td>([^<]*(?:<a .*?</a>|<button .*?</button>).*?)</td></tr>~s', $row, $actions);
		same(implode(' · ', $labels), strip_tags($actions[1]), 'Every custom operation has the same separator and spacing');
		check(false !== strpos($row, 'form="pjh5pwp-export-' . $id . '"'), 'Export retains its dedicated form target');
		same($id !== $defaultId, false !== strpos($row, '>Delete</a>'), 'Delete only available for non-default custom: ' . $id);
		same($id === $defaultId, false !== strpos($row, '>Selected</td>'), 'Current default identified in catalog');
	}
	$uxBefore = $uxStore->state;
	rejects($uxService, post_for($uxStore, 'delete', 'custom:' . $defaultId, array('confirm'=>'1')), 'theme_is_global_default');
	same($uxBefore, $uxStore->state, 'Direct default deletion leaves state intact');
	$blocked = $uxView->render($uxService->status(), 'delete', 'custom:' . $defaultId);
	check(false !== strpos($blocked, 'Select another global default before deleting this theme.'), 'Direct default deletion explanation retained');
	check(false === strpos($blocked, '<form'), 'Direct default deletion has no confirmation form');
}
ok($uxService, post_for($uxStore, 'default', '', array('default_theme'=>'core')));
$uxHtml = $uxView->render($uxService->status());
foreach (array('ux_a','ux_b') as $id) { check(false !== strpos(ux_catalog_row($uxHtml, 'custom:' . $id), '>Delete</a>'), 'Delete restored after Core selected'); }
foreach (array('daylight','lavender','mint','sunset','dark') as $id) {
	$row = ux_catalog_row($uxHtml, 'builtin:' . $id);
	check(false !== strpos($row, '>View</a> · <a ') && false !== strpos($row, '>Duplicate</a></td>'), 'Builtin separators and operations unchanged: ' . $id);
	check(false === strpos($row, '>Delete</a>') && false === strpos($row, '>Edit / rename</a>'), 'Builtin has no mutation links: ' . $id);
	$uxBefore = $uxStore->state;
	rejects($uxService, post_for($uxStore, 'delete', 'builtin:' . $id, array('confirm'=>'1')), 'theme_not_mutable');
	rejects($uxService, post_for($uxStore, 'edit', 'builtin:' . $id, extra($id)), 'theme_not_mutable');
	same($uxBefore, $uxStore->state, 'Builtin mutation attempts leave state intact');
}
echo 'Phase 1C-D focused UX: ' . ($checks - $uxStart) . " assertions passed.\n";
