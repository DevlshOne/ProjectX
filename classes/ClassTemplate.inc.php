<?php
	/***************************************************************
	 *	Class Template - Basic structure for our common class
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['lowercaseclassnamehere'] = new ClassNameHere;


class ClassNameHere{

	var $table	= '';			## Classes main table to operate on
	var $orderby	= 'id';		## Default Order field
	var $orderdir	= 'DESC';	## Default order direction


	## Page  Configuration
	var $pagesize	= 30;	## Adjusts how many items will appear on each page
	var $index	= 0;		## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

	var $index_name = 'name_me_please_index';	## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
	var $frm_name = 'nextfrm';

	var $order_prepend = 'itm_';				## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

	function ClassNameHere(){


		## REQURES DB CONNECTION!



		$this->handlePOST();
	}


	function makeDD($name,$sel,$class,$onchange,$size){

		$names		= 'name';	## or Array('field1','field2')
		$value		= 'id';
		$seperator	= '';		## If $names == Array, this will be the seperator between fields


		$fieldstring='';
		if(is_array($names)){
			$x=0;
			foreach($names as $name){
				$fieldstring.= $name.',';
			}
		}else{	$fieldstring.=$names.',';}
		$fieldstring	.= $value;

		$sql = "SELECT $fieldstring FROM ".$this->table." WHERE owner='".$_SESSION['user']['owner']."'";
		$DD = new genericDD($sql,$names,$value,$seperator);
		return $DD->makeDD($name,$sel,$class,1,$onchange,$size);
	}



	function handlePOST(){
		# Ordering adjustments
		if($_GET[$this->order_prepend.'orderby'] && $_GET[$this->order_prepend.'orderdir']){
			if($_GET[$this->order_prepend.'orderdir']=='ASC')
				$this->orderdir	='ASC';
			else	$this->orderdir ='DESC';

			$this->orderby = $_GET[$this->order_prepend.'orderby'];	# Or switch order by
		}

		# Page index adjustments
		if($_REQUEST[$this->index_name]){

			$this->index = $_REQUEST[$this->index_name] * $this->pagesize;

		}


		if(isset($_POST['adding_item'])){

			$id = intval($_POST['adding_item']);

			unset($dat);


			$dat['name'] = $_POST['name'];

			if($id){

				$_SESSION['dbapi']->aedit($id,$dat,$this->table);

			}else{

				## EXAMPLE OF FIELDS TO SET ONLY WHEN ADDING
				#$dat['owner'] = $_SESSION['user']['owner'];
				#$dat['createdby_time'] = time();


				$_SESSION['dbapi']->aadd($dat,$this->table);
				$id = mysqli_insert_id($_SESSION['dbapi']->db);
			}

			jsRedirect(stripurl('add_item').'add_item='.$id);
			exit;

		}

	}

	function handleFLOW(){
		# Handle flow, based on query string


		if(isset($_REQUEST['add_item'])){

			$this->makeAdd($_REQUEST['add_item']);

		}else{
			$this->listEntrys();
		}

	}






	function listEntrys(){

		$where = "WHERE 1";#PLACE YOUR WHERE CLAUSE IN HERE!


		## RESET PAGE TO ZERO IF NEW SEARCH
		if($_POST['the_Search_button']){
			$this->index=0;
		}


		## SEARCHING
		if(isset($_POST['searching_area'])){


			## ID SEARCH
			if($_REQUEST['s_id']){

				## ALWAYS FILTER!
				$id = intval($_REQUEST['s_id']);
				$where .= " AND id='$id' ";

			}


			## NAME SEARCH
			if($_REQUEST['s_name']){

				## ALWAYS FILTER!
				$where .= " AND name LIKE '%".addslashes($_REQUEST['s_name'])."%' ";

			}
		}



		$totalcount = getCount($this->table,$where);	## gets total row count, for page system
		$res = query("SELECT * FROM `".$this->table."` $where ORDER BY ".addslashes($this->orderby).' '.$this->orderdir."  LIMIT ".$this->index.','.$this->pagesize,1);


		?><script>var delmsg = 'Are you sure you want to delete this?';</script><?


		?><form name="<?=$this->frm_name?>" id="<?=$this->frm_name?>" method="POST" action="<?=$_SERVER['REQUEST_URI']?>">
			<input type="hidden" name="searching_area">
		<table border="0" width="100%" cellspacing="0">
		<tr>
			<td class="header">List Header</td>
			<td class="header"><?
				if($totalcount > $this->pagesize){


					echo jsNextPage($totalcount,$this);


				}else{
					?>&nbsp;<?
				}
			?></td>
		</tr>
		<tr>
			<td colspan="2"><table border="0" width="100%">
			<tr>
				<td class="row2" rowspan="2"><font size="+1">SEARCH</font></td>
				<th class="row2">ID</th>
				<th class="row2">Name</th>
				<td><input type="submit" value="Search" name="the_Search_button"></td>
			</tr>
			<tr>
				<td align="center"><input name="s_id" size="5" value="<?=htmlentities($_REQUEST['s_id'])?>"></td>
				<td align="center"><input name="s_name" size="20" value="<?=htmlentities($_REQUEST['s_name'])?>"></td>
				<td><?

				if(isset($_POST['searching_area'])){

					echo '<input type="reset" onclick="go(\''.$_SERVER['REQUEST_URI'].'\')">';

				}else{
					echo '&nbsp;';
				}

				?></td>
			</tr>
			</table></td>
		</tr></form>
		<tr>
			<td colspan="2"><table border="0" width="100%">
			<tr>
				<th class="row2"><?=$this->getOrderLink('id')?>ID</a></th>
				<th class="row2" align="left"><?=$this->getOrderLink('name')?>Name</a></th>
				<th class="row2">&nbsp;</th>
			</tr><?

			$colspan=3;

			if(mysqli_num_rows($res) == 0){
				?><tr><td colspan="<?=$colspan?>" align="center"><i>No items found</i></td></tr><?
			}

			$color=0;
			while($row = mysqli_fetch_array($res)){
				$class='row'.($color++%2);

				$onclick=' onclick="go(\'#PUT_URL_HERE\')" ';
			?><tr>
				<td class="<?=$class?> hand" <?=$onclick?> align="center"><?=$row['id']?></td>
				<td class="<?=$class?> hand" <?=$onclick?>><?=htmlentities($row['name'])?></td>
				<td align="center"><a href="<?=stripurl('del_itemid').'del_itemid='.$row['id']?>" onclick="return confirm(delmsg)">
					<font color="red">[DELETE]</font>
				</a></td>
			</tr><?
			}
			?></table></td>
		</tr></table><?

	}




	function makeAdd($id){

		if($id){
			$row = querySQL("SELECT * FROM `".$this->table."` WHERE id='$id'");
		}

		?><form method="POST" action="<?=stripurl('')?>">
			<input type="hidden" name="adding_item" value="<?=$id?>">
		<table border="0">
		<tr>
			<td colspan="2" class="header"><?=($id)?'Editing Item #'.$id:'Adding new Item'?></td>
		</tr>
		<tr>
			<th>Name</th>
			<td><input name="name" value="<?=htmlentities($row['name'])?>"></td>
		</tr>
		<tr>
			<td><input type="button" value="Cancel" onclick="go('<?=stripurl('add_item')?>')"></td>
			<th align="right"><input type="submit" value="Save Changes"></th>
		</tr></form>
		</table><?
	}



	function getOrderLink($field){
		$uri = stripurl(array($this->order_prepend.'orderby',$this->order_prepend.'orderdir'));
		$var = '<a href="'.$uri.$this->order_prepend.'orderby='.$field.'&'.$this->order_prepend.'orderdir=';
		if($this->orderby==$field && $this->orderdir=='ASC')
			$var .= 'DESC';
		else	$var .= 'ASC';
		$var .= '">';
		return $var;
	}
}
