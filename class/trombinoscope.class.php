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
 * \file    trombinoscope/class/trombinoscope.class.php
 * \ingroup trombinoscope
 * \brief   Construction de la liste des personnes affichées dans le trombinoscope.
 *
 * Le module ne crée aucune table : il lit les utilisateurs (llx_user) et/ou les adhérents
 * (llx_adherent) de Dolibarr, ainsi que leurs champs complémentaires.
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once __DIR__.'/../lib/trombinoscope.lib.php';

/**
 * Classe de lecture des personnes du trombinoscope
 */
class TrombinoscopeManager
{
	/** @var DoliDB */
	public $db;

	/** @var string Message d'erreur */
	public $error = '';

	/** @var ExtraFields Champs complémentaires des adhérents et des utilisateurs */
	public $extrafields;

	/**
	 * Constructeur
	 *
	 * @param DoliDB $db Base de données
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->extrafields = new ExtraFields($db);
		if (isModEnabled('adherent')) {
			$this->extrafields->fetch_name_optionals_label('adherent');
		}
		$this->extrafields->fetch_name_optionals_label('user');
	}

	/**
	 * Source principale des personnes : 'member' (adhérents) ou 'user' (utilisateurs).
	 * Le mode adhérents n'est possible que si le module Adhérents est activé.
	 *
	 * @return string
	 */
	public static function getSource()
	{
		if (getDolGlobalString('TROMBINOSCOPE_SOURCE', 'user') == 'member' && isModEnabled('adherent')) {
			return 'member';
		}
		return 'user';
	}

	/**
	 * Expression SQL qui prend la valeur de la fiche source, ou à défaut celle de l'autre fiche.
	 *
	 * @param	string	$memberexpr	Colonne côté adhérent (alias a)
	 * @param	string	$userexpr	Colonne côté utilisateur (alias u)
	 * @return	string
	 */
	protected function coalesce($memberexpr, $userexpr)
	{
		if (!isModEnabled('adherent')) {
			// Pas de jointure sur les adhérents : seule la fiche utilisateur existe
			return $userexpr;
		}
		if (self::getSource() == 'member') {
			return 'COALESCE('.$memberexpr.', '.$userexpr.')';
		}
		return 'COALESCE('.$userexpr.', '.$memberexpr.')';
	}

	/**
	 * Champs standards affichables sous le nom de la personne.
	 *
	 * @return array<string,array{label:string,sql:string,type:string}>
	 */
	public function getStandardFields()
	{
		$fields = array(
			'email' => array('label' => 'Email', 'sql' => $this->coalesce('a.email', 'u.email'), 'type' => 'email'),
			'phone_mobile' => array('label' => 'PhoneMobile', 'sql' => $this->coalesce('a.phone_mobile', 'u.user_mobile'), 'type' => 'phone'),
			'phone_pro' => array('label' => 'PhonePro', 'sql' => $this->coalesce('a.phone', 'u.office_phone'), 'type' => 'phone'),
			'town' => array('label' => 'Town', 'sql' => $this->coalesce('a.town', 'u.town'), 'type' => 'text'),
			'job' => array('label' => 'PostOrFunction', 'sql' => 'u.job', 'type' => 'text'),
		);
		if (isModEnabled('adherent')) {
			$fields['membertype'] = array('label' => 'MemberType', 'sql' => 't.libelle', 'type' => 'text');
		}
		return $fields;
	}

	/**
	 * Champs complémentaires affichables, préfixés par ef_member_ ou ef_user_.
	 *
	 * @return array<string,array{label:string,elementtype:string,name:string}>
	 */
	public function getExtraFields()
	{
		global $langs;

		$fields = array();
		$prefixes = array('adherent' => 'ef_member_', 'user' => 'ef_user_');
		$titles = array('adherent' => $langs->trans('Member'), 'user' => $langs->trans('User'));
		foreach ($prefixes as $elementtype => $prefix) {
			if (empty($this->extrafields->attributes[$elementtype]['label']) || !is_array($this->extrafields->attributes[$elementtype]['label'])) {
				continue;
			}
			foreach ($this->extrafields->attributes[$elementtype]['label'] as $name => $label) {
				$type = $this->extrafields->attributes[$elementtype]['type'][$name];
				if ($type == 'separate' || !preg_match('/^[a-z0-9_]+$/i', $name)) {
					continue;
				}
				$fields[$prefix.$name] = array(
					'label' => $titles[$elementtype].' - '.$langs->trans($label),
					'elementtype' => $elementtype,
					'name' => $name,
				);
			}
		}
		return $fields;
	}

	/**
	 * Liste de tous les champs proposés dans la configuration (clé => libellé traduit).
	 *
	 * @return array<string,string>
	 */
	public function getFieldsForSetup()
	{
		global $langs;

		$list = array();
		foreach ($this->getStandardFields() as $key => $def) {
			$list[$key] = $langs->trans($def['label']);
		}
		foreach ($this->getExtraFields() as $key => $def) {
			$list[$key] = $def['label'];
		}
		return $list;
	}

	/**
	 * Champs choisis dans la configuration, dans l'ordre de la liste des champs proposés.
	 *
	 * @return array<string>
	 */
	public function getSelectedFields()
	{
		$selected = array_filter(explode(',', getDolGlobalString('TROMBINOSCOPE_FIELDS', 'email,phone_mobile')));
		$available = array_keys($this->getStandardFields() + $this->getExtraFields());
		return array_values(array_intersect($available, $selected));
	}

	/**
	 * Liste des identifiants entiers d'une constante "1,4,7".
	 *
	 * @param	string	$constname	Nom de la constante
	 * @return	array<int>
	 */
	protected static function getIdsFromConst($constname)
	{
		$ids = array();
		foreach (explode(',', getDolGlobalString($constname)) as $id) {
			if ((int) $id > 0) {
				$ids[] = (int) $id;
			}
		}
		return $ids;
	}

	/**
	 * Retourne les personnes à afficher.
	 *
	 * @param	int		$limit		Nombre maximum de personnes (0 = toutes)
	 * @param	int		$onlynew	1 = uniquement les nouveaux arrivants, triés du plus récent au plus ancien
	 * @return	array<int,stdClass>|int	Tableau d'objets (voir fin de méthode), ou -1 en cas d'erreur
	 */
	public function fetchPeople($limit = 0, $onlynew = 0)
	{
		global $user;

		$source = self::getSource();
		$standardfields = $this->getStandardFields();
		$extrafieldsdef = $this->getExtraFields();
		$selectedfields = $this->getSelectedFields();
		$durationnew = getDolGlobalInt('TROMBINOSCOPE_NEW_DURATION', 6);
		$memberenabled = isModEnabled('adherent');

		$sql = "SELECT u.rowid as uid, u.photo as uphoto,";
		$sql .= " ".$this->coalesce('a.firstname', 'u.firstname')." as firstname,";
		$sql .= " ".$this->coalesce('a.lastname', 'u.lastname')." as lastname,";
		$sql .= " ".$this->coalesce('a.gender', 'u.gender')." as gender,";
		$sql .= " ".$this->coalesce('a.datec', 'u.datec')." as datesince";
		if ($memberenabled) {
			$sql .= ", a.rowid as aid, a.ref as aref, a.photo as aphoto";
		}
		foreach ($selectedfields as $key) {
			if (isset($standardfields[$key])) {
				$sql .= ", ".$standardfields[$key]['sql']." as f_".$key;
			} else {
				$alias = ($extrafieldsdef[$key]['elementtype'] == 'adherent') ? 'ae' : 'ue';
				$sql .= ", ".$alias.".".$extrafieldsdef[$key]['name']." as f_".$key;
			}
		}

		if ($source == 'member') {
			$sql .= " FROM ".$this->db->prefix()."adherent as a";
			$sql .= " LEFT JOIN ".$this->db->prefix()."user as u ON u.fk_member = a.rowid";
		} else {
			$sql .= " FROM ".$this->db->prefix()."user as u";
			if ($memberenabled) {
				$sql .= " LEFT JOIN ".$this->db->prefix()."adherent as a ON a.rowid = u.fk_member";
			}
		}
		if ($memberenabled) {
			$sql .= " LEFT JOIN ".$this->db->prefix()."adherent_type as t ON t.rowid = a.fk_adherent_type";
			$sql .= " LEFT JOIN ".$this->db->prefix()."adherent_extrafields as ae ON ae.fk_object = a.rowid";
		}
		$sql .= " LEFT JOIN ".$this->db->prefix()."user_extrafields as ue ON ue.fk_object = u.rowid";

		if ($source == 'member') {
			// Adhérents validés, personnes physiques uniquement
			$sql .= " WHERE a.entity IN (".getEntity('adherent').")";
			$sql .= " AND a.statut = 1 AND a.morphy = 'phy'";
			$typeids = self::getIdsFromConst('TROMBINOSCOPE_MEMBER_TYPES');
			if (count($typeids)) {
				$sql .= " AND a.fk_adherent_type IN (".$this->db->sanitize(implode(',', $typeids)).")";
			}
		} else {
			// Utilisateurs internes actifs
			$sql .= " WHERE u.entity IN (".getEntity('user').")";
			$sql .= " AND u.statut = 1 AND u.fk_soc IS NULL";
			if ($memberenabled && getDolGlobalInt('TROMBINOSCOPE_USERS_ONLY_MEMBERS')) {
				$sql .= " AND a.rowid IS NOT NULL AND a.statut = 1";
			}
			$groupids = self::getIdsFromConst('TROMBINOSCOPE_USER_GROUPS');
			if (count($groupids)) {
				$sql .= " AND u.rowid IN (SELECT ugu.fk_user FROM ".$this->db->prefix()."usergroup_user as ugu";
				$sql .= " WHERE ugu.fk_usergroup IN (".$this->db->sanitize(implode(',', $groupids))."))";
			}
		}

		$datelimitnew = ($durationnew > 0) ? dol_time_plus_duree(dol_now(), -$durationnew, 'm') : 0;
		if ($onlynew) {
			if (empty($datelimitnew)) {
				return array();
			}
			$sql .= " AND ".$this->coalesce('a.datec', 'u.datec')." >= '".$this->db->idate($datelimitnew)."'";
			$sql .= $this->db->order('datesince', 'DESC');
		} elseif (getDolGlobalString('TROMBINOSCOPE_SORT', 'firstname') == 'lastname') {
			$sql .= $this->db->order('lastname,firstname', 'ASC,ASC');
		} else {
			$sql .= $this->db->order('firstname,lastname', 'ASC,ASC');
		}
		if ($limit > 0) {
			$sql .= $this->db->plimit($limit, 0);
		}

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}

		$canreadmember = $memberenabled && $user->hasRight('adherent', 'lire');
		$canreaduser = $user->hasRight('user', 'user', 'lire');

		$people = array();
		while ($obj = $this->db->fetch_object($resql)) {
			$person = new stdClass();
			$person->firstname = (string) $obj->firstname;
			$person->lastname = (string) $obj->lastname;
			$person->fullname = trim($person->firstname.' '.$person->lastname);
			$datesince = $this->db->jdate($obj->datesince);
			$person->isnew = ($datelimitnew && $datesince && $datesince >= $datelimitnew) ? 1 : 0;
			$person->datesince = $datesince;
			$person->photourl = trombinoscopeGetPhotoUrl($obj, $source == 'member', (string) $obj->gender);

			// Lien vers la fiche, uniquement si l'utilisateur a le droit de la consulter
			$person->url = '';
			$aid = $memberenabled ? (int) $obj->aid : 0;
			if ($source == 'member' && $aid && $canreadmember) {
				$person->url = DOL_URL_ROOT.'/adherents/card.php?rowid='.$aid;
			} elseif ($obj->uid && ($canreaduser || (int) $obj->uid == (int) $user->id)) {
				$person->url = DOL_URL_ROOT.'/user/card.php?id='.((int) $obj->uid);
			} elseif ($aid && $canreadmember) {
				$person->url = DOL_URL_ROOT.'/adherents/card.php?rowid='.$aid;
			}

			// Valeurs des champs choisis, déjà mises en forme en HTML
			$person->fields = array();
			foreach ($selectedfields as $key) {
				$value = $obj->{'f_'.$key};
				if ($value === null || $value === '') {
					continue;
				}
				if (isset($standardfields[$key])) {
					$html = $this->formatStandardValue($standardfields[$key]['type'], (string) $value);
					$label = $standardfields[$key]['label'];
				} else {
					$def = $extrafieldsdef[$key];
					$html = $this->extrafields->showOutputField($def['name'], $value, '', $def['elementtype']);
					$label = $def['label'];
				}
				if ($html !== '') {
					$person->fields[$key] = array('label' => $label, 'html' => $html);
				}
			}

			$people[] = $person;
		}
		$this->db->free($resql);

		return $people;
	}

	/**
	 * Mise en forme HTML d'une valeur de champ standard.
	 *
	 * @param	string	$type	email, phone ou text
	 * @param	string	$value	Valeur brute
	 * @return	string
	 */
	protected function formatStandardValue($type, $value)
	{
		if ($type == 'email' && isValidEmail($value)) {
			return '<a href="mailto:'.dol_escape_htmltag($value).'">'.dol_escape_htmltag($value).'</a>';
		}
		if ($type == 'phone') {
			return '<a href="tel:'.dol_escape_htmltag(preg_replace('/[^0-9+]/', '', $value)).'">'.dol_escape_htmltag($value).'</a>';
		}
		return dol_escape_htmltag($value);
	}
}
