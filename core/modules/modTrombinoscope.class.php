<?php
/* Copyright (C) 2004-2018	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019	Nicolas ZABOURI				<info@inovea-conseil.com>
 * Copyright (C) 2019-2024	Frédéric France				<frederic.france@free.fr>
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
 * 	\defgroup   trombinoscope     Module Trombinoscope
 *  \brief      Descripteur du module Trombinoscope.
 *
 *  \file       htdocs/trombinoscope/core/modules/modTrombinoscope.class.php
 *  \ingroup    trombinoscope
 *  \brief      Fichier de description et d'activation du module Trombinoscope
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Classe de description et d'activation du module Trombinoscope
 */
class modTrombinoscope extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;

		$this->db = $db;

		$this->numero = 173327;
		$this->rights_class = 'trombinoscope';
		$this->family = "hr";
		$this->module_position = '90';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "TrombinoscopeDescription";
		$this->descriptionlong = "TrombinoscopeDescriptionLong";
		$this->editor_name = 'disQutons';
		$this->editor_url = 'https://www.disqutons.fr';		// Must be an external online web site
		$this->editor_squarred_logo = 'logo_disQutons.png@trombinoscope';
		$this->version = '1.0';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'fa-id-badge';

		$this->module_parts = array(
			'triggers' => 0,
			'login' => 0,
			'substitutions' => 0,
			'menus' => 0,
			'tpl' => 0,
			'barcode' => 0,
			'models' => 0,
			'printing' => 0,
			'theme' => 0,
			'css' => array(),
			'js' => array(),
			'hooks' => array(),
			'moduleforexternal' => 0,
			'websitetemplates' => 0,
			'captcha' => 0
		);

		$this->dirs = array();
		$this->config_page_url = array("setup.php@trombinoscope");

		$this->hidden = getDolGlobalInt('MODULE_TROMBINOSCOPE_DISABLED'); // A condition to disable module;
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();

		$this->langfiles = array("trombinoscope@trombinoscope");

		$this->phpmin = array(7, 1); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(19, -3); // Minimum version of Dolibarr required by module
		$this->need_javascript_ajax = 0;

		$this->warnings_activation = array();
		$this->warnings_activation_ext = array();

		$this->const = array();

		if (!isModEnabled("trombinoscope")) {
			$conf->trombinoscope = new stdClass();
			$conf->trombinoscope->enabled = 0;
		}

		$this->tabs = array();
		$this->dictionaries = array();

		// Widget « Nouveaux arrivants »
		$this->boxes = array(
			0 => array(
				'file' => 'box_trombinoscope_newcomers.php@trombinoscope',
				'note' => '',
				'enabledbydefaulton' => 'Home',
			),
		);

		$this->cronjobs = array();

		// Permissions
		$this->rights = array();
		$r = 0;
		$this->rights[$r][0] = $this->numero.sprintf("%02d", 1); // 17332701
		$this->rights[$r][1] = 'Voir le trombinoscope';
		$this->rights[$r][4] = 'read';
		$this->rights[$r][5] = '';
		$r++;

		// Menus : le trombinoscope se range dans le menu du module Intranet s'il est activé,
		// sinon dans son propre menu principal. Les conditions 'enabled' sont évaluées à chaque
		// affichage, il n'est donc pas nécessaire de réactiver le module si Intranet est activé après.
		$this->menu = array();
		$r = 0;
		$this->menu[$r++] = array(
			'fk_menu' => '',
			'type' => 'top',
			'titre' => 'Trombinoscope',
			'prefix' => img_picto('', $this->picto, 'class="pictofixedwidth valignmiddle"'),
			'mainmenu' => 'trombinoscope',
			'leftmenu' => '',
			'url' => '/trombinoscope/trombinoscope.php',
			'langs' => 'trombinoscope@trombinoscope',
			'position' => 1000 + $r,
			'enabled' => 'isModEnabled("trombinoscope") && !isModEnabled("intranet")',
			'perms' => '$user->hasRight("trombinoscope", "read")',
			'target' => '',
			'user' => 0, // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=trombinoscope',
			'type' => 'left',
			'titre' => 'Trombinoscope',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu' => 'trombinoscope',
			'leftmenu' => 'trombinoscope',
			'url' => '/trombinoscope/trombinoscope.php',
			'langs' => 'trombinoscope@trombinoscope',
			'position' => 1000 + $r,
			'enabled' => 'isModEnabled("trombinoscope") && !isModEnabled("intranet")',
			'perms' => '$user->hasRight("trombinoscope", "read")',
			'target' => '',
			'user' => 0,
		);
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=intranet',
			'type' => 'left',
			'titre' => 'Trombinoscope',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu' => 'intranet',
			'leftmenu' => 'trombinoscope',
			'url' => '/trombinoscope/trombinoscope.php',
			'langs' => 'trombinoscope@trombinoscope',
			'position' => 1000,
			'enabled' => 'isModEnabled("trombinoscope") && isModEnabled("intranet")',
			'perms' => '$user->hasRight("trombinoscope", "read")',
			'target' => '',
			'user' => 0,
		);
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int<-1,1>          	1 if OK, <=0 if KO
	 */
	public function init($options = '')
	{
		$this->remove($options);

		// Première activation sur une instance qui utilisait le trombinoscope du module Intranet
		$this->migrateFromIntranet();

		$sql = array();

		if (function_exists('curl_init')) {
			$ping_url = 'https://disqutons.fr/dolibarr/custom/statsmodules/ver.php'
				.'?m='.urlencode($this->rights_class)
				.'&v='.urlencode($this->version)
				.'&d='.urlencode(DOL_VERSION)
				.'&h='.md5(DOL_DATA_ROOT);
			$ch = curl_init($ping_url);
			curl_setopt_array($ch, array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT        => 1,
				CURLOPT_CONNECTTIMEOUT => 1,
				CURLOPT_FOLLOWLOCATION => false,
				CURLOPT_SSL_VERIFYPEER => true,
				CURLOPT_USERAGENT      => 'Dolibarr/'.DOL_VERSION.' modTrombinoscope/'.$this->version,
			));
			curl_exec($ch);
		}

		return $this->_init($sql, $options);
	}

	/**
	 * Reprend la configuration du trombinoscope du module Intranet, une seule fois :
	 * uniquement si le Trombinoscope n'a encore jamais été configuré et si les constantes
	 * d'Intranet existent.
	 *
	 * @return void
	 */
	protected function migrateFromIntranet()
	{
		global $conf, $langs;

		if (getDolGlobalString('TROMBINOSCOPE_SOURCE') !== '' || getDolGlobalString('INTRANET_DUREE_NEW') === '') {
			return;
		}

		require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

		$entity = $conf->entity;
		$values = array(
			'TROMBINOSCOPE_SOURCE' => getDolGlobalInt('INTRANET_ADHERENT') ? 'member' : 'user',
			'TROMBINOSCOPE_USERS_ONLY_MEMBERS' => getDolGlobalInt('INTRANET_TROMBI_USERS_SANS_ADHERENT') ? '0' : '1',
			'TROMBINOSCOPE_NEW_DURATION' => (string) getDolGlobalInt('INTRANET_DUREE_NEW'),
			'TROMBINOSCOPE_PHOTO_RATIO' => 'auto', // Les photos ont déjà été recadrées par le module Intranet
		);
		foreach (array('MAIN', 'NEW', 'OLD') as $color) {
			if (getDolGlobalString('INTRANET_COLOR_'.$color) !== '') {
				$values['TROMBINOSCOPE_COLOR_'.$color] = getDolGlobalString('INTRANET_COLOR_'.$color);
			}
		}

		// Champs affichés par le trombinoscope d'Intranet : pronoms, référent, téléphone mobile, e-mail
		$fields = array();
		$extrafields = new ExtraFields($this->db);
		$extrafields->fetch_name_optionals_label('user');
		if (!empty($extrafields->attributes['user']['label']['pronoms'])) {
			$fields[] = 'ef_user_pronoms';
		}
		if (isModEnabled('adherent')) {
			$extrafields->fetch_name_optionals_label('adherent');
			if (!empty($extrafields->attributes['adherent']['label']['referent'])) {
				$fields[] = 'ef_member_referent';
			}
		}
		$fields[] = 'phone_mobile';
		$fields[] = 'email';
		$values['TROMBINOSCOPE_FIELDS'] = implode(',', $fields);

		// Libellé des personnes repris de la traduction d'Intranet (« Bénévoles »)
		$langs->load('intranet@intranet');
		if ($langs->transnoentitiesnoconv('Benevoles') != 'Benevoles') {
			$values['TROMBINOSCOPE_PEOPLE_LABEL'] = $langs->transnoentitiesnoconv('Benevoles');
		}

		foreach ($values as $name => $value) {
			dolibarr_set_const($this->db, $name, $value, 'chaine', 0, '', $entity);
		}
	}

	/**
	 *	Function called when module is disabled.
	 *	Remove from database constants, boxes and permissions from Dolibarr database.
	 *	Data directories are not deleted
	 *
	 *	@param	string		$options	Options when enabling module ('', 'noboxes')
	 *	@return	int<-1,1>				1 if OK, <=0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
