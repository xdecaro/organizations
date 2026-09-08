<?php
declare(strict_types=1);
$service=file_get_contents(__DIR__.'/../component/admin/src/Service/CoreIntegrationService.php');
if ($service===false) exit(1);
foreach (['Xdecaro\\Core\\Integration\\EntityReference','Xdecaro\\Core\\Integration\\RelationReference','Xdecaro\\Core\\Asset\\AssetService','com_xdecaroorganizations'] as $needle) { if (!str_contains($service,$needle)) { fwrite(STDERR,"Missing Core integration marker: $needle\n"); exit(1); } }
echo "Core integration smoke: OK\n";
