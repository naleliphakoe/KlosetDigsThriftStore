<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'klosetdigsdb';

$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

// Make sure the messages table exists (mirrors chat_handler.php's setup,
// so this page works even before any message has ever been sent).
$conn->query("CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    body TEXT NULL,
    attachment_path VARCHAR(255) NULL,
    attachment_type VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
)");

$userId = (int)$_SESSION['user_id'];
$otherId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$conversations = [];
$messages = [];
$activeUser = null;

// NOTE: your real users table (created in registration.php) uses columns
// id, fullname, email - not user_id/name/surname/profile_pic. Queries below
// are matched to that real schema.
$stmt = $conn->prepare("
    SELECT CASE WHEN m.sender_id = ? THEN m.recipient_id ELSE m.sender_id END AS other_id,
           u.fullname AS other_name,
           MAX(m.created_at) AS last_time,
           SUBSTRING_INDEX(GROUP_CONCAT(m.body ORDER BY m.created_at DESC SEPARATOR '|||'), '|||', 1) AS last_message
    FROM messages m
    JOIN users u ON u.id = CASE WHEN m.sender_id = ? THEN m.recipient_id ELSE m.sender_id END
    WHERE m.sender_id = ? OR m.recipient_id = ?
    GROUP BY other_id, other_name
    ORDER BY last_time DESC
");
$stmt->bind_param("iiii", $userId, $userId, $userId, $userId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $conversations[] = $row;
$stmt->close();

if ($otherId > 0 && $otherId !== $userId) {
    $stmtU = $conn->prepare("SELECT id AS user_id, fullname AS full_name FROM users WHERE id=? LIMIT 1");
    $stmtU->bind_param("i", $otherId);
    $stmtU->execute();
    $activeUser = $stmtU->get_result()->fetch_assoc();
    $stmtU->close();

    $stmtM = $conn->prepare("SELECT sender_id, recipient_id, body, attachment_path, attachment_type, created_at FROM messages WHERE (sender_id=? AND recipient_id=?) OR (sender_id=? AND recipient_id=?) ORDER BY created_at ASC");
    $stmtM->bind_param("iiii", $userId, $otherId, $otherId, $userId);
    $stmtM->execute();
    $resM = $stmtM->get_result();
    while ($row = $resM->fetch_assoc()) $messages[] = $row;
    $stmtM->close();
}
$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Messages</title>
<style>
:root{
    --bg:#f5f5f7;
    --text:#111827;
    --muted:#6b7280;
    --border:rgba(17,24,39,.10);
    --accent:#111827;
    --card:#fff;
    --shadow:0 4px 12px rgba(0,0,0,.08);
    --blue:#007aff;
}
body{
    margin:0;
    font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
    background:var(--bg);
    color:var(--text);
    padding-bottom:86px;
}
header{
    position:sticky;
    top:0;background:rgba(255,255,255,.9);
    backdrop-filter:blur(18px);
    padding:14px 18px;
    border-bottom:1px solid var(--border);
    display:flex;
    justify-content:space-between;
    align-items:center;
    z-index:50;
}
.wrap{
    max-width:1200px;
    margin:0 auto;
    padding:18px;
}
.container{
    display:grid;
    grid-template-columns:270px 1fr;
    min-height:560px;
    background:var(--card);
    border:1px solid var(--border);
    border-radius:28px;
    overflow:hidden;
    box-shadow:var(--shadow);
}
.list{
    border-right:1px solid var(--border);
    overflow-y:auto;
    padding:10px;
}
.item{
    padding:12px 14px;
    border-radius:18px;
    cursor:pointer;
    margin-bottom:8px;
    text-decoration:none;
    color:inherit;
    display:block;
}
.item:hover,.item.active{
    background:rgba(0,122,255,.08);
}
.row{
    display:flex;
    gap:10px;
    align-items:center;
}
.avatar-fallback{
    width:36px;
    height:36px;
    border-radius:50%;
    background:#e5e7eb;
    display:grid;
    place-items:center;
    flex:0 0 auto;
    font-size:18px;
}
.name{
    font-weight:700;
    font-size:14px;
    margin-bottom:4px;
}
.preview{
    font-size:12px;
    color:var(--muted);
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}
.chat{
    display:flex;
    flex-direction:column;
}
.chat-header{
    padding:16px 18px;
    border-bottom:1px solid var(--border);
    font-weight:800;display:flex;
    align-items:center;
    gap:12px;
}
.header-link{
    display:flex;
    align-items:center;
    gap:12px;
    text-decoration:none;
    color:inherit;
}
.header-avatar-fallback{
    width:42px;
    height:42px;
    border-radius:50%;
    background:#e5e7eb;
    display:grid;
    place-items:center;
    font-size:20px;
}
.chat-messages{
    flex:1;
    padding:18px;
    display:flex;
    flex-direction:column;
    gap:10px;
    overflow-y:auto;
}
.bubble{
    max-width:75%;
    padding:10px 14px;
    border-radius:18px;
    font-size:14px;
    line-height:1.45;
}
.own{
    align-self:flex-end;
    background:var(--blue);
    color:#fff;
    border-bottom-right-radius:6px;
}
.other{
    align-self:flex-start;
    background:#e5e5ea;
    color:#111827;
    border-bottom-left-radius:6px;
}
.bubble img{
    max-width:220px;
    border-radius:14px;
    display:block;
}
.chat-input{
    display:flex;
    gap:10px;
    padding:14px;
    border-top:1px solid var(--border);
    flex-wrap:wrap;
}
.chat-input input[type="text"]{
    flex:1;
    min-width:160px;
    border:1px solid var(--border);
    border-radius:999px;
    padding:12px 16px;
    background:transparent;
    color:var(--text);
    outline:none;
}
.chat-input input[type="file"]{
    max-width:100%;
}
.chat-input button{
    border:none;
    border-radius:999px;
    padding:12px 18px;
    background:var(--blue);
    color:#fff;
    font-weight:700;
    cursor:pointer;
}
.bottom-nav{
    position:fixed;
    bottom:0;
    left:0;
    right:0;
    height:66px;
    background:rgba(255,255,255,.92);
    backdrop-filter:blur(18px);
    border-top:1px solid var(--border);
    display:flex;
    justify-content:space-around;
    align-items:center;
}
.bottom-nav a{
    display:flex;
    flex-direction:column;
    align-items:center;
    font-size:11px;
    color:var(--muted);
    text-decoration:none;
}
.bottom-nav a.active{
    color:var(--accent);
}
svg{
    width:22px;
    height:22px;
}
@media(max-width:900px){
.container{
        grid-template-columns:1fr;
}
.list{
    border-right:none;
    border-bottom:1px solid var(--border);
    }
}
</style>
</head>
<body>
<header>
  <strong>Messages</strong>
  <a href="Thrift.php">Back</a>
</header>

<div class="wrap">
  <div class="container">
    <div class="list">
      <?php if (empty($conversations)): ?>
        <div style="padding:14px;color:var(--muted)">No conversations yet.</div>
      <?php endif; ?>
      <?php foreach($conversations as $c): ?>
        <a class="item <?= $otherId === (int)$c['other_id'] ? 'active' : '' ?>" href="message.php?user_id=<?= (int)$c['other_id'] ?>">
          <div class="row">
            <div class="avatar-fallback">👤</div>
            <div style="min-width:0">
              <div class="name"><?= htmlspecialchars($c['other_name'], ENT_QUOTES, 'UTF-8') ?></div>
              <div class="preview"><?= htmlspecialchars($c['last_message'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="chat">
      <div class="chat-header">
        <?php if ($otherId > 0 && $otherId !== $userId && $activeUser): ?>
          <span class="header-link">
            <div class="header-avatar-fallback">👤</div>
            <span><?= htmlspecialchars($activeUser['full_name'], ENT_QUOTES, 'UTF-8') ?></span>
          </span>
        <?php else: ?>
          <span>Select a conversation</span>
        <?php endif; ?>
      </div>

      <div class="chat-messages" id="chatMessages">
        <?php if (!$otherId): ?>
          <div style="color:var(--muted)">Choose a chat from the left.</div>
        <?php elseif ($otherId === $userId): ?>
          <div style="color:var(--muted)">You cannot message yourself.</div>
        <?php elseif (empty($messages)): ?>
          <div style="color:var(--muted)">No messages yet. Say hello!</div>
        <?php else: ?>
          <?php foreach($messages as $m): ?>
            <div class="bubble <?= ((int)$m['sender_id'] === $userId) ? 'own' : 'other' ?>">
              <?php if (!empty($m['attachment_path']) && !empty($m['attachment_type']) && str_starts_with($m['attachment_type'], 'image/')): ?>
                <img src="<?= htmlspecialchars($m['attachment_path'], ENT_QUOTES, 'UTF-8') ?>" alt="Attachment">
                <?php if (trim((string)$m['body']) !== ''): ?>
                  <div style="margin-top:8px"><?= htmlspecialchars($m['body'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
              <?php else: ?>
                <?= htmlspecialchars($m['body'], ENT_QUOTES, 'UTF-8') ?>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <?php if ($otherId && $otherId !== $userId): ?>
      <form class="chat-input" id="chatForm" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="send_message">
        <input type="hidden" name="recipient_id" value="<?= (int)$otherId ?>">
        <input type="text" name="body" placeholder="Type a message..." autocomplete="off">
        <input type="file" name="image" accept="image/*">
        <button type="submit">Send</button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
const chatForm = document.getElementById('chatForm');
const chatMessages = document.getElementById('chatMessages');
if (chatForm) {
  chatForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const res = await fetch('chat_handler.php', { method: 'POST', body: formData });
    const data = await res.json().catch(() => ({ ok: false, error: 'Invalid server response.' }));
    if (data.ok) {
      this.reset();
      location.reload();
    } else {
      alert(data.error || 'Failed to send message.');
    }
  });
  if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;
}
</script>

<div class="bottom-nav">
  <a href="Thrift.php"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M3 12l9-9 9 9v9a1 1 0 01-1 1h-5v-6h-6v6H4a1 1 0 01-1-1z"/></svg>Home</a>
  <a href="Selling.php"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 5v14m-7-7h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>Sell</a>
  <a href="message.php" class="active"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>Messages</a>
</div>
</body>
</html>