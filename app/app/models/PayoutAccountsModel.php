<?php

class PayoutAccountsModel
{
    use Database;

    protected $table = 'payout_accounts';

    public function validatePayoutData(array $payload)
    {
        $errors = [];

        $accountHolderName = trim((string)($payload['account_holder_name'] ?? ''));
        $bankName = trim((string)($payload['bank_name'] ?? ''));
        $branchName = trim((string)($payload['branch_name'] ?? ''));
        $accountNumber = preg_replace('/\s+/', '', (string)($payload['account_number'] ?? ''));

        if ($accountHolderName === '') {
            $errors['account_holder_name'] = 'Account holder name is required';
        } elseif (!preg_match('/^[A-Za-z][A-Za-z\s.\'\-]{1,119}$/', $accountHolderName)) {
            $errors['account_holder_name'] = 'Enter a valid account holder name';
        }

        if ($bankName === '') {
            $errors['bank_name'] = 'Bank name is required';
        } elseif (!preg_match('/^[A-Za-z0-9][A-Za-z0-9\s&.\'\-]{1,119}$/', $bankName)) {
            $errors['bank_name'] = 'Enter a valid bank name';
        }

        if ($branchName !== '' && !preg_match('/^[A-Za-z0-9][A-Za-z0-9\s,().\'\-]{1,119}$/', $branchName)) {
            $errors['branch_name'] = 'Enter a valid branch name';
        }

        if ($accountNumber === '') {
            $errors['account_number'] = 'Account number is required';
        } elseif (!preg_match('/^[0-9]{8,30}$/', $accountNumber)) {
            $errors['account_number'] = 'Account number must be 8 to 30 digits';
        }

        if (!empty($errors)) {
            return $errors;
        }

        return [
            'account_holder_name' => $accountHolderName,
            'bank_name' => $bankName,
            'branch_name' => $branchName !== '' ? $branchName : null,
            'account_number' => $accountNumber,
            'is_default' => !empty($payload['is_default']) ? 1 : 0,
        ];
    }

    private function mapAccount($row)
    {
        if (!$row) {
            return null;
        }

        return [
            'id' => (int)($row->id ?? 0),
            'account_holder_name' => (string)($row->account_holder_name ?? ''),
            'bank_name' => (string)($row->bank_name ?? ''),
            'branch_name' => (string)($row->branch_name ?? ''),
            'account_number' => (string)($row->account_number ?? ''),
            'is_default' => ((int)($row->is_default ?? 0)) === 1,
            'is_verified' => ((int)($row->is_verified ?? 0)) === 1,
            'created_at' => (string)($row->created_at ?? ''),
            'updated_at' => (string)($row->updated_at ?? ''),
        ];
    }

    public function getDefaultAccountByUserId($userId)
    {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return null;
        }

        $row = $this->get_row(
            "SELECT * FROM {$this->table} WHERE user_id = :user_id ORDER BY is_default DESC, updated_at DESC, id DESC LIMIT 1",
            ['user_id' => $userId]
        );

        return $this->mapAccount($row);
    }

    public function listAccountsByUserId($userId)
    {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return [];
        }

        $rows = $this->query(
            "SELECT * FROM {$this->table} WHERE user_id = :user_id ORDER BY is_default DESC, updated_at DESC, id DESC",
            ['user_id' => $userId]
        );

        if (!is_array($rows) || empty($rows)) {
            return [];
        }

        return array_map([$this, 'mapAccount'], $rows);
    }

    public function saveDefaultAccount($userId, array $payload)
    {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return ['success' => false, 'error' => 'Invalid user'];
        }

        $normalized = $this->validatePayoutData($payload);
        $requiredKeys = ['account_holder_name', 'bank_name', 'branch_name', 'account_number', 'is_default'];
        $hasNormalizedShape = is_array($normalized);
        if ($hasNormalizedShape) {
            foreach ($requiredKeys as $requiredKey) {
                if (!array_key_exists($requiredKey, $normalized)) {
                    $hasNormalizedShape = false;
                    break;
                }
            }
        }

        if (!$hasNormalizedShape) {
            return ['success' => false, 'errors' => $normalized];
        }

        $con = $this->connect();
        try {
            $con->beginTransaction();

            $existingStmt = $con->prepare(
                "SELECT id FROM {$this->table}
                 WHERE user_id = :user_id AND is_default = 1
                 LIMIT 1"
            );
            $existingStmt->execute(['user_id' => $userId]);
            $existing = $existingStmt->fetch(PDO::FETCH_OBJ);

            if ($existing && !empty($existing->id)) {
                $updateStmt = $con->prepare(
                    "UPDATE {$this->table}
                     SET account_holder_name = :account_holder_name,
                         bank_name = :bank_name,
                         branch_name = :branch_name,
                         account_number = :account_number,
                         is_default = 1,
                         updated_at = NOW()
                     WHERE id = :id AND user_id = :user_id"
                );
                $updateStmt->execute([
                    'id' => (int)$existing->id,
                    'user_id' => $userId,
                    'account_holder_name' => $normalized['account_holder_name'],
                    'bank_name' => $normalized['bank_name'],
                    'branch_name' => $normalized['branch_name'],
                    'account_number' => $normalized['account_number'],
                ]);
            } else {
                $clearStmt = $con->prepare("UPDATE {$this->table} SET is_default = 0 WHERE user_id = :user_id");
                $clearStmt->execute(['user_id' => $userId]);

                $insertStmt = $con->prepare(
                    "INSERT INTO {$this->table}
                        (user_id, account_holder_name, bank_name, branch_name, account_number, is_default, is_verified, created_at, updated_at)
                     VALUES
                        (:user_id, :account_holder_name, :bank_name, :branch_name, :account_number, 1, 0, NOW(), NOW())"
                );
                $insertStmt->execute([
                    'user_id' => $userId,
                    'account_holder_name' => $normalized['account_holder_name'],
                    'bank_name' => $normalized['bank_name'],
                    'branch_name' => $normalized['branch_name'],
                    'account_number' => $normalized['account_number'],
                ]);
            }

            $con->commit();
        } catch (Throwable $e) {
            if ($con->inTransaction()) {
                $con->rollBack();
            }
            error_log('PayoutAccountsModel::saveDefaultAccount error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to save payout account'];
        }

        return ['success' => true, 'account' => $this->getDefaultAccountByUserId($userId)];
    }

    public function removeAccount($userId, $accountId)
    {
        $userId = (int)$userId;
        $accountId = (int)$accountId;

        if ($userId <= 0 || $accountId <= 0) {
            return false;
        }

        return $this->write(
            "DELETE FROM {$this->table} WHERE id = :id AND user_id = :user_id",
            [
                'id' => $accountId,
                'user_id' => $userId,
            ]
        ) !== false;
    }
}
