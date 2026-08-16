<?php
class VerificationDocumentModel
{
    use Database;
    protected string $table = 'verification_documents';

    public function insert(array $data): int
    {
        // Validate required fields
        if (empty($data['user_id']) || empty($data['doc_type']) || empty($data['file_path'])) {
            error_log('VerificationDocumentModel: Missing required fields: ' . json_encode($data));
            return 0;
        }

        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = 'pending';
        }

        // Add timestamps
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $query = "
            INSERT INTO verification_documents (user_id, doc_type, file_path, status, created_at, updated_at)
            VALUES (:user_id, :doc_type, :file_path, :status, :created_at, :updated_at)
        ";

        try {
            $result = $this->write($query, $data);

            // Debug logging
            error_log("=== VerificationDocumentModel Insert Debug ===");
            error_log("Data: " . json_encode($data));
            error_log("Write result type: " . gettype($result));
            error_log("Write result value: " . var_export($result, true));

            // The write() method returns:
            // - For INSERT: the last insert ID (int) if successful
            // - For UPDATE/DELETE: rowCount (int)
            // - On failure: false (bool)

            if ($result !== false && is_numeric($result) && $result > 0) {
                error_log("✓ Document inserted successfully with ID: " . $result);
                return (int) $result;
            }

            error_log("✗ Failed to insert document. Result: " . var_export($result, true));
            return 0;
        } catch (Exception $e) {
            error_log('VerificationDocumentModel insert exception: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return 0;
        }
    }

    // Alternative insert method that returns boolean for debugging
    public function insertWithDebug(array $data): array
    {
        $result = [
            'success' => false,
            'id' => 0,
            'error' => null
        ];

        if (empty($data['user_id']) || empty($data['doc_type']) || empty($data['file_path'])) {
            $result['error'] = 'Missing required fields';
            error_log('VerificationDocumentModel: ' . $result['error'] . ': ' . json_encode($data));
            return $result;
        }

        if (!isset($data['status'])) {
            $data['status'] = 'pending';
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $query = "
            INSERT INTO verification_documents (user_id, doc_type, file_path, status, created_at, updated_at)
            VALUES (:user_id, :doc_type, :file_path, :status, :created_at, :updated_at)
        ";

        try {
            $writeResult = $this->write($query, $data);

            if ($writeResult !== false && is_numeric($writeResult) && $writeResult > 0) {
                $result['success'] = true;
                $result['id'] = (int) $writeResult;
            } else {
                $result['error'] = 'Write returned: ' . var_export($writeResult, true);
            }
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    public function getByUser(int $userId): array
    {
        $query = "SELECT * FROM verification_documents WHERE user_id = ? ORDER BY created_at DESC";
        $result = $this->query($query, [$userId]);
        return $result !== false ? $result : [];
    }

    public function getRejectedDocumentsByUser(int $userId): array
    {
        $query = "
            SELECT
                doc_type,
                rejection_reason,
                reviewed_at,
                updated_at
            FROM verification_documents
            WHERE user_id = ? AND status = 'rejected'
            ORDER BY COALESCE(reviewed_at, updated_at) DESC, id DESC
        ";

        $result = $this->query($query, [$userId]);
        return $result !== false ? $result : [];
    }

    public function updateStatus(int $id, string $status, ?string $reason = null, int $reviewedBy = 0): bool
    {
        $query = "
            UPDATE verification_documents
            SET status = :status, 
                rejection_reason = :reason,
                reviewed_by = :reviewed_by, 
                reviewed_at = NOW(),
                updated_at = NOW()
            WHERE id = :id
        ";

        $result = $this->write($query, [
            'status' => $status,
            'reason' => $reason,
            'reviewed_by' => $reviewedBy,
            'id' => $id
        ]);

        return $result !== false;
    }

    public function getUserVerificationDetails(int $userId): array
    {
        $query = "
            SELECT
                u.id as user_id,
                u.name,
                u.email,
                u.role,
                u.verification_status,
                u.created_at as registered_at,
                vd.id as doc_id,
                vd.doc_type,
                vd.file_path,
                vd.status as doc_status,
                vd.rejection_reason,
                vd.created_at as doc_uploaded_at
            FROM users u
            LEFT JOIN verification_documents vd ON u.id = vd.user_id
            WHERE u.id = :user_id AND u.role IN ('farmer', 'transporter')
            ORDER BY vd.created_at DESC
        ";

        $result = $this->query($query, ['user_id' => $userId]);
        return $result ? $result : [];
    }

    public function getVerificationsWithCounts(): array
    {
        $query = "
            SELECT 
                u.id as user_id,
                u.name,
                u.email,
                u.role,
                u.verification_status,
                u.created_at as registered_at,
                COUNT(vd.id) as doc_count,
                SUM(CASE WHEN vd.status = 'approved' THEN 1 ELSE 0 END) as approved_docs,
                SUM(CASE WHEN vd.status = 'rejected' THEN 1 ELSE 0 END) as rejected_docs,
                SUM(CASE WHEN vd.status = 'pending' THEN 1 ELSE 0 END) as pending_docs,
                GROUP_CONCAT(DISTINCT vd.doc_type) as document_types,
                GROUP_CONCAT(DISTINCT vd.status) as document_statuses
            FROM users u
            LEFT JOIN verification_documents vd ON u.id = vd.user_id
            WHERE u.role IN ('farmer', 'transporter')
            GROUP BY u.id
            ORDER BY FIELD(u.verification_status, 'pending', 'approved', 'rejected'), u.created_at DESC
        ";

        $result = $this->query($query);
        return $result ? $result : [];
    }

    public function getAllVerificationsWithDocuments(): array
    {
        $query = "
            SELECT 
                u.id as user_id,
                u.name,
                u.email,
                u.role,
                u.verification_status,
                u.created_at as registered_at,
                vd.id as doc_id,
                vd.doc_type,
                vd.file_path,
                vd.status as doc_status,
                vd.rejection_reason,
                vd.created_at as doc_uploaded_at
            FROM users u
            LEFT JOIN verification_documents vd ON u.id = vd.user_id
            WHERE u.role IN ('farmer', 'transporter')
            AND u.verification_status != 'not_required'
            ORDER BY u.id, vd.created_at DESC
        ";

        $result = $this->query($query);

        $usersWithDocs = [];
        if ($result) {
            foreach ($result as $row) {
                $userId = $row->user_id;
                if (!isset($usersWithDocs[$userId])) {
                    $usersWithDocs[$userId] = [
                        'user_id' => $row->user_id,
                        'name' => $row->name,
                        'email' => $row->email,
                        'role' => $row->role,
                        'verification_status' => $row->verification_status,
                        'registered_at' => $row->registered_at,
                        'documents' => []
                    ];
                }

                if ($row->doc_id) {
                    $usersWithDocs[$userId]['documents'][] = [
                        'doc_id' => $row->doc_id,
                        'doc_type' => $row->doc_type,
                        'file_path' => $row->file_path,
                        'status' => $row->doc_status,
                        'rejection_reason' => $row->rejection_reason,
                        'uploaded_at' => $row->doc_uploaded_at
                    ];
                }
            }
        }

        return array_values($usersWithDocs);
    }

    public function getPendingVerificationsCount(): int
    {
        $query = "
            SELECT COUNT(*) as total
            FROM users u
            WHERE u.role IN ('farmer', 'transporter') 
            AND u.verification_status = 'pending'
        ";

        $result = $this->query($query);
        return $result ? (int)($result[0]->total ?? 0) : 0;
    }

    /**
     * Debug method to check if table exists and has correct structure
     */
    public function checkTableStructure(): array
    {
        $result = [
            'table_exists' => false,
            'columns' => [],
            'error' => null
        ];

        try {
            // Check if table exists
            $checkTable = $this->query("SHOW TABLES LIKE 'verification_documents'");
            if (!$checkTable || empty($checkTable)) {
                $result['error'] = 'Table verification_documents does not exist';
                return $result;
            }

            $result['table_exists'] = true;

            // Get table structure
            $columns = $this->query("DESCRIBE verification_documents");
            if ($columns) {
                foreach ($columns as $col) {
                    $result['columns'][$col->Field] = [
                        'type' => $col->Type,
                        'null' => $col->Null,
                        'key' => $col->Key,
                        'default' => $col->Default
                    ];
                }
            }
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }
}
