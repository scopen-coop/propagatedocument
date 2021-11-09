<?php
/* Copyright (C) 2021 Florian HENRY <florian.henry@scopen.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    propagatedocument/class/actions_propagatedocument.class.php
 * \ingroup propagatedocument
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsPropagateDocument
 */
class ActionsPropagateDocument
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Execute action
	 *
	 * @param	array			$parameters		Array of parameters
	 * @param	CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string			$action      	'add', 'update', 'view'
	 * @return	int         					<0 if KO,
	 *                           				=0 if OK but we want to process standard actions too,
	 *                            				>0 if OK and we want to replace standard actions.
	 */
	public function getNomUrl($parameters, &$object, &$action)
	{
		global $db, $langs, $conf, $user;
		$this->resprints = '';
		return 0;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {	    // do something only for the context 'somecontext1' or 'somecontext2'
			// Do what you want here...
			// You can for example call global vars like $fieldstosearchall to overwrite them, or update database depending on $action and $_POST values.
		}

		if (!$error) {
			$this->results = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 * Execute action addMoreActionsButtons
	 *
	 * @param   array           $parameters     Array of parameters
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         'add', 'update', 'view'
	 * @param   Hookmanager     $hookmanager    hookmanager
	 * @return  int                             <0 if KO,
	 *                                          =0 if OK but we want to process standard actions too,
	 *                                          >0 if OK and we want to replace standard actions.
	 */
	public function addMoreActionsButtons(&$parameters, &$object, &$action, $hookmanager) {

		global $conf, $user, $langs;
		global $hookmanager;

		$outputlangs = $langs;
		$ret = 0;
		dol_syslog(get_class($this).'::executeHooks action='.$action);

		if (in_array($parameters['currentcontext'], array('ordercard'))) {

			//If file exists
			$org = $conf->commande->multidir_output[isset($object->entity) ? $object->entity :$conf->entity] . '/' . dol_sanitizeFileName($object->ref);
			$file_list = dol_dir_list($org, 'files');
			if (!empty($file_list)) {
				//List objectLinked
				$resultLinkObj=$object->fetchObjectLinked();
				if ($resultLinkObj < 0) {
					setEventMessages('Module PropagateDocument:'.$object->error, 'errors');
					//$this->errors[] = $object->error;
					return 0;
				}
				if (!empty($object->linkedObjects) && is_array($object->linkedObjects)) {
					foreach ($object->linkedObjects as $type => $dataByType) {
						foreach ($dataByType as $linkedObject) {
							//print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=factoryreciept&level=1">' . $langs->trans('factoryreciept') . '</a>';
						}
					}
				}
				//Create button that display popup with row file colum doclink
				foreach ($file_list as $files) {

				}
			}





		}

		return 0;
	}


	/* Add here any other hooked methods... */
}
