<?

$_SESSION['JXMLP'] = new JXMLP;

/**
 * JXMLP - Jon's XML Parser
 * Written by: Jonathan Will
 *
 * 	Ported from my Java/J2ME implementation
 *

 *
 *	parseOne() Array Standards:
 *
 *		_XMLTAG		= The <?xml tag attributes
 *		_BASETAG	= The root tag
 *		_CDATA		= The next nodes of the XML
 *		_TAG_MEMBERS	= The array/collection of all the tags
 *
 *
 *		_(tagname)	= (tagname) being a wildcard, this is how subtags are represented/seperated from attributes
 *

 *
 *
 */

class JXMLP{


	/**
	 * finds first $tag, and builds an asso array of it, and all its sub items.
	 *
	 */
	function parseTag($xml,$tag,$self_closing,$depth){




		$depth = intval($depth);

		if($depth == 0){
			if(!$tag){

				$tag = $this->grabFirstTagName($xml);

			}

			##echo "startxml: ".$xml."\n\nTAG:".$tag."\n";

			unset($out);


			if($tag == '?xml'){

				$x = strpos($xml,'<'.$tag);
				$y = strpos($xml,'?'.'>',$x);

				$tmp = substr($xml,$x,($y-$x)+2);

				$out['_XMLTAG'] = $this->parseOne($tmp,'?xml',2);

				## TRIM OUT TAG
				$xml = substr($xml,0,$x).substr($xml,$y+2);

				## GRAB NEXT TAG
				$tag = $this->grabFirstTagName($xml);

			}



			if($tag == '?xml-stylesheet'){

				$x = strpos($xml,'<'.$tag);
				$y = strpos($xml,'?'.'>',$x);

				$tmp = substr($xml,$x,($y-$x)+2);

				$out['_XSLT'] = $this->parseOne($tmp,'?xml-stylesheet',2);

				## TRIM OUT TAG
				$xml = substr($xml,0,$x).substr($xml,$y+2);

				## GRAB NEXT TAG
				$tag = $this->grabFirstTagName($xml);
			}


			$out['_BASETAG'] = $tag;
		}


		## TRIM DOWN TO JUST THE TAG
		$xml = $this->grabOneTag($xml,$tag,'',-1);

		if(!$xml){

			##echo "NO XML PASSED IN !";

			return null;
		}


		###echo $xml."\n";	exit;



		$x=0;

		$eot = strpos($xml,'>',$x);

		$cur_idx = strlen($tag);


##$fail_safe = 20;
##$asdf=0;

		// FIND THE NEXT EQUAL SIGN
		while( ($x = strpos($xml,'=',$cur_idx)) !== FALSE && $x < $eot && $cur_idx < $eot){

			##echo $x.' '.$eot.' '.$cur_idx."<br>\n";

			##if($asdf++ > $fail_safe){

				##echo "FAILSAFE KILLSWITCH -DIS-ENGAGE!";



				##exit;
			##}


			if($x === FALSE)continue;



			// SCROLL BACKWARDS UNTIL YOU FIND WHERE THE NAME PIECE OF THE ATTRIBUTE ENDS
			for($y = $x;$y > $cur_idx;$y--){

				if($xml[$y] == "\t" || $xml[$y] == ' '){

					// move ahead of the space
					$y++;


					// break outa this loop
					break;
				}

			}


			$tmp_name = substr($xml,$y, ($x - $y));

			#echo $tmp_name."<br>\n";

			$y = strpos($xml,'"',$x+2);

			if($y === FALSE){
				#echo "FOUND IT!";
				break;
			}

			$tmp_val = substr($xml,$x+2,($y-($x+2)));

			$out[$tmp_name] = html_entity_decode($tmp_val);

			$cur_idx = $y;
		}


		## move to end of tag to continue on
		$eot++;
		$x = $eot;

		## FROM THE TAG, FIND ALL POSSIBLE ENDS OF THE NAME OF THE TAG



		if($self_closing < 0){

			$neot = strpos($xml,'</'.$tag.'>',$x);
			if($neot === FALSE){

				$neot= strpos($xml,'/>',$x);
				$neotlen = 3 + strlen($tag);

			}else{

				$neotlen = 2;

			}

		}else{

			if($self_closing){

				$neot= strpos($xml,'/>',$x);

				##echo "Self closing!.$neot";

				/*if($neot === FALSE){

					$neot = strpos($xml,'</'.$tag.'>',$x);
					$neotlen = 3 + strlen($tag);

				}else{	*/

					$neotlen = 2;

				//}

			}else{
				$neot = strpos($xml,'</'.$tag.'>',$x);

				/*if($neot === FALSE){

					$neot= strpos($xml,'/>',$x);
					$neotlen = 2;

				}else{*/

					$neotlen = 3 + strlen($tag);
				//}
			}
		}





		## END OF THE </$tag>
		$real_eot = $neot + $neotlen;


		#echo $eot . ' '.$eotlen.'<br>';

		## DONT FORGET ABOUT CDATA / TEXT NODES
		$cdata = '';


		##echo htmlentities($xml);

		## FIND NEXT TAG
		while(($ntpos = strpos($xml,'<',$eot)) !== FALSE){

			##echo $ntpos.' '.$eot.'<br>';

			## CAPTURE CDATA
			if($ntpos > $eot){
				$cdata .= substr($xml,$eot,($ntpos - $eot));
			}

			$ntpos++; ## MOVE AHEAD







## THIS IS BAD CODE! BAD JON!


			## FROM THE TAG, FIND ALL POSSIBLE ENDS OF THE NAME OF THE TAG
			$w = strpos($xml,'/>',$ntpos);
			$y = strpos($xml,'>',$ntpos);

			#echo "y:".$y.'  w:'.$w;

			if($w !== FALSE && $y > $w)$y=$w;

			$z = strpos($xml," ",$ntpos);
			$a = strpos($xml,"\t",$ntpos);

			if($z !== FALSE && $z < $y)$y=$z;
			if($a !== FALSE && $a < $y)$y=$a;

			#echo ' z:'.$z.'  a:'.$a." <br>\n";

			if($y === FALSE) break;


			## TAG NAME
			$name = substr($xml,$ntpos,($y - $ntpos));

			#echo $name." ".$tag."\n";



			## END OF MAIN TAG
			if($name == '/'.$tag){ ## $name[0] == '/'){##
				##reak;

				#echo "BEFORE:".$eot."<br>\n";

				$eot = $y + 3 + strlen($tag);


				##$eot += 3 + strlen($tag);
				#echo "AFTER:".$eot."<br>\n";

				continue;


			}


			$eot = strpos($xml,'</'.$name.'>',$y);
			$eot2= strpos($xml,'/>',$y);

			#echo $tag.' '.$name.'    eot:'.$eot.' eot2:'.$eot2."<br>\n";

			if($eot === FALSE){ ## ($eot2 !== FALSE && $eot2 < $eot)
				$eot = $eot2;
				$eotlen = 2;
				$nself_closing = 1;
			}else{
				$eotlen = 3 + strlen($name);
				$nself_closing = 0;
			}


			if($eot === FALSE){
				echo "Unable to find end of tag.<br>".$xml;
				break;
			}


			$endtag = $eot + $eotlen;
			$nxml = substr($xml,$ntpos-1,  ($endtag - ($ntpos - 1))  );



			##echo htmlentities($nxml)." <br>\n\n".$ntpos.' '.$endtag;



			$nidx = count($out['_'.$name]['_TAG_MEMBERS']);

			$out['_'.$name]['_TAG_MEMBERS'][$nidx] = $this->parseTag($nxml,$name,$nself_closing,$depth+1);





			$eot = $endtag;
		}


		##echo ' '.strlen($xml);





		## CLEANUP SINGLE ARRAY ITEMS
		foreach($out as $key=>$val){
			if($key[0] != '_')continue;

			/*foreach($val as $k2=>$v2){
				if($k2 != '_TAG_MEMBERS')continue;
			}*/

			if(is_array($val['_TAG_MEMBERS'])){


				if(count($val['_TAG_MEMBERS']) == 1){

					## DUPLICATE TO ROOT

					$out[$key]['_CDATA'] = $val['_TAG_MEMBERS'][0]['_CDATA'];

				}

			}

		}

		if(trim($cdata)){
			$out['_CDATA'] = $cdata;
		}



		return $out;
	}








	/**
	 * parses attributes of a tag into an asso array
	 */
	function parseOne($xml,$tag,$self_closing){

		$out = null;

		## FIND THE TAG

		$x = strpos($xml,'<'.$tag.' ');


		if($x === FALSE){

			$x = strpos($xml,'<'.$tag."\t");
			if($x === FALSE){

				$x = strpos($xml,'<'.$tag."\n");

				if($x === FALSE){

					$x = strpos($xml,'<'.$tag."\r\n");
				}
			}


		}

		if($x === FALSE)return '';

		if($self_closing < 0){

			$elen = 3 + strlen($tag);
			$y = strpos($xml,'</'.$tag.'>',$x);

			if($y === FALSE){
				$elen=2;
				$y = strpos($xml,'/>',$x);
			}

		}else{

			if($self_closing > 0){
				$elen=2;

				if($self_closing == 2){
					$y = strpos($xml,'?>',$x);
				}else{
					$y = strpos($xml,'/>',$x);
				}

			}else{
				$elen = 3 + strlen($tag);
				$y = strpos($xml,'</'.$tag.'>',$x);
			}

		}

		if($y === FALSE){return '';}





		// First occurence of tag, and nothing else
		$tmp = substr($xml,$x,$y+$elen);

		$eot = strpos($tmp,'>',$x);

		$cur_idx = strlen($tag);

		#echo $tmp;

		// FIND THE NEXT EQUAL SIGN
		while( ($x = strpos($tmp,'=',$cur_idx)) !== FALSE && $x < $eot && $cur_idx < $eot){
			if($x === FALSE)continue;


			// SCROLL BACKWARDS UNTIL YOU FIND WHERE THE NAME PIECE OF THE ATTRIBUTE ENDS
			for($y = $x;$y > $cur_idx;$y--){

				if($tmp[$y] == "\t" || $tmp[$y] == ' ' || $tmp[$y] == '\n' || $tmp[$y] == '\r'){

					// move ahead of the space
					$y++;


					// break outa this loop
					break;
				}

			}


			$tmp_name = substr($tmp,$y, ($x - $y));

			$y = strpos($tmp,'"',$x+2);

			$tmp_val = substr($tmp,$x+2,($y-($x+2)));


			##echo $tmp_name. ' = '.$tmp_val."\n";

			$out[$tmp_name] = html_entity_decode($tmp_val);

			$cur_idx = $y;
		}


		return $out;

	}



	function grabTagArray($xml,$tag,$self_closing){

		$y=$a=$b=$elen=0;



		$out = null;
		$outp=0;

		while(1){

			$curx = $a;

			$a = strpos($xml,"<".$tag." ",($b !== FALSE)?$b+$elen:$curx);
			$a = ($a !== FALSE)?$a:$a = strpos($xml,"<".$tag.">",($b !== FALSE)?$b+$elen:$curx);
			$a = ($a !== FALSE)?$a:$a = strpos($xml,"<".$tag."\t",($b !== FALSE)?$b+$elen:$curx);
			$a = ($a !== FALSE)?$a:$a = strpos($xml,"<".$tag."\n",($b !== FALSE)?$b+$elen:$curx);
			##$a = ($a !== FALSE)?$a:$a = strpos($xml,"<".$tag,($b !== FALSE)?$b+$elen:$curx);

			if($a === FALSE)break;


			if($self_closing){
				$elen=2;
				$b = strpos($xml,"/>",$a);
			}else{
				$elen = 3 + strlen($tag);
				$b = strpos($xml,"</".$tag.">",$a);
			}
			if($b === FALSE){

				##echo "Breaking from grabTagArray()";

				break;
			}

##echo $a.' '.($b+$elen)."<br>\n";

			$out[$outp++]= substr($xml,$a,(($b+$elen)-$a));

		}

		return $out;
	}




	/**
	 * grabOneTag($xml,$tag,$index,$self_closing)
	 *
	 * @param xml			String of XML to work with
	 * @param tag			Tag name to extract
	 * @param self_closing	Self closing means the tag your extracting, ends in '/>', 1/0
	 * @return string		XML string of the tag, all tags included
	 */
	function grabOneTag($xml,$tag,$index,$self_closing){

		$index = intval($index);

		$y=$a=$b=$elen=0;
		unset($out);


		## TRYING BETTER METHOD, THIS WAY, A <TAG> WOULD GET OVERLOOKED
		###while( ($a = strpos($xml,"<".$tag." ",($b !== FALSE)?$b+$elen:$a)) !== FALSE){


		while( 1){

##echo 'Index '.$index.' of tag '.$tag.':'.$a.' - '.$b.' - '.$elen.' - '.$y."<br>\n";
##echo 'In XML: '.htmlentities($xml);

			$cpos = $a;

			$a = strpos($xml,"<".$tag." ",($b !== FALSE)?$b+$elen:$cpos);
			$a = ($a !== FALSE)?$a:strpos($xml,"<".$tag.">",($b !== FALSE)?$b+$elen:$cpos);
			$a = ($a !== FALSE)?$a:strpos($xml,"<".$tag."\t",($b !== FALSE)?$b+$elen:$cpos);
			$a = ($a !== FALSE)?$a:strpos($xml,"<".$tag."\n",($b !== FALSE)?$b+$elen:$cpos);
			##$a = ($a !== FALSE)?$a:strpos($xml,"<".$tag,($b !== FALSE)?$b+$elen:$cpos);
			if($a === FALSE) break;



## FAILSAFE
##if($y > 10)break;

			if($self_closing < 0){


				##echo $a;

				$b = strpos($xml,"</".$tag.">",$a);
				#echo $tag;

				#echo " B: ".$b;

				if($b === FALSE){

					$b = strpos($xml,"/>",$a);

					#echo " B2: ".$b;

					$elen = 2;

				}else{
					$elen = 3 + strlen($tag);

				}

			}else{




				if($self_closing){
					$elen=2;
					$b = strpos($xml,"/>",$a);
				}else{
					$elen = 3 + strlen($tag);
					$b = strpos($xml,"</".$tag.">",$a);
				}


			}

			if($b === FALSE){
echo "Breaking from grab One Tag!\n";
				$y++;

				continue;


				#echo "Breaking from grab One Tag!";

				#break;
			}


			if($y++ != $index)continue;


			// IF OPEN AND CLOSING TAGS EXIST
			$out .= substr($xml,$a,(($b+$elen)-$a));

		}

		return $out;
	}






	/**
	 *
	 */
	function grabXML($xml,$tag,$self_closing,$ignore_tag_mode){

		$x=$y=$elen=0;
		unset($tmp);

		// Chunk apart the xml
		$x = strpos($xml,"<".$tag." ");
		if($x === FALSE)$x = strpos($xml,"<".$tag.">");
		if($x === FALSE)$x = strpos($xml,"<".$tag);
		if($x === FALSE)return null;

		if($self_closing){
			$elen=2;
			$y = strpos($xml,"/>",$x);
		}else{
			$elen = 3 + strlen($tag);
			$y = strpos($xml,"</".$tag.">",$x);
		}

		if($y === FALSE)return null;

		// IF OPEN AND CLOSING TAGS EXIST

		if($ignore_tag_mode){
			$tmp = substr($xml,0,$x-1). substr($xml,$y+$elen);
		}else{
			$tmp = substr($xml,$x,(($y+$elen)-$x));
		}

		return $tmp;
	}






		/**
	 * Grabs the first xml-ish tag (starting with '<') and returns the name
	 * @param xml
	 * @return
	 */
	function grabFirstTagName($xml){

		$x=$y=$z=$a=0;


		// Chunk apart the xml
		$x = strpos($xml,'<');
		if($x === FALSE)return null;

		// FIND NEXT SPACE
		$y = strpos($xml,'>',$x);

		$z = strpos($xml," ",$x);
		$a = strpos($xml,"\t",$x);

		if($z !== FALSE && $z < $y)$y=$z;
		if($a !== FALSE && $a < $y)$y=$a;

		/**if(y < 0){
			// SECOND CHANCE: Closing tag
			y=z;

			// FINAL HOPE, TAB CHAR
			if(y< 0)y=a;
		}*/


		if($y < 0)return null;

		$tmp = substr($xml,$x+1,($y - ($x+1)));

		return $tmp;
	}












	/**
	 * grabTagContent(String xml,String tag)
	 *
	 * Gets the Text between tags, <tag>text</tag>
	 *
	 * @param xml
	 * @param tag
	 * @return
	 */
	function grabTagContent($xml,$tag){

		$x=$y=$z=0;

		// Chunk apart the xml
		$x = strpos($xml,"<".$tag." ");
		if($x === FALSE)$x = strpos($xml,"<".$tag);
		if($x === FALSE)return null;

		$y = strpos($xml,"</".$tag.">",$x);

		if($y < 0)return null;

		// IF OPEN AND CLOSING TAGS EXIST

		// Find starting tags closing character
		$z = strpos($xml,">",$x);

		$tmp = substr($xml,$z+1,(($y)-($z+1)));
		return $tmp;

	}








	/**
	 * Searches the _$tag -> _TAG_MEMBERS array for the 'id' attribute
	 * returns the member index of the first matching item
	 */
	function grabMemberIndexByID($arr,$tag,$id){

		#echo $tag;

		#print_r($arr);

		foreach($arr['_'.$tag]['_TAG_MEMBERS'] as $x=>$ar2){

			if($ar2['id'] == $id)return $x;

		}

		return -1;
	}


	function getText($arr,$name){
		return $arr['_'.$name]['_TAG_MEMBERS'][0]['_CDATA'];
	}

	function setText($arr,$name,$value){
		$arr['_'.$name]['_TAG_MEMBERS'][0]['_CDATA'] = $value;
		return $arr;
	}



	/**
	 *
	 */
	function modifyHash($arr,$change_arr,$idx=0){

		foreach($change_arr as $key=>$val){

			if(is_array($val)){


				$arr['_TAG_MEMBERS'][$idx][$key] = $this->modifyHash($arr['_TAG_MEMBERS'][$idx][$key],$val);

			}else{

				$arr['_TAG_MEMBERS'][$idx][$key] = $val;

			}

		}

		return $arr;

	}






	function makeXMLFromHash($tag,$asso_arr,$sub_tag = null){

		##if($sub_tag)jsAlert($tag.'   '.$sub_tag);

		$xml = '';




		if(array_key_exists('_XMLTAG', $asso_arr)){

			$xml = '<'.'?xml ';
			foreach($asso_arr['_XMLTAG'] as $k=>$v){
				$xml .= ' '.$k.'="'.htmlentities($v,ENT_QUOTES).'"';
			}
			$xml .= " ?".">\n";

			unset($asso_arr['_XMLTAG']);
		}


		if(array_key_exists('_XSLT',$asso_arr)){

			$xml = '<'.'?xml-stylesheet ';
			foreach($asso_arr['_XSLT'] as $k=>$v){
				$xml .= ' '.$k.'="'.htmlentities($v,ENT_QUOTES).'"';
			}
			$xml .= " ?".">\n";

			unset($asso_arr['_XSLT']);
		}



		if($tag){

			$xml .= '<'.$tag;

		}else if(array_key_exists('_BASETAG',$asso_arr)){
			$tag = $asso_arr['_BASETAG'];

			$xml .= '<'.$asso_arr['_BASETAG'];

			unset($asso_arr['_BASETAG']);
		}

		$inner_xml='';
		$self_closing=true;




		## SOME CUSTOM BULLSHIT
		## HANDLES MULTIPLE SUBTAGS
		## MUST NOT CONTAIN ANY
		if(array_key_exists('_TAG_MEMBERS', $asso_arr) && is_array($asso_arr['_TAG_MEMBERS']) && count($asso_arr['_TAG_MEMBERS']) > 1){

			$xml = '';


			foreach($asso_arr['_TAG_MEMBERS'] as $x=>$row){

				##jsAlert($x);

				$xml .= $this->makeXMLFromHash($tag,$row);
				##jsAlert($xml);
			}


			return $xml;
			##unset($asso_arr['_TAG_MEMBERS']);


		}






		foreach($asso_arr as $key=>$val){





			##echo '__:'.$key.' = '.$val.'<br>';






			if(is_array($val)){

				##echo $key;


				##print_r($asso_arr[$key]);

				$self_closing=false;





				## SPECIAL TAGS
				if($key[0] == '_'){ ##  && $key != '_TAG_MEMBERS' && $key != '_CDATA'

					##$key = substr($key,1);
					## echo $key."<br>\n";




					switch($key){

					## SUB TAG
					default:


						$key = substr($key,1);

						break;


					## TEXT NODE
					case '_CDATA':
					case '_BASETAG':
					case '_XSLT':
					case '_XMLTAG':

						## JUST HERE TO NOT HIT DEFAULT FLOW

				##jsAlert('sadf');

						continue;

						break;





					## ALL SUB TAGS
					case '_TAG_MEMBERS':

						foreach($val as $z=>$arr){


							#echo $z.'<br>';

							#print_r($arr);

							#echo '<hr>';






							foreach($arr as $k2=>$v2){





								##echo '**'.$k2.' = '.$v2.'<br>';

								#if($k2 == '_item'){
								#	jsAlert($v2['id']);
								#}


								if(is_array($v2)){



									if($k2[0] == '_'){



										switch($k2){
										default:

											#jsAlert($key.' : '.$k2.' = '.$v2);

											$k2 = substr($k2,1);


											$inner_xml .= $this->makeXMLFromHash($k2,$v2,$key);

											break;

										case '_TAG_MEMBERS':


										##jsAlert('sdfds');

											continue ;

											break;

										case '_CDATA':

											####$inner_xml .= trim($v2);

											break;

										}
									}


								}else{
									if($k2 == '_CDATA'){

										$inner_xml .= trim($v2);

									}else{

										$xml .= ' '.$k2."=\"".htmlentities($v2,ENT_QUOTES)."\"";
									}

								}
							}
						}

						continue 2;

						break;

					}


				}




			##jsAlert($key);
			##print_r($val);




				if($key == '_CDATA' || $key == '_TAG_MEMBERS'){
					continue;
				}

				$inner_tag = (is_numeric($key))? (($sub_tag)?$sub_tag:$tag) :$key;

				##echo $inner_tag.'<br>';

				$inner_xml .= $this->makeXMLFromHash($inner_tag,$val);

				/*$inner_xml .= '<'.$key.' ';

				foreach($asso_arr[$key] as $k2=>$v2){

					$xml .= $key."=\"".htmlentities($val,ENT_QUOTES)."\" ";

				}*/

			}else{

				if($key == '_CDATA'){
					#$self_closing == false;
					#$cdata .= $val;

					##$inner_xml .= trim($v2);

					continue;
				}

				$xml .= ' '.$key."=\"".htmlentities($val,ENT_QUOTES)."\"";
			}
		}

		if($tag){

			if($self_closing == false && trim($inner_xml) != '' ){ ##

				$xml .= '>'.$inner_xml.'</'.$tag.'>';
			}else{
				$xml .= '/>';
			}


			return $xml;

		}else{
			return $inner_xml;
		}

	}

	function stripHTML($html){
		unset($out);
		for($x=$y=$c=0,$len=strlen($html);$x<$len;$x++){
			$c= $html[$x];
			if($c == '<'){
				$y = strpos($html,'>',$x);
				if($y !== FALSE){
					$x = $y+1;
					continue;
				}
			}

			$out.= $c;
		}

		return $out;
	}



	function injectHTML($source_xml, $after_tag, $injecting_xml){

		$found=false;
		$x=$y=$z=0;

		// Chunk apart the xml
		$x = strpos($source_xml,"<".$after_tag.' ');
		if($x === FALSE)$x = strpos($source_xml,'<'.$after_tag.'>');
		if($x === FALSE)$x = strpos($source_xml,'<'.$after_tag);

		if($x !== FALSE)$found=true;

		// ADD THE TAG
		if($found === false){

			return null;//.... any time now

		}
		$z = strpos($source_xml,'>',$x);

		if($z < 0)return null;



		return substr($source_xml,0,$z+1) + $injecting_xml + substr($source_xml,$z+1);
	}

	/** save yourself a couple of lines, give it xml and get the standard returned result
	 *
	 */
	function autoArray($xml) {
		$result  = $_SESSION['JXMLP']->parseTag($xml);
		$result = $_SESSION['JXMLP']->stripTagMembers($result);

		return $result['result'];
	}

	/***********************************************************
	 * strips out TAG_MEMBERS and hashes BY ID where available
	 * See xml_interface.inc.php -> loadDataFromXML() for more info
	 * $delim is used if you want to replace the underscores with something other than nothing
	 *
	 */
	function stripTagMembers($row,$preview,$num_results,$workingCell,$delim) {


		if(array_key_exists('_TAG_MEMBERS',$row)) {
			$row = $row['_TAG_MEMBERS'];
		}
		if(array_key_exists('_CDATA',$row)) {
			$row = $row['_CDATA'];
		}

		if($preview) {
			if(array_key_exists($preview,$row)) {
				return $row[$preview];
			}else {
				return false;
			}
		}

		if(!is_array($row)) {
			return $row;
		}else {
			foreach($row as $hash=>$info) {

				$hashval = $this->stripTagMembers($info,'id',$num_results,$row,$delim); // looks for ID

				if($hashval) {
					$buildAr[$hashval] = $this->stripTagMembers($info,'',$num_results,$row,$delim);
				}else {
					if($hash === 0) { // remove single cells since they arent need (push the up in the array)
						if($info[0]['id']=== 0) {}else { // make sure you arent deleteing an id that is 0
							$buildAr = $this->stripTagMembers($info,'',$num_results,$row,$delim);
						}
					}else {
						$hashval = $this->stripTagMembers($info,'_CDATA',$num_results,$row,$delim); // looks for ID
						if($hashval) {
							$buildAr[$hash] = $info['_CDATA'];
						}else {
							$us_pos = strpos($hash,"_"); // underscore position
							if($us_pos === 0) {
								$newhash = substr(strtolower($hash),1);
							}else {
								$newhash = 	strtolower($hash);
							}
							$buildAr[$newhash] = $this->stripTagMembers($info,'',$num_results,$row,$delim);
						}
					}
				}
			}
		}
		return $buildAr;
	}





}

?>