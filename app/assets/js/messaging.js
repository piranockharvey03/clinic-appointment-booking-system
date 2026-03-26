/**
 * Messaging Module
 * Handles doctor-patient messaging with SSE for real-time updates
 */

class MessagingModule {
    constructor(options = {}) {
        this.userId = options.userId;
        this.userRole = options.userRole; // 'patient' or 'doctor'
        this.conversationListPollInterval = options.conversationListPollInterval || 10000; // Increased to 10 seconds
        this.sseConnection = null;
        this.conversationListTimeoutId = null;
        this.currentConversationId = null;
        this.isLoadingMessages = false;
        this.messageBuffer = [];
        this.sseReconnectAttempts = 0;
        this.sseMaxReconnectAttempts = 5;
        this.sseReconnectDelay = 1000; // Start with 1 second, exponential backoff
        this.isPageVisible = true; // Track page visibility
        this.lastConversationRenderSignature = '';
        
        // Typing indicator state
        this.isTyping = false;
        this.typingTimeout = null;
        this.typingIndicatorDebounce = 1500;
        
        // Message pagination
        this.currentMessagePage = 1;
        this.isLoadingOlderMessages = false;
        this.hasMoreMessages = true;
    }

    /**
     * Initialize the messaging module
     */
    init() {
        this.setupEventListeners();
        this.setupPageVisibilityTracking();
        this.loadConversations();
        this.startConversationListPolling();
        
        // Load conversation from URL parameter if present
        const params = new URLSearchParams(window.location.search);
        if (params.has('conversation_id')) {
            const conversationId = parseInt(params.get('conversation_id'));
            setTimeout(() => this.loadConversation(conversationId), 500);
        }
    }

    /**
     * Set up all event listeners
     */
    setupEventListeners() {
        // Send message form
        const sendBtn = document.getElementById('sendMessageBtn');
        if (sendBtn) {
            sendBtn.addEventListener('click', () => this.handleSendMessage());
        }

        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.handleSendMessage();
                }
            });
            // Track typing
            messageInput.addEventListener('input', () => this.handleTypingInput());
        }

        // Back button for mobile
        const backBtn = document.getElementById('backToConversationsBtn');
        if (backBtn) {
            backBtn.addEventListener('click', () => this.showConversationList());
        }

        // Conversation list clicks
        const conversationList = document.getElementById('conversationList');
        if (conversationList) {
            conversationList.addEventListener('click', (e) => {
                const conversationItem = e.target.closest('[data-conversation-id]');
                if (conversationItem) {
                    const conversationId = conversationItem.getAttribute('data-conversation-id');
                    this.loadConversation(conversationId);
                }
            });
        }

        // Scroll up to load older messages
        const messagesList = document.getElementById('messagesList');
        if (messagesList) {
            messagesList.addEventListener('scroll', () => this.handleScrollUp());
        }
    }

    /**
     * Track page visibility to pause polling when tab is hidden
     */
    setupPageVisibilityTracking() {
        document.addEventListener('visibilitychange', () => {
            this.isPageVisible = document.visibilityState === 'visible';
            if (this.isPageVisible) {
                // Refresh when becoming visible (catches any missed updates)
                this.loadConversations();
            }
        });
    }

    /**
     * Handle typing input - debounced typing indicator
     */
    handleTypingInput() {
        const hasText = document.getElementById('messageInput')?.value.trim().length > 0;

        if (hasText && !this.isTyping) {
            this.isTyping = true;
            this.setTypingStatus(true);
        }

        // Reset typing timeout
        if (this.typingTimeout) {
            clearTimeout(this.typingTimeout);
        }

        this.typingTimeout = setTimeout(() => {
            if (this.isTyping) {
                this.isTyping = false;
                this.setTypingStatus(false);
            }
        }, this.typingIndicatorDebounce);
    }

    /**
     * Send typing status to server
     */
    async setTypingStatus(isTyping) {
        if (!this.currentConversationId) return;

        try {
            await fetch('../includes/set-typing-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `conversation_id=${this.currentConversationId}&is_typing=${isTyping ? 1 : 0}`
            });
        } catch (error) {
            console.error('Error setting typing status:', error);
        }
    }

    /**
     * Handle scroll-up to load older messages
     */
    handleScrollUp() {
        const messagesList = document.getElementById('messagesList');
        if (!messagesList || this.isLoadingOlderMessages || !this.hasMoreMessages) return;

        // If scrolled to top, load older messages
        if (messagesList.scrollTop <= 100) {
            this.loadOlderMessages();
        }
    }

    /**
     * Load older messages for pagination
     */
    async loadOlderMessages() {
        if (this.isLoadingOlderMessages || !this.currentConversationId) return;

        this.isLoadingOlderMessages = true;
        this.currentMessagePage++;

        try {
            const response = await fetch('../includes/get-messages.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `conversation_id=${this.currentConversationId}&page=${this.currentMessagePage}`
            });

            if (!response.ok) {
                console.error('Failed to load older messages:', response.status);
                return;
            }

            const data = await response.json();
            if (!data.success || !data.messages || data.messages.length === 0) {
                this.hasMoreMessages = false;
                return;
            }

            // Prepend older messages to the list
            const messagesList = document.getElementById('messagesList');
            if (messagesList) {
                const scrollHeightBefore = messagesList.scrollHeight;

                // Create HTML for older messages
                const olderMessagesHtml = data.messages.map(msg => {
                    const isSender = msg.sender_id === this.userId;
                    const bubbleClass = isSender
                        ? 'ml-auto bg-blue-500 text-white rounded-l-lg rounded-tr-lg'
                        : 'mr-auto bg-gray-200 text-gray-900 rounded-r-lg rounded-tl-lg';

                    return `
                        <div class="mb-3 flex ${isSender ? 'justify-end' : 'justify-start'}" data-message-id="${msg.message_id}" data-timestamp="${msg.created_at}">
                            <div class="${bubbleClass} px-4 py-2 max-w-xs break-words">
                                <p class="text-sm">${escapeHtml(msg.message_text)}</p>
                                <div class="flex items-center gap-1 mt-1">
                                    <p class="text-xs ${isSender ? 'text-blue-100' : 'text-gray-500'}">${formatTime(msg.created_at)}</p>
                                    ${isSender ? `<div class="text-xs text-blue-100">${this.getStatusTicks(msg.delivery_status)}</div>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');

                // Insert at beginning
                messagesList.insertAdjacentHTML('afterbegin', olderMessagesHtml);

                // Maintain scroll position
                const scrollHeightAfter = messagesList.scrollHeight;
                messagesList.scrollTop = scrollHeightAfter - scrollHeightBefore;
            }
        } catch (error) {
            console.error('Error loading older messages:', error);
        } finally {
            this.isLoadingOlderMessages = false;
        }
    }

    /**
     * Load conversations list
     */
    async loadConversations() {
        try {
            const response = await fetch('../includes/get-conversations.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                console.error('Failed to load conversations:', response.status);
                return;
            }

            const data = await response.json();
            if (!data.success) {
                console.error('Error:', data.error);
                return;
            }

            this.renderConversations(data.conversations);
        } catch (error) {
            console.error('Error loading conversations:', error);
        }
    }

    /**
     * Render conversations list
     */
    renderConversations(conversations) {
        const conversationList = document.getElementById('conversationList');
        if (!conversationList) return;

        // Skip full DOM re-render when list content has not changed.
        const renderSignature = JSON.stringify(
            (conversations || []).map(conv => [
                conv.conversation_id,
                conv.last_message_at,
                conv.unread_count,
                this.userRole === 'patient' ? conv.doctor_name : conv.patient_name
            ])
        );
        if (renderSignature === this.lastConversationRenderSignature) {
            return;
        }
        this.lastConversationRenderSignature = renderSignature;

        if (conversations.length === 0) {
            conversationList.innerHTML = `
                <div class="px-4 py-8 text-center text-gray-500">
                    <p>No conversations yet</p>
                    ${this.userRole === 'patient' ? '<p class="text-sm mt-2">Start a conversation by clicking Message on an appointment</p>' : ''}
                </div>
            `;
            return;
        }

        conversationList.innerHTML = conversations.map(conv => {
            const isSelected = conv.conversation_id === this.currentConversationId ? 'bg-blue-100 border-l-4 border-blue-600' : '';
            const unreadBadge = conv.unread_count > 0 
                ? `<span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">${conv.unread_count}</span>`
                : '';

            if (this.userRole === 'patient') {
                return `
                    <div data-conversation-id="${conv.conversation_id}" class="p-3 border-b border-gray-200 cursor-pointer hover:bg-gray-100 transition ${isSelected} relative">
                        <div class="flex items-start gap-3">
                            <img src="${conv.doctor_photo || 'https://via.placeholder.com/40'}" alt="${conv.doctor_name}" class="w-10 h-10 rounded-full">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">${escapeHtml(conv.doctor_name)}</p>
                                <p class="text-xs text-gray-500 truncate">${escapeHtml(conv.doctor_specialty)}</p>
                                <p class="text-xs text-gray-400 mt-1">${formatTime(conv.last_message_at)}</p>
                            </div>
                        </div>
                        ${unreadBadge}
                    </div>
                `;
            } else {
                return `
                    <div data-conversation-id="${conv.conversation_id}" class="p-3 border-b border-gray-200 cursor-pointer hover:bg-gray-100 transition ${isSelected} relative">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 truncate">${escapeHtml(conv.patient_name)}</p>
                            <p class="text-xs text-gray-500 truncate">${escapeHtml(conv.patient_email)}</p>
                            <p class="text-xs text-gray-400 mt-1">${formatTime(conv.last_message_at)}</p>
                        </div>
                        ${unreadBadge}
                    </div>
                `;
            }
        }).join('');
    }

    /**
     * Load a specific conversation and its messages
     */
    async loadConversation(conversationId) {
        if (this.isLoadingMessages) return;

        this.currentConversationId = conversationId;
        this.isLoadingMessages = true;
        this.currentMessagePage = 1;
        this.hasMoreMessages = true;
        this.isTyping = false;

        // Stop previous typing indicator
        if (this.typingTimeout) {
            clearTimeout(this.typingTimeout);
        }

        // Show conversation view and hide chat area (for mobile and desktop)
        const conversationView = document.getElementById('conversationView');
        const chatArea = document.getElementById('chatArea');
        if (conversationView) {
            conversationView.style.display = 'flex';
        }
        if (chatArea && window.innerWidth < 768) {
            chatArea.style.display = 'none';
        }

        // Update UI to show loading
        const messagesList = document.getElementById('messagesList');
        if (messagesList) {
            messagesList.innerHTML = '<div class="text-center py-4 text-gray-500">Loading messages...</div>';
        }

        try {
            // Load messages
            const response = await fetch('../includes/get-messages.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `conversation_id=${conversationId}&page=1`
            });

            if (!response.ok) {
                console.error('Failed to load messages:', response.status);
                const messagesList = document.getElementById('messagesList');
                if (messagesList) {
                    messagesList.innerHTML = '<div class="text-center py-4 text-red-500">Failed to load messages.</div>';
                }
                return;
            }

            const data = await response.json();
            if (!data.success) {
                console.error('Error:', data.error);
                const messagesList = document.getElementById('messagesList');
                if (messagesList) {
                    messagesList.innerHTML = '<div class="text-center py-4 text-red-500">Error: ' + escapeHtml(data.error) + '</div>';
                }
                return;
            }

            // Render messages
            this.renderMessages(data.messages);

            // Scroll to bottom
            this.scrollToBottom();

            // Focus message input for easy typing
            const messageInput = document.getElementById('messageInput');
            if (messageInput) {
                messageInput.disabled = false;
                messageInput.focus();
            }

            // Start SSE stream for real-time updates
            this.startMessageStream(conversationId);

        } catch (error) {
            console.error('Error loading conversation:', error);
        } finally {
            this.isLoadingMessages = false;
        }
    }

    /**
     * Start real-time message stream using Server-Sent Events
     */
    startMessageStream(conversationId) {
        // Close previous connection if exists
        if (this.sseConnection) {
            this.sseConnection.close();
        }

        this.sseReconnectAttempts = 0;
        this._connectMessageStream(conversationId);
    }

    /**
     * Connect to SSE stream with reconnect logic
     */
    _connectMessageStream(conversationId) {
        try {
            const eventSource = new EventSource(`../includes/message-stream.php?conversation_id=${conversationId}&since=${this._getLastMessageTime()}`);
            this.sseConnection = eventSource;
            this.sseReconnectAttempts = 0; // Reset attempts on successful connection

            eventSource.addEventListener('message', (event) => {
                try {
                    const data = JSON.parse(event.data);
                    
                    if (data.type === 'message') {
                        this.handleNewMessage(data.data);
                    } else if (data.type === 'typing') {
                        this.handleTypingStatus(data.data);
                    } else if (data.type === 'reconnect') {
                        // Server ended connection, reconnect
                        this.startMessageStream(conversationId);
                    }
                } catch (e) {
                    console.error('Error parsing SSE message:', e);
                }
            });

            eventSource.onerror = (error) => {
                console.error('SSE connection error:', error);
                eventSource.close();
                this.sseConnection = null;
                
                // Reconnect with exponential backoff
                if (this.sseReconnectAttempts < this.sseMaxReconnectAttempts) {
                    const delay = this.sseReconnectDelay * Math.pow(2, this.sseReconnectAttempts);
                    this.sseReconnectAttempts++;
                    console.log(`Reconnecting to message stream in ${delay}ms...`);
                    setTimeout(() => this._connectMessageStream(conversationId), delay);
                } else {
                    console.error('Max SSE reconnection attempts reached, falling back to polling');
                    // Fall back to manual refresh
                    this._startManualRefreshFallback(conversationId);
                }
            };

        } catch (error) {
            console.error('Error starting SSE connection:', error);
        }
    }

    /**
     * Fallback to manual refresh if SSE fails
     */
    _startManualRefreshFallback(conversationId) {
        const fallbackInterval = setInterval(() => {
            if (this.currentConversationId === conversationId) {
                // Check for new messages every 5 seconds
                fetch('../includes/get-messages.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `conversation_id=${conversationId}&page=1`
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        this.renderMessages(data.messages);
                    }
                })
                .catch(e => console.error('Fallback refresh error:', e));
            } else {
                clearInterval(fallbackInterval);
            }
        }, 5000);
    }

    /**
     * Get the timestamp of the last message
     */
    _getLastMessageTime() {
        const messagesList = document.getElementById('messagesList');
        if (!messagesList) return new Date(Date.now() - 60000).toISOString();
        
        // Get the last message element and extract timestamp
        const lastMsg = messagesList.lastElementChild;
        if (lastMsg && lastMsg.dataset.timestamp) {
            return lastMsg.dataset.timestamp;
        }
        return new Date(Date.now() - 60000).toISOString();
    }

    /**
     * Handle new message from SSE stream
     */
    async handleNewMessage(message) {
        // Add to messages list
        const messagesList = document.getElementById('messagesList');
        if (!messagesList) return;

        const isSender = message.sender_id === this.userId;
        const bubbleClass = isSender
            ? 'ml-auto bg-blue-500 text-white rounded-l-lg rounded-tr-lg'
            : 'mr-auto bg-gray-200 text-gray-900 rounded-r-lg rounded-tl-lg';

        // Check if message already exists
        if (messagesList.querySelector(`[data-message-id="${message.message_id}"]`)) {
            return; // Already rendered
        }

        const msgHtml = `
            <div class="mb-3 flex ${isSender ? 'justify-end' : 'justify-start'}" data-message-id="${message.message_id}" data-timestamp="${message.created_at}">
                <div class="${bubbleClass} px-4 py-2 max-w-xs break-words">
                    <p class="text-sm">${escapeHtml(message.message_text)}</p>
                    <div class="flex items-center gap-1 mt-1">
                        <p class="text-xs ${isSender ? 'text-blue-100' : 'text-gray-500'}">${formatTime(message.created_at)}</p>
                        ${isSender ? `<div class="text-xs text-blue-100 leading-none">${this.getStatusTicks(message.delivery_status || 'sent')}</div>` : ''}
                    </div>
                </div>
            </div>
        `;

        // Use insertAdjacentHTML for faster DOM insertion
        messagesList.insertAdjacentHTML('beforeend', msgHtml);
        const insertedMessage = messagesList.lastElementChild;
        if (insertedMessage) {
            this._animateMessageEntrance(insertedMessage);
        }
        this.scrollToBottom();
    }

    /**
     * Handle typing status updates from SSE
     */
    handleTypingStatus(typingUsers) {
        const typingIndicator = document.getElementById('typingIndicator');
        if (!typingIndicator) return;

        if (!typingUsers || typingUsers.length === 0) {
            typingIndicator.innerHTML = '';
            typingIndicator.style.display = 'none';
            return;
        }

        typingIndicator.style.display = 'block';
        const names = typingUsers.map(u => {
            return this.userRole === 'patient' ? 'Doctor' : 'Patient';
        });
        typingIndicator.innerHTML = `<p class="text-sm text-gray-500 italic animate-pulse">${names.join(', ')} ${names.length === 1 ? 'is' : 'are'} typing...</p>`;
    }

    /**
     * Get visual representation of message delivery status (ticks)
     */
    getStatusTicks(status) {
        switch (status) {
            case 'read':
                return '✓✓'; // Double tick for read
            case 'delivered':
                return '✓✓'; // Double tick for delivered
            case 'sent':
                return '✓'; // Single tick for sent
            case 'sending':
                return '⏱'; // Clock for sending
            default:
                return '';
        }
    }

    /**
     * Render messages for a conversation
     */
    renderMessages(messages) {
        const messagesList = document.getElementById('messagesList');
        if (!messagesList) return;

        if (messages.length === 0) {
            messagesList.innerHTML = '<div class="text-center py-8 text-gray-500">No messages yet. Start the conversation!</div>';
            return;
        }

        // Render asynchronously using requestAnimationFrame for smooth UI
        requestAnimationFrame(() => {
            messagesList.innerHTML = messages.map(msg => {
                const isSender = msg.sender_id === this.userId;
                const bubbleClass = isSender
                    ? 'ml-auto bg-blue-500 text-white rounded-l-lg rounded-tr-lg'
                    : 'mr-auto bg-gray-200 text-gray-900 rounded-r-lg rounded-tl-lg';

                return `
                    <div class="mb-3 flex ${isSender ? 'justify-end' : 'justify-start'}" data-message-id="${msg.message_id}" data-timestamp="${msg.created_at}">
                        <div class="${bubbleClass} px-4 py-2 max-w-xs break-words">
                            <p class="text-sm">${escapeHtml(msg.message_text)}</p>
                            <div class="flex items-center gap-1 mt-1">
                                <p class="text-xs ${isSender ? 'text-blue-100' : 'text-gray-500'}">${formatTime(msg.created_at)}</p>
                                ${isSender ? `<div class="text-xs text-blue-100 leading-none">${this.getStatusTicks(msg.delivery_status || 'sent')}</div>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            // Scroll after rendering
            this.scrollToBottom();
        });
    }

    /**
     * Handle sending a message
     */
    async handleSendMessage() {
        const messageInput = document.getElementById('messageInput');
        if (!messageInput || !this.currentConversationId) return;

        const messageText = messageInput.value.trim();
        if (!messageText) return;

        const sendBtn = document.getElementById('sendMessageBtn');
        if (sendBtn) sendBtn.disabled = true;

        this._animateComposeSending(true);

        try {
            const response = await fetch('../includes/send-message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `conversation_id=${this.currentConversationId}&message_text=${encodeURIComponent(messageText)}`
            });

            if (!response.ok) {
                console.error('Failed to send message:', response.status);
                return;
            }

            const data = await response.json();
            if (!data.success) {
                console.error('Error:', data.error);
                return;
            }

            // Clear input
            messageInput.value = '';
            this._animateInputCleared(messageInput);
            messageInput.focus();

            // Render the sent message immediately; SSE will keep the stream in sync.
            this._appendLocalMessage({
                message_id: data.message_id,
                conversation_id: this.currentConversationId,
                sender_id: this.userId,
                sender_role: this.userRole,
                message_text: messageText,
                created_at: data.created_at || new Date().toISOString()
            });

            // Refresh only the conversation sidebar metadata asynchronously.
            this.loadConversations();

        } catch (error) {
            console.error('Error sending message:', error);
        } finally {
            if (sendBtn) sendBtn.disabled = false;
            this._animateComposeSending(false);
        }
    }

    /**
     * Briefly animate the compose area while sending.
     */
    _animateComposeSending(isSending) {
        const messageInput = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendMessageBtn');
        if (!messageInput) return;

        messageInput.style.transition = 'box-shadow 160ms ease, background-color 160ms ease, transform 160ms ease';

        if (isSending) {
            messageInput.style.backgroundColor = '#f8fafc';
            messageInput.style.boxShadow = '0 0 0 2px rgba(59, 130, 246, 0.18)';
            messageInput.style.transform = 'translateY(-1px)';
            if (sendBtn) {
                sendBtn.style.transition = 'transform 140ms ease, opacity 140ms ease';
                sendBtn.style.transform = 'scale(0.97)';
                sendBtn.style.opacity = '0.9';
            }
            return;
        }

        messageInput.style.backgroundColor = '';
        messageInput.style.boxShadow = '';
        messageInput.style.transform = '';
        if (sendBtn) {
            sendBtn.style.transform = '';
            sendBtn.style.opacity = '';
        }
    }

    /**
     * Animate input clear so users feel the composer is ready for the next message.
     */
    _animateInputCleared(inputEl) {
        if (!inputEl) return;

        inputEl.style.transition = 'transform 180ms ease, box-shadow 220ms ease, background-color 220ms ease';
        inputEl.style.backgroundColor = '#ecfeff';
        inputEl.style.boxShadow = '0 0 0 2px rgba(34, 197, 94, 0.2)';
        inputEl.style.transform = 'translateY(-1px)';

        setTimeout(() => {
            inputEl.style.backgroundColor = '';
            inputEl.style.boxShadow = '';
            inputEl.style.transform = '';
        }, 260);
    }

    /**
     * Append a local outgoing message without reloading the whole conversation.
     */
    _appendLocalMessage(message) {
        const messagesList = document.getElementById('messagesList');
        if (!messagesList || !message) return;

        if (message.message_id && messagesList.querySelector(`[data-message-id="${message.message_id}"]`)) {
            return;
        }

        const isSender = true;
        const bubbleClass = isSender
            ? 'ml-auto bg-blue-500 text-white rounded-l-lg rounded-tr-lg'
            : 'mr-auto bg-gray-200 text-gray-900 rounded-r-lg rounded-tl-lg';

        const safeMessageId = message.message_id || (`local-${Date.now()}`);
        const msgHtml = `
            <div class="mb-3 flex justify-end" data-message-id="${safeMessageId}" data-timestamp="${message.created_at}">
                <div class="${bubbleClass} px-4 py-2 max-w-xs break-words">
                    <p class="text-sm">${escapeHtml(message.message_text)}</p>
                    <div class="flex items-center gap-1 mt-1">
                        <p class="text-xs text-blue-100">${formatTime(message.created_at)}</p>
                        <div class="text-xs text-blue-100 leading-none">${this.getStatusTicks(message.delivery_status || 'sent')}</div>
                    </div>
                </div>
            </div>
        `;

        messagesList.insertAdjacentHTML('beforeend', msgHtml);
        const insertedMessage = messagesList.lastElementChild;
        if (insertedMessage) {
            this._animateMessageEntrance(insertedMessage);
        }
        this.scrollToBottom();
    }

    /**
     * Animate a just-added message bubble with a subtle lift and fade-in.
     */
    _animateMessageEntrance(messageEl) {
        if (!messageEl) return;

        messageEl.style.opacity = '0';
        messageEl.style.transform = 'translateY(8px)';
        messageEl.style.transition = 'opacity 180ms ease, transform 180ms ease';

        requestAnimationFrame(() => {
            messageEl.style.opacity = '1';
            messageEl.style.transform = 'translateY(0)';
        });
    }

    /**
     * Show conversation list and hide conversation view (for mobile)
     */
    showConversationList() {
        const conversationView = document.getElementById('conversationView');
        const chatArea = document.getElementById('chatArea');
        
        if (conversationView) {
            conversationView.style.display = 'none';
        }
        if (chatArea) {
            chatArea.style.display = 'flex';
        }
        
        this.currentConversationId = null;
    }

    /**
     * Scroll messages to bottom
     */
    scrollToBottom() {
        const messagesList = document.getElementById('messagesList');
        if (messagesList) {
            messagesList.scrollTop = messagesList.scrollHeight;
        }
    }

    /**
     * Start polling for conversation list updates
     */
    startConversationListPolling() {
        this.stopConversationListPolling();
        this.conversationListTimeoutId = setInterval(() => {
            // Only poll if page is visible
            if (this.isPageVisible) {
                this.loadConversations();
            }
        }, this.conversationListPollInterval);
    }

    /**
     * Stop polling conversation list
     */
    stopConversationListPolling() {
        if (this.conversationListTimeoutId) {
            clearInterval(this.conversationListTimeoutId);
            this.conversationListTimeoutId = null;
        }
    }

    /**
     * Destroy the module
     */
    destroy() {
        this.stopConversationListPolling();
        if (this.sseConnection) {
            this.sseConnection.close();
            this.sseConnection = null;
        }
    }
}

/**
 * Utility: Format datetime to relative time
 */
function formatTime(datetimeStr) {
    if (!datetimeStr) return '';
    const date = new Date(datetimeStr);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);

    if (seconds < 60) return 'just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
    if (seconds < 604800) return Math.floor(seconds / 86400) + 'd ago';

    return date.toLocaleDateString();
}

/**
 * Utility: Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
