<?
	define("AUTO",0);
	define("KB",1);
	define("MB",2);
	define("GB",3);
	define("TB",3);

	/**
	 * wrapper for formatBytes
	 */
	function byteFormat($in,$mode = AUTO){ return formatBytes($in,$mode);}
	function formatBytes($in, $mode = AUTO){

		switch($mode){
		case AUTO:
		default:
			if($in < 1024)			return number_format($in).' bytes';
			else if($in < 1048576)		return number_format($in/1024,2).' KB';
			else if($in < 1073741824)	return number_format($in/1048576,2).' MB';
			else if($in < 1099511627776)	return number_format($in/1073741824,2).' GB';
			else				return number_format($in/1099511627776,2).' TB';

			break;
		case KB:	return number_format($in/1024,2).' KB';
		case MB:	return number_format($in/1048576,2).' MB';
		case GB:	return number_format($in/1073741824,2).' GB';
		case TB:	return number_format($in/1099511627776,2).' TB';
		}

	}

?>