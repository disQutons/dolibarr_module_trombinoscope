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
 * \file    trombinoscope/css/trombinoscope.css.php
 * \ingroup trombinoscope
 * \brief   Feuille de style du trombinoscope (couleurs issues de la configuration du module)
 */

if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1);
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

session_cache_limiter('public');

// Load Dolibarr environment
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
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
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/../main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/../main.inc.php";
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

header('Content-type: text/css');
// Cache navigateur : CTRL+F5 pour forcer le rafraîchissement après un changement de couleur
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=10800, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}

/**
 * Couleur de la configuration au format #rrggbb (la valeur est stockée avec ou sans #)
 *
 * @param	string	$constname	Nom de la constante
 * @param	string	$default	Couleur par défaut sans #
 * @return	string
 */
function trombinoscopeCssColor($constname, $default)
{
	$color = ltrim(getDolGlobalString($constname, $default), '#');
	if (!preg_match('/^[0-9a-f]{3}([0-9a-f]{3})?$/i', $color)) {
		$color = $default;
	}
	return '#'.$color;
}

$colormain = trombinoscopeCssColor('TROMBINOSCOPE_COLOR_MAIN', '006eff');
$colornew = trombinoscopeCssColor('TROMBINOSCOPE_COLOR_NEW', '41af1b');
$colorold = trombinoscopeCssColor('TROMBINOSCOPE_COLOR_OLD', 'ff0000');

// Format des photos : 'auto' = proportions d'origine, sinon ratio largeur/hauteur recadré
$ratio = getDolGlobalString('TROMBINOSCOPE_PHOTO_RATIO', '1/1');
if (!preg_match('/^\d+\/\d+$/', $ratio)) {
	$ratio = 'auto';
}
$cardwidth = max(100, min(400, getDolGlobalInt('TROMBINOSCOPE_CARD_WIDTH', 160)));

?>

.trombi-toolbar {
	margin-bottom: 15px;
}
.trombi-legend {
	margin-left: 15px;
}
.trombi-legend-new {
	border-bottom: 5px solid <?php echo $colornew; ?>;
	margin-right: 10px;
}
.trombi-legend-old {
	border-bottom: 5px solid <?php echo $colorold; ?>;
}

.trombi-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(<?php echo $cardwidth; ?>px, 1fr));
	gap: 10px;
}
.trombi-card {
	text-align: center;
	background-color: var(--colorbackbody, #ffffff);
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
	border-radius: 4px;
	overflow: hidden;
}
.trombi-photo img {
	display: block;
	width: 100%;
<?php if ($ratio != 'auto') { ?>
	aspect-ratio: <?php echo $ratio; ?>;
	object-fit: cover;
<?php } ?>
}
.trombi-infos {
	padding: 5px 4px 8px 4px;
	word-break: break-word;
}
.trombi-new .trombi-infos {
	border-top: 5px solid <?php echo $colornew; ?>;
}
.trombi-old .trombi-infos {
	border-top: 5px solid <?php echo $colorold; ?>;
}
.trombi-infos p {
	margin: 4px;
}
.trombi-name {
	font-size: 13px;
}
.trombi-name a {
	color: inherit;
}
.trombi-firstname {
	font-weight: bold;
	color: <?php echo $colormain; ?>;
}
.trombi-field {
	font-size: 12px;
}
.trombi-field-email {
	font-size: 11px;
}

