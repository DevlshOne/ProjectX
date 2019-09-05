<?
	if(!isset($_GET['percent'])){
		print "No percent given.";
		exit;
	}else{

		/**if($_GET['percent']>100){		$percent=100;}else **/

		if($_GET['percent'] < 0){
			$percent = 0;
		}else{
			$percent=preg_replace("/[^0-9.]/",'',$_GET['percent']);
		}

		$w=($_REQUEST['width'])?intval($_REQUEST['width']):100;
		$h=($_REQUEST['height'])?intval($_REQUEST['height']):10;


		// 0-59
		if(intval($percent) < 60){
			// RED

			$r = 255;
			$g = 0;
			$b = 0;

		// 60-69
		}else if(intval($percent) < 70){

			// ORANGE

			$r = 255;
			$g = 128;
			$b = 0;

		// 70-79
		}else if(intval($percent) < 80){

			// YELLOW
			$r = 255;
			$g = 200;
			$b = 0;

		// 80-89
		}else if(intval($percent) < 90){

			// GREENISH YELLOW
			$r = 128;
			$g = 255;
			$b = 0;

		// 90-99
		}else if(intval($percent) < 100){

			// MOREE GREEN
			$r = 64;
			$g = 255;
			$b = 0;

		// 100%
		}else{

			// GREEN

			// YELLOW
			$r = 0;
			$g = 255;
			$b = 0;
		}


/**
		$r=(isset($_REQUEST['r']))?intval($_REQUEST['r']):164;
		$g=(isset($_REQUEST['g']))?intval($_REQUEST['g']):26;
		$b=(isset($_REQUEST['b']))?intval($_REQUEST['b']):26;

		$setclr=((isset($_REQUEST['b'])||isset($_REQUEST['b'])||isset($_REQUEST['b'])) ? 1:0);
**/

		$filename = 'images/percentages/percent_'.$percent.'_'.$w.'_'.$h.'.png';



		## CHECK IF IMAGE EXISTS ALREADY
		if($setclr||!file_exists($filename)){

			$im = @imagecreate ($w, $h) or die ("Error : Cannot Initialize new GD image stream");

			header ("Content-type: image/png");

			$b = imagecolorallocate ($im, 72, 77, 81);
			$font = imagecolorallocate ($im, 0, 0, 0);
//			if(floatval($_GET['percent']) >= 100){
//				$f = imagecolorallocate ($im, 0, 128, 0);
//			}else{

				$f = imagecolorallocate ($im, $r, $g, $b);

//			}

			$filled_width = ($w != 100)?(($w/100)*$percent):$percent;

			$text = floatval($_GET['percent']).'%';
			$text_pos = $w/3;
			if(strlen($text) > 4){
				$text_pos -= (strlen($text)-4)*4;
			}

			imagefilledrectangle($im,0,0,$filled_width,$h,$f);

			imagestring ($im, 2, $text_pos, floor($h/2)-7,  $text, $font);
			imagepng($im,$filename);
		}

		## READ TO OUTPUT BUFFER
		readfile($filename);

	}