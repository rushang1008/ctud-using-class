<?php
class User
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create($data, $photo)
    {
        $photoName = $this->uploadPhoto($photo);
        $password = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $this->conn->prepare("INSERT INTO users (name, phone, email, password, age, address, gender, salary, profile_photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "ssssissds",
            $data['name'],
            $data['phone'],
            $data['email'],
            $password,
            $data['age'],
            $data['address'],
            $data['gender'],
            $data['salary'],
            $photoName
        );
        return $stmt->execute();
    }

    public function readAll()
    {
        $result = $this->conn->query("SELECT * FROM users");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function get($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function update($id, $data, $photo = null)
    {
        $old = $this->get($id);

        // If a new photo is uploaded
        if ($photo && isset($photo['name']) && $photo['name']) {
            $photoName = $this->uploadPhoto($photo, $old['profile_photo']);
        } else {
            // Fallback to existing photo sent in hidden input or old photo
            $photoName = $data['existing_photo'] ?? $old['profile_photo'];
        }

        $stmt = $this->conn->prepare("UPDATE users SET name=?, phone=?, email=?, age=?, address=?, gender=?, salary=?, profile_photo=? WHERE id=?");
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


    public function delete($id)
    {
        $user = $this->get($id);
        if ($user['profile_photo'] && file_exists("uploads/" . $user['profile_photo'])) {
            unlink("uploads/" . $user['profile_photo']);
        }

        $stmt = $this->conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    private function uploadPhoto($photo, $oldPhoto = null)
    {
        $uploadDir = "uploads/";
        if (!file_exists($uploadDir))
            mkdir($uploadDir);

        $fileName = uniqid() . "_" . basename($photo['name']);
        $targetPath = $uploadDir . $fileName;

        move_uploaded_file($photo['tmp_name'], $targetPath);

        if ($oldPhoto && file_exists($uploadDir . $oldPhoto)) {
            unlink($uploadDir . $oldPhoto);
        }

        return $fileName;
    }
    public function emailExists($email, $excludeId = null)
    {
        $sql = "SELECT id FROM users WHERE email = ?";
        if ($excludeId)
            $sql .= " AND id != ?";
        $stmt = $this->conn->prepare($sql);
        if ($excludeId) {
            $stmt->bind_param("si", $email, $excludeId);
        } else {
            $stmt->bind_param("s", $email);
        }
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function phoneExists($phone, $excludeId = null)
    {
        $sql = "SELECT id FROM users WHERE phone = ?";
        if ($excludeId)
            $sql .= " AND id != ?";
        $stmt = $this->conn->prepare($sql);
        if ($excludeId) {
            $stmt->bind_param("si", $phone, $excludeId);
        } else {
            $stmt->bind_param("s", $phone);
        }
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

}
?>