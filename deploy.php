<?php

    file_put_contents('deploy.log', date('c')." Deployment triggered\n", FILE_APPEND);
    shell_exec('cd /home/yszraxwq/mumbrahillresort.com/calender && git pull origin main');
    echo "Deployed!";
?>
