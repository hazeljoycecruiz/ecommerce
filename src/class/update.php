<?php

class Update
{
    private $db;
    private $userId;
    private $data;
    private $token;

    public function __construct($db, $userId, $data, $token)
    {
        $this->db = $db;
        $this->userId = $userId;
        $this->data = $data;
        $this->token = $token;
    }

    public function updateUser()
    {
        try {
            // Check if the token is invalidated by verifying it in session_tokens table
            $invalidatedQuery = "SELECT * FROM session_tokens WHERE token = :token AND user_id = :user_id";
            $invalidatedStmt = $this->db->prepare($invalidatedQuery);
            $invalidatedStmt->bindParam(':token', $this->token);
            $invalidatedStmt->bindParam(':user_id', $this->userId); // Ensure the user ID matches
            $invalidatedStmt->execute();

            // Check if the token is found in the table
            if ($invalidatedStmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Token is invalidated. You cannot update the profile.'];
            }

            // Validate that at least one field is provided for the update
            if (empty($this->data->first_name) && empty($this->data->last_name) && empty($this->data->phone_num) && empty($this->data->address)) {
                return ['success' => false, 'message' => 'No data provided for update'];
            }

            // Prepare the SQL update query dynamically
            $fields = [];
            $params = [];

            if (!empty($this->data->first_name)) {
                $fields[] = "first_name = :first_name";
                $params[':first_name'] = $this->data->first_name;
            }

            if (!empty($this->data->last_name)) {
                $fields[] = "last_name = :last_name";
                $params[':last_name'] = $this->data->last_name;
            }

            if (!empty($this->data->phone_num)) {
                $fields[] = "phone_num = :phone_num";
                $params[':phone_num'] = $this->data->phone_num;
            }

            if (!empty($this->data->address)) {
                $fields[] = "address = :address";
                $params[':address'] = $this->data->address;
            }

            // Generate dynamic query
            $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE user_id = :user_id";
            $params[':user_id'] = $this->userId;

            // Prepare and execute the query
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);

            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'User updated successfully'];
            } else {
                return ['success' => false, 'message' => 'No changes made to the user'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}
?>