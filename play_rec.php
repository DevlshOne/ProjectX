<?
	session_start();

	$url = trim($_REQUEST['play_url']);

	
	if($_SESSION['user']['id']){
		
		include_once("dbapi/dbapi.inc.php");
		
		$_SESSION['dbapi']->users->updateLastActionTime();
		
	}

?><audio id="audio_obj" autoplay controls>
	<source src="<?=$url?>" type="audio/mpeg" />
	Your browser does not support the audio element.
</audio>
<a href="#" onclick="parent.hideAudio();return false">[Close]</a>

<script>
	parent.applyUniformity();
</script><?


