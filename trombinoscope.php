<?php
/* Copyright (C) 2025-2026	François Brichart			<francois@disqutons.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    trombinoscope/trombinoscope.php
 * \ingroup trombinoscope
 * \brief   Page du trombinoscope
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once __DIR__.'/class/trombinoscope.class.php';
require_once __DIR__.'/lib/trombinoscope.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$langs->loadLangs(array("trombinoscope@trombinoscope", "members", "users", "companies", "other"));

$hookmanager->initHooks(array('trombinoscopelist'));

// Contrôle d'accès
if (!isModEnabled('trombinoscope')) {
	accessforbidden('Module trombinoscope not enabled');
}
if ($user->socid > 0) {
	accessforbidden();
}
if (!$user->hasRight('trombinoscope', 'read')) {
	accessforbidden();
}


/*
 * View
 */

$manager = new TrombinoscopeManager($db);
$people = $manager->fetchPeople();

$title = $langs->trans('Trombinoscope');

llxHeader('', $title, '', '', 0, 0, '', array('/trombinoscope/css/trombinoscope.css.php'), '', 'mod-trombinoscope page-trombinoscope');

if (!is_array($people)) {
	setEventMessages($manager->error, null, 'errors');
	llxFooter();
	$db->close();
	exit;
}

$num = count($people);
$durationnew = getDolGlobalInt('TROMBINOSCOPE_NEW_DURATION', 6);

print load_fiche_titre($title.' <span class="opacitymedium">('.$num.' '.dol_escape_htmltag(trombinoscopeGetPeopleLabel()).')</span>', '', 'fa-id-badge');

print '<div class="trombi-toolbar">';
print '<input type="search" id="trombi-search" class="minwidth300" placeholder="'.dol_escape_htmltag($langs->trans('TrombinoscopeSearch')).'" autocomplete="off">';
if ($durationnew > 0) {
	print ' <span class="trombi-legend">'.$langs->trans('TrombinoscopeLegend').' : ';
	print '<span class="trombi-legend-new">'.$langs->trans('TrombinoscopeLessThan', $durationnew).'</span>';
	print ' <span class="trombi-legend-old">'.$langs->trans('TrombinoscopeMoreThan', $durationnew).'</span>';
	print '</span>';
}
print '</div>';

if ($num == 0) {
	print '<div class="opacitymedium">'.$langs->trans('TrombinoscopeNobody').'</div>';
} else {
	print '<div class="trombi-grid" id="trombi-grid">';
	foreach ($people as $person) {
		print trombinoscopeRenderCard($person);
	}
	print '</div>';
	print '<div class="opacitymedium hideobject" id="trombi-noresult">'.$langs->trans('TrombinoscopeNoResult').'</div>';
}

// Filtre de recherche côté navigateur (sans rechargement)
print '<script>
(function () {
	var input = document.getElementById("trombi-search");
	var grid = document.getElementById("trombi-grid");
	var noresult = document.getElementById("trombi-noresult");
	if (!input || !grid) {
		return;
	}
	input.addEventListener("input", function () {
		var terms = input.value.toLowerCase().trim().split(/\s+/).filter(Boolean);
		var visible = 0;
		grid.querySelectorAll(".trombi-card").forEach(function (card) {
			var text = card.getAttribute("data-search") || "";
			var match = terms.every(function (t) { return text.indexOf(t) !== -1; });
			card.style.display = match ? "" : "none";
			if (match) {
				visible++;
			}
		});
		noresult.classList.toggle("hideobject", visible > 0);
	});
})();
</script>';

llxFooter();
$db->close();
