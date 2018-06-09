<?php

use t41;
use t41\Core;
use t41\View;

require_once 'vendor/crapougnax/t41/controllers/DefaultController.php';

class Utilities_ObjectController extends t41_DefaultController {

    protected static $constraintsMap = [
        'mandatory' => 'Valeur requise',
        'protected' => 'Valeur protégée',
        'uppercase' => 'Convertie en majuscules',
        'lowercase' => 'Convertie en minuscule',
        'emailaddress' => 'De format email',
        'maxlength' => 'Caractères max.',
        'minlength' => 'Caractères min.',
        'even' => 'Nombre pair',
        'minval' => 'Valeur minimum',
        'maxval' => 'Valeur maximale',
        'odd'=> 'Nombre impair',
        'digits' => 'Valeur numérique',
        'format' => 'Format'
    ];

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

		echo '<ul><li><a href="/utilities/object/index/namespace/41">Core Modules (t41/*)</a></li>';

		foreach ($vendors as $vendor => $modules) {
			printf('<li>Dossier vendor <b>%s</li><ul>', $vendor);
			foreach ($modules as $module => $config) {
				printf('<li><a href="/utilities/object/index/namespace/%s">%s (%s/%s)</a></li>',
				  rawurlencode($config['namespace']),
				  is_array($config['label']) ? $config['label']['fr'] : $config['label'],
					$vendor,
					$module
				);
			}
			print"</ul>";
		}
		print"</ul>";

		$objects = t41\ObjectModel::getList();
		sort($objects);

		foreach ($objects as $class) {
			if (strstr($class, $this->getParam('namespace')) === false) {
				continue; // ignore objects with different namespace
			}

			$backend = t41\ObjectModel::getObjectBackend($class);
			echo sprintf("<p>&nbsp;</p><h3>Objet <i>%s%s</i></h3><p>Backend associé : <i>@%s</i>, nom de la table : <i>%s</i></p><p>&nbsp;</p>", 
			    $class, 
			    t41\ObjectModel::getObjectExtends($class) ? " étend " . t41\ObjectModel::getObjectExtends($class) : null,
			    $backend->getAlias(),
			    $backend->getTableFromClass($class)
			    );

			echo "<table border=1 width=100% cellpadding=5 cellspacing=0><tr><th>Propriété</th><th>Identifiant</th><th>Type</th><th>Paramètres</th><th>Contraintes & formats</th></tr>";
			foreach (t41\ObjectModel::getObjectProperties($class) as $key => $val) {
				printf("<tr><td>%s</td><td>%s</td><td>%s</td><td><br/>%s</td><td><br/>%s</td></tr>",
				    is_array($val['label']) ? $val['label']['en'] : $val['label'],
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
	    if (count($constraints) == 0) {
	        return;
	    }

	    $str = [];
	    $str[] = "<table border=1 width=100% cellpadding=5 cellspacing=0><tr><th>Clé</th><th>Valeur</th></tr>";

	    foreach ($constraints as $key => $val) {
			$str[] = sprintf('<tr><td>%s</td><td>%s</td></tr>',
			    isset(self::$constraintsMap[$key]) ? self::$constraintsMap[$key] : $key,
			    $val ? $val : 'Oui'
			);
		}
		$str[] = '</table>';

		return implode($str);
	}

	protected function compileOthers(array $val)
	{
		$str = [];
		switch ($val['type']) {

			case 'object':
    			$str[] = "<table border=1 width=90% cellpadding=3 cellspacing=0><tr><th colspan=2>Paramètres de l'objet lié</th></tr><tr><th>Clé</th><th>Valeur</th></tr>";
    			$str[] = "<tr><td>Classe</td><td>" . $val['instanceof'] . '</td></tr>';
    			$str[] = sprintf("<tr><td>%s affichées</td><td>%s</td></tr>",
    			             substr($val['display'], 0, 1) == '[' ? 'Motif' : 'Propriétés',
    				         $val['display']
    			         );
    			$str[] = '</table>';
			break;

			case 'string':
			    if (isset($val['multilines'])) {
        			$str[] = sprintf("multi-lignes : %s", $val['multilines'] ? 'oui' : 'non');
			    }
			break;

			case 'collection':
    			$str[] = "<table border=1 width=90% cellpadding=3 cellspacing=0><tr><th colspan=2>Paramètres de la collection</th></tr><tr><th>Clé</th><th>Valeur</th></tr>";    
    			$str[] = sprintf("<tr><td>Classe</td><td>%s</td></tr>", $val['instanceof']);
    			$str[] = sprintf("<tr><td>Clé étrangère</td><td>%s</td></tr>", $val['keyprop']);
    			$str[] = sprintf("<tr><td>%s affichées</td><td>%s</td></tr>",
    			    substr($val['display'], 0, 1) == '[' ? 'Motif' : 'Propriétés',
    			    $val['display']);
    			$str[] = '</table>';
			break;

			case 'enum':
    			$str[] = "<table border=1 width=90% cellpadding=3 cellspacing=0><tr><th colspan=2>Valeurs</th></tr><tr><th>Clé</th><th>Valeur</th></tr>";
    			foreach ($val['values'] as $key => $val) {
    				$str[] = sprintf('<tr><td>%s</td><td>%s</td></tr>',
    					$key,
    					$val['label']
    				);
    			}
    			$str[] = '</table>';
			break;
		}

		if (isset($val['defaultvalue'])) {
    		$str[] = sprintf("valeur par défaut : %s", $val['defaultvalue'] ?? "Aucune");
		}

		return implode($str);
	}
}
