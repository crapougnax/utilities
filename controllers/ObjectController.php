<?php

use t41;
use t41\Core;
use t41\View;

require_once 'vendor/crapougnax/t41/controllers/DefaultController.php';

class Utilities_ObjectController extends t41_DefaultController {

	public function init()
	{
		parent::init();
    View::setTemplate('default.html');
    View::addCoreLib(['core.js','object.js','locale.js']);
    View::addEvent("t41.locale.lang='fr'", 'js');

    $defaultMenu = Core\Layout::getMenu('main');

    $menu = new View\MenuComponent();
    $menu->setMenu($defaultMenu);
    $menu->register('menu');
	}

	public function indexAction()
	{
		$vendors = Core\Module::getConfig();
		foreach ($vendors as $vendor => $modules) {
			printf('Proposés par %s<br/>', $vendor);
			foreach ($modules as $module => $config) {
				printf('- <a href="/utilities/object/index/namespace/%s">%s (%s/%s)</a><br/>',
				  $config['namespace'],
				  is_array($config['label']) ? $config['label']['fr'] : $config['label'],
					$vendor,
					$module
				);
			}
		}

		//\Zend_debug::dump($vendors); die;

		$objects = t41\ObjectModel::getList();
		sort($objects);

		foreach ($objects as $class) {
			if (! strstr($class, $this->getParam('namespace')) === -1) {
				continue;
			}
			//Zend_Debug::dump(t41\ObjectModel::getList()); die;
			echo "<h2>$class</h2>";
			echo "<table border=1 cellpadding=5 cellspacing=0><tr><th>Propriété</th><th>Identifiant</th><th>Type</th><th>Paramètres</th><th>Contraintes</th></tr>";
			foreach (t41\ObjectModel::getObjectProperties($class) as $key => $val) {
				printf("<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>",
				$val['label'],
				$key,
				ucfirst($val['type']),
				$this->compileOthers((array) $val),
				$this->compileConstraints((array) $val['constraints'])
				);
			}
			echo "</table>";
		}
		die;
	}

	protected function compileConstraints(array $constraints = [])
	{
		$str = '';
		foreach ($constraints as $key => $val) {
			$str .= $str ? ', ' : '';
			$str .= $key;
			if ($val) {
				$str .= ' : ' . $val;
			}
		}

		return $str;
	}

	protected function compileOthers(array $val)
	{
		$str = [];
		switch ($val['type']) {

			case 'object':
			$str[] = "classe : " . $val['instanceof'];
			$str[] = "clé étrangère : " . $val['id'];
			$str[] = sprintf("%s affichées : %s",
			  substr($val['display'], 0, 1) == '[' ? 'motif' : 'propriétés',
				$val['display']);
			break;

			case 'string':
			$str[] = sprintf("multi-lignes : %s", $val['multilines'] ? 'oui' : 'non');
			break;

			case 'collection':
			$str[] = "classe : " . $val['instanceof'];
			$str[] = "clé étrangère : " . $val['keyprop'];
			$str[] = "champs d'affichage : " . $val['display'];
			break;

			case 'enum':
			$str[] = "<table border=1 cellpadding=5 cellspacing=0><tr><th colspan=2>Valeurs</th></tr><tr><th>Clé</th><th>Valeur</th></tr>";
			foreach ($val['values'] as $key => $val) {
				$str[] = sprintf('<tr><td>%s</td><td>%s</td></tr>',
					$key,
					$val['label']
				);
			}
			$str[] = '</table>';
			break;
		}

		$str[] = sprintf("valeur par défaut : %s",
			$val['defaultvalue'] ?? "Aucune"
		);

		return implode("<br/>", $str);
	}
}
