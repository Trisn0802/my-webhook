<?php
/**
 * Saran nama service (datalist) untuk input nama.
 * Dipakai di views/dashboard.php dan views/hooks/form.php.
 * Varsional: $suggestId — id unik datalist per form.
 */
$suggestId = $suggestId ?? 'serviceSuggestions';
$serviceNames = [
    'Uptime Kuma', 'UptimeRobot', 'Healthchecks.io', 'GitHub',
    'GitLab', 'Gitea', 'Grafana', 'Prometheus Alertmanager',
    'Zabbix', 'Nagios', 'Nginx', 'Proxmox', 'Docker',
    'Portainer', 'Pi-hole', 'Home Assistant', 'Synology DSM',
    'TrueNAS', 'qbittorrent', 'Jenkins', 'n8n', 'Discord',
    'WhatsApp', 'IFTTT', 'Zapier', 'Cron Job', 'Server Pribadi',
];
?>
<datalist id="<?= e($suggestId) ?>">
  <?php foreach ($serviceNames as $s): ?>
    <option value="<?= e($s) ?>"></option>
  <?php endforeach; ?>
</datalist>
