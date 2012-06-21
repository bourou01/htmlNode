<?php
	/*
		- Plus de détails sur les commentaires
		- Rétabli les espaces avant et après des balises incorporées, exemple 'toi <i>et</i> moi' donnait 'toietmoi' après l'analyse syntaxique.
		- Reformulation de la fonction find(), $myNode->find('div; id="toto"; div id="toto"; h1 > b; h3 div id="/to.+/";');
			le premier filtre recherche un noeud "div", le dernier recherche un noeud "h3" ou "div" avec un attribut id dont la valeur commence par "to"
		-
		
		
	*/
	
	
	/**
		classe de noeud html/xml
		traite toutes les fonctionnalités de language de balise html et xml
		ex: $my = new htmlNode('input', 'type="text" value="ok"');
		*/
	class htmlNode
	{
		// variables
		var $index = 0;
		var $name = '';
		var $args = '';
		
		// règle html
		const htuni = ' area base basefont br col frame hr img input isindex link meta param ';
		const htbloc = ' address blockquote body caption center dl dir div fieldset form h1 h2 h3 h4 h5 h6 head hr html isindex legend li link menu meta noframes noscript ol option p pre script style table td title tr ul ';
		const htinc = ' a abbr acronym applet b basefont bdo big br button cite code dfn em font i img input iframe kbd label map object q samp select small span strong sub sup textarea tt var ';
		
		// stockage des noeuds dans un environnement absolue
		static $myElements = array();
		static $counter = 0;
		
		/**
			constructeur
			string $name: nom de la balise ou "text" pour les noeuds texte
			string $args: attributs du noeud ou le texte du noeud texte
			*/
		function htmlNode($name = '', $args = ''){
			$this->index = self::$counter++;
			$this->name = strtolower($name);
			if ($this->name == 'text')
				$this->value = $args;
			else {
				$this->setAttributes($args);
				$this->iscontainer = $this->name == '' || ! preg_match('/\b'. $this->name .'\b/', self::htuni);
				if ($this->iscontainer)
					$this->nodes = array();
				}
			self::$myElements[$this->index] = $this;
			}
			
			
		/**
			ajoute une liste d'attributs
			string $args: la liste d'attributs à ajouter
			ex: $my->setAttributes('id="toto" classe="frametop"');
			*/
		function setAttributes($args){
			preg_match_all('/\b([\w\-]+)=\s*"([^"]+)"|\b([\w\-]+)=\s*\'([^\']+)\'|\b([\w\-]+)=\s*(\w+)/', $args, $match);
			$index = 0;
			foreach ($match[0] as $string){
				if ($match[5][$index])
					$this->setAttribute($match[5][$index], $match[6][$index]);
				else if ($match[3][$index])
					$this->setAttribute($match[3][$index], $match[4][$index]);
				else if ($match[1][$index])
					$this->setAttribute($match[1][$index], $match[2][$index]);
				$index++;
				}
			}
			
			
		/**
			ajoute un attribut
			string $name: le nom de l'attribut à ajouter
			string $value: sa valeur
			ex: $this->setAttribute("id", "toto")
			*/
		function setAttribute($name, $value){
			$name = strtolower($name);
			$this->removeAttribute($name);
			$this->args .= ' '. strtolower($name) .'="'. $value .'"';
			}
			
			
		/**
			retourne la valeur d'un attribut
			string $name: nom de l'attribut
			ex: $this->getAttribute("id");
			*/
		function getAttribute($name){
			if (preg_match('/'. $name .'="([^"]+)"/', $this->args, $match))
				return $match[1];
			}
			
			
		/**
			retire un attribut
			string $name: nom de l'attribut à retirer du noeud
			ex: $this->removeAttribute("style");
			*/
		function removeAttribute($name){
			$this->args = preg_replace('/ '. strtolower($name) .'="([^"]+)"/', '', $this->args);
			}
			
			
		/**
			ajoute un noeud
			string $name: nom de la balise ou "text" pour les noeuds texte
			string $args: liste d'attributs de la balise ou le texte du noeud texte
			ex: $my->create('input', 'type="text" value="ok"');
			*/
		function create($name = '', $args = ''){
			if ($this->iscontainer) {
				$node = new htmlNode($name, $args);
				$node->setAttribute('tmp_parent', $this->index);
				$this->nodes[] = $node->index;
				return $node;
				}
			}
			
			
		/**
			ajoute un texte formaté dans un noeud
			string $name: nom de la balise
			string $args: liste d'attributs de la balise
			string $text: texte à insérer dans la balise
			ex: $this->createText('h3', 'id="toto"', 'hello !!!');
			*/
		function createText($name = '', $args = '', $text){
			if ($this->iscontainer) {
				$node = new htmlNode($name, $args);
				$node->setAttribute('tmp_parent', $this->index);
				$this->nodes[] = $node->index;
				$node->create('text', $text);
				return $node;
				}
			}
			
			
		/**
			supprime le noeud courrant
			bool $forever: suppression permanente du noeud, le noeud sera retiré du tableau des noeuds existants
			ex: $my->delete(true);
			
			*/
		function delete ($forever = false) {
			if ($forever) {
				if ($this->iscontainer)
					foreach ($this->nodes as $index) { self::$myElements[$index]->delete(true); }
				unset(self::$myElements[$this->index]);
				}
			else if ($this->getAttribute('tmp_parent') != '-1')
				$this->mother->remove($this);
			}
		
		
		/**
			supprime un noeud enfant
			mixed $node: l'objet htmlNode ou nom d'accès du noeud
			bool $forever: suppression permanente du noeud, le noeud sera retiré du tableau des noeuds existants
			ex: $my->remove('p1', true); $my->remove($my->first);
			*/
		function remove ($node, $forever = false) {
			if (is_a($node, 'htmlNode')) {
				$nodes = $this->nodes;
				$this->nodes = array();
				foreach ($nodes as $index) {
					if ($index == $node->index) {
						if ($forever) {
							if ($this->iscontainer)
								foreach (self::$myElements[$node->index]->nodes as $ind) {
									self::$myElements[$node->index]->remove(self::$myElements[$ind], true);
									}
							self::$myElements[$node->index]->delete(true);
							}
						else
							$node->setAttribute('tmp_parent', '-1');
						}
					else
						$this->nodes[] = $index;
					}
				}
			else if ($this->$node)
				$this->remove($this->$node);
			}
			
			
		/**
			retourne une copie du noeud
			*/
		function copy () {
			$node = new htmlNode($this->name, preg_replace('/ tmp_parent="[^"]+"/', '', $this->args));
			if ($this->iscontainer)
				foreach ($this->nodes as $index) { $node->add(self::$myElements[$index]->copy()); }
			return $node;
			}
			
			
		/**
			Ajoute un noeud existant
			htmlNode $node: noeud à attacher
			htmlNode $before: noeud de référence suivant ou précédant le noeud à attacher
			string $mode: détermine si le noeud doit être placé après $before ou remplacer before
			ex: $this->add($div, $this->p1, true);
			*/
		function add ($node, $before = null, $mode = '') {
			$spy = false;
			
			if ($node->mother != null)
				$node->delete();
			$node->setAttribute('tmp_parent', $this->index);
			
			if ($before) {
				$array = $this->nodes;
				$this->nodes = array();
				foreach ($array as $index) {
					if ($before->index == $index) {
						if ($mode == 'next') 
							$this->nodes[] = $index;
						if ($mode != 'replace')
							$this->nodes[] = $node->index;
						if (! $mode)
							$this->nodes[] = $index;
						$spy = true;
						}
						else
							$this->nodes[] = $index;
					}
				}
			if (! $spy)
				$this->nodes[] = $node->index;
			return $node;
			}
		
		
		
		
		/**
			retrouve une liste de noeuds correspondant aux filtres dans l'argument $filtre
			ex: $my->find('div h3 p; id="toto"; h1 id="/to.+/"; h2 title="myTitle" > b id="value"');
			*/
		function find ($filter) {
			if ($this->iscontainer) {// si le noeud(ou la balise) peut avoir des noeuds enfants
				// Si $filter est d'une chaine de caractère, on établi un tableau de requète de sorte à ce qu'il soit plus exploitable.
				if (is_string($filter)) {
					$finders = array(array());
					$index = 0;
	
					$match = array();
					$i = 0;
					$maxLength = strlen($filter);
					$finded = '';
					
					while ($i < $maxLength)
					{
						if (preg_match('/;|>|\s*([\w\-]+)="([^"]+)"|\s*(\w+)/', $filter, $match, PREG_OFFSET_CAPTURE, $i))// Prochain caractËre trouvÈ
						{
							$finded = $match[0][0];
							$i = $match[0][1] + strlen($finded);
							if (isset($match[3])) {
								if (isset($finders[$index]['processHtmlFind'])) {
									if (! isset($finders[$index]['processHtmlFind']['processHtmlTags']))
										$finders[$index]['processHtmlFind']['processHtmlTags'] = '';
									$finders[$index]['processHtmlFind']['processHtmlTags'] .= ' '. $match[3][0];
								}
								else {
									if (! isset($finders[$index]['processHtmlTags']))
										$finders[$index]['processHtmlTags'] = '';
									$finders[$index]['processHtmlTags'] .= ' '. $match[3][0];
									}
								}
							else if (isset($match[2])) {
								if (isset($finders[$index]['processHtmlFind']))
									$finders[$index]['processHtmlFind'][$match[1][0]] = $match[2][0];
								else
									$finders[$index][$match[1][0]] = $match[2][0];
								
								}
							else if ($finded == ';') {
								$finders[] = array();
								$index++;
								}
							else {
								$finders[$index]['processHtmlFind'] = array();
								}
						}
						else if ($i != $maxLength){
							debug('htmlNodeErreur: requête invalide vers "'. substr($filter, $i) .'"');
							$i = $maxLength;
						}
						else
							$i = $maxlength;
						
					}	
				}
				else
					$finders = $filter;
				
				
				
				$nodes = array();
				$ident = '';
				$spy = false;
				$finder = array();
				$node  = null;
				$name = '';
				$value = '';
				foreach ($this->nodes as $ident) {
					$node = self::$myElements[$ident];
					foreach ($finders as $finder) {
						$spy = true;
						foreach ($finder as $name => $value) {
							if ($name == 'processHtmlTags') {
								if (! preg_match('/\b'. $node->name.'\b/', $finder['processHtmlTags'])){
									$spy = false;
									break;
								}
							}
							else if ($name == 'processHtmlFind') {
								$spy = false;
								$nodes = array_merge($nodes, $node->find(array($value)));
								break;
							}
							else {
								if ($value[0] == '/' && $value[strlen($value) - 1] == '/') {
									if (! preg_match($value, $node->getAttribute($name)))  {
										$spy = false;
										break;
									}
								}
								else if ($value != $node->getAttribute($name)) {
									$spy = false;
									break;
								}
							}
						}
						
						if ($spy) {
							if (! isset($nodes[$node->index]))
								$nodes['node_'. $node->index] = self::$myElements[$ident];
							break;
						}
					}
					
					if ($node->iscontainer)
						$nodes = array_merge($nodes, $node->find($finders));
					
				}
				return $nodes;
			}
			
		}
			
			
			
		/**
			retourne un élément en lien direct avec le noeud
			string $name: nom d'accès du noeud
			ex: $this->mother; $this->before; $this->first; $this->div_3 ou encore $this->nom_i, si le noeud est responsable du chargement d'éléments pourtant un id
			*/
		function __get($name) {
			if ($name == 'mother')// Le noeud parent
				return isset(self::$myElements[$this->getAttribute('tmp_parent')] ) ? self::$myElements[$this->getAttribute('tmp_parent')] : null;
			else if ($this->mother && ($name == 'before' || $name == 'after'))// précédent et suivant
			{
				for ($i = 0; $i < count($this->mother->nodes); $i++) {
					if ($this->index == $this->mother->nodes[$i]) {
						if ($i != 0 && $name == 'before')
							return self::$myElements[$this->mother->nodes[$i - 1]];
						else if ($i != count($this->mother->nodes) -1)
							return self::$myElements[$this->mother->nodes[$i + 1]];
						}
					}
				}
			else if (isset($this->iscontainer) && count($this->nodes)) {// Noeud enfants
				switch ($name) {
					case 'first':// le premier
					case 'last':// le dernier
						return self::$myElements[$this->nodes[($name == 'last' ? count($this->nodes) - 1 : 0)]];
						break;
					case 'childs':// tous
						$childs = array();
						foreach ($this->nodes as $index) { $childs[] = self::$myElements[$index]; }
						return $childs;
						break;
					default:// $this->p; $this->div1; $this->mon_id (si le noeud d'id "mon_id" est charger grâce à la fonction apply() ou load() );
						if (isset($this->elements) && $this->elements[$name])
							return self::$myElements[$this->elements[$name]];
						
						$nodes = array();
						foreach ($this->nodes as $index) {
							$nodename = self::$myElements[$index]->name;
							$ind = 0;
							while (in_array($nodename .'_'. $ind, array_keys($nodes))) { $ind++; }
							$nodes[$nodename .'_'. $ind] = $index;
							}
						if (in_array($name, array_keys($nodes)))
							return self::$myElements[$nodes[$name]];
						}
						break;
				}
			}
			
		/**
			transforme une chaine de caractère en objet htmlNode
			string $text: le texte à transformer
			string $attName: nom de l'attribut d'une balise que l'on souhaite recupérer en particulier dans toute la chaine
			string $attValue: sa valeur
			$bool $all: retourner tous les éléments correspondant à la recherche
			ex: $this->apply('<html><body><h3 style="text-align: center"> Hello ! </h3></body></html>');
			*/
		function apply ($text, $attName = '', $attValue = '', $all = false)
		{
			if (! $this->iscontainer) return;
			
			$tmp = null;
			$i = 0;
			$maxLength = strlen($text);
			$level = 'text';
			$mark = '/<\w+|<\/\w+/';
			$name = '';
			$attributes = '';
			$txt = '';
			$match = array();
			$nodes = array($this);
			$line = 0;
			$spy = false;
			
			if ($attName){
				if (preg_match('/<.+\s+'. $attName .'=\s*"'. $attValue .'"|<.+\s+'. $attName .'=\s*\''. $attValue .'\'|<.+\s+'. $attName .'=\s*'. $attValue .'/', $text, $match, PREG_OFFSET_CAPTURE))
					$text = substr($text, $match[0][1]);
				else
					self::debug('htmlErreur: le nom de l\'attribut "'. $attName .'" est introuvable dans le contenu à convertir.', true);
				}
			
			while ($i < $maxLength)
			{
				if (preg_match($mark, $text, $match, PREG_OFFSET_CAPTURE, $i))// Prochain caractËre trouvÈ
				{
					$line += substr_count($text, "\n", $i, $match[0][1] - $i + strlen($match[0][0]));
					
					$txt = substr($text, $i, $match[0][1] - $i);
					$i = $match[0][1] + strlen($match[0][0]);
					
					if ($level == 'text')// CaractËre d'ouverture de balise trouvÈ
					{
						//$txt = preg_replace('/\s*\n\s*/', '', $txt);
						if (trim($txt))// Texte non vide
							$nodes[count($nodes) - 1]->create('text', $txt);
						if ($match[0][0][1] == '/')// Balise de fermeture
						{
							$i++;
							if (substr($match[0][0], 2) !== $nodes[count($nodes) - 1]->name)
								self::debug('htmlErreur: Balise de Fermeture "'. substr($match[0][0], 2) .'" invalide ‡ la ligne '. $line .' !!! Tente de fermer "'
									. $nodes[count($nodes) - 1]->name .'".', true, true);
							if ($attName && $attValue == $nodes[count($nodes) - 1]->getAttribute($attName)) {
								if (! $all)
									break;
								else {
									if (preg_match('/<.+\s+'. $attName .'=\s*"'. $attValue .'"|<.+\s+'. $attName .'=\s*\''. $attValue .'\'|<.+\s+'. $attName .'=\s*'. $attValue .'/', $text, $match, PREG_OFFSET_CAPTURE, $i))
										$i = $match[0][1];
									else
										break;
									}
								}
							if (count($nodes) > 1)
								array_pop($nodes);
							
						}
						else// PrÈparation ‡ l'ouverture de la balise
						{
							$name = substr($match[0][0], 1);
							$level = 'attributes';
							$mark = '/ +[\w\-]+=\s*"[^"]+"| +[\w\-]+=\s*\'[^\']+\'| +[\w\-]+=\s*\w+|\/>|>/';
						}
					}
					else if ($level == 'attributes')// Dans la zone de saisie d'attributs
					{
						if ($match[0][0] == '>' || $match[0][0] == '/>')// CaractËre de fermeture de balise trouvÈ
						{
							if ($match[0][0] != '/>' && ! preg_match('/ '. $name .' /', self::htuni))
								$tmp = $nodes[] = $nodes[count($nodes) - 1]->create($name, $attributes);
							else
								$tmp = $nodes[count($nodes) - 1]->create($name, $attributes);
							
							$ident = $tmp->getAttribute('id');
							if ($ident) {
								if (! isset($this->elements))
									$this->elements = array();
								$this->elements[$ident] = $tmp->index;
								}
								
							$attributes = '';
							$level = 'text';
							$mark = '/<\w+|<\/\w+/';
							
							if (strtolower($name) == 'script') {
								$mark = '/<\/\w+|".*[^\\\]"|\'.*[^\\\]\'/';
								$level = 'script';
								$str = '';
							}
						}
						else// Ajout d'un attribut ‡ la liste d'attributs de la balise en cours...
							$attributes .= $match[0][0];
					}
					else if ($level == 'script') {
						if ($match[0][0][0] == '<') {
							$i++;
							if (substr($match[0][0], 2) !== $nodes[count($nodes) - 1]->name)
								self::debug('htmlErreur: Balise de Fermeture "'. substr($match[0][0], 2) .'" invalide ‡ la ligne '. $line .' !!! Tente de fermer "'
									. $nodes[count($nodes) - 1]->name .'".', true, true);
							$nodes[count($nodes) - 1]->create('text', $str . $txt);
							if ($attName && $attValue == $nodes[count($nodes) - 1]->getAttribute($attName)) {
								if (! $all)
									break;
								else {
									if (preg_match('/<.+\s+'. $attName .'=\s*"'. $attValue .'"|<.+\s+'. $attName .'=\s*\''. $attValue .'\'|<.+\s+'. $attName .'=\s*'. $attValue .'/', $text, $match, PREG_OFFSET_CAPTURE, $i))
										$i = $match[0][1];
									else
										break;
									}
								}
							if (count($nodes) > 1) array_pop($nodes);
							$level = 'text';
							$mark = '/<\w+|<\/\w+/';
							}
						else 
							$str .= $txt . $match[0][0];
						}
				}
				else// Aucun caractËre spÈcial trouvÈ
				{
					if ($level == 'text')// RÈcupËre le reste du text
						$nodes[count($nodes) - 1]->create('text', substr($text, $i));
					$i = $maxLength;
				}
			}
		}
		
		/**
			charge le contenu d'un fichier html dans le document
			string $file: le lien d'accès au fichier
			string $attName: nom de l'attribut d'une balise que l'on souhaite recupérer en particulier dans tout le fichier
			string $attValue: sa valeur
			$bool $all: retourner tous les éléments correspondant à la recherche
			ex: $my->load('home.html');
			*/
		function load ($file, $attName = '', $attValue = '', $all = false) {
			$content = '';
			
			$handle = fopen($file, 'r');
			if ($handle) 
				while (!feof($handle)) { $content .= stream_get_line($handle, 4096); }
			else
				return self::debug('htmlNodeErreur: chargement de fichier impossible !!! Le fichier '. $file .' n\'existe pas.', true);
		   
			fclose($handle);
			$this->apply($content, $attName, $attValue, $all);
			}

		/**
			affiche le noeud en sortie (dans le navigateur)
			bool $onlychilds: n'afficher que les enfants (le contenu) du noeud
			ex: $my->display();
			*/
		function display($onlychilds = false){
			if ($onlychilds)
				foreach($this->nodes as $index){ self::$myElements[$index]->display(); }
			else
				echo $this->toString();
			}
		
		/**
			retourne la représentation de l'objet htmlNode en chaine de caractères
			string $tab: la tabulation à afficher avant le noeud
			ex: $html = $my->toString(); echo $html;
			*/
		function toString($tab = ''){
			$txt = '';
			if ($this->name == 'text')
				$txt = utf8_decode($this->value);
			else
			{
				$isbloc = preg_match('/ '. $this->name .' /', self::htbloc);
				$args = preg_replace('/ (tmp_\w+)="([^"]+)"/', '',$this->args);
				$node = null;
				$txt .= '<'. $this->name . utf8_decode($args);
				if ($this->iscontainer){
					$txt .= '>';
					foreach($this->nodes as $index){
						$node = self::$myElements[$index];
						if (preg_match('/ '. $node->name .' /', self::htbloc))
							$txt .= chr(10) . $tab . chr(9);
						$txt .= $node->tostring($isbloc ? $tab . chr(9) : $tab);
						if (preg_match('/ '. $node->name .' /', self::htbloc) && $index == $this->nodes[count($this->nodes) - 1])
							$txt .= chr(10) . $tab . chr(9);
						}
					
					$txt .= '</'. $this->name .'>';
					}
				else
					$txt .= ' />';
				}
				
			return $txt;
			}
		
		/**
			Affiche une erreur ou une info
			mixed $object: l'élément à afficher
			bool $die: Détermine si le script doit prendre fin après l'affichage
			bool $details: montrer plus de détails sur l'élément à afficher
			ex: htmlNode::debug ($my->first);
			*/
		static function debug($object, $die = false, $details = false)
		{
			ob_start();
			if ($details)
				{ var_dump($object); }
			else
				{ print_r($object); }
			$txt = ob_get_contents();
			ob_end_clean();
			$txt = htmlentities($txt);
			
			echo '<pre style="border: outset 4px #FFFFFF;">' . $txt .'</pre>'. chr (10);
			
			if ($die)  die();
			}
		}

?>