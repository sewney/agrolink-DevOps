<?php

class NotificationSettingsModel
{
    use Database;

    protected $table = 'notification_settings';

    public function getSettings($userId, array $defaults)
    {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return $defaults;
        }

        $row = $this->get_row(
            "SELECT settings_json FROM {$this->table} WHERE user_id = :user_id LIMIT 1",
            ['user_id' => $userId]
        );

        if (!$row || !isset($row->settings_json)) {
            return $defaults;
        }

        $decoded = json_decode((string)$row->settings_json, true);
        if (!is_array($decoded)) {
            return $defaults;
        }

        foreach ($defaults as $key => $value) {
            if (array_key_exists($key, $decoded)) {
                $defaults[$key] = (bool)$decoded[$key];
            }
        }

        return $defaults;
    }

    public function saveSettings($userId, array $settings, array $defaults)
    {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return $defaults;
        }

        $normalized = $defaults;
        foreach ($normalized as $key => $value) {
            if (array_key_exists($key, $settings)) {
                $normalized[$key] = (bool)$settings[$key];
            }
        }

        $payload = json_encode($normalized);
        if ($payload === false) {
            return $defaults;
        }

        $sql = "INSERT INTO {$this->table} (user_id, settings_json, created_at, updated_at)
                VALUES (:user_id, :settings_json, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    settings_json = VALUES(settings_json),
                    updated_at = NOW()";

        $saved = $this->write($sql, [
            'user_id' => $userId,
            'settings_json' => $payload,
        ]);

        return $saved === false ? $defaults : $normalized;
    }
}
