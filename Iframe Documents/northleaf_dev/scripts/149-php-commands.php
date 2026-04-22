<?php
//install package-files
//$command = 'php /opt/processmaker/artisan route:clear';
$command = 'php /opt/processmaker/artisan processmaker:unblock-request --request=779';
exec($command, $output, $return_var);
var_dump([$command, $output, $return_var]);
exit();