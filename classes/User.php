<?php
class User {
    private $pdo;
    private $id;
    private $username;
    private $email;
    private $firstName;
    private $lastName;
    private $role;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Register a new user
    public function register($username, $email, $password, $firstName, $lastName, $role) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$username, $email, $hashedPassword, $firstName, $lastName, $role]);
    }

    // Login user
    public function login($username, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $this->id = $user['id'];
            $this->username = $user['username'];
            $this->email = $user['email'];
            $this->firstName = $user['first_name'];
            $this->lastName = $user['last_name'];
            $this->role = $user['role'];

            $_SESSION['user_id'] = $this->id;
            $_SESSION['user_role'] = $this->role;
            return true;
        }
        return false;
    }

    // Logout user
    public function logout() {
        session_destroy();
        session_unset();
    }

    // Get user by ID
    public function getUserById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update user profile
    public function updateProfile($id, $data) {
        $stmt = $this->pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
        return $stmt->execute([$data['first_name'], $data['last_name'], $data['email'], $id]);
    }

    // Check if username exists
    public function usernameExists($username) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetchColumn() > 0;
    }

    // Check if email exists
    public function emailExists($email) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetchColumn() > 0;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getUsername() { return $this->username; }
    public function getEmail() { return $this->email; }
    public function getFirstName() { return $this->firstName; }
    public function getLastName() { return $this->lastName; }
    public function getRole() { return $this->role; }
}
