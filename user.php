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
        return ['status' => 'success', 'message' => 'Logged out'];
    }

    public function createuser($data, $file = null)
    {
        if ($this->emailExists($data['email'])) {
            return ['status' => 'error', 'field' => 'email', 'message' => 'Email already exists'];
        }

        if ($this->phoneExists($data['phone'])) {
            return ['status' => 'error', 'field' => 'phone', 'message' => 'Phone already exists'];
        }

        $id = $this->create($data, $file);
        if (!$id) {
            return ['status' => 'error', 'message' => 'User creation failed'];
        }

        return ['status' => 'success', 'user' => $this->get($id)];
    }

    public function create($data, $file = null)
    {
        $profilePhoto = '';
    
        if ($file && $file['name']) {
            $uploaded = uploadPhoto($file);
            if (!$uploaded) return false;
            $profilePhoto = $uploaded;
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
    
        if ($stmt->execute()) {
            return $this->conn->insert_id; // ✅ Return inserted ID only
        } else {
            return false;
        }
    }
    

    public function updateUser($id, $data, $file = null)
    {
        if (!$id) return ['status' => 'error', 'message' => 'User ID is required'];
    
        if ($this->emailExists($data['email'], $id)) {
            return ['status' => 'error', 'field' => 'email', 'message' => 'Email already exists'];
        }
    
        if ($this->phoneExists($data['phone'], $id)) {
            return ['status' => 'error', 'field' => 'phone', 'message' => 'Phone already exists'];
        }
    
        // Get existing user
        $existingUser = $this->get($id);
        if (!$existingUser) {
            return ['status' => 'error', 'message' => 'User not found'];
        }
    
        $photoName = $existingUser['profile_photo']; // Default to existing photo
    
        // Handle new photo upload if provided
        if ($file && isset($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
            $uploaded = uploadPhoto($file, $photoName);
            if ($uploaded) {
                $photoName = $uploaded;
            }
        }
    
        $success = $this->update($id, $data, $photoName);
        if (!$success) {
            return ['status' => 'error', 'message' => 'User update failed'];
        }
    
        return ['status' => 'success', 'user' => $this->get($id), 'newPhoto' => $photoName];
    }
    

    public function update($id, $data, $photo = null)
    {
        $stmt = $this->conn->prepare("
            UPDATE users SET 
                name = ?, 
                email = ?, 
                phone = ?, 
                age = ?, 
                address = ?, 
                gender = ?, 
                salary = ?, 
                profile_photo = ?
            WHERE id = ?
        ");
    
        $stmt->bind_param(
            "ssssssdsi", 
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['age'],
            $data['address'],
            $data['gender'],
            $data['salary'],
            $photo,
            $id
        );
    
        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }
    
    public function deleteUser($id)
    {
        $user = $this->get($id);
        if ($user && $user['profile_photo']) {
            $photoPath = "uploads/" . $user['profile_photo'];
            if (file_exists($photoPath)) unlink($photoPath);
        }

        $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute() ? ['status' => 'success'] : ['status' => 'error', 'message' => 'Delete failed'];
    }

    public function getUserById($id)
    {
        if (!$id) return ['status' => 'error', 'message' => 'User ID required'];
        $user = $this->get($id);
        return $user ?: ['status' => 'error', 'message' => 'User not found'];
    }

    public function getAllUsers()
    {
        return ['status' => 'success', 'data' => $this->readAll()];
    }

    public function get($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    
        $result = $stmt->get_result();
        $user = $result->fetch_assoc(); 
    
        return $user;
    }
    
    public function readAll()
    {
        $stmt = $this->conn->prepare("SELECT * FROM users");
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function emailExists($email, $excludeId = null)
    {
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $excludeId
            ? $this->conn->prepare($sql . " AND id != ?")
            : $this->conn->prepare($sql);
        $excludeId
            ? $stmt->bind_param("si", $email, $excludeId)
            : $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function phoneExists($phone, $excludeId = null)
    {
        $sql = "SELECT id FROM users WHERE phone = ?";
        $stmt = $excludeId
            ? $this->conn->prepare($sql . " AND id != ?")
            : $this->conn->prepare($sql);
        $excludeId
            ? $stmt->bind_param("si", $phone, $excludeId)
            : $stmt->bind_param("s", $phone);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
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
    public function getByPhone($phone)
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE phone = ?");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }   

}