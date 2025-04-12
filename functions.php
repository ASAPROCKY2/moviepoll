<?php
require_once 'config.php';

class DatabaseOperations {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Security Logging Function
    public function logEvent($user, $type, $action, $ip) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO security_logs (user, type, action, ip_address) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$user, $type, $action, $ip]);
        } catch (PDOException $e) {
            error_log("Failed to log security event: " . $e->getMessage());
            return false;
        }
    }

    // Dashboard Statistics
    public function getDashboardStats() {
        $stats = [
            'total_users' => $this->getCount('users'),
            'total_movies' => $this->getCount('movies'),
            'total_polls' => $this->getCount('polls'),
            'active_polls' => $this->getCount('polls', 'active = 1'),
            'total_votes' => $this->conn->query("SELECT SUM(votes) FROM poll_options")->fetchColumn() ?: 0,
            'unread_notifications' => $this->getUnreadNotificationCount($_SESSION['user_id'] ?? 0)
        ];

        // Add security stats only if security_logs table exists
        if ($this->tableExists('security_logs')) {
            try {
                $stats['security_events'] = $this->getCount('security_logs', "type = 'security'");
                $stats['admin_actions'] = $this->getCount('security_logs', "type = 'admin'");
                $stats['login_attempts'] = $this->getCount('security_logs', "type = 'login'");
            } catch (PDOException $e) {
                error_log("Error getting security stats: " . $e->getMessage());
                $stats['security_events'] = 0;
                $stats['admin_actions'] = 0;
                $stats['login_attempts'] = 0;
            }
        } else {
            $stats['security_events'] = 0;
            $stats['admin_actions'] = 0;
            $stats['login_attempts'] = 0;
        }

        return $stats;
    }

    // Helper method to count records
    private function getCount($table, $condition = '') {
        $query = "SELECT COUNT(*) FROM $table";
        if ($condition) {
            $query .= " WHERE $condition";
        }
        return $this->conn->query($query)->fetchColumn();
    }

    // Notification System
    public function getUnreadNotificationCount($user_id) {
        if (!$this->tableExists('notifications')) {
            return 0;
        }

        $query = "SELECT COUNT(*) FROM notifications WHERE is_read = 0";
        if ($this->columnExists('notifications', 'user_id')) {
            $query .= " AND (user_id = :user_id OR user_id IS NULL)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        } else {
            $stmt = $this->conn->prepare($query);
        }
        
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getUserNotifications($user_id) {
        if (!$this->tableExists('notifications')) {
            return [];
        }

        $query = "SELECT * FROM notifications";
        if ($this->columnExists('notifications', 'user_id')) {
            $query .= " WHERE user_id = :user_id OR user_id IS NULL";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        } else {
            $stmt = $this->conn->prepare($query);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markNotificationAsRead($notification_id, $user_id) {
        if (!$this->tableExists('notifications')) {
            return false;
        }

        $query = "UPDATE notifications SET is_read = 1 WHERE id = :id";
        if ($this->columnExists('notifications', 'user_id')) {
            $query .= " AND (user_id = :user_id OR user_id IS NULL)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        } else {
            $stmt = $this->conn->prepare($query);
        }
        
        $stmt->bindParam(':id', $notification_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function addNotification($user_id, $message) {
        if (!$this->tableExists('notifications')) {
            return false;
        }

        $columns = ['message'];
        $values = [':message'];
        
        if ($this->columnExists('notifications', 'user_id')) {
            $columns[] = 'user_id';
            $values[] = ':user_id';
        }

        $query = "INSERT INTO notifications (" . implode(', ', $columns) . ") 
                  VALUES (" . implode(', ', $values) . ")";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);
        
        if ($this->columnExists('notifications', 'user_id')) {
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        }
        
        return $stmt->execute();
    }

    // Movie Management
    public function getMovies($limit = null) {
        $query = "SELECT * FROM movies ORDER BY created_at DESC";
        if ($limit) {
            $query .= " LIMIT " . (int)$limit;
        }
        return $this->conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMovieById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM movies WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function addMovie($title, $description, $release_date) {
        $stmt = $this->conn->prepare("INSERT INTO movies (title, description, release_date) VALUES (?, ?, ?)");
        return $stmt->execute([$title, $description, $release_date]);
    }

    public function updateMovie($id, $title, $description, $release_date) {
        $stmt = $this->conn->prepare("UPDATE movies SET title = ?, description = ?, release_date = ? WHERE id = ?");
        return $stmt->execute([$title, $description, $release_date, $id]);
    }

    public function deleteMovie($id) {
        $stmt = $this->conn->prepare("DELETE FROM movies WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Poll Management
    public function getPolls($only_active = false) {
        $query = "SELECT * FROM polls";
        if ($only_active) {
            $query .= " WHERE active = 1";
        }
        $query .= " ORDER BY created_at DESC";
        
        $polls = $this->conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($polls as &$poll) {
            $poll['options'] = $this->getPollOptions($poll['id']);
        }
        
        return $polls;
    }

    public function getPollById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM polls WHERE id = ?");
        $stmt->execute([$id]);
        $poll = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($poll) {
            $poll['options'] = $this->getPollOptions($poll['id']);
        }
        
        return $poll;
    }

    public function getPollOptions($poll_id) {
        $stmt = $this->conn->prepare("
            SELECT po.*, 
            (SELECT COUNT(*) FROM votes WHERE option_id = po.id) as votes
            FROM poll_options po 
            WHERE po.poll_id = ?
        ");
        $stmt->execute([$poll_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addPoll($question, $options, $active = true) {
        try {
            $this->conn->beginTransaction();
            
            $stmt = $this->conn->prepare("INSERT INTO polls (question, active) VALUES (?, ?)");
            $stmt->execute([$question, $active ? 1 : 0]);
            $pollId = $this->conn->lastInsertId();
            
            $stmt = $this->conn->prepare("INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)");
            foreach ($options as $optionText) {
                if (!empty(trim($optionText))) {
                    $stmt->execute([$pollId, $optionText]);
                }
            }
            
            $this->conn->commit();
            return $pollId;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Poll creation failed: " . $e->getMessage());
            return false;
        }
    }

    public function updatePoll($id, $question, $active) {
        $stmt = $this->conn->prepare("UPDATE polls SET question = ?, active = ? WHERE id = ?");
        return $stmt->execute([$question, $active ? 1 : 0, $id]);
    }

    public function deletePoll($id) {
        try {
            $this->conn->beginTransaction();
            $this->conn->exec("DELETE FROM poll_options WHERE poll_id = $id");
            $this->conn->exec("DELETE FROM polls WHERE id = $id");
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Poll deletion failed: " . $e->getMessage());
            return false;
        }
    }

    public function togglePollStatus($poll_id) {
        $stmt = $this->conn->prepare("UPDATE polls SET active = NOT active WHERE id = ?");
        return $stmt->execute([$poll_id]);
    }

    public function clearPollVotes($poll_id) {
        try {
            $this->conn->beginTransaction();
            $this->conn->exec("UPDATE poll_options SET votes = 0 WHERE poll_id = $poll_id");
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error clearing poll votes: " . $e->getMessage());
            return false;
        }
    }

    // Helper methods for database schema checking
    private function tableExists($tableName) {
        try {
            $result = $this->conn->query("SHOW TABLES LIKE '$tableName'");
            return $result->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error checking table existence: " . $e->getMessage());
            return false;
        }
    }

    private function columnExists($tableName, $columnName) {
        try {
            $result = $this->conn->query("SHOW COLUMNS FROM $tableName LIKE '$columnName'");
            return $result->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error checking column existence: " . $e->getMessage());
            return false;
        }
    }
}

// Initialize database operations
$db = new DatabaseOperations($conn);

// Global helper functions
function logEvent($user, $type, $action, $ip) {
    global $db;
    return $db->logEvent($user, $type, $action, $ip);
}

function getMovies($limit = null) {
    global $db;
    return $db->getMovies($limit);
}

function getMovieById($id) {
    global $db;
    return $db->getMovieById($id);
}

function addMovie($title, $description, $release_date) {
    global $db;
    return $db->addMovie($title, $description, $release_date);
}

function updateMovie($id, $title, $description, $release_date) {
    global $db;
    return $db->updateMovie($id, $title, $description, $release_date);
}

function deleteMovie($id) {
    global $db;
    return $db->deleteMovie($id);
}

function getPolls($only_active = false) {
    global $db;
    return $db->getPolls($only_active);
}

function getPollById($id) {
    global $db;
    return $db->getPollById($id);
}

function addPoll($question, $options, $active = true) {
    global $db;
    return $db->addPoll($question, $options, $active);
}

function updatePoll($id, $question, $active) {
    global $db;
    return $db->updatePoll($id, $question, $active);
}

function deletePoll($id) {
    global $db;
    return $db->deletePoll($id);
}

function togglePollStatus($poll_id) {
    global $db;
    return $db->togglePollStatus($poll_id);
}

function clearPollVotes($poll_id) {
    global $db;
    return $db->clearPollVotes($poll_id);
}

function getDashboardStats() {
    global $db;
    return $db->getDashboardStats();
}

function getPollOptions($poll_id) {
    global $db;
    return $db->getPollOptions($poll_id);
}

function getPollsWithVoteCounts($only_active = false) {
    global $db;
    return $db->getPolls($only_active);
}

function getUserNotifications($user_id) {
    global $db;
    return $db->getUserNotifications($user_id);
}

function getUnreadNotificationCount($user_id) {
    global $db;
    return $db->getUnreadNotificationCount($user_id);
}

function markNotificationAsRead($notification_id, $user_id) {
    global $db;
    return $db->markNotificationAsRead($notification_id, $user_id);
}

function addNotification($user_id, $message) {
    global $db;
    return $db->addNotification($user_id, $message);









 function getUserStats($userId) {
    $pollsWon = 0;
    $totalPollsVoted = 0;
    $totalEngagedHours = 0;

    $stmt = $this->conn->prepare("
        SELECT v.poll_id, v.movie_id, p.end_time 
        FROM votes v 
        JOIN polls p ON v.poll_id = p.id 
        WHERE v.user_id = ?
    ");
    $stmt->execute([$userId]);
    $votes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($votes as $vote) {
        $pollId = $vote['poll_id'];
        $votedMovieId = $vote['movie_id'];

        $winnerStmt = $this->conn->prepare("
            SELECT movie_id 
            FROM votes 
            WHERE poll_id = ? 
            GROUP BY movie_id 
            ORDER BY COUNT(*) DESC 
            LIMIT 1
        ");
        $winnerStmt->execute([$pollId]);
        $winner = $winnerStmt->fetchColumn();

        if ($winner && $winner == $votedMovieId) {
            $pollsWon++;
        }

        $totalPollsVoted++;
    }

    return [
        'polls_won' => $pollsWon,
        'total_voted' => $totalPollsVoted,
        'engaged_hours' => $totalEngagedHours
    ];
}

function fetch_column($query, $params = []) {
    global $conn; // assuming you're using a global PDO instance
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}



    // Calculate hours engaged (optional logic - using created_at timestamps or login sessions is more accurate)
    $timeStmt = $this->conn->prepare("SELECT SUM(TIMESTAMPDIFF(SECOND, created_at, updated_at)) AS total_seconds FROM user_sessions WHERE user_id = ?");
    $timeStmt->execute([$userId]);
    $seconds = $timeStmt->fetchColumn();
    $totalEngagedHours = round(($seconds ?: 0) / 3600, 1);

    $accuracy = $totalPollsVoted > 0 ? round(($pollsWon / $totalPollsVoted) * 100, 1) : 0;

    return [
        'polls_won' => $pollsWon,
        'hours_engaged' => $totalEngagedHours,
        'accuracy' => $accuracy
    ];
}





