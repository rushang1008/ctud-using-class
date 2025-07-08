<?php
class User
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create($data, $file = null)
    {
        $profilePhoto = '';

        if ($file && $file['name']) {
            $uploaded = $this->uploadPhoto($file);
            if ($uploaded === false) return false;
            $profilePhoto = $uploaded;
        } elseif (!empty($data['profile_photo'])) {
            $profilePhoto = $data['profile_photo'];
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO users (name, email, phone, age, salary, address, gender, profile_photo) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "sssidsss",
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['age'],
            $data['salary'],
            $data['address'],
            $data['gender'],
            $profilePhoto
        );

        return $stmt->execute() ? $this->conn->insert_id : false;
    }

    public function update($id, $data, $photo = null)
    {
        $old = $this->get($id);

        if ($photo && isset($photo['name']) && $photo['name']) {
            $photoName = $this->uploadPhoto($photo, $old['profile_photo']);
        } else {
            $photoName = $data['existing_photo'] ?? $old['profile_photo'];
        }

        $stmt = $this->conn->prepare(
            "UPDATE users 
             SET name = ?, phone = ?, email = ?, age = ?, address = ?, gender = ?, salary = ?, profile_photo = ? 
             WHERE id = ?"
        );
        $stmt->bind_param(
            "sssissdsi",
            $data['name'],
            $data['phone'],
            $data['email'],
            $data['age'],
            $data['address'],
            $data['gender'],
            $data['salary'],
            $photoName,
            $id
        );

        return $stmt->execute() ? $photoName : false;
    }

    public function readAll()
    {
        $result = $this->conn->query("SELECT * FROM users");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function get($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function delete($id)
    {
        $user = $this->get($id);
        if ($user['profile_photo'] && file_exists("uploads/" . $user['profile_photo'])) {
            unlink("uploads/" . $user['profile_photo']);
        }

        $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function uploadPhoto($photo, $oldPhoto = null)
    {
        $uploadDir = "uploads/";
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $mimeType = mime_content_type($photo['tmp_name']);

        if (!in_array($mimeType, $allowedTypes)) return false;
        if ($photo['size'] > 2 * 1024 * 1024) return false;

        $fileName = uniqid() . "_" . basename($photo['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($photo['tmp_name'], $targetPath)) {
            if ($oldPhoto && file_exists($uploadDir . $oldPhoto)) {
                unlink($uploadDir . $oldPhoto);
            }
            return $fileName;
        }

        return false;
    }

    public function emailExists($email, $excludeId = null)
    {
        $sql = "SELECT id FROM users WHERE email = ?";
        if ($excludeId) $sql .= " AND id != ?";
        $stmt = $this->conn->prepare($sql);
        $excludeId ? $stmt->bind_param("si", $email, $excludeId) : $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function phoneExists($phone, $excludeId = null)
    {
        $sql = "SELECT id FROM users WHERE phone = ?";
        if ($excludeId) $sql .= " AND id != ?";
        $stmt = $this->conn->prepare($sql);
        $excludeId ? $stmt->bind_param("si", $phone, $excludeId) : $stmt->bind_param("s", $phone);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
    public function getByPhone($phone)
{
    $stmt = $this->conn->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

    // ✅ For admin login
    public function getByEmail($email)
    {
        $stmt = $this->conn->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // ✅ NEW: for user check/update by email (for Excel)
    public function getUserByEmail($email)
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
?>
