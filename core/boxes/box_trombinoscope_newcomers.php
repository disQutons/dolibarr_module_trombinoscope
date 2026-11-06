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
 * \file    trombinoscope/core/boxes/box_trombinoscope_newcomers.php
 * \ingroup trombinoscope
 * \brief   Widget « Nouveaux arrivants » du trombinoscope
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

/**
 * Widget listant les dernières personnes arrivées (durée « nouveau » de la configuration)
 */
class box_trombinoscope_newcomers extends ModeleBoxes
{
	public $boxcode = "trombinoscopenewcomers";
	public $boximg = "object_user";
	public $boxlabel = "TrombinoscopeBoxNewcomers";
	public $depends = array("trombinoscope");

	public $enabled = 1;

	/**
	 * Constructeur
	 *
	 * @param	DoliDB	$db		Base de données
	 * @param	string	$param	Paramètres éventuels
	 */
	public function __construct($db, $param = '')
	{
		global $user;

		$this->db = $db;
		$this->param = $param;
		$this->hidden = !($user->hasRight('trombinoscope', 'read') && empty($user->socid));
	}

	/**
	 * Charge le contenu du widget
	 *
	 * @param	int		$max	Nombre maximum de lignes
	 * @return	void
	 */
	public function loadBox($max = 5)
	{
		global $langs;

		$langs->load("trombinoscope@trombinoscope");
		dol_include_once('/trombinoscope/class/trombinoscope.class.php');

		$this->max = $max;
		$this->info_box_head = array(
			'text' => $langs->trans("TrombinoscopeBoxNewcomers"),
			'sublink' => dol_buildpath('/trombinoscope/trombinoscope.php', 1),
			'subtext' => $langs->trans("Trombinoscope"),
			'subpicto' => 'fa-id-badge',
		);

		$manager = new TrombinoscopeManager($this->db);
		$people = $manager->fetchPeople($max, 1);

		if (!is_array($people)) {
			$this->info_box_contents[0][0] = array(
				'td' => '',
				'maxlength' => 500,
				'text' => dol_escape_htmltag($manager->error),
			);
			return;
		}

		$line = 0;
		foreach ($people as $person) {
			$name = dol_escape_htmltag($person->fullname);
			if ($person->url) {
				$name = '<a href="'.dol_escape_htmltag($person->url).'">'.$name.'</a>';
			}
			$this->info_box_contents[$line][0] = array(
				'td' => 'class="nowraponall" style="width: 40px"',
				'text' => '<img src="'.dol_escape_htmltag($person->photourl).'" loading="lazy" alt="" style="width: 32px; height: 32px; object-fit: cover; border-radius: 50%; vertical-align: middle;">',
				'asis' => 1,
			);
			$this->info_box_contents[$line][1] = array(
				'td' => 'class="tdoverflowmax200"',
				'text' => $name,
				'asis' => 1,
			);
			$this->info_box_contents[$line][2] = array(
				'td' => 'class="right nowraponall"',
				'text' => dol_print_date($person->datesince, 'day'),
			);
			$line++;
		}

		if ($line == 0) {
			$this->info_box_contents[0][0] = array(
				'td' => 'class="center"',
				'text' => '<span class="opacitymedium">'.$langs->trans("None").'</span>',
				'asis' => 1,
			);
		}
	}

	/**
	 * Affiche le widget
	 *
	 * @param	array<array{text?:string,sublink?:string,subpicto:?string,nbcol?:int,limit?:int,subclass?:string,graph?:string}>|null	$head	Tableau d'en-tête
	 * @param	array<array<array{tr?:string,td?:string,target?:string,text?:string,text2?:string,textnoformat?:string,tooltip?:string,logo?:string,url?:string,maxlength?:string,asis?:int}>>|null	$contents	Contenu
	 * @param	int<0,1>	$nooutput	1 = retourne le HTML sans l'afficher
	 * @return	string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
