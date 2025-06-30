<?php

   file_put_contents('deploy.log', date('c')." Deployment triggered\\n", FILE_APPEND);
$output = shell_exec('cd /home/yszraxwq/mumbrahillresort.com/calender && git pull origin main');
file_put_contents('deploy.log', $output, FILE_APPEND);
file_put_contents('deploy.log', "Deployed!\\n", FILE_APPEND);
echo "Deployed!";

?>
