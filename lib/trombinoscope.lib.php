<?php
/* Copyright (C) 2026		François Brichart			<francois@disqutons.fr>
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
 * \file    trombinoscope/lib/trombinoscope.lib.php
 * \ingroup trombinoscope
 * \brief   Fonctions communes du module Trombinoscope
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

/**
 * Retourne l'URL de la photo d'une personne, servie par le viewimage.php du cœur.
 *
 * Cherche d'abord la vignette générée par Dolibarr (photos/thumbs/xxx_small.ext, 480 px max),
 * puis la photo d'origine. Essaie la photo adhérent puis la photo utilisateur, ou l'inverse
 * selon $preferMember. Les modulepart 'memberphoto' et 'userphoto' sont autorisés par le cœur
 * à tout utilisateur connecté, dès lors que le chemin est de la forme <ref ou id>/photos/...
 * Sans photo, retourne l'image anonyme du thème (homme/femme selon le genre si renseigné).
 *
 * @param	object	$obj			Ligne SQL contenant aref, aphoto, uid, uphoto
 * @param	bool	$preferMember	true = photo adhérent prioritaire, false = photo utilisateur prioritaire
 * @param	string	$gender			Genre ('man', 'woman' ou vide) pour l'image par défaut
 * @return	string					URL de l'image
 */
function trombinoscopeGetPhotoUrl($obj, $preferMember, $gender = '')
{
	global $conf;

	$sources = array();
	if (isModEnabled('adherent') && !empty($obj->aphoto) && !empty($obj->aref)) {
		// Les fichiers d'un adhérent sont rangés sous sa ref (cf. get_exdir()), pas sous son rowid
		$sources['memberphoto'] = array($conf->adherent->dir_output, dol_sanitizeFileName($obj->aref), $obj->aphoto);
	}
	if (!empty($obj->uphoto) && !empty($obj->uid)) {
		$sources['userphoto'] = array($conf->user->dir_output, (string) ((int) $obj->uid), $obj->uphoto);
	}
	if (!$preferMember) {
		$sources = array_reverse($sources, true);
	}

	foreach ($sources as $modulepart => $source) {
		list($dir, $subdir, $photo) = $source;
		if (empty($dir)) {
			continue;
		}
		foreach (array(getImageFileNameForSize($photo, '_small'), $photo) as $filename) {
			$relativepath = $subdir.'/photos/'.$filename;
			$fullpath = $dir.'/'.$relativepath;
			if (dol_is_file($fullpath)) {
				// Le paramètre cache active le cache navigateur ; sa valeur change si la photo est remplacée
				return DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart
					.'&entity='.((int) $conf->entity)
					.'&cache='.((int) dol_filemtime($fullpath))
					.'&file='.urlencode($relativepath);
			}
		}
	}

	if ($gender == 'man') {
		return DOL_URL_ROOT.'/public/theme/common/user_man.png';
	}
	if ($gender == 'woman') {
		return DOL_URL_ROOT.'/public/theme/common/user_woman.png';
	}
	return DOL_URL_ROOT.'/public/theme/common/user_anonymous.png';
}

/**
 * Retourne le libellé des personnes affiché dans les titres (ex : « Bénévoles »).
 *
 * @return string
 */
function trombinoscopeGetPeopleLabel()
{
	global $langs;

	$label = getDolGlobalString('TROMBINOSCOPE_PEOPLE_LABEL');
	return $label !== '' ? $label : $langs->trans('TrombinoscopePeople');
}

/**
 * Retourne le code HTML de la fiche d'une personne.
 *
 * @param	stdClass	$person		Personne retournée par TrombinoscopeManager::fetchPeople()
 * @return	string
 */
function trombinoscopeRenderCard($person)
{
	global $langs;

	$durationnew = getDolGlobalInt('TROMBINOSCOPE_NEW_DURATION', 6);
	$classstatus = ($durationnew > 0) ? ($person->isnew ? ' trombi-new' : ' trombi-old') : '';

	// Texte utilisé par le filtre de recherche (nom + valeurs affichées)
	$searchtext = $person->fullname;
	foreach ($person->fields as $field) {
		$searchtext .= ' '.dol_string_nohtmltag($field['html']);
	}

	$out = '<div class="trombi-card'.$classstatus.'" data-search="'.dol_escape_htmltag(dol_strtolower($searchtext)).'">';
	$out .= '<div class="trombi-photo">';
	$out .= '<img src="'.dol_escape_htmltag($person->photourl).'" loading="lazy" decoding="async" alt="'.dol_escape_htmltag($person->fullname).'">';
	$out .= '</div>';
	$out .= '<div class="trombi-infos">';
	$name = '<span class="trombi-firstname">'.dol_escape_htmltag($person->firstname).'</span> <span class="trombi-lastname">'.dol_escape_htmltag($person->lastname).'</span>';
	if ($person->url) {
		$name = '<a href="'.dol_escape_htmltag($person->url).'">'.$name.'</a>';
	}
	$out .= '<p class="trombi-name">'.$name.'</p>';
	foreach ($person->fields as $key => $field) {
		$out .= '<p class="trombi-field trombi-field-'.dol_escape_htmltag($key).'" title="'.dol_escape_htmltag($langs->trans($field['label'])).'">'.$field['html'].'</p>';
	}
	$out .= '</div>';
	$out .= '</div>';

	return $out;
}

/**
 * Prépare les onglets des pages d'administration
 *
 * @return array<array{string,string,string}>
 */
function trombinoscopeAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("trombinoscope@trombinoscope");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/trombinoscope/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/trombinoscope/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'trombinoscope@trombinoscope');
	complete_head_from_modules($conf, $langs, null, $head, $h, 'trombinoscope@trombinoscope', 'remove');

	return $head;
}
