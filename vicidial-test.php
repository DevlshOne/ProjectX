<!DOCTYPE HTML>
<html>
<head>
	<title>Project X - Administration</title>




	<link rel="stylesheet" href="//code.jquery.com/ui/1.11.3/themes/cupertino/jquery-ui.css">
	<script src="//code.jquery.com/jquery-1.10.2.js"></script>
	<script src="//code.jquery.com/ui/1.11.3/jquery-ui.js"></script>

	<script>
		function applyUniformity(){
			$("input:submit, button, input:button").button();
			$("input:text, input:password, input:reset, input:checkbox, input:radio, input:file").uniform();
		}

		$(function() {
			$( "#tabs" ).tabs();
		});
</script>
</head>
<body>


<div id="tabs" style="width:100%;height:100%">
	<ul>
		<li><a href="#tabs-1">COLD 1</a></li>
		<li><a href="#tabs-2">COLD 2</a></li>
		<li><a href="#tabs-3">COLD 3</a></li>
		<li><a href="#tabs-4">COLD 4</a></li>
		<li><a href="#tabs-7">COLD 5</a></li>
		<li><a href="#tabs-8">COLD 6</a></li>
		<li><a href="#tabs-9">COLD 7</a></li>
		<li><a href="#tabs-10">COLD 9</a></li>
		<li><a href="#tabs-5">TAPS</a></li>
		<li><a href="#tabs-6">VERIFIER 1</a></li>
		
	</ul>
	<div id="tabs-1">

		<iframe src="http://10.101.1.9/vicidial/welcome.php" width="100%" height="640"></iframe>

	</div>
	<div id="tabs-2">
		<iframe src="http://10.101.2.9/vicidial/welcome.php" width="100%" height="640"></iframe>

	</div>
	<div id="tabs-3">
                <iframe src="http://10.101.3.9/vicidial/welcome.php" width="100%" height="640"></iframe>

        </div>
	<div id="tabs-4">

		<iframe src="http://10.101.4.9/vicidial/welcome.php" width="100%" height="640"></iframe>


	</div>
	<div id="tabs-5">

		<iframe src="http://10.101.11.9/vicidial/welcome.php" width="100%" height="640"></iframe>

	</div>

	<div id="tabs-6">

		<iframe src="http://10.101.13.11/vicidial/welcome.php" width="100%" height="640"></iframe>

        </div>
	 <div id="tabs-7">

	         <iframe src="http://10.101.5.9/vicidial/welcome.php" width="100%" height="640"></iframe>

        </div>
	 <div id="tabs-8">

                <iframe src="http://10.101.6.9/vicidial/welcome.php" width="100%" height="640"></iframe>

        </div>

	<div id="tabs-9">

                <iframe src="http://10.101.7.9/vicidial/welcome.php" width="100%" height="640"></iframe>

        </div>

	<div id="tabs-10">

                <iframe src="http://10.101.9.9/vicidial/welcome.php" width="100%" height="640"></iframe>

        </div>



</div>

</body>
</html>
