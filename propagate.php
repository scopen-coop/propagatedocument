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

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
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
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
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

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';


$error = 0;

$langs->load('propagatedocument@propagatedocument');

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$element = GETPOST('element', 'alpha');
$data_all_post = GETPOST('data_all', 'none');

if ($action=='propagate') {
	$dataFiles=json_decode(html_entity_decode($data_all_post), true);
	if (!empty($dataFiles) && is_array($dataFiles)) {
		foreach ($dataFiles as $key=>$data) {
			if (GETPOSTISSET('objfile_'.$key)) {
				if (empty($data['destfilenamepath'])) {
					//var_dump($data['filesrcfullpath'], $data['destfilepath'].'/'.$data['file']);
					if (!dol_is_dir($data['destfilepath'])) {
						dol_mkdir($data['destfilepath']);
					}
					$resultCopy = dol_copy($data['filesrcfullpath'], $data['destfilepath'].'/'.$data['file']);
					if ($resultCopy<0) {
						var_dump($data['filesrcfullpath']);
						var_dump($data['destfilepath'].'/'.$data['file']);
						setEventMessage($langs->trans('ErrorFailToCopyFile'), 'errors');
					} else {
						setEventMessage($langs->trans('FileCopied',$data['file'],$data['ref']));
					}
				}
			} else {
				if (!empty($data['destfilenamepath'])) {
					$resultDelFile = dol_delete_file($data['destfilenamepath']);
					if ($resultDelFile) {
						setEventMessage($langs->trans('FileDeleted', $data['file'], $data['ref']));
					} else {
						setEventMessage('ErrorFailToDeleteFile', 'errors');
					}
				}
			}
		}
	}
}


top_htmlhead('', '');

print '<div class="fichecenter">';

print load_fiche_titre($langs->trans("PropagateDoc"), '', 'title_setup');

$res = 0;
if (!empty($id)) {
	if ($element == 'commande') {
		require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
		$object = new Commande($db);
	}
	$resultFetch = $object->fetch($id);
	if ($resultFetch < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
		$error++;
	}
	if (empty($error)) {
		$dataAll=array();

		$org = $conf->{$element}->multidir_output[isset($object->entity) ? $object->entity : $conf->entity] . '/' . dol_sanitizeFileName($object->ref);
		$file_list = dol_dir_list($org, 'files');
		if (!empty($file_list)) {
			//List objectLinked
			$resultLinkObj = $object->fetchObjectLinked();
			if ($resultLinkObj < 0) {
				setEventMessages('Module PropagateDocument:' . $object->error, 'errors');
				$error++;
			}

			if (empty($error)) {

				print '<form method="post" action="' . $_SERVER["PHP_SELF"] . '">';
				print '<input type="hidden" name="token" value="' . newToken() . '">';
				print '<input type="hidden" name="action" value="propagate">';
				print '<table class="noborder centpercent">';
				foreach ($object->linkedObjects as $type => $dataByType) {
					print '<tr class="liste_titre">';
					print '<td>' . $langs->trans('Ref') . '</td>';
					foreach ($file_list as $files) {
						print '<td>' . $files['name'] . '</td>';
					}
					print '</tr>';
					foreach ($dataByType as $linkedObject) {
						print '<tr>';
						print '<td>' . $linkedObject->ref . '</td>';
						foreach ($file_list as $files) {
							$chkid = $linkedObject->element . '_' . $linkedObject->id . '_' . htmlentities($files['name']);
							$dataFile=array('element'=>$linkedObject->element,
											'id'=>$linkedObject->id,
											'ref'=>$linkedObject->ref,
											'file'=>$files['name'],
											'filesrcfullpath'=>$files['fullname']
							);
							//find if file is alredy in dest object
							if ($linkedObject->element == 'order_supplier') {
								$destdir_element = $conf->fournisseur->commande->multidir_output[isset($linkedObject->entity) ? $linkedObject->entity : $conf->entity];
							} elseif ($linkedObject->element == 'invoice_supplier') {
								$destdir_element = $conf->fournisseur->facture->multidir_output[isset($linkedObject->entity) ? $linkedObject->entity : $conf->entity];
							} else {
								$destdir_element = $destdir_element = $conf->{$linkedObject->element}->multidir_output[isset($linkedObject->entity) ? $linkedObject->entity : $conf->entity];
							}
							if (!empty($destdir_element)) {
								$destdir = $destdir_element . '/' . dol_sanitizeFileName($linkedObject->ref);
								$dataFile['destfilepath']= $destdir;
								if (file_exists($destdir . '/' . $files['name'])) {
									$checked = "checked";
									$dataFile['destfilenamepath']= $destdir . '/' . $files['name'];
								} else {
									$checked = "";
									$dataFile['destfilenamepath']='';

								}
								$iddata=md5($chkid);
								print '<td><input type="checkbox" ' . $checked . ' name="objfile_'.$iddata.'" id="objfile_'.$iddata.'" value="objfile_' . $chkid . '"/></td>';

								$dataAll[$iddata]=$dataFile;
							} else {
								print '<td>' . $langs->trans('CannotFindDestPath') . '</td>';
							}

						}
						print '</tr>';
					}
				}

				print '</table>';
			}
		}

		print '<input type="hidden" name="data_all" value="'.htmlentities(json_encode($dataAll)).'">';
		print '<div class="center">' . "\n";
		//print dolGetButtonAction($langs->trans("Cancel"), '', 'default', '' . 'cancel', '', 1);
		//print dolGetButtonAction($langs->trans("Copy"), '', 'default', '' . 'valid', '', 1);
		print '<input class="button button-save" type="submit" value="' . $langs->trans("Copy").'/'.$langs->trans("Delete") . '">';
		print '</div>' . "\n";
		print '</form>' . "\n";
	}
}
print '</div>';
llxFooter();
