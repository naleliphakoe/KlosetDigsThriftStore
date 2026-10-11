<!DOCTYPE html>
<html>
<head>
    <title>Customers</title>
<style>
body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #bdc9e0; 
            display: flex;
        }

        
        .sidebar {
            width: 220px;
            height: 100vh;
            background-color: #9eb3ec;
            color: white;
            padding: 20px;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            margin: 10px 0;
            padding: 10px;
            border-radius: 8px;
        }

        .sidebar a:hover {
            background-color: #3e99e9;
        }

        /* Main */
        .main {
            flex: 1;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .header h2 {
            color: #688bec;
        }

        .header input {
            padding: 10px;
            width: 900px;
            border-radius: 70px;
            margin-right: 100px;
            border: none;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #e2e8f0;
            text-align: left;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }
        .tab-btn {
            padding: 12px 24px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 700;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }
        .tab-btn.active {
            color: #2563eb;
            border-bottom-color: #2563eb;
        }
        .tab-btn:hover {
            color: #2563eb;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        /* Messages Styling */
        .messages-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
            height: 600px;
        }
        .conversations-list {
            background: white;
            border-radius: 12px;
            overflow-y: auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .conversation-item {
            padding: 14px;
            border-bottom: 1px solid #e2e8f0;
            cursor: pointer;
            transition: background 0.2s;
        }
        .conversation-item:hover {
            background: #f0f4ff;
        }
        .conversation-item.active {
            background: #2563eb;
            color: white;
        }
        .conversation-item .user-id {
            font-weight: 700;
            margin-bottom: 4px;
        }
        .conversation-item .last-msg {
            font-size: 12px;
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .conversation-item.active .last-msg {
            color: #e0f2fe;
        }
        .chat-panel {
            background: white;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .chat-header {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 12px 12px 0 0;
        }
        .chat-header h3 {
            margin: 0;
            color: #0f172a;
        }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .msg {
            display: flex;
            gap: 10px;
            margin-bottom: 8px;
        }
        .msg.admin {
            justify-content: flex-end;
        }
        .msg-bubble {
            max-width: 70%;
            padding: 10px 14px;
            border-radius: 10px;
            word-wrap: break-word;
            font-size: 14px;
        }
        .msg.user .msg-bubble {
            background: #e0f2fe;
            color: #0c4a6e;
        }
        .msg.admin .msg-bubble {
            background: #2563eb;
            color: white;
            border-radius: 10px 0 10px 10px;
        }
        .msg-time {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 2px;
            padding: 0 4px;
        }
        .chat-input {
            padding: 12px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 10px;
        }
        .chat-input input {
            flex: 1;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            outline: none;
        }
        .chat-input input:focus {
            border-color: #2563eb;
        }
        .chat-input button {
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 18px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
        }
        .chat-input button:hover {
            background: #1d4ed8;
        }
        .empty-chat {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            height: 100%;
            text-align: center;
        }
</style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Customers</h2>
        <a href="admin.php">Home</a>
        <a href="Orders.html">Orders</a>
        <a href="customers.php">Customers</a>
        <a href="products.php">Products</a>
        <a href="Reports.php">Reports</a>
        <a href="Settings.php">Settings</a>
        <a href="admin_logout.php">Logout</a>
    </div>
<!-- Main Content -->
    <div class="main">

        <!-- Header -->
        <div class="header">
            <h2>Admin Dashboard</h2>
            <input type="text" placeholder="Search..." style="width: 300px;">
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" data-tab="customers">Customers</button>
            <button class="tab-btn" data-tab="donations">Donations</button>
            <button class="tab-btn" data-tab="selling">Selling</button>
            <button class="tab-btn" data-tab="messages">Messages</button>
        </div>

        <!-- Customers Tab (registered accounts) -->
        <div id="customers" class="tab-content active">
            <h3>Registered Customers</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Age</th>
                        <th>Date of Birth</th>
                        <th>Gender</th>
                        <th>Registered On</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="customersTableBody">
                    <tr><td colspan="8" style="text-align:center; color:#94a3b8;">Loading customers...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Donations Tab -->
        <div id="donations" class="tab-content">
            <h3>Donations</h3>
            <table>
                <tr>
                    <th>Name</th>
                    <th>Number of items</th>
                    <th>pickup/drop-off</th>
                    <th>Date</th>
                </tr>
            </table>
        </div>

        <!-- Selling Tab -->
        <div id="selling" class="tab-content">
            <h3>Selling</h3>
            <table>
                <thead>
                    <tr>
                        <th>Customer name</th>
                        <th>ID number</th>
                        <th>Phone number</th>
                        <th>Home address</th>
                        <th>Bank</th>
                        <th>Account number</th>
                    </tr>
                </thead>
                <tbody id="sellingTableBody">
                    <tr><td colspan="6" style="text-align:center; color:#94a3b8;">Loading seller information...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Messages Tab -->
        <div id="messages" class="tab-content">
            <h3>Client Messages</h3>
            <div class="messages-container">
                <div class="conversations-list" id="conversationsList">
                    <div style="padding: 20px; text-align: center; color: #94a3b8;">Loading conversations...</div>
                </div>
                <div class="chat-panel">
                    <div class="chat-header">
                        <h3 id="selectedUserLabel">Select a conversation to view messages</h3>
                    </div>
                    <div class="chat-messages" id="chatMessages">
                        <div class="empty-chat">📭 No conversation selected</div>
                    </div>
                    <div class="chat-input" id="chatInputArea" style="display: none;">
                        <input type="text" id="adminMessageInput" placeholder="Type your reply here...">
                        <button id="sendAdminMessageBtn">Send</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const tabName = btn.dataset.tab;
                document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.getElementById(tabName).classList.add('active');
                btn.classList.add('active');

                if (tabName === 'customers') {
                    loadCustomersData();
                }

                // Load conversations when messages tab is opened
                if (tabName === 'messages') {
                    loadConversations();
                    // If a conversation was previously selected, reload it too
                    if (selectedUserId) loadConversationMessages(selectedUserId);
                }

                // Load seller info when selling tab is opened
                if (tabName === 'selling') {
                    loadSellingData();
                }
            });
        });

        function loadCustomersData() {
            const tbody = document.getElementById('customersTableBody');
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:#94a3b8;">Loading customers...</td></tr>';

            fetch('admin_customers_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status !== 'success') {
                        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; color:#dc2626;">Unable to load customers.</td></tr>';
                        return;
                    }
                    if (!data.data || data.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; color:#94a3b8;">No registered customers yet.</td></tr>';
                        return;
                    }
                    tbody.innerHTML = data.data.map(c => `
                        <tr data-id="${c.id}">
                            <td>${escapeHtml(String(c.id))}</td>
                            <td>${escapeHtml(c.fullname ?? '')}</td>
                            <td>${escapeHtml(c.email ?? '')}</td>
                            <td>${escapeHtml(c.age !== null ? String(c.age) : '')}</td>
                            <td>${escapeHtml(c.dob ?? '')}</td>
                            <td>${escapeHtml(c.gender ?? '')}</td>
                            <td>${escapeHtml(c.created_at ?? '')}</td>
                            <td><button class="delete-customer-btn" data-id="${c.id}" style="background:#dc2626; color:white; border:none; border-radius:6px; padding:6px 12px; cursor:pointer;">Delete</button></td>
                        </tr>
                    `).join('');

                    document.querySelectorAll('.delete-customer-btn').forEach(btn => {
                        btn.addEventListener('click', () => deleteCustomer(btn.dataset.id));
                    });
                })
                .catch(() => {
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; color:#dc2626;">Unable to load customers.</td></tr>';
                });
        }

        function deleteCustomer(id) {
            if (!confirm('Are you sure you want to delete this customer account? This cannot be undone.')) return;

            fetch('admin_customer_delet.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + encodeURIComponent(id)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const row = document.querySelector(`tr[data-id="${id}"]`);
                    if (row) row.remove();
                } else {
                    alert(data.message || 'Unable to delete customer.');
                }
            })
            .catch(() => {
                alert('Unable to delete customer. Please try again.');
            });
        }

        function loadSellingData() {
            const tbody = document.getElementById('sellingTableBody');
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:#94a3b8;">Loading seller information...</td></tr>';

            fetch('admin_selling_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status !== 'success') {
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:#dc2626;">Unable to load seller information.</td></tr>';
                        return;
                    }
                    if (!data.data || data.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:#94a3b8;">No seller profiles found.</td></tr>';
                        return;
                    }
                    tbody.innerHTML = data.data.map(item => `
                        <tr>
                            <td>${escapeHtml(item.customer_name)}</td>
                            <td>${escapeHtml(item.id_number)}</td>
                            <td>${escapeHtml(item.phone)}</td>
                            <td>${escapeHtml(item.address)}</td>
                            <td>${escapeHtml(item.bank_name)}</td>
                            <td>${escapeHtml(item.bank_account)}</td>
                        </tr>
                    `).join('');
                })
                .catch(() => {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:#dc2626;">Unable to load seller information.</td></tr>';
                });
        }

        let selectedUserId = null;
        let messageRefreshInterval = null;

        // Load all conversations
        function loadConversations() {
fetch('ChatHandler.php?action=getAllConversations')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const list = document.getElementById('conversationsList');
                        if (data.conversations.length === 0) {
                            list.innerHTML = '<div style="padding: 20px; text-align: center; color: #94a3b8;">No conversations yet</div>';
                        } else {
                            list.innerHTML = '';
                            data.conversations.forEach(conv => {
                                const div = document.createElement('div');
                                div.className = 'conversation-item';
                                if (conv.user_id === selectedUserId) div.classList.add('active');
                                div.innerHTML = `<div class="user-id">${escapeHtml(conv.user_id.substring(0, 20))}</div><div class="last-msg">${escapeHtml(conv.last_message.substring(0, 40))}</div>`;
                                div.addEventListener('click', () => selectConversation(conv.user_id));
                                list.appendChild(div);
                            });
                        }
                    }
                })
                .catch(err => console.error('Error loading conversations:', err));
        }

        // Select a conversation
        function selectConversation(userId) {
            selectedUserId = userId;
            document.getElementById('selectedUserLabel').textContent = 'Conversation with: ' + userId.substring(0, 20);
            document.getElementById('chatInputArea').style.display = 'flex';
            
            // Update active state
            document.querySelectorAll('.conversation-item').forEach(item => item.classList.remove('active'));
            event.target.closest('.conversation-item')?.classList.add('active');
            
            // Load messages for this user
            loadConversationMessages(userId);
            
            // Clear previous interval and set new one
            if (messageRefreshInterval) clearInterval(messageRefreshInterval);
            messageRefreshInterval = setInterval(() => loadConversationMessages(userId), 2000);
        }

        // Load messages for selected user
        function loadConversationMessages(userId) {
fetch('ChatHandler.php?action=getMessages&userId=' + userId)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const chatMessages = document.getElementById('chatMessages');
                        chatMessages.innerHTML = '';
                        if (data.messages.length === 0) {
                            chatMessages.innerHTML = '<div class="empty-chat">No messages yet</div>';
                        } else {
                            data.messages.forEach(msg => {
                                const msgDiv = document.createElement('div');
                                msgDiv.className = 'msg ' + (msg.sender === 'user' ? 'user' : 'admin');
                                const time = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                                msgDiv.innerHTML = `<div><div class="msg-bubble">${escapeHtml(msg.message)}</div><div class="msg-time">${time}</div></div>`;
                                chatMessages.appendChild(msgDiv);
                            });
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                        }
                    }
                })
                .catch(err => console.error('Error loading messages:', err));
        }

        // Send admin reply
        document.getElementById('sendAdminMessageBtn').addEventListener('click', () => {
            const input = document.getElementById('adminMessageInput');
            const text = input.value.trim();
            
            if (!text || !selectedUserId) return;
            
fetch('ChatHandler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=sendAdminReply&userId=' + selectedUserId + '&message=' + encodeURIComponent(text)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    input.value = '';
                    loadConversationMessages(selectedUserId);
                }
            })
            .catch(err => console.error('Error sending message:', err));
        });

        // Enter key to send
        document.getElementById('adminMessageInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                document.getElementById('sendAdminMessageBtn').click();
            }
        });

        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Initial load
        loadCustomersData();
    </script>

    </body>
    </html>