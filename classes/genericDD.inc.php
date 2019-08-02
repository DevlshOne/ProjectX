<?

	class genericDD{

		var 	$sql,
			$namefield,
			$valfield,
			$sep;

		var 	$namedata,
			$valdata;


		var $addslashes = 0;

		function genericDD($s,$nf,$vf,$d){
			if(!$sep)$sep='-';

			#echo $s;

			$this->sql = $s;
			$this->namefield = $nf;
			$this->valfield  = $vf;
			$this->sep = $d;
			$this->generate();
		}

		function generate(){
			$res = $_SESSION['dbapi']->query($this->sql,1);

			if(mysqli_num_rows($res) > 0){
				for($x=0;$row = mysqli_fetch_array($res);$x++){
					$this->namedata[$x]='';
					if(is_array($this->namefield)){
						foreach($this->namefield as $y=>$f){
							$this->namedata[$x] .= $row[$f];
							if($y!= count($this->namefield)-1)$this->namedata[$x] .= $this->sep;
						}
					}else{
						$this->namedata[$x] = $row[$this->namefield];
					}
					$this->valdata[$x]  = $row[$this->valfield];
				}
			}
		}

		function DOMmakeDD($doc,$node,$name,$sel,$class,$blankentry,$onchange,$size){


			$root = $node;#$doc->document_element();

			$el = $doc->create_element("SELECT");
			$el->set_attribute("name",$name);
			$el->set_attribute("id",$name);
			if($size > 0)	$el->set_attribute("size",$size);
			if($onchange)	$el->set_attribute("onchange",$onchange);


			$elnode = $root->append_child($el);

			if($blankentry){
				$el = $doc->create_element("OPTION");
				$el->set_attribute("value","");
				$elnode->append_child($el);
			}

			for($x=0;$x<count($this->namedata);$x++){

				$el = $doc->create_element("OPTION");
				$el->set_attribute("value",$this->valdata[$x]);
				if($this->valdata[$x] == $sel)$el->set_attribute("SELECTED");
				$opnode = $elnode->append_child($el);

				$tnode = $doc->create_text_node($this->namedata[$x]);
				$opnode->append_child($tnode);

			}
			return $root;
		}

		function makeDD($name,$sel,$class,$blankentry=0,$onchange="",$size=0,$extratags=""){
			$out = '<select name="'.$name.'" id="'.$name.'" ';
			if($size > 0)	$out .= ' size="'.$size.'"';

			if($onchange)	$out .= ' onchange="'.$onchange.'"';
			if($extratags)	$out .= $extratags;
			$out .= '>';

			if($blankentry)$out .= '<option value="">'.((!is_numeric($blankentry))?$blankentry:'').'</option>';
			for($x=0;$x<count($this->namedata);$x++){
				$out .= '<option value="'.$this->valdata[$x].'"';

				if($this->valdata[$x] == $sel)$out .= ' SELECTED';
				$out .= ' >';
				$out .= substr( (($this->addslashes)?addslashes(htmlentities($this->namedata[$x])):htmlentities($this->namedata[$x])) ,0,36);
				$out .= '</option >';
			}

			$out .= '</select>';
			return $out;
		}

	}
?>
