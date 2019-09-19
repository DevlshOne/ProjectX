<?
	/***************************************************************
	 *	JS Functions
	 *
	 *	Provides a PHP API for common Javascript actions
	 *
	 ***************************************************************/


	function makeGetString($array) {
	    $output = '';
	    foreach($array as $key => $value) {
	            $output .= '&'.$key.'='.$value;
	    }
	    return $output;
	}

	/**
	 *
	 * @return
	 * @param object $itemCount
	 * @param object $pageSize[optional]=5
	 * @param object $pagerClass[optional]='pager'
	 * @param object $pagerID[optional]='page_nav'
	 * @param object $curClass[optional]='cur'
	 * @param object $prevClass[optional]='btn prev'
	 * @param object $nextClass[optional]='btn next'
	 * @param object $varName[optional]='page'
	 * @param object $nextLabel[optional]='Next'
	 * @param object $prevLabel[optional]='Prev'
	 * @param object $range[optional]='20''
	 * @param object $item_has['url']=stripurl(Array('resettsearch','del_row','searchid'))
	 *
	 */
	function makePageSystem($item_hash){
		$itemCount	=$item_hash['item_count'];
		$pageSize	=((strlen($item_hash['page_size']))?$item_hash['page_size']:5);
		$pagerClass	=((strlen($item_hash['page_class']))?$item_hash['page_class']:'pager');
		$pagerID	=((strlen($item_hash['page_id']))?$item_hash['page_id']:'page_nav');
		$curClass	=((strlen($item_hash['css_current']))?$item_hash['css_current']:'cur');
		$prevClass	=((strlen($item_hash['css_prev']))?$item_hash['css_prev']:'btn prev');
		$nextClass	=((strlen($item_hash['css_next']))?$item_hash['css_next']:'btn next');
		$varName	=((strlen($item_hash['page_index']))?$item_hash['page_index']:'page');
		$nextLabel	=((strlen($item_hash['next_lbl']))?$item_hash['next_lbl']:'Next');
		$prevLabel	=((strlen($item_hash['prev_lbl']))?$item_hash['prev_lbl']:'Prev');
		$range		=((strlen($item_hash['range']))?$item_hash['range']:'20');
//			stripurl(Array('resettsearch','del_row','searchid'));

		if ($itemCount>$pageSize){
			$pageName	= ($_SERVER['REDIRECT_URL'])?$_SERVER['REDIRECT_URL']:$_SERVER['PHP_SELF'];
			$numPages	= ceil($itemCount/$pageSize);
			$thisPage	= ($_REQUEST[$varName])?$_REQUEST[$varName]:1;
			$span 		= ($numPages>$range)?$range:$numPages;
			$xtraArr 	= $item_hash['url'];
			unset($xtraArr['surl']);
			unset($xtraArr[$varName]);
			unset($xtraArr['user']);
			$xtraGet 	=  makeGetString($xtraArr);

//			echo('<br>['.$xtraGet.']<br>');
			$res['pagerHTML'] = "<div class='$pagerClass' id='$pagerID'>";
			if ($numPages>$range){
				$start	= ($thisPage>=($span/2) && $span<=$numPages)?$thisPage-floor($span/2):0;
			}
			$start 	= ($start<1)?1:$start;
//			$start 	= intval($start);

			$finish = ($thisPage>=($span/2) && $span<=$numPages)?($thisPage-floor($span/2))+$span:$span;

			if (($finish>=($numPages-($range/2)))&&$finish>=$span) {
				$finish = $numPages;
			}
			if ($thisPage>1){
				$res['pagerHTML'] .= "<a class='$prevClass' href='$pageName?$varName=".($thisPage-1)."$xtraGet'>$prevLabel</a> ";
			}
			if ($numPages>($range*2)){
				$first 	= "<a href='$pageName?$varName=0'>1</a>...";
				$last 	= ($thisPage<($numPages-($range/2)))?"...<a href='$pageName?$varName=$numPages$xtraGet'>$numPages</a>":'';
				if($start>11){
					$res['pagerHTML'] .= $first;
				}
			}

			while ($finish>=$start) {
				$res['pagerHTML'] .= "<a href='$pageName?$varName=$start$xtraGet'";
				if ($thisPage==$start){
					$res['pagerHTML'] .= " class='$curClass' onclick='return false;'";
				}
				$res['pagerHTML'] .= "  class='$nextClass' >$start</a> ";
				$start++;
			}
			if($fsForward) {
				$res['pagerHTML'] .= $fsForward;
			}
			if ($thisPage<$numPages){
				$res['pagerHTML'] .= $last."<a class='$nextClass' href='$pageName?$varName=".($thisPage+1)."$xtraGet'>$nextLabel</a> ";
			}
			$res['pagerHTML'] .= "</div>";
		}
		return $res['pagerHTML'];
	}


	/** jsNextPage($total,$curidx, $pagesize,$frm, $jsfunc,$classname)<br>
	 * This function is used to change the page of records for the selected class<br>
	 */
	 function jsNextPage($total,$obj, $jsfunc){

		$jsfunc		= trim($jsfunc);
		$pages 		= intval($total  / $obj->pagesize) ;
		$current	= intval($obj->index / $obj->pagesize);

		if(($total > $obj->pagesize) && (($total%$obj->pagesize) != 0)){
			$pages++;

		}

		?><table border="0" cellpadding="0" cellspacing="4" >
		<tr><?

		if($obj->index > 0){

			#echo('<a href="#" onclick="getEl(\''.$obj->frm_name.'\').'.$obj->index_name.'.value--;'.$jsfunc.' getEl(\''.$obj->frm_name.'\').submit();return false;">'.backIcon('').'</a>');
			echo('<td><a href="#" onclick="getEl(\''.$obj->frm_name.'\').'.$obj->index_name.'.value--; '.$jsfunc.' getEl(\''.$obj->frm_name.'\').submit();return false;">'.
					'<img src="images/arrow_previous.png" width="22" height="22" border="0"></a></td>');

		}else{

			echo('<td><img src="images/spacer.gif" width="22" height="22" border="0"></td>');

		}

		echo("\n".'<td valign="middle"><select name="'.$obj->index_name.'" id="'.$obj->index_name.'" onchange="'.$jsfunc.' getEl(\''.$obj->frm_name.'\').submit();" style=font-size:8pt;">'."\n");

		$curpage = intval($obj->index/$obj->pagesize);



		## NEW GUTS LOCATION
		printOptionGuts($obj,$pages,$curpage);



		/*
		for($x=0;$x<$pages;$x++){

			echo("\t".'<option value="'.$x.'"');

			if( intval($obj->index/$obj->pagesize) == ($x)){

				echo(' SELECTED');

			}

			echo('>page ('.($x).')'."\n");

		}	*/

		echo('</select>'."\n</td>");

		if($current <  (--$pages)){
			#echo('<a href="#" onclick="getEl(\''.$obj->frm_name.'\').'.$obj->index_name.'.value++;'.$jsfunc.' getEl(\''.$obj->frm_name.'\').submit();return false;">'.nextIcon('').'</a>');
			echo('<td><a href="#"  onclick="getEl(\''.$obj->frm_name.'\').'.$obj->index_name.'.value++;   '.$jsfunc.' getEl(\''.$obj->frm_name.'\').submit();return false;">'.
				'<img src="images/arrow_next.png" width="22" height="22" border="0"></a></td>');

		}else{

			echo('<td><img src="images/spacer.gif" width="22" height="22" border="0"></td>');

		}
		?></tr>
		</table><?
	}



	function printOptionGuts($obj,$pages,$curpage){


		if($pages < 1000){

			?><script>
				var sel = <?=$curpage?>;
				for(var x=0;x < <?=$pages?>;x++){
					document.write('<option value="'+x+'"');
					if(sel == x){document.write(' SELECTED ');}
					document.write('>Page ('+(x+1)+')</option>');
				}
			</script><?

		}else{

			## SOMETHING COOLs

			$range = 20;

			$index = ($obj->index/$obj->pagesize);

			$start = ($index > 10)?($index-10):$index;
			$start = ($start < 1)?1:$start;

			//jsAlert($index.' '.$start);


			## PRE PAGE - RANGES
			if($start > 1){

				if($start < 20){

					$stepping = 1;

				}else{

					## 10 steps
					$stepping = round($start / 20);
					$stepping = ($stepping < 1)?1:$stepping;

				}


				//jsAlert("start stepping: ".$stepping);
				for($x = 1;$x < $start;$x += $stepping){

					## SAFETY NET
					if($x > $pages)break;

					echo '<option value="'.($x-1).'"';

					if( $index == ($x-1)){

						echo(' SELECTED');

					}

					echo('>Page ('.($x).')'."\n");

				}
			}


			## DO THE MAIN "center block" OF NUMBERS
			for($x=$start;$x < ($start+$range);$x++){

				## SHOULDNT HIT THIS, BUT ITS A SAFETY NET
				if($x > $pages)break;

				echo '<option value="'.($x-1).'"';

				if( $index == ($x-1)){

					echo(' SELECTED');

				}

				echo('>Page ('.($x).')'."\n");
			}


			## POST PAGE - RANGES
			if($x < $pages){ ## MOAR PAGES LEFT TO PRINT

				$stepping = round( ($pages - $x) / 20 ) ;
				$stepping = ($stepping < 1)?1:$stepping;

				//jsAlert("end stepping: ".$stepping);

				for(;$x < $pages;$x += $stepping){

					if($x > $pages)break;
					echo '<option value="'.($x-1).'"';

					if( $index == ($x-1)){

						echo(' SELECTED');

					}

					echo('>Page ('.($x).')'."\n");



				}


			}

		}

	}


	function jsNextPage_Secondary($total,$obj){

		$jsfunc		= trim($jsfunc);
		$pages 		= intval($total  / $obj->pagesize) ;
		$current	= intval($obj->index / $obj->pagesize);

		if(($total > $obj->pagesize) && (($total%$obj->pagesize) != 0)){

			$pages++;

		}

		?><table border="0" cellpadding="0" cellspacing="4" >
		<tr><?

		if($obj->index > 0){

			#echo('<a href="#" onclick="getEl(\''.$obj->frm_name.'\').'.$obj->index_name.'.value--;'.$jsfunc.' getEl(\''.$obj->frm_name.'\').submit();return false;">'.backIcon('').'</a>');
			echo('<td><a href="#" onclick="getEl(\''.$obj->frm_name.'\').'.$obj->index_name.'.value--;try{getEl(\''.$obj->index_name.'_2\').value=getEl(\''.$obj->frm_name.'\').'.$obj->index_name.'.value;}catch(e){} getEl(\''.$obj->frm_name.'\').submit();return false;">'.
					'<img src="images/arrow_previous.png" width="22" height="22" border="0"></a></td>');

		}else{

			echo('<td><img src="images/spacer.gif" width="22" height="22" border="0"></td>');

		}

		echo("\n".'<td valign="middle"><select name="'.$obj->index_name.'_2" id="'.$obj->index_name.'_2" onchange="try{getEl(\''.$obj->index_name.'\').value=this.value;}catch(e){} getEl(\''.$obj->frm_name.'\').submit();" style=font-size:8pt;">'."\n");

		$curpage = intval($obj->index/$obj->pagesize);

		printOptionGuts($obj,$pages,$curpage);


		/***?><script>
			var sel = <?=$curpage?>;
			for(var x=0;x < <?=$pages?>;x++){
				document.write('<option value="'+x+'"');
				if(sel == x){document.write(' SELECTED ');}
				document.write('>Page ('+(x+1)+')</option>');
			}

		</script><?**/

		/*
		for($x=0;$x<$pages;$x++){

			echo("\t".'<option value="'.$x.'"');

			if( intval($obj->index/$obj->pagesize) == ($x)){

				echo(' SELECTED');

			}

			echo('>page ('.($x).')'."\n");

		}	*/

		echo('</select>'."\n</td>");

		if($current <  (--$pages)){
			#echo('<a href="#" onclick="getEl(\''.$obj->frm_name.'\').'.$obj->index_name.'.value++;'.$jsfunc.' getEl(\''.$obj->frm_name.'\').submit();return false;">'.nextIcon('').'</a>');
			echo('<td><a href="#"  onclick="getEl(\''.$obj->frm_name.'\').'.$obj->index_name.'.value++; try{getEl(\''.$obj->index_name.'_2\').value=getEl(\''.$obj->frm_name.'\').'.$obj->index_name.'.value;}catch(e){} getEl(\''.$obj->frm_name.'\').submit();return false;">'.
				'<img src="images/arrow_next.png" width="22" height="22" border="0"></a></td>');

		}else{

			echo('<td><img src="images/spacer.gif" width="22" height="22" border="0"></td>');

		}
		?></tr>
		</table><?
	}




	/******
	 *
	 ****/
	function jsNextPageOLD($total,$obj, $jsfunc){

		$jsfunc		= trim($jsfunc);
		$pages 		= intval($total  / $obj->pagesize) ;
		$current	= intval($obj->index / $obj->pagesize);


		if(($total > $obj->pagesize) && (($total%$obj->pagesize) != 0)){

			$pages++;

		}
		?><table border="0" cellpadding="0" cellspacing="4" >
		<tr><?
		if($obj->index>0){

			echo('<td><a href="#" onclick="'.$obj->frm_name.'.'.$obj->index_name.'.value--;'.$jsfunc.''.$obj->frm_name.'.submit();return false;"><img src="images/button/back.jpg" width="51" height="20" border="0"></a></td>');
			#echo('<a href="#" onclick="'.$obj->frm_name.'.'.$obj->index_name.'.value--;'.$jsfunc.''.$obj->frm_name.'.submit();return false;"><input type="button" name="bktbtn" id="bktbtn" value=" <<< "  style="width:45px;" ></a>');

		}else{

			echo('<td><img src="" width="23" height="1" border="0"></td>');

		}

		echo("\n".'<td valign="middle"><select name="'.$obj->index_name.'" id="'.$obj->index_name.'" onchange="'.$jsfunc.' '.$obj->frm_name.'.submit();" style=font-size:8pt;">'."\n");

		for($x=0;$x<$pages;$x++){

			echo("\t".'<option value="'.$x.'"');

			if( intval($obj->index/$obj->pagesize) == ($x)){

				echo(' SELECTED');

			}

			echo('>page ('.($x).')'."\n");

		}

		echo('</select></td>'."\n");

		if($current <  (--$x)){

			echo('<td><a href="#" onclick="'.$obj->frm_name.'.'.$obj->index_name.'.value++;'.$jsfunc.' '.$obj->frm_name.'.submit();return false;"><img src="images/button/next.jpg" width="51" height="20" border="0"></a></td>');
			#echo('<a href="#" onclick="'.$obj->frm_name.'.'.$obj->index_name.'.value++;'.$jsfunc.' '.$obj->frm_name.'.submit();return false;"><input type="button" name="nextbtn" id="nextbtn" value=" >>> " style="width:45px;" ></a>');

		}else{

			echo('<td><img src="" width="23" height="1" border="0"></td>');

		}
		?></tr>
		</table><?

	}
	/** jsAlert(msg,quotemode)<br>
	 * This function will write opening and closing script tags!<br>
	 * Calls alert(msg), will apply addslashes() to message, unless quotemode is set to 1<br>
	 * Quote mode of 1 will allow you to pass in \n for creating multilined alerts
	 */
	function jsAlert($msg,$quotemode=0){
		if($quotemode < 1)
			echo "<script>alert('".addslashes($msg)."');</script>";
		else	echo "<script>alert(\"".$msg."\");</script>";
	}
	/** jsReload()<br>
	 * This function will the window<br/>
	 */
	function jsReload(){
		echo "<script>window.location.reload();</script>";
	}
	/** jsRedirect($url)<br>
	 * This function will write opening and closing script tags!<br>
	 * Calls window.location.replace(url)
	 */
	function jsRedirect($url){
		echo "<script>window.location.replace('$url')</script>";
	}
	/** jsParentFilter($url)<br>
	 * This function will write opening and closing script tags!<br>
	 * Grabs and parses the current query string, then strips and replaces using the array you pass in, then calls window.parent.location.replace()<br>
	 * $replacearr_example['auto_show_clientid'] = 35; ## This will remove the current 'auto_show_clientid' from the query and add a new one, with 35 as the new value
	 */
	function jsParentFilter($replacearr,$mode,$location_suffix){
		?><script>

			var root = <?=($mode)?'(window.opener)?window.opener:window.parent;':'(window.parent)?window.parent:window;';?>

			var qsParm = new Array();
			function qs(){
				var query = root.location.search.substring(1);
				query = (query.indexOf('#') > -1)?query.substring(0,query.indexOf('#')):query;

				var parms = query.split('&');
				for (var i=0; i<parms.length; i++) {
					var pos = parms[i].indexOf('=');
					if (pos > 0) {
						var key = parms[i].substring(0,pos);
						var val = parms[i].substring(pos+1);
						qsParm[key] = val;
					}
				}
			}
			qs();



			var loc = ''+root.location;
			loc = (loc.indexOf('#') > -1)?loc.substring(0,loc.indexOf('#')):loc;
			<?

				foreach($replacearr as $key=>$val){

					?>loc = loc.replace('&<?=$key?>='+qsParm['<?=$key?>'],'');
					loc += '&<?=$key?>=<?=$val?>';<?
				}


			?>

			loc += '<?=$location_suffix?>';

			/**if(window.parent){	window.parent.location.replace(loc);	}
			else{ 			window.location.replace(loc);		}**/

			try{

				window.opener.location.replace(loc);

			}catch(e){

				try{
					window.parent.location.replace(loc);
				}catch(e){
					root.location.replace(loc);
				}

			}

		</script><?
	}
	/** jsParentRedirect($url)<br>
	 * This function will write opening and closing script tags!<br>
	 * Calls window.parent.location.replace(url) (if it exists,otherwise does location), then closes script tag.
	 */
	function jsParentRedirect($url){
		?><script>
			if(window.opener){window.opener.location.replace('<?=$url?>'); }
			else if(window.parent){	window.parent.location.replace('<?=$url?>'); }
			//else{ 			window.location.replace('<?=$url?>'); }
		</script><?
	}
	/******
	 *
	 ****/
	function jsClose(){
		echo "<script>window.close();</script>";
	}
	/******
	 *
	 ****/
	function jsCloseSelf(){
		echo "<script>self.close();</script>";
	}
	/******
	 *
	 ****/
	function jsConfirm($txt){
		echo "<script>window.confirm('".addslashes($txt)."');</script>";
	}
	/******
	 *
	 ****/
	function jsConfirmLocation($txt,$loc){
		echo "<script>\n";
		echo "var loc = window.confirm('".addslashes($txt)."');\n";
		echo "if (loc){window.location.replace('".$loc."');\n";
		echo "</script>\n";
	}
	/******
	 *
	 ****/
	function jsReloadParent(){
		?><script>

		try{
			if(window.parent){
				window.parent.location=window.parent.location+( (window.parent.location.indexOf("?") > -1)?'&':'?');
				if(window.parent.parent){
					// APPENDING TO LOCATION CAUSES A 'GET' RELOAD, INSTEAD OF POST-RELOAD which throws warning
					window.parent.location=window.parent.parent.location+( (window.parent.parent.location.indexOf("?") > -1)?'&':'?');
				}
			}
		}catch(e){
			try{
				if(window.opener){
					window.opener.location.reload();
					if(window.opener.opener){
						window.opener.opener.location.reload();
					}
				}
			}catch(e){

				window.status = 'JS Error reloading window opener: ' + e;

			}
		}

		</script><?
	}
	/******
	 *
	 ****/
	function jsReloadTacos(){
		?><script>

		try{
			if(	window.opener){
				window.opener.location.reload();
			}else if(window.parent && window.parent != window){
				window.parent.location.reload();
			}
		}catch(e){
			try{

				if(window.parent && window.parent != window){
					window.parent.location.reload();
				}
			}catch(e){}
		}
		</script><?
	}
	/******
	 *
	 ****/
	 function jsCloseDivFrame($item){


	?>
		<h1><?=$item['msg']?></h1>
		<script  type="text/javascript">
			var interval_time =<?=(($_SESSION['is_ie']) ? 1150 : 886)?>;
			try{
				setTimeout("window.parent.getEl('<?=$item['divid']?>').style.display='none';window.parent.location.reload();",interval_time);
//				window.parent.getEl('<?=$item['divid']?>').style.display='inline';
			}catch(e){
				try{
					setTimeout("window.opener.getEl('<?=$item['divid']?>').style.display='none';window.opener.location.reload();",interval_time);
				}catch(e){
					try{
						window.opener.getEl('<?=$item['divid']?>').style.display='none';
					}catch(e){
						window.parent.getEl('<?=$item['divid']?>').style.display='none';
					}
				}
			}
		</script>
	<?
	 }
	/******
	 *
	 ****/
	function getErrorImage($bError){
		?><img src="/images/error.gif" width="15" height="15" border="0" onMouseover="showtip(this,event,'<?print($bError);?>')" onMouseout="hidetip()" style="cursor:hand;"><?
	}


	/* include functions.js  with activeembed() for this to embed properly
	 */
	function jsEmbed($src, $id, $height, $width, $divid,$zindex, $quality) {

		echo('<script>activeEmbed(\''. $src . '\',\'' . $id . '\',\'' . $height . '\',\'' . $width . '\',\'' . $divid . '\',\'' . $zindex . '\',\'\',\'\',\''.$quality.'\');</script>');
		# located in functions.js
	}
	/******
	 *
	 ****/

?>
