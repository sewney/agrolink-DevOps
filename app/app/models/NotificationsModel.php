<?php

class NotificationsModel
{
    use Database;

    protected $table = 'notifications';
    private $systemAliases = ['system', 'maintenance', 'promotion', 'alert'];

    private function normalizeManualItem(array $payload)
    {
        $title = trim((string)($payload['title'] ?? ''));
        $message = trim((string)($payload['message'] ?? ''));
        $type = strtolower(trim((string)($payload['type'] ?? 'system')));
        $link = trim((string)($payload['link'] ?? ''));
        $relatedId = isset($payload['related_id']) ? (int)$payload['related_id'] : null;
        $createdAt = trim((string)($payload['created_at'] ?? ''));
        $eventKey = trim((string)($payload['event_key'] ?? ''));

        if ($title === '' || $message === '') {
            return null;
        }

        if ($type === '') {
            $type = 'system';
        }

        if ($createdAt === '' || !strtotime($createdAt)) {
            $createdAt = date('Y-m-d H:i:s');
        }

        if ($eventKey === '') {
            $eventKey = 'admin_' . date('YmdHis') . '_' . bin2hex(random_bytes(6));
        }

        return [
            'event_key' => substr($eventKey, 0, 120),
            'title' => substr($title, 0, 180),
            'message' => $message,
            'type' => substr($type, 0, 50),
            'related_id' => $relatedId > 0 ? $relatedId : null,
            'link' => $link !== '' ? substr($link, 0, 255) : null,
            'created_at' => $createdAt,
        ];
    }

    private function normalizeGeneratedItem(array $item)
    {
        $eventKey = trim((string)($item['event_key'] ?? $item['id'] ?? ''));
        $title = trim((string)($item['title'] ?? 'Notification'));
        $message = trim((string)($item['message'] ?? ''));
        $type = trim((string)($item['type'] ?? $item['category'] ?? 'system'));
        $link = trim((string)($item['link'] ?? ''));
        $relatedId = isset($item['related_id']) ? (int)$item['related_id'] : null;
        $createdAt = trim((string)($item['created_at'] ?? ''));

        if ($eventKey === '' || $title === '' || $message === '' || $type === '') {
            return null;
        }

        if ($createdAt === '' || !strtotime($createdAt)) {
            $createdAt = date('Y-m-d H:i:s');
        }

        return [
            'event_key' => substr($eventKey, 0, 120),
            'title' => substr($title, 0, 180),
            'message' => $message,
            'type' => substr($type, 0, 50),
            'related_id' => $relatedId > 0 ? $relatedId : null,
            'link' => $link !== '' ? substr($link, 0, 255) : null,
            'created_at' => $createdAt,
        ];
    }

    public function syncNotifications($userId, array $items, array $managedTypes = [])
    {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return false;
        }

        $normalizedItems = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $normalized = $this->normalizeGeneratedItem($item);
            if (!$normalized) {
                continue;
            }

            $normalizedItems[] = $normalized;
        }

        if (empty($normalizedItems) && empty($managedTypes)) {
            return false;
        }

        $sql = "INSERT INTO {$this->table}
                    (user_id, event_key, title, message, type, related_id, link, created_at, is_read, read_at)
                VALUES
                    (:user_id, :event_key, :title, :message, :type, :related_id, :link, :created_at, 0, NULL)
                ON DUPLICATE KEY UPDATE
                    title = VALUES(title),
                    message = VALUES(message),
                    type = VALUES(type),
                    related_id = VALUES(related_id),
                    link = VALUES(link)";

        foreach ($normalizedItems as $normalized) {
            $this->write($sql, [
                'user_id' => $userId,
                'event_key' => $normalized['event_key'],
                'title' => $normalized['title'],
                'message' => $normalized['message'],
                'type' => $normalized['type'],
                'related_id' => $normalized['related_id'],
                'link' => $normalized['link'],
                'created_at' => $normalized['created_at'],
            ]);
        }

        return true;
    }

    private function appendDebugFilters(string $sql)
    {
        return $sql . "
                AND event_key NOT LIKE 'mock\\_%'
                AND event_key NOT LIKE 'test\\_%'
                AND event_key NOT LIKE 'debug\\_%'
                AND LOWER(COALESCE(title, '')) NOT LIKE '%localhost%'
                AND LOWER(COALESCE(message, '')) NOT LIKE '%localhost%'";
    }

    private function buildTypeFilterSql(array $allowedTypes, &$params)
    {
        if (empty($allowedTypes)) {
            return '';
        }

        $placeholders = [];
        foreach (array_values($allowedTypes) as $index => $type) {
            $key = 'allowed_type_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $type;
        }

        return ' AND type IN (' . implode(', ', $placeholders) . ')';
    }

    private function formatRow($row)
    {
        $type = (string)($row->type ?? 'system');
        $category = $type;
        if (in_array($type, $this->systemAliases, true)) {
            $category = 'system';
        }

        return [
            'id' => (int)($row->id ?? 0),
            'event_key' => (string)($row->event_key ?? ''),
            'category' => $category,
            'icon' => $category,
            'title' => (string)($row->title ?? 'Notification'),
            'message' => (string)($row->message ?? ''),
            'is_read' => ((int)($row->is_read ?? 0)) === 1,
            'related_id' => isset($row->related_id) ? (int)$row->related_id : null,
            'link' => (string)($row->link ?? '#'),
            'created_at' => (string)($row->created_at ?? date('Y-m-d H:i:s')),
            'read_at' => (string)($row->read_at ?? ''),
        ];
    }

    public function listNotifications($userId, $filter = 'all', array $allowedTypes = [])
    {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return [];
        }

        $normalizedFilter = strtolower(trim((string)$filter));
        $params = ['user_id' => $userId];

        $sql = "SELECT id, event_key, title, message, type, is_read, related_id, link, created_at, read_at
                FROM {$this->table}
                WHERE user_id = :user_id";

        $sql = $this->appendDebugFilters($sql);

        $sql .= $this->buildTypeFilterSql($allowedTypes, $params);

        if ($normalizedFilter === 'unread') {
            $sql .= " AND is_read = 0";
        } elseif ($normalizedFilter === 'system') {
            // Treat admin "system-like" announcements as part of system filter.
            $placeholders = [];
            foreach ($this->systemAliases as $index => $alias) {
                $key = 'system_alias_' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $alias;
            }
            $sql .= " AND type IN (" . implode(', ', $placeholders) . ")";
        } elseif ($normalizedFilter !== '' && $normalizedFilter !== 'all') {
            $sql .= " AND type = :filter_type";
            $params['filter_type'] = $normalizedFilter;
        }

        $sql .= " ORDER BY id DESC";

        $rows = $this->query($sql, $params);
        if (!is_array($rows) || empty($rows)) {
            return [];
        }

        return array_map([$this, 'formatRow'], $rows);
    }

    public function getUnreadCount($userId, array $allowedTypes = [])
    {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return 0;
        }

        $params = ['user_id' => $userId];
        $sql = "SELECT COUNT(*) AS unread_count
                FROM {$this->table}
                WHERE user_id = :user_id
                AND is_read = 0";

        $sql = $this->appendDebugFilters($sql);

        $sql .= $this->buildTypeFilterSql($allowedTypes, $params);

        $row = $this->get_row($sql, $params);
        return (int)($row->unread_count ?? 0);
    }

    public function markAsRead($userId, $notificationId)
    {
        $userId = (int)$userId;
        $notificationId = (int)$notificationId;

        if ($userId <= 0 || $notificationId <= 0) {
            return false;
        }

        return $this->write(
            "UPDATE {$this->table}
             SET is_read = 1, read_at = NOW()
             WHERE id = :id AND user_id = :user_id",
            [
                'id' => $notificationId,
                'user_id' => $userId,
            ]
        ) !== false;
    }

    public function markAllAsRead($userId)
    {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return false;
        }

        return $this->write(
            "UPDATE {$this->table}
             SET is_read = 1, read_at = NOW()
             WHERE user_id = :user_id AND is_read = 0",
            ['user_id' => $userId]
        ) !== false;
    }

    public function deleteNotification($userId, $notificationId)
    {
        $userId = (int)$userId;
        $notificationId = (int)$notificationId;

        if ($userId <= 0 || $notificationId <= 0) {
            return false;
        }

        return $this->write(
            "DELETE FROM {$this->table} WHERE id = :id AND user_id = :user_id",
            [
                'id' => $notificationId,
                'user_id' => $userId,
            ]
        ) !== false;
    }

    public function createNotification($userId, array $payload)
    {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return false;
        }

        $normalized = $this->normalizeManualItem($payload);
        if (!$normalized) {
            return false;
        }

        $sql = "INSERT INTO {$this->table}
                    (user_id, event_key, title, message, type, related_id, link, created_at, is_read, read_at)
                VALUES
                    (:user_id, :event_key, :title, :message, :type, :related_id, :link, :created_at, 0, NULL)";

        return $this->write($sql, [
            'user_id' => $userId,
            'event_key' => $normalized['event_key'],
            'title' => $normalized['title'],
            'message' => $normalized['message'],
            'type' => $normalized['type'],
            'related_id' => $normalized['related_id'],
            'link' => $normalized['link'],
            'created_at' => $normalized['created_at'],
        ]) !== false;
    }

    /**
     * @param array<int, int|string> $userIds
     * @return array{sent:int,failed:int}
     */
    public function broadcast(array $userIds, array $payload): array
    {
        $normalized = $this->normalizeManualItem($payload);
        if (!$normalized) {
            return ['sent' => 0, 'failed' => count($userIds)];
        }

        $sent = 0;
        $failed = 0;

        $this->beginTransaction();
        foreach ($userIds as $rawUserId) {
            $userId = (int)$rawUserId;
            if ($userId <= 0) {
                $failed++;
                continue;
            }

            $ok = $this->write(
                "INSERT INTO {$this->table}
                    (user_id, event_key, title, message, type, related_id, link, created_at, is_read, read_at)
                 VALUES
                    (:user_id, :event_key, :title, :message, :type, :related_id, :link, :created_at, 0, NULL)",
                [
                    'user_id' => $userId,
                    'event_key' => $normalized['event_key'],
                    'title' => $normalized['title'],
                    'message' => $normalized['message'],
                    'type' => $normalized['type'],
                    'related_id' => $normalized['related_id'],
                    'link' => $normalized['link'],
                    'created_at' => $normalized['created_at'],
                ]
            );

            if ($ok === false) {
                $failed++;
            } else {
                $sent++;
            }
        }

        if ($this->inTransaction()) {
            $this->commit();
        }

        return ['sent' => $sent, 'failed' => $failed];
    }
}
