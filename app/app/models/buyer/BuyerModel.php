<?php

class BuyerModel
{
    use Database;

    protected $table = 'buyer_profiles';
    protected $userTable = 'users';
    private $hasAdditionalAddressField = null;

    private function supportsAdditionalAddressField()
    {
        if ($this->hasAdditionalAddressField !== null) {
            return $this->hasAdditionalAddressField;
        }

        $sql = "SHOW COLUMNS FROM {$this->table} LIKE 'additional_address_details'";
        $rows = $this->query($sql);
        $this->hasAdditionalAddressField = is_array($rows) && !empty($rows);
        return $this->hasAdditionalAddressField;
    }

    /**
     * Get buyer profile by user ID
     */
    public function getProfileByUserId($userId)
    {
        $sql = "SELECT bp.*, u.name, u.email, u.status, u.deactivated_at
                FROM {$this->table} bp
                LEFT JOIN {$this->userTable} u ON u.id = bp.user_id
                WHERE bp.user_id = :user_id";

        return $this->get_row($sql, ['user_id' => $userId]);
    }

    /**
     * Create buyer profile
     */
    public function createProfile($userId, $data)
    {
        $hasAdditionalAddress = $this->supportsAdditionalAddressField();
        $columns = ['user_id', 'phone', 'apartment_code', 'street_name', 'city', 'district', 'postal_code', 'profile_photo'];
        if ($hasAdditionalAddress) {
            $columns[] = 'additional_address_details';
        }

        $columnSql = implode(', ', $columns);
        $valueSql = ':' . implode(', :', $columns);
        $sql = "INSERT INTO {$this->table} ({$columnSql}, created_at, updated_at)
                VALUES ({$valueSql}, NOW(), NOW())";

        $params = [
            'user_id' => $userId,
            'phone' => $data['phone'] ?? null,
            'apartment_code' => $data['apartment_code'] ?? null,
            'street_name' => $data['street_name'] ?? null,
            'city' => $data['city'] ?? null,
            'district' => $data['district'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'profile_photo' => $data['profile_photo'] ?? null
        ];
        if ($hasAdditionalAddress) {
            $params['additional_address_details'] = $data['additional_address_details'] ?? null;
        }

        return $this->write($sql, $params);
    }

    /**
     * Update buyer profile
     */
    public function updateProfile($userId, $data)
    {
        $allowed = ['phone', 'apartment_code', 'street_name', 'city', 'district', 'postal_code', 'profile_photo'];
        if ($this->supportsAdditionalAddressField()) {
            $allowed[] = 'additional_address_details';
        }
        $set = [];
        $params = ['user_id' => $userId];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $set[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($set)) {
            return false;
        }

        // First check if profile exists
        $checkSql = "SELECT id FROM {$this->table} WHERE user_id = :user_id";
        $profileExists = $this->get_row($checkSql, ['user_id' => $userId]);

        if (!$profileExists) {
            // Profile doesn't exist, create it with the new data
            $data['user_id'] = $userId;
            return $this->createProfile($userId, $data);
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $set) . ", updated_at = NOW() WHERE user_id = :user_id";

        $result = $this->write($sql, $params);

        // write() returns:
        // - true on successful UPDATE/DELETE (when lastInsertId is 0)
        // - insert ID (int > 0) on successful INSERT
        // - false on failure
        // So for UPDATE: true/1 = success, false = failure
        return $result !== false ? true : false;
    }

    /**
     * Update profile photo only
     */
    public function updateProfilePhoto($userId, $filename)
    {
        $sql = "UPDATE {$this->table} SET profile_photo = :profile_photo, updated_at = NOW() WHERE user_id = :user_id";
        return $this->write($sql, ['user_id' => $userId, 'profile_photo' => $filename]);
    }

    /**
     * Remove profile photo (set to NULL)
     */
    public function removeProfilePhoto($userId)
    {
        $sql = "UPDATE {$this->table} SET profile_photo = NULL, updated_at = NOW() WHERE user_id = :user_id";
        return $this->write($sql, ['user_id' => $userId]);
    }

    /**
     * Get old profile photo filename for deletion
     */
    public function getOldPhotoFilename($userId)
    {
        $sql = "SELECT profile_photo FROM {$this->table} WHERE user_id = :user_id";
        $result = $this->get_row($sql, ['user_id' => $userId]);
        return $result ? $result->profile_photo : null;
    }

    /**
     * Check whether minimum delivery details are present.
     */
    public function hasDeliveryDetails($userId)
    {
        $profile = $this->getProfileByUserId($userId);
        if (!$profile) {
            return false;
        }

        return !empty($profile->phone)
            && !empty($profile->street_name)
            && !empty($profile->city)
            && !empty($profile->district);
    }

    /**
     * Validate profile data
     */
    public function validateProfile($data)
    {
        $errors = [];

        $name = trim((string)($data['name'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $phone = trim((string)($data['phone'] ?? ''));
        $district = trim((string)($data['district'] ?? ''));
        $apartmentCode = trim((string)($data['apartment_code'] ?? ''));
        $streetName = trim((string)($data['street_name'] ?? ''));
        $city = trim((string)($data['city'] ?? ''));
        $postalCode = trim((string)($data['postal_code'] ?? ''));
        $additionalAddress = trim((string)($data['additional_address_details'] ?? ''));

        if ($name === '') {
            $errors['name'] = 'Full name is required';
        } elseif (strlen($name) < 2) {
            $errors['name'] = 'Full name must be at least 2 characters';
        } elseif (strlen($name) > 100) {
            $errors['name'] = 'Full name is too long (max 100 characters)';
        } elseif (!preg_match('/^[a-zA-Z\s\-\.]+$/', $name)) {
            $errors['name'] = 'Full name can only contain letters, spaces, hyphens, and dots';
        }

        if ($email !== '') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Please enter a valid email address';
            } elseif (strlen($email) > 100) {
                $errors['email'] = 'Email is too long (max 100 characters)';
            }
        }

        if ($phone === '') {
            $errors['phone'] = 'Phone number is required';
        } else {
            $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
            if (!preg_match('/^(\+?94|0)[0-9]{9}$/', $cleanPhone)) {
                $errors['phone'] = 'Phone number must be a valid Sri Lankan number';
            }
        }

        $validDistricts = [
            'Ampara',
            'Anuradhapura',
            'Badulla',
            'Batticaloa',
            'Colombo',
            'Galle',
            'Gampaha',
            'Jaffna',
            'Kalutara',
            'Kandy',
            'Kegalle',
            'Kilinochchi',
            'Kurunegala',
            'Mannar',
            'Matale',
            'Matara',
            'Mullaitivu',
            'Nuwara Eliya',
            'Polonnaruwa',
            'Puttalam',
            'Ratnapura',
            'Trincomalee',
            'Vavuniya'
        ];

        if ($district === '') {
            $errors['district'] = 'District is required';
        } elseif (!in_array($district, $validDistricts, true)) {
            $errors['district'] = 'Please select a valid district';
        }

        if ($apartmentCode !== '') {
            if (strlen($apartmentCode) > 50) {
                $errors['apartment_code'] = 'Apartment code is too long (max 50 characters)';
            }
        }

        if ($streetName === '') {
            $errors['street_name'] = 'Street name is required';
        } elseif (strlen($streetName) > 100) {
            $errors['street_name'] = 'Street name is too long (max 100 characters)';
        }

        if ($city === '') {
            $errors['city'] = 'City is required';
        } elseif (strlen($city) > 50) {
            $errors['city'] = 'City is too long (max 50 characters)';
        }

        if ($postalCode !== '' && !preg_match('/^\d{5}$/', $postalCode)) {
            $errors['postal_code'] = 'Postal code must be 5 digits if provided';
        }

        if ($additionalAddress !== '' && strlen($additionalAddress) > 100) {
            $errors['additional_address_details'] = 'Additional address details are too long (max 100 characters)';
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Validate password change request
     */
    public function validatePasswordChange($userId, $currentPassword, $newPassword, $confirmPassword)
    {
        $errors = [];

        // Get current password from database
        $sql = "SELECT password FROM {$this->userTable} WHERE id = :id";
        $user = $this->get_row($sql, ['id' => $userId]);

        if (!$user) {
            $errors['current'] = 'User not found';
            return $errors;
        }

        // Verify current password
        if (!password_verify($currentPassword, $user->password)) {
            $errors['current'] = 'Current password is incorrect';
        }

        // Validate new password
        if (empty($newPassword)) {
            $errors['new'] = 'New password is required';
        } elseif (strlen($newPassword) < 8) {
            $errors['new'] = 'New password must be at least 8 characters long';
        } elseif (!preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            $errors['new'] = 'New password must include at least one letter and one number';
        }

        // Validate password confirmation
        if (empty($confirmPassword)) {
            $errors['confirm'] = 'Password confirmation is required';
        } elseif ($newPassword !== $confirmPassword) {
            $errors['confirm'] = 'Passwords do not match';
        }

        // Check if new password is same as old
        if (!empty($newPassword) && password_verify($newPassword, $user->password)) {
            $errors['new'] = 'New password must be different from current password';
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Change user password
     */
    public function changePassword($userId, $newPassword)
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $sql = "UPDATE {$this->userTable}
                SET password = :password,
                    password_updated_at = NOW(),
                    updated_at = NOW()
                WHERE id = :id";

        return $this->write($sql, [
            'id' => $userId,
            'password' => $hashedPassword
        ]);
    }
}
