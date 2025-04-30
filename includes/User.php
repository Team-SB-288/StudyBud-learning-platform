<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    private $table = 'users';

    public $id;
    public $name;
    public $username;
    public $email;
    public $gender;
    public $password;
    public $profile_picture;
    public $bio;
    public $created_at;
    public $updated_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create() {
        try {
            $query = "INSERT INTO " . $this->table . "
                    (name, username, email, gender, password, profile_picture, bio, created_at, updated_at)
                    VALUES
                    (:name, :username, :email, :gender, :password, :profile_picture, :bio, NOW(), NOW())";

            $stmt = $this->conn->prepare($query);

            // Sanitize and hash password
            $this->name = htmlspecialchars(strip_tags($this->name));
            $this->username = htmlspecialchars(strip_tags($this->username));
            $this->email = htmlspecialchars(strip_tags($this->email));
            $this->bio = htmlspecialchars(strip_tags($this->bio));
            $this->gender = htmlspecialchars(strip_tags($this->gender));
            $this->password = password_hash($this->password, PASSWORD_BCRYPT);

            // Set default profile picture based on gender
            if (empty($this->profile_picture)) {
                $this->profile_picture = 'assets/images/' . ($this->gender === 'female' ? 'female.png' : 'male.png');
            }

            // Bind parameters
            $stmt->bindParam(":name", $this->name);
            $stmt->bindParam(":username", $this->username);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":gender", $this->gender);
            $stmt->bindParam(":password", $this->password);
            $stmt->bindParam(":profile_picture", $this->profile_picture);
            $stmt->bindParam(":bio", $this->bio);

            return $stmt->execute();
        } catch (PDOException $e) {
            if ($e->getCode() == '23000' && strpos($e->getMessage(), 'Duplicate entry') !== false) {
                if (strpos($e->getMessage(), 'email') !== false) {
                    throw new Exception("This email address is already registered.");
                }
                if (strpos($e->getMessage(), 'username') !== false) {
                    throw new Exception("This username is already taken.");
                }
            }
            throw $e;
        }
    }

    public function login($login, $password) {
        $query = "SELECT id, name, username, email, gender, password, profile_picture, bio 
                FROM " . $this->table . " 
                WHERE email = :login OR username = :login";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":login", $login);
        $stmt->execute();

        if($row = $stmt->fetch()) {
            if(password_verify($password, $row['password'])) {
                // Start session and set user data
                session_start();
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['name'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['user_email'] = $row['email'];
                $_SESSION['gender'] = $row['gender'];
                $_SESSION['profile_picture'] = $row['profile_picture'];
                return true;
            }
        }
        return false;
    }

    public function getUserById($id) {
        $query = "SELECT id, name, username, email, gender, profile_picture, bio, created_at 
                FROM " . $this->table . " 
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function updateProfile() {
        $query = "UPDATE " . $this->table . "
                SET name = :name,
                    username = :username,
                    bio = :bio,
                    gender = :gender,
                    profile_picture = COALESCE(:profile_picture, profile_picture),
                    updated_at = NOW()
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->bio = htmlspecialchars(strip_tags($this->bio));
        $this->gender = htmlspecialchars(strip_tags($this->gender));

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":bio", $this->bio);
        $stmt->bindParam(":gender", $this->gender);
        $stmt->bindParam(":profile_picture", $this->profile_picture);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            // Update session with new profile data
            $_SESSION['user_name'] = $this->name;
            $_SESSION['username'] = $this->username;
            $_SESSION['gender'] = $this->gender;
            if($this->profile_picture) {
                $_SESSION['profile_picture'] = $this->profile_picture;
            }
            return true;
        }
        return false;
    }

    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public static function logout() {
        session_start();
        session_destroy();
        return true;
    }
}
?>