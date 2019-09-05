#!/usr/bin/php
<?php


	$hosts = array(

	// LINCOLN ROOM
	/*		'7011-7012',
			'7013-7014',
			'7015-7016',
                        '7017-7018',
                        '7021-7022',
                        '7023-7024',
                        '7025-7026',
                        '7027-7028',
                        '7031-7032',
                        '7033-7034',
                        '7035-7036',
                        '7037-7038',
                        '7041-7042',
                        '7043-7044',
                        '7045-7046',
                        '7047-7048',
	*/
	// UPSTAIRS EAST (NICHOLES ROOM)
			/*'10.10.1.43',
                        '10.10.1.44',
                        '10.10.1.45',
                        '10.10.1.46',
                        '10.10.1.47',
                        '10.10.1.48',
                        '10.10.1.49',
                        '10.10.1.50',*/


			'11001-11002', //24
                        '11003-11004',
                        '11005-11006',

                        '11007-11008',
                        '11009-11010',
                        '11011-11012',
                        '11013-11014',
                        '11015-11016',
                        '11017-11018',
                        '11019-11020',
                        '11021-11022',
                        '11023-11024',



	// TRAINING ROOM

	/*	a	'7091.courtesycall.com',
			'7092.courtesycall.com',
                        '7093.courtesycall.com',
                        '7094.courtesycall.com',

                        '7061.courtesycall.com',
                        '7062.courtesycall.com',
                        '7063.courtesycall.com',
                        '7064.courtesycall.com',
                        '7065.courtesycall.com',
                        '7066.courtesycall.com',
                        '7067.courtesycall.com',
                        '7068.courtesycall.com',

                        '7051-7052.courtesycall.com',
			'7053.courtesycall.com',
			'7054.courtesycall.com',
	*/





 /*                       '11101-11102',
			'11103-11104',
                        '11105-11106',
                        '11107-11108',
                        '11109-11110',
                        '11111-11112',
                        '11113-11114',
                        '11115-11116'
*/

		);

	// UPDATE ONLY
	//$remote_cmd = "/ProjectX-Client/updateClient.sh";
	//$remote_cmd = "cd /ProjectX-Client;mv updateClient.sh updateClient.old;wget http://10.10.0.65/download/updateClient.sh";

	// UPDATE THEN SHUTDOWN
	//#$remote_cmd = "/ProjectX-Client/updateClient.sh;shutdown -h now";


	// JUST SHUT THEM DOWN
	//$remote_cmd = "shutdown -h now";

	//$remote_cmd = "chmod 777 /ProjectX-Client/ProjectX-Client.jar";

	$remote_cmd = "sed -i s/10.100.0.65/10.10.0.65/g /home/cci1/config.xml;sed -i s/10.100.0.65/10.10.0.65/g /home/cci2/config.xml;sed -i s/10.100.0.65/10.10.0.65/g /home/cci/config.xml";

	///$remote_cmd = 'sed -i  s/volume_mode=\\\\\"2\\\\\"/volume_mode=\\\\\"1\\\\\"/g /home/cci1/config.xml';

//sed -i  s/volume_mode=\\\"2\\\"/volume_mode=\\\"1\\\"/g /home/cci2/config.xml';

	$cmd = "sshpass -p Insod#1 ssh  -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no root@";


	foreach($hosts as $host){

		$tmpcmd = $cmd .$host." \"".$remote_cmd."\"";

		echo "Running: ".$tmpcmd."\n";

		// EXECUTE
		echo `$tmpcmd &`;

	}

