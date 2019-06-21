<?	/***************************************************************
	 *	Languages
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['languages'] = new LanguagesClass;


class LanguagesClass{

	var $table	= 'languages';			## Classes main table to operate on

	function LanguagesClass(){


	}

	function getLanguageArray(){

		$res = $_SESSION['dbapi']->query("SELECT * FROM `languages` WHERE 1 ORDER BY id ASC ",1);
		$out=array();
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			$out[] = $row;

		}
		return $out;
	}


	function makeDropdown($field_name, $selected, $css_class, $extra_attr_str, $extra_where, $id_value=true, $long_names=true){

		$out = '<select name="'.$field_name.'" id="'.$field_name.'" ';

		$out.= ($css_class)?' class="'.$css_class.'" ':'';

		$out.= ($extra_attr_str)?$extra_attr_str:'';

		$out .= '>';

		$res = $_SESSION['dbapi']->query("SELECT * FROM `languages` WHERE 1 ".(($extra_where)?$extra_where:'').
										" ORDER BY id ASC "
										,1);
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			$out .= '<option value="';
			$out .= ($id_value)?$row['id']:$row['short'];
			$out .= '">'.(($long_names)?htmlentities($row['name']):$row['short']).'</option>';
		}

		$out .= '</select>';

		return $out;
	}


}
