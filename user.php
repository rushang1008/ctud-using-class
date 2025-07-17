<?php
require_once 'upload_helper.php';

class User
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function login($email, $password)
    {
        $admin = $this->getByEmail($email);
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['name'] = $admin['name'];
            return ['status' => 'success', 'name' => $admin['name']];
        }
        return ['status' => 'error', 'message' => 'Invalid email or password'];
    }

    public function logout()
    {
        session_destroy();
        return ['status' => 'logged_out'];
    }

    public function createUser($data, $file = null)
    {
        if ($this->emailExists($data['email'])) {
            return ['status' => 'error', 'field' => 'email', 'message' => 'Email already exists'];
        }

        if ($this->phoneExists($data['phone'])) {
            return ['status' => 'error', 'field' => 'phone', 'message' => 'Phone already exists'];
        }

        $newId = $this->create($data, $file);
        if ($newId) {
            $createdUser = $this->get($newId);
            return ['status' => 'success', 'user' => $createdUser];
        }

        return ['status' => 'error', 'message' => 'User creation failed'];
    }

    public function updateUser($data, $file = null)
    {
        $id = $data['id'] ?? null;
        if (!$id) return ['status' => 'error', 'message' => 'User ID is required'];

        if ($this->emailExists($data['email'], $id)) {
            return ['status' => 'error', 'field' => 'email', 'message' => 'Email already exists'];
        }

        if ($this->phoneExists($data['phone'], $id)) {
            return ['status' => 'error', 'field' => 'phone', 'message' => 'Phone already exists'];
        }

        $newPhoto = $this->update($id, $data, $file);
        if ($newPhoto !== false) {
            $updatedUser = $this->get($id);
            return ['status' => 'success', 'user' => $updatedUser, 'newPhoto' => $newPhoto];
        }

        return ['status' => 'error', 'message' => 'User update failed'];
    }

    public function deleteUser($id)
    {
        if ($this->delete($id)) {
            return ['status' => 'success'];
        }
        return ['status' => 'error', 'message' => 'Delete failed'];
    }

    public function getUserById($id)
    {
        if (!$id) {
            return ['status' => 'error', 'message' => 'ID is required'];
        }

        $user = $this->get($id);
        return $user ?: ['status' => 'error', 'message' => 'User not found'];
    }

    public function getAllUsers()
    {
        return ['status' => 'success', 'data' => $this->readAll()];
    }

    public function create($data, $file = null)
    {
        $profilePhoto = '';

        if ($file && $file['name']) {
            $uploaded = uploadPhoto($file);
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
            $photoName = uploadPhoto($photo, $old['profile_photo']);
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
        $stmt = $this->conn->prepare("SELECT * FROM users ORDER BY id DESC");
        $stmt->execute();
        $result = $stmt->get_result();
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

    public function getByEmail($email)
    {
        $stmt = $this->conn->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getUserByEmail($email)
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
} 