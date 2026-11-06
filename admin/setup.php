<?php
/* Copyright (C) 2004-2017	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2024		Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2025-2026	François Brichart			<francois@disqutons.fr>
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
 * \file    trombinoscope/admin/setup.php
 * \ingroup trombinoscope
 * \brief   Page de configuration du module Trombinoscope
 */

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
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
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

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
require_once '../lib/trombinoscope.lib.php';
require_once '../class/trombinoscope.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Translations
$langs->loadLangs(array("admin", "members", "users", "companies", "trombinoscope@trombinoscope"));

$hookmanager->initHooks(array('trombinoscopesetup', 'globalsetup'));

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

// Access control
if (!$user->admin) {
	accessforbidden();
}

$manager = new TrombinoscopeManager($db);
$formSetup = new FormSetup($db);


// Section : personnes affichées
$formSetup->newItem('TrombinoscopeSetupPeopleTitle')->setAsTitle();

$sources = array('user' => $langs->trans('TrombinoscopeSourceUser'));
if (isModEnabled('adherent')) {
	$sources['member'] = $langs->trans('TrombinoscopeSourceMember');
}
$item = $formSetup->newItem('TROMBINOSCOPE_SOURCE');
$item->setAsSelect($sources);
$item->defaultFieldValue = 'user';

if (isModEnabled('adherent')) {
	$formSetup->newItem('TROMBINOSCOPE_USERS_ONLY_MEMBERS')->setAsYesNo();

	$membertypes = array();
	$sql = "SELECT rowid, libelle FROM ".$db->prefix()."adherent_type";
	$sql .= " WHERE entity IN (".getEntity('member_type').")";
	$sql .= " ORDER BY libelle";
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$membertypes[$obj->rowid] = $obj->libelle;
		}
	}
	$item = $formSetup->newItem('TROMBINOSCOPE_MEMBER_TYPES');
	$item->setAsMultiSelect($membertypes);
}

$usergroups = array();
$sql = "SELECT rowid, nom FROM ".$db->prefix()."usergroup";
$sql .= " WHERE entity IN (".getEntity('usergroup').")";
$sql .= " ORDER BY nom";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$usergroups[$obj->rowid] = $obj->nom;
	}
}
$item = $formSetup->newItem('TROMBINOSCOPE_USER_GROUPS');
$item->setAsMultiSelect($usergroups);


// Section : affichage
$formSetup->newItem('TrombinoscopeSetupDisplayTitle')->setAsTitle();

$item = $formSetup->newItem('TROMBINOSCOPE_PEOPLE_LABEL');
$item->fieldAttr['placeholder'] = $langs->trans('TrombinoscopePeople');
$item->cssClass = 'minwidth200';

$item = $formSetup->newItem('TROMBINOSCOPE_FIELDS');
$item->setAsMultiSelect($manager->getFieldsForSetup());
$item->defaultFieldValue = 'email,phone_mobile';

$item = $formSetup->newItem('TROMBINOSCOPE_SORT');
$item->setAsSelect(array(
	'firstname' => $langs->trans('Firstname'),
	'lastname' => $langs->trans('Lastname'),
));
$item->defaultFieldValue = 'firstname';

$item = $formSetup->newItem('TROMBINOSCOPE_PHOTO_RATIO');
$item->setAsSelect(array(
	'1/1' => $langs->trans('TrombinoscopeRatioSquare'),
	'3/4' => $langs->trans('TrombinoscopeRatioPortrait'),
	'4/3' => $langs->trans('TrombinoscopeRatioLandscape'),
	'2/1' => $langs->trans('TrombinoscopeRatioWide'),
	'auto' => $langs->trans('TrombinoscopeRatioAuto'),
));
$item->defaultFieldValue = '1/1';

$item = $formSetup->newItem('TROMBINOSCOPE_CARD_WIDTH');
$item->defaultFieldValue = '160';
$item->cssClass = 'maxwidth50';


// Section : nouveaux arrivants
$formSetup->newItem('TrombinoscopeSetupNewTitle')->setAsTitle();

$item = $formSetup->newItem('TROMBINOSCOPE_NEW_DURATION');
$item->defaultFieldValue = '6';
$item->cssClass = 'maxwidth50';

$item = $formSetup->newItem('TROMBINOSCOPE_COLOR_MAIN');
$item->setAsColor();
$item->defaultFieldValue = '006eff';

$item = $formSetup->newItem('TROMBINOSCOPE_COLOR_NEW');
$item->setAsColor();
$item->defaultFieldValue = '41af1b';

$item = $formSetup->newItem('TROMBINOSCOPE_COLOR_OLD');
$item->setAsColor();
$item->defaultFieldValue = 'ff0000';


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

$action = 'edit';


/*
 * View
 */

$help_url = '';
$title = "TrombinoscopeSetup";

llxHeader('', $langs->trans($title), $help_url, '', 0, 0, '', '', '', 'mod-trombinoscope page-admin');

$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

$head = trombinoscopeAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($title), -1, 'fa-id-badge');

print '<span class="opacitymedium">'.$langs->trans("TrombinoscopeSetupPage").'</span><br><br>';

print $formSetup->generateOutput(true);

print '<br><a class="butAction" href="'.dol_buildpath('/trombinoscope/trombinoscope.php', 1).'">'.img_picto('', 'fa-id-badge', 'class="pictofixedwidth"').$langs->trans('TrombinoscopeShow').'</a>';

print dol_get_fiche_end();

llxFooter();
$db->close();
