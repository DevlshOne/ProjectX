<?
	## Renders elapsed time
	## ex: '1 hour 15min', instead of '75 min'

	function rendertime($in){

		$days	= floor($in/86400);
		if($days > 0)	$in -= ($days * 86400);

		$hours	= floor($in/3600);
		if($hours > 0)	$in -= ($hours * 3600);

		$min	= floor($in/60);



		$d_plural = ($days > 1)?'s':'';
		$h_plural = ($hours > 1)?'s':'';
		$m_plural = ($min > 1)?'s':'';

		$out = (($days > 0)?number_format($days).' Day'.$d_plural:'');
		$out.= ($days > 0 && ($hours > 0 ) )?', ':'';
		$out.= (($hours > 0)?$hours.' Hour'.$h_plural:'');
		$out.= ($min > 0 && ($days > 0 || $hours > 0) )?', ':'';
		$out.= ($min)?$min.' Minute'.$m_plural:'';

		if(!$days && !$hours && !$min && $in){
			## SHOW SECONDS
			$out .= $in.' Sec.';
		}

		return $out;
	}



	function renderTimeFormatted($in){

		$tmptime = intval($in);
		$tmphours = floor($tmptime/3600);
		// REMOVE HOURS
		$tmptime -= ($tmphours * 3600);

		$tmpmin = floor($tmptime/60);

		$tmptime -= ($tmpmin * 60);

		$tmpsec = ($tmptime%60);
		$out = 	(($tmphours > 0)?$tmphours.':':'').
						(($tmpmin < 10)?'0'.$tmpmin:$tmpmin).':'.
						(($tmpsec < 10)?'0'.$tmpsec:$tmpsec);

		return $out;

	}


	function renderTimeFormattedSTD($in){

		$tmptime = intval($in);
		$tmphours = floor($tmptime/3600);
		// REMOVE HOURS
		$tmptime -= ($tmphours * 3600);

		$tmpmin = floor($tmptime/60);

		$tmptime -= ($tmpmin * 60);

		$tmpsec = ($tmptime%60);
		$out = 	(($tmphours > 0)?$tmphours.':':'0:').
						(($tmpmin < 10)?'0'.$tmpmin:$tmpmin).':'.
						(($tmpsec < 10)?'0'.$tmpsec:$tmpsec);

		return $out;

	}
