<?php
// merge_users.php - merge two user accounts (source into target).
// Usage: php merge_users.php --from="Omer Shalom Rimon" --to="OSRG"

error_reporting(E_ALL);
ini_set('display_errors', 1);

$defaults = ['from' => "Omer Shalom Rimon", 'to' => "OSRG"];

$opts = getopt("", ["from::", "to::", "help::"]);
if (isset($opts['help'])) {
    echo "Usage: php merge_users.php --from='SOURCE' --to='TARGET'\n";
    exit;
}
$from = $opts['from'] ?? $defaults['from'];
$to = $opts['to'] ?? $defaults['to'];

require_once __DIR__ . '/series_db.php';

try {
    $pdo = getDB();
} catch (Exception $e) {
    echo "DB connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

function findUser($pdo, $identifier) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->execute([$identifier, $identifier]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function tableExists($pdo, $table) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
        $stmt->execute([$table]);
        return intval($stmt->fetchColumn()) > 0;
    } catch (Exception $e) {
        return false;
    }
}

$source = findUser($pdo, $from);
$target = findUser($pdo, $to);

if (!$source) { echo "Source user '{$from}' not found.\n"; exit(1); }
if (!$target) { echo "Target user '{$to}' not found.\n"; exit(1); }
if ($source['id'] == $target['id']) { echo "Source and target are the same user (id={$source['id']}). Nothing to do.\n"; exit(0); }

$source_id = $source['id'];
$target_id = $target['id'];

echo "Merging user '{$source['username']}' (id={$source_id}) into '{$target['username']}' (id={$target_id}).\n";

$actions = [];

try {
    $pdo->beginTransaction();

    // Posts
    if (tableExists($pdo, 'posts')) {
        $stmt = $pdo->prepare("UPDATE posts SET user_id = ? WHERE user_id = ?");
        $stmt->execute([$target_id, $source_id]);
        $actions[] = "posts updated: " . $stmt->rowCount();
    }

    // Comments
    if (tableExists($pdo, 'comments')) {
        $stmt = $pdo->prepare("UPDATE comments SET user_id = ? WHERE user_id = ?");
        $stmt->execute([$target_id, $source_id]);
        $actions[] = "comments updated: " . $stmt->rowCount();
    }

    // Reactions: dedupe then update
    if (tableExists($pdo, 'reactions')) {
        $stmt = $pdo->prepare("DELETE r1 FROM reactions r1 JOIN reactions r2 ON r1.post_id = r2.post_id AND r1.reaction_type = r2.reaction_type AND r2.user_id = ? WHERE r1.user_id = ?");
        $stmt->execute([$target_id, $source_id]);
        $actions[] = "reactions deduped: " . $stmt->rowCount();

        $stmt = $pdo->prepare("UPDATE reactions SET user_id = ? WHERE user_id = ?");
        $stmt->execute([$target_id, $source_id]);
        $actions[] = "reactions updated: " . $stmt->rowCount();
    }

    // Friends: remove duplicates and reassign
    if (tableExists($pdo, 'friends')) {
        // remove duplicates where target already has same friend
        $stmt = $pdo->prepare("DELETE f1 FROM friends f1 JOIN friends f2 ON f1.friend_id = f2.friend_id AND f2.user_id = ? WHERE f1.user_id = ?");
        $stmt->execute([$target_id, $source_id]);
        $actions[] = "friends dedup user side: " . $stmt->rowCount();

        $stmt = $pdo->prepare("DELETE f1 FROM friends f1 JOIN friends f2 ON f1.user_id = f2.user_id AND f2.friend_id = ? WHERE f1.friend_id = ?");
        $stmt->execute([$target_id, $source_id]);
        $actions[] = "friends dedup friend side: " . $stmt->rowCount();

        $stmt = $pdo->prepare("UPDATE friends SET user_id = ? WHERE user_id = ?");
        $stmt->execute([$target_id, $source_id]);
        $actions[] = "friends user_id updated: " . $stmt->rowCount();

        $stmt = $pdo->prepare("UPDATE friends SET friend_id = ? WHERE friend_id = ?");
        $stmt->execute([$target_id, $source_id]);
        $actions[] = "friends friend_id updated: " . $stmt->rowCount();

        $stmt = $pdo->prepare("DELETE FROM friends WHERE user_id = friend_id");
        $stmt->execute();
        $actions[] = "self-friends removed: " . $stmt->rowCount();
    }

    // Messages
    if (tableExists($pdo, 'messages')) {
        $stmt = $pdo->prepare("UPDATE messages SET sender_id = ? WHERE sender_id = ?");
        $stmt->execute([$target_id, $source_id]);
        $actions[] = "messages sender updated: " . $stmt->rowCount();

        $stmt = $pdo->prepare("UPDATE messages SET receiver_id = ? WHERE receiver_id = ?");
        $stmt->execute([$target_id, $source_id]);
        $actions[] = "messages receiver updated: " . $stmt->rowCount();

        // remove messages where sender=receiver (now self-messages)
        $stmt = $pdo->prepare("DELETE FROM messages WHERE sender_id = receiver_id");
        $stmt->execute();
        $actions[] = "self-messages removed: " . $stmt->rowCount();
    }

    // admin_uploads
    if (tableExists($pdo, 'admin_uploads')) {
        $stmt = $pdo->prepare("UPDATE admin_uploads SET uploaded_by = ? WHERE uploaded_by = ?");
        $stmt->execute([$target_id, $source_id]);
        $actions[] = "admin_uploads updated: " . $stmt->rowCount();
    }

    // remember_tokens: dedupe and update
    if (tableExists($pdo, 'remember_tokens')) {
        $stmt = $pdo->prepare("DELETE t1 FROM remember_tokens t1 JOIN remember_tokens t2 ON t1.token = t2.token AND t2.user_id = ? WHERE t1.user_id = ?");
        $stmt->execute([$target_id, $source_id]);
        $actions[] = "remember_tokens deduped: " . $stmt->rowCount();

        $stmt = $pdo->prepare("UPDATE remember_tokens SET user_id = ? WHERE user_id = ?");
        $stmt->execute([$target_id, $source_id]);
        $actions[] = "remember_tokens updated: " . $stmt->rowCount();
    }

    // Final: delete the source user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$source_id]);
    $actions[] = "source user deleted: " . $stmt->rowCount();

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error during merge: " . $e->getMessage() . "\n";
    exit(1);
}

// Output summary
echo "Merge completed. Summary:\n";
foreach ($actions as $act) {
    echo " - $act\n";
}
echo "Done.\n";
exit(0);
