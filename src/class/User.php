<?php

class User

{
    private $pdo;
    //private $db;

    // Initialize database connection
    public function __construct($db)
    {
        // $database = new Database();
        $this->pdo = $db;
    }

    // // Assign role to a user (NEW METHOD)
    // public function assignRole($user_id, $role) {
    //     try {
    //         $stmt = $this->pdo->prepare("UPDATE users SET role_id = :role_id WHERE user_id = :user_id");
    //         $stmt->execute([
    //             ':role' => $role,
    //             ':user_id' => $user_id
    //         ]);

    //         if ($stmt->rowCount() > 0) {
    //             return ['success' => true, 'message' => 'Role assigned successfully.'];
    //         } else {
    //             return ['success' => false, 'message' => 'Failed to assign role.'];
    //         }
    //     } catch (PDOException $e) {
    //         error_log("Error assigning role: " . $e->getMessage());
    //         return ['success' => false, 'message' => 'An error occurred while assigning the role.'];
    //     }
    // }

    // Function to get user profile by user_id
    public function getUserProfile($user_id)
    {
        $query = "SELECT user_id, CONCAT(first_name, ' ', last_name) as name, email, role_id FROM users WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return [
                //'user_id' => $result['user_id'],
                'name' => $result['name'],
                'email' => $result['email'],
            ];
        }
        return null; // User not found
    }
    

    // Function to assign a role to a user
    public function assignRole($hashed_user_id, $role_id)
    {
        // Define role ID mappings
        $valid_roles = [1, 2, 3]; // buyer = 1, seller = 2, admin = 3

        // Validate role ID
        if (!in_array($role_id, $valid_roles)) {
            return ['success' => false, 'message' => 'Invalid role specified'];
        }

        try {
            // Fetch current role of the user
            $query = "SELECT role_id FROM users WHERE user_id = :user_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':user_id', $hashed_user_id, PDO::PARAM_STR);
            $stmt->execute();
            $current_role_id = $stmt->fetchColumn();

            // Check if role is already assigned
            if ($current_role_id == $role_id) {
                return ['success' => false, 'message' => 'Role already assigned to the user'];
            }

            // Only allow role elevation from buyer (1) to seller (2) or admin (3)
            if ($current_role_id == 1 && $role_id == 3) {
                return ['success' => false, 'message' => 'Unauthorized role elevation to admin'];
            }

            // Update the user's role
            $update_query = "UPDATE users SET role_id = :role_id WHERE user_id = :user_id";
            $update_stmt = $this->pdo->prepare($update_query);
            $update_stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
            $update_stmt->bindParam(':user_id', $hashed_user_id, PDO::PARAM_STR);

            if ($update_stmt->execute()) {
                return ['success' => true, 'message' => 'Role assigned successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to assign role'];
            }
        } catch (PDOException $e) {
            error_log("Error assigning role: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while assigning the role'];
        }
    }



    public function listUserRoles()
    {
        try {
            // Prepare the query to fetch all users and their roles
            $query = "SELECT u.user_id, u.username, r.role_name 
                      FROM users u 
                      LEFT JOIN roles r ON u.role_id = r.role_id";
            $stmt = $this->pdo->prepare($query);

            // Execute the query
            $stmt->execute();

            // Fetch all user-role pairs
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($users) {
                // If users are found, return them with success status
                return ['success' => true, 'data' => $users];
            } else {
                // No users found
                return ['success' => false, 'message' => 'No users or roles found.'];
            }
        } catch (PDOException $e) {
            // Log the detailed error
            error_log("Error fetching all user roles: " . $e->getMessage());

            // Return a detailed error message for debugging purposes
            return [
                'success' => false,
                'message' => 'An error occurred while fetching user roles.',
                'error' => $e->getMessage() // Include error message for better debugging
            ];
        }
    }


    /*public function getUserProfile($userId) {
        try {
            // Prepare the query to fetch the user's profile based on the user ID
            $query = "SELECT first_name, last_name FROM users WHERE user_id = :userId";
            $stmt = $this->pdo->prepare($query);
    
            // Bind the user ID parameter
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    
            // Execute the query
            $stmt->execute();
    
            // Fetch the user profile
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($user) {
                // If user is found, return their profile with success status
                return [
                    'success' => true,
                    'data' => [
                        'id' => $userId,
                        'first_name' => $user['first_name'],
                        'last_name' => $user['last_name']
                    ]
                ];
            } else {
                // No user found
                return [
                    'success' => false,
                    'message' => 'User not found.'
                ];
            }
        } catch (PDOException $e) {
            // Log the detailed error
            error_log("Error fetching user profile: " . $e->getMessage());
    
            // Return a detailed error message for debugging purposes
            return [
                'success' => false,
                'message' => 'An error occurred while fetching the user profile.',
                'error' => $e->getMessage() // Include error message for better debugging
            ];
        }
    }*/
}
