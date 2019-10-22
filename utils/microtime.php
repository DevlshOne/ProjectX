<?

	/**
	 * Grabs the microtime, in float format, much more sane
	 */
	function microtime_float(){
	    list($usec, $sec) = explode(" ", microtime());
	    return ((float)$usec + (float)$sec);
	}

